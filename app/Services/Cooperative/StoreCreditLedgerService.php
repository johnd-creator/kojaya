<?php

namespace App\Services\Cooperative;

use App\Enums\MemberStoreAccountStatus;
use App\Enums\MemberStoreLedgerEffect;
use App\Enums\MemberStoreLedgerEntryType;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\MemberStoreFundingRequest;
use App\Models\MemberStoreLedgerEntry;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use App\Support\MemberStoreAccountContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for member store account balance mutation.
 *
 * All balance changes are persisted as immutable ledger entries inside a
 * database transaction with the account row locked via lockForUpdate().
 * Money is represented as signed BIGINT whole Rupiah.
 */
class StoreCreditLedgerService
{
    public const string MODULE = 'store-credit';

    public function __construct(private AuditLogService $auditLog) {}

    public function openAccount(
        MemberStoreAccountContext $context,
    ): MemberStoreAccount {
        return DB::transaction(function () use ($context): MemberStoreAccount {
            $existing = MemberStoreAccount::query()
                ->where('organization_id', $context->organizationId)
                ->where('cooperative_member_id', $context->cooperativeMemberId)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $account = new MemberStoreAccount([
                'organization_id' => $context->organizationId,
                'cooperative_member_id' => $context->cooperativeMemberId,
                'balance' => 0,
                'credit_limit' => max(0, $context->creditLimit),
                'status' => MemberStoreAccountStatus::Active->value,
                'opened_at' => now(),
            ]);
            $account->save();

            if ($context->openingBalance > 0) {
                $this->post(
                    account: $account,
                    entryType: MemberStoreLedgerEntryType::OpeningBalance,
                    effect: MemberStoreLedgerEffect::Credit,
                    amount: $context->openingBalance,
                    referenceType: MemberStoreAccount::class,
                    referenceId: (string) $account->id,
                    idempotencyKey: $context->idempotencyKey ?? $this->referenceKey('opening', $account, $account->id),
                    actor: $context->openedBy,
                    reason: $context->reason ?? 'Saldo awal pembukaan akun',
                    metadata: ['opening_balance' => true],
                );
            }

            $this->audit('member_store_credit.account.opened', $account, $context->openedBy, [
                'new' => [
                    'credit_limit' => $account->credit_limit,
                    'opening_balance' => $context->openingBalance,
                ],
                'reason' => $context->reason,
            ]);

            return $account->refresh();
        });
    }

    public function postPurchase(
        MemberStoreAccount $account,
        PosTransaction $transaction,
        int $amount,
        ?User $cashier,
        ?MemberStoreDelegate $delegate,
        ?string $idempotencyKey = null,
    ): MemberStoreLedgerEntry {
        if (! $account->canPurchase()) {
            throw ValidationException::withMessages([
                'account' => 'Akun saldo toko tidak aktif, pembelian tidak diizinkan.',
            ]);
        }

        return $this->post(
            account: $account,
            entryType: MemberStoreLedgerEntryType::PosPurchase,
            effect: MemberStoreLedgerEffect::Debit,
            amount: $amount,
            referenceType: PosTransaction::class,
            referenceId: (string) $transaction->id,
            idempotencyKey: $idempotencyKey ?? $this->referenceKey('pos-purchase', $account, $transaction->id),
            actor: $cashier,
            delegate: $delegate,
            reason: "Pembelian POS {$transaction->transaction_no}",
            metadata: ['pos_transaction_no' => $transaction->transaction_no],
        );
    }

    public function postRefund(
        MemberStoreAccount $account,
        PosTransaction $transaction,
        int $amount,
        ?User $cashier,
        ?string $idempotencyKey = null,
    ): MemberStoreLedgerEntry {
        return $this->postRefundFor($transaction, $account, $amount, $cashier, $idempotencyKey);
    }

    public function postRefundFor(
        object $reference,
        MemberStoreAccount $account,
        int $amount,
        ?User $cashier,
        ?string $idempotencyKey = null,
    ): MemberStoreLedgerEntry {
        $referenceType = $reference::class;
        $referenceId = (string) ($reference->id ?? null);
        $label = property_exists($reference, 'transaction_no') ? $reference->transaction_no
            : (property_exists($reference, 'return_no') ? $reference->return_no : get_class($reference));

        return $this->post(
            account: $account,
            entryType: MemberStoreLedgerEntryType::PosRefund,
            effect: MemberStoreLedgerEffect::Credit,
            amount: $amount,
            referenceType: $referenceType,
            referenceId: $referenceId,
            idempotencyKey: $idempotencyKey ?? $this->referenceKey('pos-refund', $account, $referenceId),
            actor: $cashier,
            reason: "Pengembalian POS {$label}",
            metadata: ['reference' => $label],
        );
    }

