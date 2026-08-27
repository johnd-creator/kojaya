<?php

namespace App\Services\Cooperative;

use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use Illuminate\Support\Collection;

class PosSalesReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summaryForPeriod(string $from, string $to, array $filters = []): array
    {
        $base = $this->baseTransactionQuery($from, $to, $filters);

        $completed = (clone $base)->where('status', 'COMPLETED');
        $voided = (clone $base)->where('status', 'VOIDED');

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'transactions' => (clone $completed)->count(),
            'voided_transactions' => (clone $voided)->count(),
            'gross_sales' => (float) (clone $completed)->sum('total_amount'),
            'total_discount' => (float) (clone $completed)->sum('discount_amount'),
            'gross_profit' => (float) (clone $completed)->sum('gross_profit'),
            'voided_amount' => (float) (clone $voided)->sum('total_amount'),
            'returns' => $this->returnsForPeriod($from, $to, $filters),
            'net_sales' => round((float) (clone $completed)->sum('total_amount') - $this->returnsTotalForPeriod($from, $to, $filters), 2),
            'member_transactions' => (clone $completed)->whereNotNull('cooperative_member_id')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{method: string, count: int, total: float}>
     */
    public function paymentReconciliation(string $from, string $to, array $filters = []): array
    {
        $base = $this->baseTransactionQuery($from, $to, $filters)->where('status', 'COMPLETED');

        $rows = $base->clone()
            ->join('pos_payments', 'pos_transactions.id', '=', 'pos_payments.pos_transaction_id')
            ->selectRaw('pos_payments.payment_method as method, COUNT(*) as cnt, SUM(pos_payments.amount) as total')
            ->groupBy('pos_payments.payment_method')
            ->orderByDesc('total')
            ->get();

        return $rows->map(fn ($row): array => [
            'method' => $row->method,
            'count' => (int) $row->cnt,
            'total' => (float) $row->total,
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function productSalesForPeriod(string $from, string $to, array $filters = []): Collection
    {
        return PosTransactionItem::query()
            ->selectRaw('pos_product_id, sum(quantity) as quantity, sum(line_total) as revenue, sum(line_profit) as gross_profit')
            ->with('product.category')
            ->whereHas('transaction', function ($query) use ($from, $to, $filters): void {
                $query->where('status', 'COMPLETED')
                    ->whereDate('sold_at', '>=', $from)
                    ->whereDate('sold_at', '<=', $to);
                $this->applyFilters($query, $filters);
            })
            ->groupBy('pos_product_id')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn (PosTransactionItem $item): array => [
                'pos_product_id' => $item->pos_product_id,
                'product_name' => $item->product?->name ?? 'Produk tidak tersedia',
                'category' => $item->product?->category?->name,
                'quantity' => (int) $item->quantity,
                'revenue' => (float) $item->revenue,
                'gross_profit' => (float) $item->gross_profit,
                'margin_percent' => $item->revenue > 0
                    ? round((float) $item->gross_profit / (float) $item->revenue * 100, 2)
                    : 0.0,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{date: string, revenue: float, transactions: int}>
     */
    public function dailyTrend(string $from, string $to, array $filters = []): array
    {
        $rows = $this->baseTransactionQuery($from, $to, $filters)
            ->where('status', 'COMPLETED')
            ->selectRaw('DATE(sold_at) as date, COUNT(*) as cnt, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $rows->map(fn ($row): array => [
            'date' => $row->date,
            'transactions' => (int) $row->cnt,
            'revenue' => (float) $row->revenue,
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function topMembers(string $from, string $to, array $filters = [], int $limit = 10): array
    {
        return $this->baseTransactionQuery($from, $to, $filters)
            ->where('status', 'COMPLETED')
            ->whereNotNull('cooperative_member_id')
            ->selectRaw('cooperative_member_id, COUNT(*) as cnt, SUM(total_amount) as total')
            ->groupBy('cooperative_member_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->with('member:id,name,member_no')
            ->get()
            ->map(fn ($row): array => [
                'cooperative_member_id' => $row->cooperative_member_id,
                'member_name' => $row->member?->name ?? 'Anggota',
                'member_no' => $row->member?->member_no,
                'transactions' => (int) $row->cnt,
                'total' => (float) $row->total,
            ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function cashierPerformance(string $from, string $to, array $filters = []): array
    {
        return $this->baseTransactionQuery($from, $to, $filters)
            ->where('status', 'COMPLETED')
            ->whereNotNull('cashier_id')
            ->selectRaw('cashier_id, COUNT(*) as cnt, SUM(total_amount) as total')
            ->groupBy('cashier_id')
            ->orderByDesc('total')
            ->with('cashier:id,name')
            ->get()
            ->map(fn ($row): array => [
                'cashier_id' => $row->cashier_id,
                'cashier_name' => $row->cashier?->name ?? 'Kasir',
                'transactions' => (int) $row->cnt,
                'total' => (float) $row->total,
            ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summaryForYear(int $year): array
    {
        $from = sprintf('%d-01-01', $year);
        $to = sprintf('%d-12-31', $year);

        return $this->summaryForPeriod($from, $to);
    }

    public function productSalesForYear(int $year): Collection
    {
        return $this->productSalesForPeriod(
            sprintf('%d-01-01', $year),
            sprintf('%d-12-31', $year),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseTransactionQuery(string $from, string $to, array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = PosTransaction::query()
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to);

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        if (array_key_exists('organization_id', $filters) && $filters['organization_id'] !== null) {
            $query->whereHas('items.product', fn ($q) => $q->where('organization_id', $filters['organization_id']));
        }

        if (! empty($filters['pos_product_id'])) {
            $query->whereHas('items', fn ($q) => $q->where('pos_product_id', $filters['pos_product_id']));
        }
        if (! empty($filters['category_id'])) {
            $query->whereHas('items.product', fn ($q) => $q->where('pos_category_id', $filters['category_id']));
        }
        if (! empty($filters['cashier_id'])) {
            $query->where('cashier_id', $filters['cashier_id']);
        }
        if (! empty($filters['cooperative_member_id'])) {
            $query->where('cooperative_member_id', $filters['cooperative_member_id']);
        }
        if (! empty($filters['payment_method'])) {
            $query->whereHas('payments', fn ($q) => $q->where('payment_method', $filters['payment_method']));
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function returnsForPeriod(string $from, string $to, array $filters = []): array
    {
        $base = $this->baseReturnQuery($from, $to, $filters);

        return [
            'count' => (clone $base)->count(),
            'total' => $this->returnsTotalForPeriod($from, $to, $filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function returnsTotalForPeriod(string $from, string $to, array $filters = []): float
    {
        return (float) $this->baseReturnQuery($from, $to, $filters)->sum('total_amount');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseReturnQuery(string $from, string $to, array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = PosReturn::query()
            ->whereDate('returned_at', '>=', $from)
            ->whereDate('returned_at', '<=', $to);

        if (! empty($filters['pos_product_id'])) {
            $query->whereHas('items.transactionItem', fn ($q) => $q->where('pos_product_id', $filters['pos_product_id']));
        }
        if (! empty($filters['category_id'])) {
            $query->whereHas('items.transactionItem.product', fn ($q) => $q->where('pos_category_id', $filters['category_id']));
        }
        if (! empty($filters['cashier_id'])) {
            $query->where('cashier_id', $filters['cashier_id']);
        }
        if (! empty($filters['cooperative_member_id'])) {
            $query->where('cooperative_member_id', $filters['cooperative_member_id']);
        }
        if (! empty($filters['payment_method'])) {
            $query->whereHas('transaction.payments', fn ($q) => $q->where('payment_method', $filters['payment_method']));
        }

        if (array_key_exists('organization_id', $filters) && $filters['organization_id'] !== null) {
            $query->whereHas('items.transactionItem.product', fn ($q) => $q->where('organization_id', $filters['organization_id']));
        }

        return $query;
    }
}
