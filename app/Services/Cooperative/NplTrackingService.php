<?php

namespace App\Services\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Enums\InstallmentStatus;
use App\Enums\LoanRiskRating;
use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class NplTrackingService
{
    public function __construct(
        private readonly OrganizationScopedQueryService $scopeService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function agingReport(User $actor, ?Carbon $asOf = null): array
    {
        $asOf ??= today();

        return $this->computeAgingReport($actor, $asOf);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function agingBuckets(User $actor, ?Carbon $asOf = null): array
    {
        return $this->agingReport($actor, $asOf)['buckets'];
    }

    public function nplRatio(User $actor, ?Carbon $asOf = null): float
    {
        return $this->agingReport($actor, $asOf)['npl_ratio'];
    }

    /**
     * @return array<string, mixed>
     */
    private function computeAgingReport(User $actor, CarbonInterface $asOf): array
    {
        $scopedLoanQuery = $this->scopeService->scopeVisibleTo(Loan::query(), $actor);

        $activeOutstanding = (float) (clone $scopedLoanQuery)
            ->where('status', LoanStatus::Active->value)
            ->sum('outstanding_amount');

        $eligibleLoanIds = (clone $scopedLoanQuery)
            ->whereIn('status', [
                LoanStatus::Active->value,
                LoanStatus::Approved->value,
                LoanStatus::Defaulted->value,
            ])
            ->pluck('id');

        $rows = LoanInstallment::query()
            ->with('loan.loanType', 'loan.member')
            ->whereIn('loan_id', $eligibleLoanIds)
            ->whereDate('due_date', '<', $asOf->toDateString())
            ->whereIn('status', [
                InstallmentStatus::Pending->value,
                InstallmentStatus::Partial->value,
                InstallmentStatus::Overdue->value,
            ])
            ->get();

        $buckets = $this->emptyBuckets();
        $nplOutstanding = 0.0;

        foreach ($rows as $installment) {
            $daysOverdue = (int) $installment->due_date->diffInDays($asOf);
            $remaining = max(0, (float) $installment->amount_due - (float) $installment->amount_paid);
            $bucket = $this->bucketFor($daysOverdue);
            $threshold = (int) ($installment->loan?->loanType?->npl_threshold_days ?? 90);
            $riskRating = $this->riskRatingFor($daysOverdue, $threshold);

            $buckets[$bucket]['installment_count']++;
            $buckets[$bucket]['outstanding_amount'] = round($buckets[$bucket]['outstanding_amount'] + $remaining, 2);
            $buckets[$bucket]['provisioning_amount'] = round($buckets[$bucket]['provisioning_amount'] + ($remaining * $this->provisionRate($riskRating)), 2);

            if ($daysOverdue >= $threshold) {
                $nplOutstanding = round($nplOutstanding + $remaining, 2);
            }
        }

        return [
            'as_of' => $asOf->toDateString(),
            'active_loan_outstanding' => $activeOutstanding,
            'npl_outstanding' => $nplOutstanding,
            'npl_ratio' => $activeOutstanding > 0 ? round($nplOutstanding / $activeOutstanding, 4) : 0,
            'buckets' => array_values($buckets),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function emptyBuckets(): array
    {
        return [
            '1-30' => ['bucket' => '1-30', 'installment_count' => 0, 'outstanding_amount' => 0.0, 'provisioning_amount' => 0.0],
            '31-60' => ['bucket' => '31-60', 'installment_count' => 0, 'outstanding_amount' => 0.0, 'provisioning_amount' => 0.0],
            '61-90' => ['bucket' => '61-90', 'installment_count' => 0, 'outstanding_amount' => 0.0, 'provisioning_amount' => 0.0],
            '91-120' => ['bucket' => '91-120', 'installment_count' => 0, 'outstanding_amount' => 0.0, 'provisioning_amount' => 0.0],
            '120+' => ['bucket' => '120+', 'installment_count' => 0, 'outstanding_amount' => 0.0, 'provisioning_amount' => 0.0],
        ];
    }

    private function bucketFor(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue <= 30 => '1-30',
            $daysOverdue <= 60 => '31-60',
            $daysOverdue <= 90 => '61-90',
            $daysOverdue <= 120 => '91-120',
            default => '120+',
        };
    }

    private function riskRatingFor(int $daysOverdue, int $threshold): LoanRiskRating
    {
        return match (true) {
            $daysOverdue >= $threshold => LoanRiskRating::Npl,
            $daysOverdue > 60 => LoanRiskRating::High,
            $daysOverdue > 30 => LoanRiskRating::Medium,
            default => LoanRiskRating::Low,
        };
    }

    private function provisionRate(LoanRiskRating $rating): float
    {
        return match ($rating) {
            LoanRiskRating::Low => 0.01,
            LoanRiskRating::Medium => 0.05,
            LoanRiskRating::High => 0.15,
            LoanRiskRating::Npl => 0.5,
            LoanRiskRating::WrittenOff => 1.0,
        };
    }
}