    public function postCashFunding(
        MemberStoreAccount $account,
        MemberStoreFundingRequest $funding,
        ?User $cashier,
        ?string $idempotencyKey = null,
    ): MemberStoreLedgerEntry {
        $this->ensureFundingAllowed($account);

        return $this->post(
            account: $account,
            entryType: MemberStoreLedgerEntryType::CashFunding,
            effect: MemberStoreLedgerEffect::Credit,
            amount: (int) $funding->amount,
            referenceType: MemberStoreFundingRequest::class,
            referenceId: (string) $funding->id,
            idempotencyKey: $idempotencyKey ?? $this->referenceKey('cash-funding', $account, $funding->id),
            actor: $cashier,
            reason: 'Setoran tunai',
            metadata: ['method' => 'cash'],
        );
    }

    public function postTransferFunding(
        MemberStoreAccount $account,
        MemberStoreFundingRequest $funding,
        User $reviewer,
        ?string $idempotencyKey = null,
    ): MemberStoreLedgerEntry {
        $this->ensureFundingAllowed($account);

        return $this->post(
            account: $account,
            entryType: MemberStoreLedgerEntryType::TransferFunding,
            effect: MemberStoreLedgerEffect::Credit,
            amount: (int) $funding->amount,
            referenceType: MemberStoreFundingRequest::class,
            referenceId: (string) $funding->id,
            idempotencyKey: $idempotencyKey ?? $this->referenceKey('transfer-funding', $account, $funding->id),
            actor: $reviewer,
            reason: 'Setoran transfer terverifikasi',
            metadata: ['method' => 'transfer'],
        );
    }

