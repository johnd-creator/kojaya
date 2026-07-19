<?php

namespace App\Services\Cooperative;

use App\Enums\MemberStoreAccountStatus;
use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\MemberStoreLedgerEntry;
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
        ?User $cashier,
        ?int $delegateId = null,
        ?string $delegatePin = null,
    ): StoreCreditPurchaseContext {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nilai pembelian store credit harus lebih besar dari 0.',
            ]);
        }

        return DB::transaction(function () use ($member, $amount, $delegateId, $delegatePin): StoreCreditPurchaseContext {
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

            if ($delegateId !== null) {
                $delegate = MemberStoreDelegate::query()
                    ->where('account_id', $account->id)
                    ->where('organization_id', $account->organization_id)
                    ->where('id', $delegateId)
                    ->first();

                if ($delegate === null) {
                    throw ValidationException::withMessages([
                        'delegate' => 'Delegate tidak ditemukan pada akun ini.',
                    ]);
                }

                if ($delegatePin !== null) {
                    $this->delegateService->verifyForCheckout($delegate, (string) $delegatePin);
                }

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

    public function storeAccountAmount(array $payments): int
    {
        $sum = 0;

        foreach ($payments as $payment) {
            if (strtoupper((string) ($payment['payment_method'] ?? '')) === 'MEMBER_STORE_ACCOUNT') {
                $sum += (int) round((float) $payment['amount']);
            }
        }

        return $sum;
    }

    public function hasStoreAccountPayment(array $payments): bool
    {
        return $this->storeAccountAmount($payments) > 0;
    }
}
