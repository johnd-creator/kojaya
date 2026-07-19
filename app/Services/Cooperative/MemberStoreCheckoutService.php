<?php

namespace App\Services\Cooperative;

use App\Enums\MemberStoreAccountStatus;
use App\Enums\MemberStoreLedgerEntryType;
use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\MemberStoreLedgerEntry;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\User;
use App\Support\MemberStoreAccountContext;
use App\Support\StoreCreditPurchaseContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bridge between the existing POS checkout flow and the store credit ledger.
 *
 * Validates member/delegate/account authorization before stock is mutated,
 * then commits the ledger purchase entry after the POS transaction persists.
 * The entire checkout runs inside the caller's database transaction, so a
 * projected-balance failure rolls back stock, payments, and the POS row.
 */
class MemberStoreCheckoutService
{
    public function __construct(
        private StoreCreditLedgerService $ledger,
        private StoreCreditDelegateService $delegateService,
    ) {}

    public function resolveAccount(CooperativeMember $member): MemberStoreAccount
    {
        return $this->ledger->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
        ));
    }

    public function preparePurchase(
        CooperativeMember $member,
        int $amount,
        User $cashier,
        ?string $delegateCode = null,
        ?string $delegatePin = null,
    ): StoreCreditPurchaseContext {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nilai pembelian store credit harus lebih besar dari 0.',
            ]);
        }

        // PIN without a delegate (or delegate without PIN) is never a valid state.
        if (($delegateCode === null) !== ($delegatePin === null)) {
            throw ValidationException::withMessages([
                'delegate' => 'Delegate dan PIN harus dikirim bersamaan.',
            ]);
        }

        return DB::transaction(function () use ($member, $amount, $cashier, $delegateCode, $delegatePin): StoreCreditPurchaseContext {
            // Defense-in-depth: store-credit checkout requires an authenticated cashier
            // from the same organization as the member being debited.
            if ($cashier->organization_id !== $member->organization_id) {
                throw ValidationException::withMessages([
                    'cooperative_member_id' => 'Anggota tidak berada pada organisasi kasir.',
                ]);
            }

            $account = MemberStoreAccount::query()
                ->where('organization_id', $member->organization_id)
                ->where('cooperative_member_id', $member->id)
                ->lockForUpdate()
                ->first();

            if ($account === null) {
                $account = $this->resolveAccount($member);
                $account = MemberStoreAccount::query()->lockForUpdate()->findOrFail($account->id);
            }

            if ($account->status === MemberStoreAccountStatus::Closed) {
                throw ValidationException::withMessages([
                    'account' => 'Akun yang ditutup tidak dapat melakukan pembelian.',
                ]);
            }

            if (! $account->canPurchase()) {
                throw ValidationException::withMessages([
                    'account' => 'Akun yang ditangguhkan tidak dapat melakukan pembelian baru.',
                ]);
            }

            $projected = (int) $account->balance - $amount;

            if ($projected < 0 && abs($projected) > (int) $account->credit_limit) {
                throw ValidationException::withMessages([
                    'account' => 'Saldo toko tidak mencukupi. Proyeksi saldo melebihi limit kredit anggota.',
                ]);
            }

            $delegate = null;

            if ($delegateCode !== null) {
                // Lookup by public code scoped to the account and organization — never by raw id.
                $delegate = MemberStoreDelegate::query()
                    ->where('account_id', $account->id)
                    ->where('organization_id', $account->organization_id)
                    ->where('code', $delegateCode)
                    ->first();

                if ($delegate === null) {
                    throw ValidationException::withMessages([
                        'delegate' => 'Delegate tidak ditemukan pada akun ini.',
                    ]);
                }

                // PIN is mandatory whenever a delegate is used.
                $this->delegateService->verifyForCheckout($delegate, (string) $delegatePin);

                $this->delegateService->assertUsableForPurchase($delegate, $amount);
            }

            return new StoreCreditPurchaseContext($account, $delegate, $amount);
        });
    }

    public function postPurchase(
        StoreCreditPurchaseContext $context,
        PosTransaction $transaction,
        ?User $cashier,
    ): MemberStoreLedgerEntry {
        return $this->ledger->postPurchase(
            account: $context->account,
            transaction: $transaction,
            amount: $context->amount,
            cashier: $cashier,
            delegate: $context->delegate,
        );
    }

    public function postRefund(
        MemberStoreAccount $account,
        PosTransaction $transaction,
        int $amount,
        ?User $cashier,
    ): MemberStoreLedgerEntry {
        return $this->ledger->postRefund($account, $transaction, $amount, $cashier);
    }

    public function postReturnRefund(
        object $return,
        MemberStoreAccount $account,
        int $amount,
        ?User $cashier,
    ): MemberStoreLedgerEntry {
        return $this->ledger->postRefundFor($return, $account, $amount, $cashier);
    }

    /**
     * Deterministic refund-allocation policy for split-tender transactions.
     *
     * The store-account credit refunded for a transaction can never exceed the
     * amount originally paid via MEMBER_STORE_ACCOUNT, across the original sale
     * and every prior partial return / void. This caps the return amount so a
     * mixed-tender transaction can never be over-refunded to the store account:
     *
     *   store_credit_refund = min(return_amount, original_store_paid - prior_refunds)
     *
     * Row locking is the caller's responsibility (the POS return/void flows
     * already lock the transaction row).
     */
    public function cappedStoreCreditRefund(MemberStoreAccount $account, PosTransaction $transaction, int $returnAmount): int
    {
        $originalStorePaid = (int) $transaction->payments
            ->where('payment_method', 'MEMBER_STORE_ACCOUNT')
            ->sum('amount');

        if ($originalStorePaid <= 0) {
            return 0;
        }

        $priorRefunds = $this->priorStoreCreditRefunds($account, $transaction);
        $available = max($originalStorePaid - $priorRefunds, 0);

        return min($returnAmount, $available);
    }

    private function priorStoreCreditRefunds(MemberStoreAccount $account, PosTransaction $transaction): int
    {
        $returnIds = PosReturn::query()->where('pos_transaction_id', $transaction->id)->pluck('id');

        return (int) MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('entry_type', MemberStoreLedgerEntryType::PosRefund->value)
            ->where(static function ($query) use ($transaction, $returnIds): void {
                $query->where(static function ($void) use ($transaction): void {
                    $void->where('reference_type', PosTransaction::class)
                        ->where('reference_id', $transaction->id);
                });

                if ($returnIds->isNotEmpty()) {
                    $query->orWhere(static function ($returns) use ($returnIds): void {
                        $returns->where('reference_type', PosReturn::class)
                            ->whereIn('reference_id', $returnIds->all());
                    });
                }
            })
            ->sum('amount');
    }

    public function storeAccountAmount(array $payments): int
    {
        $this->assertStoreCreditPaymentsIntegral($payments);

        $sum = 0;

        foreach ($payments as $payment) {
            if (strtoupper((string) ($payment['payment_method'] ?? '')) === 'MEMBER_STORE_ACCOUNT') {
                $sum += (int) $payment['amount'];
            }
        }

        return $sum;
    }

    public function hasStoreAccountPayment(array $payments): bool
    {
        foreach ($payments as $payment) {
            if (strtoupper((string) ($payment['payment_method'] ?? '')) === 'MEMBER_STORE_ACCOUNT' && (float) $payment['amount'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Store-credit is BIGINT whole Rupiah. Fractional amounts must be rejected,
     * never silently rounded, so the ledger entry exactly matches the payment row.
     *
     * @param  array<int, array{payment_method: string, amount: float|int|string}>  $payments
     */
    public function assertStoreCreditPaymentsIntegral(array $payments): void
    {
        foreach ($payments as $payment) {
            if (strtoupper((string) ($payment['payment_method'] ?? '')) !== 'MEMBER_STORE_ACCOUNT') {
                continue;
            }

            $amount = (float) $payment['amount'];

            if (floor($amount) !== $amount) {
                throw ValidationException::withMessages([
                    'payments' => 'Pembayaran MEMBER_STORE_ACCOUNT harus dalam Rupiah bulat (tanpa sen).',
                ]);
            }
        }
    }
}
