<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use Illuminate\Database\Eloquent\Builder;

class SavingsSummaryService
{
    private const CATEGORIES = ['POKOK', 'WAJIB', 'SUKARELA', 'KHUSUS'];

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total_balance: float, by_category: array<string, float>, uncategorized: float}
     */
    public function summary(?CooperativeMember $member = null, array $filters = []): array
    {
        return $this->summaryFromQuery($this->ledgerQuery($member, $filters));
    }

    /**
     * Calculate a summary from an already-scoped ledger query.
     *
     * @param  Builder<CooperativeLedgerEntry>  $ledgerQuery
     * @return array{total_balance: float, by_category: array<string, float>, uncategorized: float}
     */
    public function summaryFromQuery(Builder $ledgerQuery): array
    {
        $rows = (clone $ledgerQuery)
            ->leftJoin('cooperative_contribution_types', 'cooperative_ledger_entries.cooperative_contribution_type_id', '=', 'cooperative_contribution_types.id')
            ->selectRaw('COALESCE(cooperative_contribution_types.category, cooperative_ledger_entries.category_snapshot, ? ) as category_key', ['UNCATEGORIZED'])
            ->selectRaw('COALESCE(SUM(cooperative_ledger_entries.credit - cooperative_ledger_entries.debit), 0) as balance')
            ->groupBy('category_key')
            ->get();

        $byCategory = array_fill_keys(self::CATEGORIES, 0.0);
        $uncategorized = 0.0;

        foreach ($rows as $row) {
            $category = strtoupper((string) $row->category_key);
            $balance = round((float) $row->balance, 2);

            if (array_key_exists($category, $byCategory)) {
                $byCategory[$category] = $balance;
            } else {
                $uncategorized += $balance;
            }
        }

        return [
            'total_balance' => round(array_sum($byCategory) + $uncategorized, 2),
            'by_category' => $byCategory,
            'uncategorized' => round($uncategorized, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<CooperativeLedgerEntry>
     */
    public function ledgerQuery(?CooperativeMember $member = null, array $filters = []): Builder
    {
        $ledgerScope = array_key_exists('ledger_scope', $filters)
            ? $filters['ledger_scope']
            : 'SAVINGS';

        return CooperativeLedgerEntry::query()
            ->with(['member', 'contributionType', 'payment'])
            ->when($member, fn (Builder $query) => $query->where('cooperative_member_id', $member->id))
            ->when($ledgerScope !== null && $ledgerScope !== '', fn (Builder $query) => $query->where('ledger_scope', $ledgerScope))
            ->when($filters['member_id'] ?? null, fn (Builder $query, mixed $memberId) => $query->where('cooperative_member_id', $memberId))
            ->when($filters['member_search'] ?? null, function (Builder $query, mixed $search): void {
                $query->whereHas('member', function (Builder $memberQuery) use ($search): void {
                    $keyword = '%'.mb_strtolower((string) $search).'%';
                    $columns = ['name', 'member_no', 'no_anggota', 'nama_anggota'];

                    $memberQuery->whereRaw("LOWER(COALESCE({$columns[0]}, '')) LIKE ?", [$keyword]);

                    foreach (array_slice($columns, 1) as $column) {
                        $memberQuery->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$keyword]);
                    }
                });
            })
            ->when($filters['entry_type'] ?? null, fn (Builder $query, mixed $entryType) => $query->where('entry_type', $entryType))
            ->when($filters['contribution_type_id'] ?? null, fn (Builder $query, mixed $typeId) => $query->where('cooperative_contribution_type_id', $typeId))
            ->when($filters['category'] ?? null, function (Builder $query, mixed $category): void {
                $query->where(function (Builder $categoryQuery) use ($category): void {
                    $categoryQuery->where('category_snapshot', $category)
                        ->orWhereHas('contributionType', fn (Builder $typeQuery) => $typeQuery->where('category', $category));
                });
            })
            ->when($filters['start_date'] ?? null, fn (Builder $query, mixed $date) => $query->whereDate('posted_at', '>=', $date))
            ->when($filters['end_date'] ?? null, fn (Builder $query, mixed $date) => $query->whereDate('posted_at', '<=', $date));
    }
}
