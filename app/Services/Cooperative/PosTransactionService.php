<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\PosCashierShift;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosTransactionService
{
    public function __construct(
        private MemberPointService $memberPointService,
        private PosInventoryService $inventory,
        private PosClosingGuard $closingGuard,
        private PosJournalPostingService $journal,
        private CooperativeNotificationDispatcher $notificationDispatcher,
        private MemberStoreCheckoutService $storeCheckout,
        private PosProductAccessService $productAccess,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $cashier = null): PosTransaction
    {
        if ($cashier === null) {
            throw new AuthorizationException('A cashier is required to create a POS transaction.');
        }

        $isGlobalCashier = $cashier->can('view_cooperative_all');

        if (! $isGlobalCashier && empty($cashier->organization_id)) {
            throw new AuthorizationException('A cooperative organization is required for this operation.');
        }

        if (empty($data['items']) || ! is_array($data['items'])) {
            throw ValidationException::withMessages([
                'items' => 'Item transaksi wajib diisi.',
            ]);
        }

        $targetOrgId = $this->resolveTargetOrganization($data, $cashier);

        $memberId = $data['cooperative_member_id'] ?? null;
        if ($memberId) {
            $member = CooperativeMember::query()->whereKey($memberId)->first();
            if (! $member || empty($member->organization_id)) {
                throw ValidationException::withMessages([
                    'cooperative_member_id' => 'Anggota tidak memiliki organisasi yang valid.',
                ]);
            }

            if ((string) $member->organization_id !== $targetOrgId) {
                throw ValidationException::withMessages([
                    'cooperative_member_id' => 'Anggota berada di luar organisasi transaksi.',
                ]);
            }
        }

        if (! empty($data['pos_cashier_shift_id'])) {
            $shift = PosCashierShift::query()->with('cashier')->find($data['pos_cashier_shift_id']);
            if (! $shift || ! $shift->cashier || empty($shift->cashier->organization_id)) {
                throw ValidationException::withMessages([
                    'pos_cashier_shift_id' => 'Shift kasir tidak valid atau tidak memiliki organisasi.',
                ]);
            }

            if ((string) $shift->cashier->organization_id !== (string) $targetOrgId) {
                throw ValidationException::withMessages([
                    'pos_cashier_shift_id' => 'Shift kasir berada di luar organisasi transaksi.',
                ]);
            }
        }

        if (! empty($data['client_reference'])) {
            $existing = PosTransaction::query()
                ->where('organization_id', $targetOrgId)
                ->where('client_reference', $data['client_reference'])
                ->with(['items.product', 'payments', 'member'])
                ->first();

            if ($existing) {
                $this->memberPointService->postFromTransaction($existing);
                $this->journal->postSale($existing);
                $this->journal->postCogs($existing);
                $this->journal->postMemberCredit($existing);

                return $existing;
            }
        }

        $saleDate = ($data['sold_at'] ?? null) ?: now()->toDateString();
        $this->closingGuard->guardSale((string) $saleDate, $targetOrgId);

        try {
            return DB::transaction(function () use ($data, $cashier, $saleDate, $targetOrgId): PosTransaction {
                $this->closingGuard->assertAndLockSale($targetOrgId, (string) $saleDate);

                $memberId = $data['cooperative_member_id'] ?? null;

                $subtotal = 0;
                $grossProfit = 0;
                $items = [];

                foreach ($data['items'] as $item) {
                    $product = PosProduct::query()->lockForUpdate()->findOrFail($item['pos_product_id']);
                    if (empty($product->organization_id)) {
                        throw ValidationException::withMessages([
                            'items' => "Produk {$product->name} tidak memiliki organisasi yang valid.",
                        ]);
                    }
                    if ((string) $product->organization_id !== $targetOrgId) {
                        throw ValidationException::withMessages([
                            'items' => "Produk {$product->name} berada di luar organisasi transaksi.",
                        ]);
                    }
                    if ($cashier !== null) {
                        $this->productAccess->assertCanOperate($cashier, $product);
                    }
                    $quantity = (int) $item['quantity'];

                    if ($product->is_discontinued || ! $product->is_active || $product->stock < $quantity) {
                        throw ValidationException::withMessages([
                            'items' => "Produk {$product->name} tidak tersedia atau stok tidak cukup.",
                        ]);
                    }

                    $lineTotal = (float) $product->sale_price * $quantity;
                    $unitProfit = (float) $product->sale_price - (float) $product->cost_price;
                    $lineProfit = $unitProfit * $quantity;
                    $subtotal += $lineTotal;
                    $grossProfit += $lineProfit;
                    $items[] = [$product, $quantity, $lineTotal, $unitProfit, $lineProfit];
                }

                $discount = (float) ($data['discount_amount'] ?? 0);
                if ($discount > $subtotal) {
                    throw ValidationException::withMessages([
                        'discount_amount' => 'Diskon tidak boleh melebihi subtotal.',
                    ]);
                }
                $total = max($subtotal - $discount, 0);

                $payments = $this->normalizePayments($data, $total);

                $member = null;
                if ($memberId) {
                    $member = CooperativeMember::query()->lockForUpdate()->find($memberId);
                    if (! $member || empty($member->organization_id)) {
                        throw ValidationException::withMessages([
                            'cooperative_member_id' => 'Anggota tidak memiliki organisasi yang valid.',
                        ]);
                    }

                    if ((string) $member->organization_id !== (string) $targetOrgId) {
                        throw ValidationException::withMessages([
                            'cooperative_member_id' => 'Anggota berada di luar organisasi transaksi.',
                        ]);
                    }
                }
                if ($this->paymentsRequireMember($payments) && (! $member || $member->status !== 'ACTIVE')) {
                    throw ValidationException::withMessages([
                        'cooperative_member_id' => 'Pembayaran kredit anggota membutuhkan anggota aktif.',
                    ]);
                }

                if ($member && $this->memberCreditAmount($payments) > 0 && ! $member->hasAvailableCredit($this->memberCreditAmount($payments))) {
                    throw ValidationException::withMessages([
                        'cooperative_member_id' => 'Limit kredit anggota tidak cukup. Sisa: Rp '.number_format($member->availableCredit(), 0, ',', '.'),
                    ]);
                }

                $storePurchaseContext = null;
                $storeAccountAmount = $this->storeCheckout->storeAccountAmount($payments);
                if ($storeAccountAmount > 0) {
                    if ($cashier === null) {
                        throw ValidationException::withMessages([
                            'payment_method' => 'Pembayaran saldo toko anggota membutuhkan kasir terautentikasi.',
                        ]);
                    }

                    if (! $cashier->can('cashier_store_credit')) {
                        throw ValidationException::withMessages([
                            'payment_method' => 'Kasir tidak memiliki izin mencatat pembelian Saldo Toko.',
                        ]);
                    }

                    if ($member === null) {
                        throw ValidationException::withMessages([
                            'cooperative_member_id' => 'Pembayaran saldo toko anggota membutuhkan anggota aktif.',
                        ]);
                    }

                    $storePurchaseContext = $this->storeCheckout->preparePurchase(
                        member: $member,
                        amount: $storeAccountAmount,
                        cashier: $cashier,
                        delegateCode: isset($data['store_delegate_code']) ? (string) $data['store_delegate_code'] : null,
                        purchaserName: isset($data['purchaser_name']) ? (string) $data['purchaser_name'] : null,
                        purchaseNote: isset($data['purchase_note']) ? (string) $data['purchase_note'] : null,
                    );
                }

                $paymentTotal = array_sum(array_map(fn ($p) => (float) $p['amount'], $payments));
                if (abs($paymentTotal - $total) > 0.005) {
                    throw ValidationException::withMessages([
                        'payments' => 'Total pembayaran harus sama dengan total tagihan.',
                    ]);
                }

                $cashReceived = null;
                $cashChange = null;
                $hasMultiplePayments = count($payments) > 1;
                foreach ($payments as $payment) {
                    if ($payment['payment_method'] === 'CASH') {
                        $cashReceived = isset($data['cash_received']) && $data['cash_received'] !== null
                            ? (float) $data['cash_received']
                            : (float) $payment['amount'];
                        if (! $hasMultiplePayments && $cashReceived + 0.005 < $total) {
                            throw ValidationException::withMessages([
                                'cash_received' => 'Tunai diterima kurang dari total tagihan.',
                            ]);
                        }
                        $cashChange = round(max($cashReceived - ($hasMultiplePayments ? $payment['amount'] : $total), 0), 2);
                    }
                }

                $transaction = PosTransaction::query()->create([
                    'organization_id' => $targetOrgId,
                    'transaction_no' => $this->nextTransactionNo(),
                    'client_reference' => $data['client_reference'] ?? null,
                    'cooperative_member_id' => $memberId,
                    'cashier_id' => $cashier?->id,
                    'pos_cashier_shift_id' => $data['pos_cashier_shift_id'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'total_amount' => $total,
                    'gross_profit' => max($grossProfit - $discount, 0),
                    'cash_received' => $cashReceived,
                    'cash_change' => $cashChange,
                    'status' => 'COMPLETED',
                    'sold_at' => $saleDate,
                ]);

                $location = $this->inventory->resolveLocationFor($data['pos_cashier_shift_id'] ?? null);

                foreach ($items as [$product, $quantity, $lineTotal, $unitProfit, $lineProfit]) {
                    $transaction->items()->create([
                        'pos_product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->sale_price,
                        'cost_price' => $product->cost_price,
                        'unit_profit' => $unitProfit,
                        'line_total' => $lineTotal,
                        'line_profit' => $lineProfit,
                    ]);

                    $this->inventory->sellStock(
                        product: $product,
                        location: $location,
                        quantity: $quantity,
                        sourceType: PosTransaction::class,
                        sourceId: $transaction->id,
                        referenceNo: $transaction->transaction_no,
                        movementType: 'SALE',
                    );
                }

                foreach ($payments as $payment) {
                    $transaction->payments()->create([
                        'payment_method' => $payment['payment_method'],
                        'amount' => $payment['amount'],
                        'reference_no' => $payment['reference_no'] ?? null,
                    ]);
                }

                foreach ($payments as $payment) {
                    if ($payment['payment_method'] === 'MEMBER_CREDIT' && $member) {
                        CooperativeLedgerEntry::query()->create([
                            'cooperative_member_id' => $member->id,
                            'source_type' => PosTransaction::class,
                            'source_id' => $transaction->id,
                            'entry_type' => 'POS_MEMBER_CREDIT',
                            'ledger_scope' => 'POS',
                            'debit' => $payment['amount'],
                            'credit' => 0,
                            'description' => "Kredit belanja POS {$transaction->transaction_no}",
                            'posted_at' => now()->toDateString(),
                        ]);

                        $member->increment('outstanding_balance', (float) $payment['amount']);
                    }
                }

                if ($storePurchaseContext !== null) {
                    $this->storeCheckout->postPurchase(
                        context: $storePurchaseContext,
                        transaction: $transaction,
                        cashier: $cashier,
                    );
                }

                $this->memberPointService->postFromTransaction($transaction->refresh());

                $this->journal->postSale($transaction);
                $this->journal->postCogs($transaction);
                $this->journal->postMemberCredit($transaction);

                $completedTransaction = $transaction->load(['items.product', 'payments', 'member']);
                DB::afterCommit(fn () => $this->notificationDispatcher->posSaleCompleted($completedTransaction, $cashier));

                return $completedTransaction;
            });
        } catch (QueryException $exception) {
            if (! empty($data['client_reference'])) {
                foreach (range(1, 10) as $_) {
                    $existing = PosTransaction::query()
                        ->where('organization_id', $targetOrgId)
                        ->where('client_reference', $data['client_reference'])
                        ->with(['items.product', 'payments', 'member'])
                        ->first();

                    if ($existing) {
                        $this->memberPointService->postFromTransaction($existing);
                        $this->journal->postSale($existing);
                        $this->journal->postCogs($existing);
                        $this->journal->postMemberCredit($existing);

                        return $existing;
                    }

                    usleep(50000);
                }
            }

            throw $exception;
        }
    }

    public function requestVoid(PosTransaction $transaction, User $requester, string $reason): PosVoidRequest
    {
        app(OrganizationScopeService::class)->assertVisible($requester, $transaction);

        if ($transaction->isVoided()) {
            throw ValidationException::withMessages([
                'transaction' => 'Transaksi sudah di-void sebelumnya.',
            ]);
        }

        if ($transaction->hasOpenVoidRequest()) {
            throw ValidationException::withMessages([
                'transaction' => 'Masih ada pengajuan void yang menunggu persetujuan.',
            ]);
        }

        return DB::transaction(function () use ($transaction, $requester, $reason) {
            $request = PosVoidRequest::query()->create([
                'pos_transaction_id' => $transaction->id,
                'requested_by' => $requester->id,
                'reason' => $reason,
                'status' => PosVoidRequest::STATUS_PENDING,
            ]);

            $transaction->update(['status' => 'VOID_PENDING']);

            DB::afterCommit(fn () => $this->notificationDispatcher->posVoidRequested($transaction, $requester, $requester->organization_id));

            return $request;
        });
    }

    public function approveVoid(PosVoidRequest $request, User $supervisor): PosTransaction
    {
        app(OrganizationScopeService::class)->assertVisible($supervisor, $request);

        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => 'Pengajuan void sudah diproses.',
            ]);
        }

        $transaction = $request->transaction()->lockForUpdate()->with('payments')->firstOrFail();
        app(OrganizationScopeService::class)->assertVisible($supervisor, $transaction);

        $this->closingGuard->guardVoid($transaction);

        return DB::transaction(function () use ($request, $supervisor, $transaction): PosTransaction {
            $this->closingGuard->assertAndLockVoid($transaction);

            if ($transaction->isVoided()) {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaksi sudah di-void.',
                ]);
            }

            foreach ($transaction->items as $item) {
                $product = PosProduct::query()->lockForUpdate()->find($item->pos_product_id);
                if (! $product || empty($product->organization_id) || (string) $product->organization_id !== (string) $transaction->organization_id) {
                    $productName = $product?->name ?? 'ID '.$item->pos_product_id;
                    throw ValidationException::withMessages([
                        'items' => "Produk {$productName} tidak valid atau berada di luar organisasi transaksi.",
                    ]);
                }
                $location = $this->inventory->resolveLocationFor($transaction->pos_cashier_shift_id);
                $this->inventory->restoreSaleStock(
                    product: $product,
                    location: $location,
                    quantity: (int) $item->quantity,
                    sourceType: PosTransaction::class,
                    sourceId: $transaction->id,
                    referenceNo: $transaction->transaction_no,
                    movementType: 'VOID',
                );
            }

            if ($transaction->cooperative_member_id) {
                $member = CooperativeMember::query()->lockForUpdate()->find($transaction->cooperative_member_id);
                $creditPayments = $transaction->payments->where('payment_method', 'MEMBER_CREDIT');

                if ($member && $creditPayments->isNotEmpty()) {
                    $amount = (float) $creditPayments->sum('amount');
                    $newOutstanding = max((float) $member->outstanding_balance - $amount, 0);
                    DB::table('cooperative_members')
                        ->where('id', $member->id)
                        ->update(['outstanding_balance' => $newOutstanding]);
                }

                $storeAccountPayments = $transaction->payments->where('payment_method', 'MEMBER_STORE_ACCOUNT');
                if ($member && $storeAccountPayments->isNotEmpty()) {
                    $storeAccount = \App\Models\MemberStoreAccount::query()
                        ->where('organization_id', $member->organization_id)
                        ->where('cooperative_member_id', $member->id)
                        ->first();

                    if ($storeAccount !== null) {
                        $originalStorePaid = (int) $storeAccountPayments->sum('amount');
                        $refundAmount = $this->storeCheckout->cappedStoreCreditRefund($storeAccount, $transaction, $originalStorePaid);

                        if ($refundAmount > 0) {
                            $this->storeCheckout->postVoidRefund(
                                account: $storeAccount,
                                transaction: $transaction,
                                amount: $refundAmount,
                                cashier: $supervisor,
                            );
                        }
                    }
                }
            }

            $this->journal->postVoidReversal($transaction->refresh());

            $transaction->update([
                'status' => 'VOIDED',
                'voided_at' => now(),
                'voided_by' => $supervisor->id,
                'void_reason' => $request->reason,
                'gross_profit' => 0,
            ]);

            $request->update([
                'status' => PosVoidRequest::STATUS_APPROVED,
                'approved_by' => $supervisor->id,
                'approved_at' => now(),
            ]);

            DB::afterCommit(fn () => $this->notificationDispatcher->posVoidApproved($transaction->refresh(), $request, $supervisor));

            return $transaction->refresh();
        });
    }

    public function rejectVoid(PosVoidRequest $request, User $supervisor, ?string $reason = null): PosVoidRequest
    {
        app(OrganizationScopeService::class)->assertVisible($supervisor, $request);

        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => 'Pengajuan void sudah diproses.',
            ]);
        }

        $request->update([
            'status' => PosVoidRequest::STATUS_REJECTED,
            'approved_by' => $supervisor->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $transaction = $request->transaction()->first();
        if ($transaction) {
            app(OrganizationScopeService::class)->assertVisible($supervisor, $transaction);
            $transaction->update(['status' => 'COMPLETED']);
        }

        if ($transaction) {
            $this->notificationDispatcher->posVoidRejected($transaction, $request, $supervisor);
        }

        return $request;
    }

    private function resolveTargetOrganization(array $data, User $cashier): string
    {
        $productIds = array_values(array_filter(array_map(fn ($item) => $item['pos_product_id'] ?? null, $data['items'] ?? [])));
        if ($productIds === []) {
            throw ValidationException::withMessages([
                'items' => 'Item transaksi tidak memiliki produk yang valid.',
            ]);
        }

        $products = PosProduct::query()->whereIn('id', $productIds)->get();
        if ($products->count() !== count(array_unique($productIds))) {
            throw ValidationException::withMessages([
                'items' => 'Satu atau lebih produk tidak ditemukan.',
            ]);
        }

        foreach ($products as $product) {
            if (empty($product->organization_id)) {
                throw ValidationException::withMessages([
                    'items' => "Produk {$product->name} tidak memiliki organisasi yang valid.",
                ]);
            }
        }

        $distinctOrgIds = $products->pluck('organization_id')->map(fn ($id) => (string) $id)->unique()->values();
        if ($distinctOrgIds->count() > 1) {
            throw ValidationException::withMessages([
                'items' => 'Semua produk dalam transaksi harus berasal dari organisasi yang sama.',
            ]);
        }

        $productOrgId = (string) $distinctOrgIds->first();

        if ($cashier->can('view_cooperative_all')) {
            $activeOrg = session('active_organization_id');
            if (! empty($activeOrg) && (string) $activeOrg !== $productOrgId) {
                throw ValidationException::withMessages([
                    'items' => 'Produk tidak sesuai dengan konteks organisasi aktif.',
                ]);
            }

            return $productOrgId;
        }

        $cashierOrgId = (string) $cashier->organization_id;
        if ($cashierOrgId === '' || $cashierOrgId !== $productOrgId) {
            throw new AuthorizationException('The product is outside the cashier organization.');
        }

        return $cashierOrgId;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{payment_method: string, amount: float, reference_no: ?string}>
     */
    private function normalizePayments(array $data, float $total = 0): array
    {
        if (isset($data['payments']) && is_array($data['payments'])) {
            return collect($data['payments'])
                ->map(fn ($p) => [
                    'payment_method' => strtoupper((string) $p['payment_method']),
                    'amount' => (float) $p['amount'],
                    'reference_no' => $p['reference_no'] ?? null,
                ])
                ->values()
                ->all();
        }

        $method = strtoupper((string) ($data['payment_method'] ?? 'CASH'));

        $amount = match (true) {
            $method === 'CASH' => $total,
            $method === 'MEMBER_CREDIT' => $total,
            $method === 'MEMBER_STORE_ACCOUNT' => $total,
            isset($data['amount']) => (float) $data['amount'],
            $total > 0 => $total,
            default => 0.0,
        };

        return [[
            'payment_method' => $method,
            'amount' => $amount,
            'reference_no' => $data['reference_no'] ?? null,
        ]];
    }

    private function paymentsRequireMember(array $payments): bool
    {
        foreach ($payments as $payment) {
            if ($payment['payment_method'] === 'MEMBER_CREDIT' || $payment['payment_method'] === 'MEMBER_STORE_ACCOUNT') {
                return true;
            }
        }

        return false;
    }

    private function memberCreditAmount(array $payments): float
    {
        return (float) array_sum(array_map(
            fn ($payment) => $payment['payment_method'] === 'MEMBER_CREDIT' ? (float) $payment['amount'] : 0,
            $payments,
        ));
    }

    private function nextTransactionNo(): string
    {
        return 'POS-'.now()->format('Ymd-His-u').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
