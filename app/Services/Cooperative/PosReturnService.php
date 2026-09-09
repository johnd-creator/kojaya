<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\PointTransaction;
use App\Models\PosCashierShift;
use App\Models\PosMemberPoint;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosReturnService
{
    public function __construct(
        private readonly PointService $pointService,
        private readonly PosInventoryService $inventory,
        private readonly PosClosingGuard $closingGuard,
        private readonly PosJournalPostingService $journal,
        private readonly MemberStoreCheckoutService $storeCheckout,
        private readonly PosProductAccessService $productAccess,
        private readonly PosReturnNumberGenerator $returnNumberGenerator = new PosReturnNumberGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $cashier = null): PosReturn
    {
        if ($cashier === null) {
            throw new AuthorizationException('Kasir terautentikasi wajib diisi untuk retur POS.');
        }

        if (! $cashier->can('access_cooperative_pos')) {
            throw new AuthorizationException('Izin access_cooperative_pos diperlukan untuk retur POS.');
        }

        $returnDate = ($data['returned_at'] ?? null) ?: now()->toDateString();
        $transactionId = (int) $data['pos_transaction_id'];

        $initialTransaction = PosTransaction::query()->find($transactionId);
        if ($initialTransaction) {
            app(\App\Services\Authorization\OrganizationScopeService::class)->assertVisible($cashier, $initialTransaction);
            $this->closingGuard->guardReturn($initialTransaction, (string) $returnDate);
        }

        return DB::transaction(function () use ($cashier, $data, $returnDate, $transactionId): PosReturn {
            // STEP 1: Lock parent transaction
            /** @var PosTransaction $transaction */
            $transaction = PosTransaction::query()
                ->with(['payments'])
                ->lockForUpdate()
                ->findOrFail($transactionId);

            // STEP 2: Authorize transaction visibility
            app(\App\Services\Authorization\OrganizationScopeService::class)->assertVisible($cashier, $transaction);

            if ($cashier->can('view_cooperative_all')) {
                $activeOrg = session('active_organization_id');
                if (! empty($activeOrg) && (string) $activeOrg !== (string) $transaction->organization_id) {
                    throw ValidationException::withMessages([
                        'pos_transaction_id' => 'Transaksi berada di luar konteks organisasi aktif.',
                    ]);
                }
            }

            // STEP 3: Establish transaction organization
            $targetOrgId = $transaction->organization_id ? (string) $transaction->organization_id : null;
            if (empty($targetOrgId)) {
                throw ValidationException::withMessages([
                    'pos_transaction_id' => 'Transaksi tidak memiliki organisasi yang valid.',
                ]);
            }

            $this->closingGuard->assertAndLockReturn($transaction, (string) $returnDate);

            if ($transaction->status !== 'COMPLETED') {
                throw ValidationException::withMessages([
                    'pos_transaction_id' => 'Hanya transaksi selesai yang bisa diretur.',
                ]);
            }

            // PREFLIGHT: VALIDATE EVERYTHING FIRST BEFORE ANY MUTATIONS!

            // STEP 4, 5, 6: Transaction items and product integrity
            $itemsToReturn = [];
            $total = 0.0;

            foreach ($data['items'] as $index => $item) {
                /** @var PosTransactionItem|null $transactionItem */
                $transactionItem = PosTransactionItem::query()
                    ->where('pos_transaction_id', $transaction->id)
                    ->lockForUpdate()
                    ->find($item['pos_transaction_item_id']);

                if ($transactionItem === null) {
                    throw ValidationException::withMessages([
                        "items.{$index}.pos_transaction_item_id" => 'Item transaksi tidak cocok dengan transaksi ini.',
                    ]);
                }

                $quantity = (int) $item['quantity'];
                $returnedQuantity = (int) PosReturnItem::query()
                    ->where('pos_transaction_item_id', $transactionItem->id)
                    ->sum('quantity');

                if ($quantity <= 0 || $quantity > ((int) $transactionItem->quantity - $returnedQuantity)) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah retur melebihi sisa item transaksi.',
                    ]);
                }

                /** @var PosProduct|null $product */
                $product = PosProduct::query()->lockForUpdate()->find($transactionItem->pos_product_id);

                if ($product === null) {
                    throw ValidationException::withMessages([
                        "items.{$index}.pos_product_id" => 'Produk transaksi tidak ditemukan.',
                    ]);
                }

                if (empty($product->organization_id)) {
                    throw ValidationException::withMessages([
                        'items' => "Produk {$product->name} tidak memiliki organisasi yang valid.",
                    ]);
                }

                // Strictly enforce product.organization_id === transaction.organization_id (even for global operators!)
                if ((string) $product->organization_id !== $targetOrgId) {
                    throw ValidationException::withMessages([
                        'items' => "Produk {$product->name} berada di luar organisasi transaksi.",
                    ]);
                }

                $this->productAccess->assertCanOperate($cashier, $product);

                $lineTotal = round((float) $transactionItem->unit_price * $quantity, 2);
                $total = round($total + $lineTotal, 2);

                $itemsToReturn[] = [
                    'transaction_item' => $transactionItem,
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $transactionItem->unit_price,
                    'line_total' => $lineTotal,
                ];
            }

            // STEP 7, 8: Member integrity
            $member = null;
            if (! empty($transaction->cooperative_member_id)) {
                $member = CooperativeMember::query()->lockForUpdate()->find($transaction->cooperative_member_id);
                if ($member === null || empty($member->organization_id) || (string) $member->organization_id !== $targetOrgId) {
                    throw ValidationException::withMessages([
                        'pos_transaction_id' => 'Anggota transaksi tidak valid atau berada di luar organisasi transaksi.',
                    ]);
                }
            }

            // STEP 9, 10: Store account integrity
            $storeAccountPayment = $transaction->payments->firstWhere('payment_method', 'MEMBER_STORE_ACCOUNT');
            $storeAccount = null;
            if ($storeAccountPayment !== null && $total > 0) {
                if (empty($transaction->cooperative_member_id) || $member === null) {
                    throw ValidationException::withMessages([
                        'pos_transaction_id' => 'Transaksi kredit toko tidak memiliki anggota yang valid.',
                    ]);
                }

                $storeAccount = MemberStoreAccount::query()
                    ->where('cooperative_member_id', $member->id)
                    ->lockForUpdate()
                    ->first();

                if ($storeAccount === null || empty($storeAccount->organization_id) || (string) $storeAccount->organization_id !== $targetOrgId) {
                    throw ValidationException::withMessages([
                        'pos_transaction_id' => 'Akun simpanan/kredit toko tidak valid atau berada di luar organisasi transaksi.',
                    ]);
                }
            }

            // STEP 11: Points integrity
            if (! empty($transaction->cooperative_member_id)) {
                $point = PosMemberPoint::query()
                    ->where('pos_transaction_id', $transaction->id)
                    ->first();

                if ($point !== null) {
                    $pointMember = CooperativeMember::query()->find($point->cooperative_member_id);
                    if ($pointMember === null || empty($pointMember->organization_id) || (string) $pointMember->organization_id !== $targetOrgId) {
                        throw ValidationException::withMessages([
                            'pos_transaction_id' => 'Poin anggota transaksi berada di luar organisasi transaksi.',
                        ]);
                    }
                    if ((int) $point->cooperative_member_id !== (int) $transaction->cooperative_member_id) {
                        throw ValidationException::withMessages([
                            'pos_transaction_id' => 'Poin anggota transaksi tidak cocok dengan anggota transaksi.',
                        ]);
                    }
                }
            }

            // STEP 12: Shift / inventory location integrity
            $location = $this->inventory->resolveLocationFor($transaction->pos_cashier_shift_id);
            if (! empty($transaction->pos_cashier_shift_id)) {
                $shift = PosCashierShift::query()->with('cashier')->find($transaction->pos_cashier_shift_id);
                if ($shift && $shift->cashier && ! empty($shift->cashier->organization_id) && (string) $shift->cashier->organization_id !== $targetOrgId) {
                    throw ValidationException::withMessages([
                        'pos_transaction_id' => 'Shift kasir transaksi berada di luar organisasi transaksi.',
                    ]);
                }
            }

            // PREFLIGHT COMPLETE! NOW AND ONLY NOW PERFORM MUTATIONS!

            $return = PosReturn::query()->create([
                'pos_transaction_id' => $transaction->id,
                'cooperative_member_id' => $transaction->cooperative_member_id,
                'cashier_id' => $cashier->id,
                'return_no' => $this->nextReturnNo(),
                'status' => 'APPROVED',
                'total_amount' => $total,
                'points_reversed' => 0,
                'reason' => $data['reason'] ?? null,
                'returned_at' => $returnDate,
            ]);

            foreach ($itemsToReturn as $itemData) {
                $return->items()->create([
                    'pos_transaction_item_id' => $itemData['transaction_item']->id,
                    'pos_product_id' => $itemData['product']->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'line_total' => $itemData['line_total'],
                ]);

                $this->inventory->restoreSaleStock(
                    product: $itemData['product'],
                    location: $location,
                    quantity: $itemData['quantity'],
                    sourceType: PosReturn::class,
                    sourceId: $return->id,
                    referenceNo: $return->return_no,
                    movementType: 'RETURN',
                );
            }

            $pointsReversed = $this->reversePoints($transaction, $return->refresh());

            $return->forceFill([
                'points_reversed' => $pointsReversed,
            ])->save();

            $this->journal->postReturn($return->refresh());

            if ($storeAccount !== null && $total > 0) {
                $refundAmount = $this->storeCheckout->cappedStoreCreditRefund($storeAccount, $transaction, (int) $total);

                if ($refundAmount > 0) {
                    $this->storeCheckout->postReturnRefund(
                        return: $return->refresh(),
                        account: $storeAccount,
                        amount: $refundAmount,
                        cashier: $cashier,
                    );
                }
            }

            return $return->load(['items', 'transaction']);
        });
    }

    private function reversePoints(PosTransaction $transaction, PosReturn $return): int
    {
        if (! $transaction->cooperative_member_id) {
            return 0;
        }

        $point = PosMemberPoint::query()
            ->where('pos_transaction_id', $transaction->id)
            ->first();

        if (! $point) {
            return 0;
        }

        $member = $point->member;
        if (! $member || empty($member->organization_id) || (string) $member->organization_id !== (string) $transaction->organization_id) {
            return 0;
        }

        $alreadyReversed = PointTransaction::query()
            ->where('transaction_type', 'REVERSED')
            ->where('source_type', PosReturn::class)
            ->where('source_id', $return->id)
            ->exists();

        if ($alreadyReversed) {
            return 0;
        }

        $points = (int) floor(((float) $return->total_amount / max((float) $transaction->total_amount, 1)) * (int) $point->points);

        if ($points <= 0) {
            return 0;
        }

        $this->pointService->recordTransaction(
            member: $member,
            transactionType: 'REVERSED',
            points: $points * -1,
            description: 'Pembalikan poin karena retur POS',
            postedAt: now(),
            sourceType: PosReturn::class,
            sourceId: (string) $return->id,
            referenceNumber: $return->return_no,
            metadata: [
                'pos_transaction_id' => $transaction->id,
                'pos_member_point_id' => $point->id,
            ],
        );

        return $points;
    }

    public function nextReturnNo(): string
    {
        return $this->returnNumberGenerator->generate();
    }
}
