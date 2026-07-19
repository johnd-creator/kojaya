<?php

namespace App\Services\Cooperative;

use App\Models\PointTransaction;
use App\Models\PosMemberPoint;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
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
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $cashier = null): PosReturn
    {
        $returnDate = ($data['returned_at'] ?? null) ?: now()->toDateString();
        $transactionId = (int) $data['pos_transaction_id'];
        $transaction = PosTransaction::query()->find($transactionId);
        if ($transaction) {
            $this->closingGuard->guardReturn($transaction, (string) $returnDate);
        }

        return DB::transaction(function () use ($cashier, $data, $returnDate): PosReturn {
            $transaction = PosTransaction::query()
                ->with(['items', 'payments'])
                ->lockForUpdate()
                ->findOrFail($data['pos_transaction_id']);

            if ($transaction->status !== 'COMPLETED') {
                throw ValidationException::withMessages([
                    'pos_transaction_id' => 'Hanya transaksi selesai yang bisa diretur.',
                ]);
            }

            $return = PosReturn::query()->create([
                'pos_transaction_id' => $transaction->id,
                'cooperative_member_id' => $transaction->cooperative_member_id,
                'cashier_id' => $cashier?->id,
                'return_no' => $this->nextReturnNo(),
                'status' => 'APPROVED',
                'total_amount' => 0,
                'points_reversed' => 0,
                'reason' => $data['reason'] ?? null,
                'returned_at' => $returnDate,
            ]);

            $total = 0.0;

            foreach ($data['items'] as $index => $item) {
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

                $lineTotal = round((float) $transactionItem->unit_price * $quantity, 2);
                $total = round($total + $lineTotal, 2);

                $return->items()->create([
                    'pos_transaction_item_id' => $transactionItem->id,
                    'pos_product_id' => $transactionItem->pos_product_id,
                    'quantity' => $quantity,
                    'unit_price' => $transactionItem->unit_price,
                    'line_total' => $lineTotal,
                ]);

                $location = $this->inventory->resolveLocationFor($transaction->pos_cashier_shift_id);

                $product = PosProduct::query()->lockForUpdate()->findOrFail($transactionItem->pos_product_id);

                $this->inventory->restoreSaleStock(
                    product: $product,
                    location: $location,
                    quantity: $quantity,
                    sourceType: PosReturn::class,
                    sourceId: $return->id,
                    referenceNo: $return->return_no,
                    movementType: 'RETURN',
                );
            }

            $return->forceFill(['total_amount' => $total])->save();

            $pointsReversed = $this->reversePoints($transaction, $return->refresh());

            $return->forceFill([
                'points_reversed' => $pointsReversed,
            ])->save();

            $this->journal->postReturn($return->refresh());

            $storeAccountPayment = $transaction->payments->firstWhere('payment_method', 'MEMBER_STORE_ACCOUNT');
            if ($transaction->cooperative_member_id && $storeAccountPayment !== null && $total > 0) {
                $storeAccount = \App\Models\MemberStoreAccount::query()
                    ->where('cooperative_member_id', $transaction->cooperative_member_id)
                    ->first();

                if ($storeAccount !== null) {
                    $this->storeCheckout->postReturnRefund(
                        return: $return->refresh(),
                        account: $storeAccount,
                        amount: (int) round((float) $total),
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

        $alreadyReversed = PointTransaction::query()
            ->where('transaction_type', 'REVERSED')
            ->where('source_type', PosReturn::class)
            ->where('source_id', $return->id)
            ->exists();

        if ($alreadyReversed) {
            return 0;
        }

        $points = (int) floor(((float) $return->total_amount / max((float) $transaction->total_amount, 1)) * (int) $point->points);

        if ($points <= 0 || ! $point->member) {
            return 0;
        }

        $this->pointService->recordTransaction(
            member: $point->member,
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

    private function nextReturnNo(): string
    {
        return 'RET-'.now()->format('Ymd-His').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }
}
