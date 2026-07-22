<?php

namespace App\Services\Cooperative;

use App\Enums\MemberStoreAccountStatus;
use App\Enums\MemberStoreLedgerEffect;
use App\Models\MemberStoreAccount;
use App\Support\OrganizationVisibility;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Reporting for member store credit accounts.
 *
 * Aging uses FIFO debt allocation: credit entries (funding/refund) repay the
 * oldest outstanding purchase (debit) lots first. The oldest still-uncovered
 * lot date is reported as the account debt age. This is an exact, traceable
 * allocation model, not an approximation.
 */
class StoreCreditReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(OrganizationVisibility $visibility, array $filters = []): array
    {
        $utilizationThreshold = (float) ($filters['utilization_threshold'] ?? 0.8);

        $baseQuery = $visibility->applyTo(MemberStoreAccount::query())
            ->where('status', '!=', MemberStoreAccountStatus::Closed);

        $positiveDepositLiability = (int) (clone $baseQuery)->where('balance', '>', 0)->sum('balance');
        $negativeReceivable = (int) (clone $baseQuery)->where('balance', '<', 0)->sum('balance');

        $positiveCount = (clone $baseQuery)->where('balance', '>', 0)->count();
        $zeroCount = (clone $baseQuery)->where('balance', 0)->count();
        $negativeCount = (clone $baseQuery)->where('balance', '<', 0)->count();
        $suspendedCount = $visibility->applyTo(MemberStoreAccount::query())
            ->where('status', MemberStoreAccountStatus::Suspended->value)
            ->count();

        $highUtilizationAccounts = $this->highUtilizationAccounts($visibility, $utilizationThreshold);
        $oldestDebtDate = $this->oldestOrganizationDebtDate($visibility);

        return [
            'organization_id' => $visibility->organizationId,
            'positive_deposit_liability' => $positiveDepositLiability,
            'negative_receivable' => abs($negativeReceivable),
            'positive_account_count' => $positiveCount,
            'zero_account_count' => $zeroCount,
            'negative_account_count' => $negativeCount,
            'suspended_account_count' => $suspendedCount,
            'utilization_threshold' => $utilizationThreshold,
            'high_utilization_accounts' => $highUtilizationAccounts,
            'oldest_uncovered_debt_date' => $oldestDebtDate?->toDateString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function highUtilizationAccounts(OrganizationVisibility $visibility, float $threshold = 0.8): array
    {
        return $visibility->applyTo(MemberStoreAccount::query())
            ->where('balance', '<', 0)
            ->where('credit_limit', '>', 0)
            ->get()
            ->filter(function (MemberStoreAccount $account) use ($threshold): bool {
                $utilization = abs((int) $account->balance) / (int) $account->credit_limit;

                return $utilization >= $threshold;
            })
            ->map(fn (MemberStoreAccount $account) => [
                'id' => $account->id,
                'cooperative_member_id' => $account->cooperative_member_id,
                'balance' => (int) $account->balance,
                'credit_limit' => (int) $account->credit_limit,
                'utilization' => (int) $account->credit_limit > 0
                    ? round(abs((int) $account->balance) / (int) $account->credit_limit, 4)
                    : 0,
            ])
            ->values()
            ->all();
    }

    public function oldestUncoveredDebtDate(MemberStoreAccount $account): ?Carbon
    {
        $entries = $account->ledgerEntries()
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        return $this->allocateFifo($entries);
    }

    public function oldestOrganizationDebtDate(OrganizationVisibility $visibility): ?Carbon
    {
        $oldest = null;

        $visibility->applyTo(MemberStoreAccount::query())
            ->where('balance', '<', 0)
            ->chunkById(200, function (Collection $accounts) use (&$oldest): void {
                foreach ($accounts as $account) {
                    $date = $this->oldestUncoveredDebtDate($account);

                    if ($date !== null && ($oldest === null || $date->lt($oldest))) {
                        $oldest = $date;
                    }
                }
            });

        return $oldest;
    }

    /**
     * FIFO allocation across a chronologically ordered entry collection.
     * Debit entries create debt lots; credit entries repay the oldest lots.
     */
    public function allocateFifo(Collection $entries): ?Carbon
    {
        $lots = [];

        foreach ($entries as $entry) {
            if ($entry->effect === MemberStoreLedgerEffect::Debit) {
                $lots[] = ['date' => $entry->occurred_at, 'amount' => (int) $entry->amount];

                continue;
            }

            $remaining = (int) $entry->amount;

            while ($remaining > 0 && $lots !== []) {
                if ($lots[0]['amount'] <= $remaining) {
                    $remaining -= $lots[0]['amount'];
                    array_shift($lots);
                } else {
                    $lots[0]['amount'] -= $remaining;
                    $remaining = 0;
                }
            }
        }

        return $lots !== [] ? Carbon::parse($lots[0]['date']) : null;
    }
}
