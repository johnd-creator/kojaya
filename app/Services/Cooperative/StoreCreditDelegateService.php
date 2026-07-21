<?php

namespace App\Services\Cooperative;

use App\Enums\MemberStoreDelegateStatus;
use App\Enums\MemberStoreLedgerEntryType;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreCreditDelegateService
{
    public function __construct(private AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(MemberStoreAccount $account, array $data, User $createdBy): MemberStoreDelegate
    {
        $this->assertDelegateUserSameOrganization($account, $data['user_id'] ?? null);

        return DB::transaction(function () use ($account, $data, $createdBy): MemberStoreDelegate {
            $delegate = MemberStoreDelegate::create([
                'account_id' => $account->id,
                'organization_id' => $account->organization_id,
                'user_id' => $data['user_id'] ?? null,
                'display_name' => $data['display_name'],
                'code' => $this->generateUniqueCode($account),
                'per_transaction_limit' => $data['per_transaction_limit'] ?? null,
                'daily_limit' => $data['daily_limit'] ?? null,
                'valid_from' => $data['valid_from'] ?? Carbon::today()->toDateString(),
                'expires_at' => $data['expires_at'] ?? null,
                'status' => MemberStoreDelegateStatus::Active->value,
                'created_by' => $createdBy->id,
            ]);

            $this->audit('member_store_credit.delegate.created', $delegate, $createdBy);

            return $delegate;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MemberStoreDelegate $delegate, array $data, User $actor): MemberStoreDelegate
    {
        $delegate->fill([
            'display_name' => $data['display_name'] ?? $delegate->display_name,
            'per_transaction_limit' => array_key_exists('per_transaction_limit', $data) ? $data['per_transaction_limit'] : $delegate->per_transaction_limit,
            'daily_limit' => array_key_exists('daily_limit', $data) ? $data['daily_limit'] : $delegate->daily_limit,
            'valid_from' => $data['valid_from'] ?? $delegate->valid_from,
            'expires_at' => $data['expires_at'] ?? $delegate->expires_at,
        ]);
        $delegate->save();

        return $delegate->refresh();
    }

    public function revoke(MemberStoreDelegate $delegate, User $revokedBy): MemberStoreDelegate
    {
        return DB::transaction(function () use ($delegate, $revokedBy): MemberStoreDelegate {
            $locked = MemberStoreDelegate::query()->lockForUpdate()->findOrFail($delegate->id);
            $locked->status = MemberStoreDelegateStatus::Revoked->value;
            $locked->revoked_by = $revokedBy->id;
            $locked->revoked_at = now();
            $locked->save();

            $this->audit('member_store_credit.delegate.revoked', $locked, $revokedBy);

            return $locked->refresh();
        });
    }

    public function assertUsableForPurchase(MemberStoreDelegate $delegate, int $amount): MemberStoreDelegate
    {
        if (! $delegate->isCurrentlyActive()) {
            throw ValidationException::withMessages([
                'delegate' => 'Delegate tidak aktif, sudah kedaluwarsa, atau telah dicabut.',
            ]);
        }

        if ($delegate->per_transaction_limit !== null && $amount > (int) $delegate->per_transaction_limit) {
            throw ValidationException::withMessages([
                'delegate' => 'Nilai pembelian melebihi limit per transaksi delegate.',
            ]);
        }

        if ($delegate->daily_limit !== null) {
            $spentToday = (int) $delegate->ledgerEntries()
                ->where('entry_type', MemberStoreLedgerEntryType::PosPurchase->value)
                ->whereDate('occurred_at', Carbon::today())
                ->sum('amount');

            if ($spentToday + $amount > (int) $delegate->daily_limit) {
                throw ValidationException::withMessages([
                    'delegate' => 'Pembelian akan melebihi limit harian delegate.',
                ]);
            }
        }

        return $delegate;
    }

    private function generateUniqueCode(MemberStoreAccount $account): string
    {
        do {
            $code = strtoupper(Str::random(8));
            $exists = MemberStoreDelegate::query()
                ->where('organization_id', $account->organization_id)
                ->where('code', $code)
                ->exists();
        } while ($exists);

        return $code;
    }

    /**
     * A delegate's optional user must belong to the same organization as the
     * owning account. This is the authoritative domain boundary check — the
     * member API route has no `{account}` parameter, so route-bound form
     * validation cannot be relied upon. The check runs before any row is
     * created and before any audit event is recorded.
     */
    private function assertDelegateUserSameOrganization(MemberStoreAccount $account, mixed $userId): void
    {
        if ($userId === null || $userId === '') {
            return;
        }

        $delegateUser = User::query()->find($userId);

        if ($delegateUser === null || (string) $delegateUser->organization_id !== (string) $account->organization_id) {
            throw ValidationException::withMessages([
                'user_id' => 'Pengguna delegate harus berada pada organisasi yang sama.',
            ]);
        }
    }

    private function audit(string $action, MemberStoreDelegate $delegate, User $actor): void
    {
        $this->auditLog->log(
            action: $action,
            module: StoreCreditLedgerService::MODULE,
            subject: $delegate,
            changes: [
                'new' => [
                    'delegate_id' => $delegate->id,
                    'display_name' => $delegate->display_name,
                    'status' => $delegate->status->value,
                ],
            ],
            context: AuditContext::forActor($actor),
        );
    }
}
