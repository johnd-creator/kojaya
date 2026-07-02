<?php

namespace App\Services\Cooperative;

use App\Enums\WithdrawalStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\SavingsWithdrawal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsWithdrawalService
{
    private const VOLUNTARY_ENTRY_TYPES = [
        'SIMPANAN_SUKARELA',
        'SAVINGS_VOLUNTARY',
        'VOLUNTARY_SAVING',
    ];

    public function __construct(
        private readonly CooperativeNotificationDispatcher $notificationDispatcher,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function request(CooperativeMember $member, array $data, ?User $actor = null): SavingsWithdrawal
    {
        $amount = round((float) $data['amount'], 2);
        $balance = $this->voluntaryBalance($member);

        if ($amount <= 0 || $amount > $balance) {
            throw ValidationException::withMessages([
                'amount' => 'Saldo simpanan sukarela tidak mencukupi.',
            ]);
        }

        $withdrawal = SavingsWithdrawal::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $actor?->id,
            'amount' => $amount,
            'status' => WithdrawalStatus::Pending,
            'destination_bank' => $data['destination_bank'] ?? null,
            'destination_account_no' => $data['destination_account_no'] ?? null,
            'destination_account_name' => $data['destination_account_name'] ?? null,
            'reason' => $data['reason'] ?? null,
        ]);

        $this->notificationDispatcher->withdrawalRequested($withdrawal->refresh(), $actor);

        return $withdrawal;
    }

    public function approve(SavingsWithdrawal $withdrawal, ?User $actor = null): SavingsWithdrawal
    {
        return DB::transaction(function () use ($actor, $withdrawal): SavingsWithdrawal {
            $locked = SavingsWithdrawal::query()->lockForUpdate()->findOrFail($withdrawal->id);

            if ($locked->status !== WithdrawalStatus::Pending) {
                return $locked;
            }

            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($locked->cooperative_member_id);

            if ((float) $locked->amount > $this->voluntaryBalance($member)) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo simpanan sukarela tidak mencukupi.',
                ]);
            }

            $locked->forceFill([
                'status' => WithdrawalStatus::Processed,
                'approved_by' => $actor?->id,
                'approved_at' => now(),
                'processed_at' => now(),
            ])->save();

            $sukarela = CooperativeContributionType::query()
                ->where('category', 'SUKARELA')
                ->where('is_active', true)
                ->orderBy('id')
                ->first();

            CooperativeLedgerEntry::query()->create([
                'cooperative_member_id' => $locked->cooperative_member_id,
                'cooperative_payment_id' => null,
                'source_type' => SavingsWithdrawal::class,
                'source_id' => $locked->id,
                'entry_type' => 'SAVING_WITHDRAWAL',
                'cooperative_contribution_type_id' => $sukarela?->id,
                'ledger_scope' => 'SAVINGS',
                'category_snapshot' => $sukarela?->category ?? 'SUKARELA',
                'debit' => $locked->amount,
                'credit' => 0,
                'period' => now()->format('Y-m'),
                'description' => 'Penarikan simpanan sukarela',
                'posted_at' => now()->toDateString(),
            ]);

            DB::afterCommit(fn () => $this->notificationDispatcher->withdrawalApproved($locked->refresh(), $actor));

            return $locked->refresh();
        });
    }

    public function reject(SavingsWithdrawal $withdrawal, ?User $actor = null, ?string $reason = null): SavingsWithdrawal
    {
        return DB::transaction(function () use ($actor, $reason, $withdrawal): SavingsWithdrawal {
            $locked = SavingsWithdrawal::query()->lockForUpdate()->findOrFail($withdrawal->id);

            if ($locked->status !== WithdrawalStatus::Pending) {
                return $locked;
            }

            $locked->forceFill([
                'status' => WithdrawalStatus::Rejected,
                'approved_by' => $actor?->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            DB::afterCommit(fn () => $this->notificationDispatcher->withdrawalRejected($locked->refresh(), $actor, $reason));

            return $locked->refresh();
        });
    }

    public function voluntaryBalance(CooperativeMember $member): float
    {
        return (float) CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $member->id)
            ->where(function ($query): void {
                $query->whereIn('entry_type', self::VOLUNTARY_ENTRY_TYPES)
                    ->orWhere('category_snapshot', 'SUKARELA')
                    ->orWhereHas('contributionType', fn ($typeQuery) => $typeQuery->where('category', 'SUKARELA'));
            })
            ->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')
            ->value('balance');
    }
}
