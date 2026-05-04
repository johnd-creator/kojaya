<?php

namespace App\Services\Accounting;

use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class FinancialStatementService
{
    public function trialBalance(?string $organizationId = null, ?string $asOfDate = null): Collection
    {
        $query = JournalEntryLine::query()
            ->selectRaw('chart_of_account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->with(['account'])
            ->whereHas('journalEntry', function ($journalQuery) use ($asOfDate, $organizationId): void {
                $journalQuery->where('status', 'POSTED');

                if ($organizationId) {
                    $journalQuery->where('organization_id', $organizationId);
                }

                if ($asOfDate) {
                    $journalQuery->whereDate('entry_date', '<=', $asOfDate);
                }
            })
            ->groupBy('chart_of_account_id')
            ->orderBy('chart_of_account_id');

        return $query->get()->map(function (JournalEntryLine $line): array {
            $account = $line->account;
            $debit = (float) $line->total_debit;
            $credit = (float) $line->total_credit;
            $balance = in_array($account->normal_balance, ['DEBIT', 'Debit'], true)
                ? $debit - $credit
                : $credit - $debit;

            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->account_type,
                'category' => $account->category,
                'normal_balance' => $account->normal_balance,
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
                'balance' => round($balance, 2),
            ];
        });
    }

    public function balanceSheet(?string $organizationId = null, ?string $asOfDate = null): array
    {
        $rows = $this->trialBalance($organizationId, $asOfDate);

        return [
            'assets' => $rows->where('account_type', 'ASSET')->values(),
            'liabilities' => $rows->where('account_type', 'LIABILITY')->values(),
            'equity' => $rows->where('account_type', 'EQUITY')->values(),
        ];
    }

    public function incomeStatement(?string $organizationId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $rows = $this->trialBalance($organizationId, $endDate);

        if ($startDate) {
            $rows = $rows->filter(fn (array $row): bool => $row['account_type'] === 'REVENUE' || $row['account_type'] === 'EXPENSE');
        }

        $revenues = $rows->where('account_type', 'REVENUE')->values();
        $expenses = $rows->where('account_type', 'EXPENSE')->values();

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'net_income' => round($revenues->sum('balance') - $expenses->sum('balance'), 2),
        ];
    }
}
