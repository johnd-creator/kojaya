<?php

namespace App\Services\Cooperative;

use App\Enums\CooperativeShuPeriodStatus;
use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativePayment;
use App\Models\CooperativeShuPeriod;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\PayrollApproval;
use App\Models\PosProduct;
use App\Models\RewardRedemption;
use Illuminate\Support\Collection;

class OperatorProcedureService
{
    /**
     * @return array<string, mixed>
     */
    public function approvalInbox(): array
    {
        return [
            'summary' => [
                'pending_payments' => CooperativePayment::query()->where('status', 'PENDING')->count(),
                'pending_loans' => Loan::query()->where('status', LoanStatus::Applied->value)->count(),
                'pending_redemptions' => RewardRedemption::query()->where('status', 'PENDING')->count(),
                'pending_payroll_approvals' => PayrollApproval::query()->where('status', 'PENDING')->count(),
            ],
            'items' => [
                'payments' => CooperativePayment::query()
                    ->with(['member', 'invoice.contributionType'])
                    ->where('status', 'PENDING')
                    ->latest()
                    ->limit(15)
                    ->get(),
                'loans' => Loan::query()
                    ->with(['member', 'loanType'])
                    ->where('status', LoanStatus::Applied->value)
                    ->latest()
                    ->limit(15)
                    ->get(),
                'redemptions' => RewardRedemption::query()
                    ->with(['member', 'reward'])
                    ->where('status', 'PENDING')
                    ->latest()
                    ->limit(15)
                    ->get(),
                'payroll' => PayrollApproval::query()
                    ->where('status', 'PENDING')
                    ->latest()
                    ->limit(15)
                    ->get(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function exceptions(): array
    {
        $today = today();

        return [
            'overdue_loans' => LoanInstallment::query()
                ->with('loan.member')
                ->whereDate('due_date', '<', $today)
                ->whereIn('status', [InstallmentStatus::Pending->value, InstallmentStatus::Overdue->value])
                ->orderBy('due_date')
                ->limit(20)
                ->get(),
            'unpaid_dues' => CooperativeDuesInvoice::query()
                ->with(['member', 'contributionType'])
                ->whereIn('status', ['UNPAID', 'PARTIAL'])
                ->whereDate('due_date', '<', $today)
                ->orderBy('due_date')
                ->limit(20)
                ->get(),
            'pending_payments' => CooperativePayment::query()
                ->with(['member', 'invoice'])
                ->where('status', 'PENDING')
                ->latest()
                ->limit(20)
                ->get(),
            'low_stock' => PosProduct::query()
                ->whereColumn('stock', '<=', 'minimum_stock')
                ->orderBy('stock')
                ->limit(20)
                ->get(),
            'analytics' => $this->analytics(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function analytics(): array
    {
        $activeLoanOutstanding = (float) Loan::query()
            ->where('status', LoanStatus::Active->value)
            ->sum('outstanding_amount');

        $overdueOutstanding = (float) LoanInstallment::query()
            ->whereDate('due_date', '<', today())
            ->whereIn('status', [InstallmentStatus::Pending->value, InstallmentStatus::Overdue->value])
            ->sum('amount_due');

        $latestShu = CooperativeShuPeriod::query()
            ->whereIn('status', [CooperativeShuPeriodStatus::Closed->value, CooperativeShuPeriodStatus::ClosedRevised->value])
            ->latest('year')
            ->first();

        return [
            'active_loan_outstanding' => $activeLoanOutstanding,
            'overdue_installment_amount' => $overdueOutstanding,
            'npl_ratio' => $activeLoanOutstanding > 0 ? round($overdueOutstanding / $activeLoanOutstanding, 4) : 0,
            'unpaid_dues_amount' => (float) CooperativeDuesInvoice::query()->whereIn('status', ['UNPAID', 'PARTIAL'])->sum('amount'),
            'pending_payment_amount' => (float) CooperativePayment::query()->where('status', 'PENDING')->sum('amount'),
            'latest_shu_year' => $latestShu?->year,
            'latest_shu_pool' => $latestShu ? (float) ($latestShu->cooperative_pool + $latestShu->pos_profit_pool) : 0,
        ];
    }

    public function export(string $type, ?string $period = null): string
    {
        $rows = match ($type) {
            'members' => \App\Models\CooperativeMember::query()->orderBy('member_no')->get(['member_no', 'name', 'status', 'joined_at']),
            'savings' => CooperativeLedgerEntry::query()->when($period, fn ($query) => $query->where('period', $period))->with('member')->orderBy('posted_at')->get(),
            'loans' => Loan::query()->with('member')->latest()->get(),
            'shu' => CooperativeShuPeriod::query()->with('allocations.member')->latest('year')->get(),
            'payments' => CooperativePayment::query()->when($period, fn ($query) => $query->whereHas('invoice', fn ($invoice) => $invoice->where('period', $period)))->with('member')->latest('paid_at')->get(),
            default => collect(),
        };

        return $this->toCsv($this->normalizeRows($type, $rows));
    }

    /**
     * @param  Collection<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(string $type, Collection $rows): array
    {
        return match ($type) {
            'members' => $rows->map(fn ($member): array => [
                'member_no' => $member->member_no,
                'name' => $member->name,
                'status' => $member->status,
                'joined_at' => $member->joined_at,
            ])->all(),
            'savings' => $rows->map(fn ($entry): array => [
                'member_no' => $entry->member?->member_no,
                'member_name' => $entry->member?->name,
                'period' => $entry->period,
                'entry_type' => $entry->entry_type,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'posted_at' => $entry->posted_at,
            ])->all(),
            'loans' => $rows->map(fn ($loan): array => [
                'member_no' => $loan->member?->member_no,
                'member_name' => $loan->member?->name,
                'reference_no' => $loan->reference_no,
                'status' => $loan->status?->value ?? $loan->status,
                'principal_amount' => $loan->principal_amount,
                'outstanding_amount' => $loan->outstanding_amount,
            ])->all(),
            'shu' => $rows->flatMap(fn ($period) => $period->allocations->map(fn ($allocation): array => [
                'year' => $period->year,
                'member_no' => $allocation->member?->member_no,
                'member_name' => $allocation->member?->name,
                'total_amount' => $allocation->total_amount,
            ]))->all(),
            'payments' => $rows->map(fn ($payment): array => [
                'member_no' => $payment->member?->member_no,
                'member_name' => $payment->member?->name,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'receipt_no' => $payment->receipt_no,
                'reconciliation_reference' => $payment->reconciliation_reference,
            ])->all(),
            default => [],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function toCsv(array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_keys($rows[0]));

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);

        return (string) stream_get_contents($handle);
    }
}