    public function reverseEntry(
        MemberStoreLedgerEntry $original,
        User $actor,
        string $reason,
        ?string $idempotencyKey = null,
    ): MemberStoreLedgerEntry {
        return DB::transaction(function () use ($original, $actor, $reason, $idempotencyKey): MemberStoreLedgerEntry {
            $locked = MemberStoreLedgerEntry::query()->lockForUpdate()->findOrFail($original->id);

            if ($locked->entry_type === MemberStoreLedgerEntryType::Reversal) {
                throw ValidationException::withMessages([
                    'entry' => 'Entry pembatalan tidak dapat dibatalkan lagi.',
                ]);
            }

            if ($locked->isReversed()) {
                $existing = MemberStoreLedgerEntry::query()
                    ->where('reversal_of_entry_id', $locked->id)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $account = MemberStoreAccount::query()->lockForUpdate()->findOrFail($locked->account_id);
            $oppositeEffect = $locked->effect === MemberStoreLedgerEffect::Credit
                ? MemberStoreLedgerEffect::Debit
                : MemberStoreLedgerEffect::Credit;

            $entry = $this->post(
                account: $account,
                entryType: MemberStoreLedgerEntryType::Reversal,
                effect: $oppositeEffect,
                amount: (int) $locked->amount,
                referenceType: MemberStoreLedgerEntry::class,
                referenceId: (string) $locked->id,
                idempotencyKey: $idempotencyKey ?? $this->referenceKey('reversal', $account, $locked->id),
                actor: $actor,
                reason: $reason,
                metadata: ['reversed_entry_type' => $locked->entry_type->value],
                reversalOf: $locked,
            );

            return $entry;
        });
    }

    public function adjust(
        MemberStoreAccount $account,
        int $amount,
        MemberStoreLedgerEffect $effect,
        User $actor,
        string $reason,
        ?string $idempotencyKey = null,
    ): MemberStoreLedgerEntry {
        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Alasan penyesuaian wajib diisi.',
            ]);
        }

        $entryType = $effect === MemberStoreLedgerEffect::Credit
            ? MemberStoreLedgerEntryType::AdjustmentCredit
            : MemberStoreLedgerEntryType::AdjustmentDebit;

        return $this->post(
            account: $account,
            entryType: $entryType,
            effect: $effect,
            amount: $amount,
            referenceType: null,
            referenceId: null,
            idempotencyKey: $idempotencyKey ?? $this->key('adjust', $account),
            actor: $actor,
            reason: $reason,
            metadata: ['manual_adjustment' => true],
        );
    }

    public function changeCreditLimit(
        MemberStoreAccount $account,
        int $newLimit,
        User $actor,
        string $reason,
        bool $overrideBelowDebt = false,
    ): MemberStoreAccount {
        if ($newLimit < 0) {
            throw ValidationException::withMessages([
                'credit_limit' => 'Limit kredit tidak boleh negatif.',
            ]);
        }

        return DB::transaction(function () use ($account, $newLimit, $actor, $reason, $overrideBelowDebt): MemberStoreAccount {
            $locked = MemberStoreAccount::query()->lockForUpdate()->findOrFail($account->id);
            $previousLimit = (int) $locked->credit_limit;
            $debt = abs(min(0, (int) $locked->balance));

            if ($newLimit < $debt && ! $overrideBelowDebt) {
                throw ValidationException::withMessages([
                    'credit_limit' => 'Limit baru lebih rendah dari utang saat ini. Ubah membutuhkan elevated permission.',
                ]);
            }

            $locked->credit_limit = $newLimit;
            $locked->save();

            $this->audit('member_store_credit.limit.changed', $locked, $actor, [
                'old' => ['credit_limit' => $previousLimit],
                'new' => ['credit_limit' => $newLimit],
                'reason' => $reason,
            ]);

            return $locked->refresh();
        });
    }

    public function suspend(MemberStoreAccount $account, User $actor, ?string $reason = null): MemberStoreAccount
    {
        return $this->transitionStatus($account, MemberStoreAccountStatus::Suspended, $actor, $reason);
    }

    public function reactivate(MemberStoreAccount $account, User $actor, ?string $reason = null): MemberStoreAccount
    {
        return $this->transitionStatus($account, MemberStoreAccountStatus::Active, $actor, $reason);
    }

    public function close(MemberStoreAccount $account, User $actor, ?string $reason = null): MemberStoreAccount
    {
        return DB::transaction(function () use ($account, $actor, $reason): MemberStoreAccount {
            $locked = MemberStoreAccount::query()->lockForUpdate()->findOrFail($account->id);

            if ((int) $locked->balance < 0) {
                throw ValidationException::withMessages([
                    'account' => 'Akun dengan saldo negatif (piutang) tidak dapat ditutup.',
                ]);
            }

            $previous = $locked->status->value;
            $locked->status = MemberStoreAccountStatus::Closed->value;
            $locked->save();

            $this->audit('member_store_credit.account.closed', $locked, $actor, [
                'old' => ['status' => $previous],
                'new' => ['status' => $locked->status->value],
                'reason' => $reason,
            ]);

            return $locked->refresh();
        });
    }

    /**
     * Core atomic posting routine. Acquires an exclusive account row lock,
     * validates the projected balance, writes an immutable ledger entry, and
     * updates the cached balance. Idempotent on (account, idempotency_key)
     * and (reference_type, reference_id, entry_type).
     */
    private function post(
        MemberStoreAccount $account,
        MemberStoreLedgerEntryType $entryType,
        MemberStoreLedgerEffect $effect,
        int $amount,
        ?string $referenceType,
        ?string $referenceId,
        string $idempotencyKey,
        ?User $actor,
        ?MemberStoreDelegate $delegate = null,
        ?string $reason = null,
        ?array $metadata = null,
        ?MemberStoreLedgerEntry $reversalOf = null,
    ): MemberStoreLedgerEntry {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nilai amount harus lebih besar dari 0.',
            ]);
        }

        if ($referenceType !== null && $referenceId !== null) {
            $existing = MemberStoreLedgerEntry::query()
                ->where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->where('entry_type', $entryType->value)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return DB::transaction(function () use (
            $account, $entryType, $effect, $amount, $referenceType, $referenceId,
            $idempotencyKey, $actor, $delegate, $reason, $metadata, $reversalOf
        ): MemberStoreLedgerEntry {
            $locked = MemberStoreAccount::query()->lockForUpdate()->findOrFail($account->id);

            if ($locked->status === MemberStoreAccountStatus::Closed) {
                throw ValidationException::withMessages([
                    'account' => 'Akun yang ditutup tidak dapat menerima transaksi baru.',
                ]);
            }

            $balanceBefore = (int) $locked->balance;

            if ($effect === MemberStoreLedgerEffect::Debit) {
                $projected = $balanceBefore - $amount;

                if ($projected < 0 && abs($projected) > (int) $locked->credit_limit) {
                    Log::warning('store-credit.purchase.rejected_over_limit', [
                        'account_id' => $locked->id,
                        'projected' => $projected,
                        'credit_limit' => (int) $locked->credit_limit,
                        'entry_type' => $entryType->value,
                    ]);

                    throw ValidationException::withMessages([
                        'account' => 'Saldo toko tidak mencukupi. Proyeksi saldo melebihi limit kredit.',
                    ]);
                }
            }

            if ($entryType === MemberStoreLedgerEntryType::PosPurchase && ! $locked->canPurchase()) {
                throw ValidationException::withMessages([
                    'account' => 'Akun yang ditangguhkan tidak dapat melakukan pembelian baru.',
                ]);
            }

            $balanceAfter = $effect === MemberStoreLedgerEffect::Credit
                ? $balanceBefore + $amount
                : $balanceBefore - $amount;

            try {
                $entry = new MemberStoreLedgerEntry([
                    'account_id' => $locked->id,
                    'organization_id' => $locked->organization_id,
                    'entry_type' => $entryType->value,
                    'amount' => $amount,
                    'effect' => $effect->value,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'idempotency_key' => $idempotencyKey,
                    'reversal_of_entry_id' => $reversalOf?->id,
                    'actor_user_id' => $actor?->id,
                    'delegate_id' => $delegate?->id,
                    'reason' => $reason,
                    'metadata' => $metadata,
                    'occurred_at' => now(),
                ]);
                $entry->save();
            } catch (QueryException $exception) {
                $existing = MemberStoreLedgerEntry::query()
                    ->where('account_id', $locked->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    Log::info('store-credit.idempotency.replay', [
                        'account_id' => $locked->id,
                        'idempotency_key' => $idempotencyKey,
                    ]);

                    return $existing;
                }

                throw $exception;
            }

            $locked->balance = $balanceAfter;
            $locked->save();

            $this->assertCachedBalanceMatchesLedger($locked);
            $this->audit('member_store_credit.ledger.posted', $entry, $actor, [
                'new' => [
                    'entry_type' => $entry->entry_type->value,
                    'effect' => $entry->effect->value,
                    'amount' => $entry->amount,
                    'balance_after' => $entry->balance_after,
                ],
                'reason' => $reason,
            ]);

            return $entry;
        });
    }

    private function assertCachedBalanceMatchesLedger(MemberStoreAccount $account): void
    {
        $sum = (int) MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->sum(DB::raw('CASE WHEN effect = \'credit\' THEN amount ELSE -amount END'));

        if ($sum !== (int) $account->balance) {
            Log::critical('store-credit.invariant.balance_mismatch', [
                'account_id' => $account->id,
                'cached' => (int) $account->balance,
                'ledger_sum' => $sum,
            ]);

            throw new \RuntimeException('Saldo akun tidak sesuai dengan jumlah ledger entry.');
        }
    }

    private function ensureFundingAllowed(MemberStoreAccount $account): void
    {
        if (! $account->canReceiveFunding()) {
            throw ValidationException::withMessages([
                'account' => 'Akun yang ditutup tidak dapat menerima setoran.',
            ]);
        }
    }

    private function transitionStatus(
        MemberStoreAccount $account,
        MemberStoreAccountStatus $status,
        User $actor,
        ?string $reason,
    ): MemberStoreAccount {
        return DB::transaction(function () use ($account, $status, $actor, $reason): MemberStoreAccount {
            $locked = MemberStoreAccount::query()->lockForUpdate()->findOrFail($account->id);
            $previous = $locked->status->value;
            $locked->status = $status->value;
            $locked->suspended_at = $status === MemberStoreAccountStatus::Suspended ? now() : null;
            $locked->save();

            $action = $status === MemberStoreAccountStatus::Suspended
                ? 'member_store_credit.account.suspended'
                : 'member_store_credit.account.reactivated';

            $this->audit($action, $locked, $actor, [
                'old' => ['status' => $previous],
                'new' => ['status' => $locked->status->value],
                'reason' => $reason,
            ]);

            return $locked->refresh();
        });
    }

    private function audit(string $action, $subject, ?User $actor, array $changes): void
    {
        $this->auditLog->log(
            action: $action,
            module: self::MODULE,
            subject: $subject,
            changes: $changes,
            context: AuditContext::forActor($actor),
        );
    }

    private function referenceKey(string $prefix, MemberStoreAccount $account, int|string $referenceId): string
    {
        return $prefix.':'.$account->id.':'.$referenceId;
    }

    private function key(string $prefix, MemberStoreAccount $account, int|string|null $ref = null): string
    {
        $segments = [$prefix, $account->id];

        if ($ref !== null) {
            $segments[] = $ref;
        }

        return implode(':', $segments).':'.Str::uuid();
    }
}
