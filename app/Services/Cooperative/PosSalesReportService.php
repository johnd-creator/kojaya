<?php

namespace App\Services\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
use App\Support\OrganizationVisibility;
use App\Support\ReportAuthorizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PosSalesReportService
{
    private ?ReportAuthorizationScope $scopeCeiling = null;

    public function __construct(
        private readonly OrganizationScopedQueryService $scopeService,
    ) {}

    public function setScopeCeiling(ReportAuthorizationScope|OrganizationVisibility|null $scopeCeiling): self
    {
        if ($scopeCeiling instanceof OrganizationVisibility) {
            $scopeCeiling = ReportAuthorizationScope::forVisibility($scopeCeiling);
        }

        $this->scopeCeiling = $scopeCeiling;

        return $this;
    }

    public function withScopeCeiling(ReportAuthorizationScope|OrganizationVisibility|null $scopeCeiling): static
    {
        $clone = clone $this;
        $clone->setScopeCeiling($scopeCeiling);

        return $clone;
    }

    public function getScopeCeiling(): ?ReportAuthorizationScope
    {
        return $this->scopeCeiling;
    }

    public function resolveEffectiveVisibility(User $actor, ReportAuthorizationScope|OrganizationVisibility|null $scopeCeiling = null): OrganizationVisibility
    {
        $ceiling = $scopeCeiling ?? $this->scopeCeiling;
        if ($ceiling instanceof OrganizationVisibility) {
            $ceiling = ReportAuthorizationScope::forVisibility($ceiling);
        }

        $currentVisibility = $this->scopeService->visibilityFor($actor);

        if ($ceiling === null) {
            return $currentVisibility;
        }

        return $ceiling->intersect($currentVisibility);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summaryForPeriod(User $actor, string $from, string $to, array $filters = []): array
    {
        $base = $this->baseTransactionQuery($actor, $from, $to, $filters);

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
            'returns' => $this->returnsForPeriod($actor, $from, $to, $filters),
            'net_sales' => round((float) (clone $completed)->sum('total_amount') - $this->returnsTotalForPeriod($actor, $from, $to, $filters), 2),
            'member_transactions' => (clone $completed)->whereNotNull('cooperative_member_id')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{method: string, count: int, total: float}>
     */
    public function paymentReconciliation(User $actor, string $from, string $to, array $filters = []): array
    {
        $base = $this->baseTransactionQuery($actor, $from, $to, $filters)->where('status', 'COMPLETED');

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
    public function productSalesForPeriod(User $actor, string $from, string $to, array $filters = []): Collection
    {
        $visibility = $this->resolveEffectiveVisibility($actor);

        return PosTransactionItem::query()
            ->selectRaw('pos_product_id, sum(quantity) as quantity, sum(line_total) as revenue, sum(line_profit) as gross_profit')
            ->with('product.category')
            ->whereHas('transaction', function (Builder $query) use ($visibility, $from, $to, $filters): void {
                $this->scopeService->applyVisibility($query, $visibility)
                    ->where('status', 'COMPLETED')
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
    public function dailyTrend(User $actor, string $from, string $to, array $filters = []): array
    {
        $rows = $this->baseTransactionQuery($actor, $from, $to, $filters)
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
    public function topMembers(User $actor, string $from, string $to, array $filters = [], int $limit = 10): array
    {
        return $this->baseTransactionQuery($actor, $from, $to, $filters)
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
    public function cashierPerformance(User $actor, string $from, string $to, array $filters = []): array
    {
        return $this->baseTransactionQuery($actor, $from, $to, $filters)
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
    public function summaryForYear(User $actor, int $year, array $filters = []): array
    {
        $from = sprintf('%d-01-01', $year);
        $to = sprintf('%d-12-31', $year);

        return $this->summaryForPeriod($actor, $from, $to, $filters);
    }

    public function productSalesForYear(User $actor, int $year, array $filters = []): Collection
    {
        return $this->productSalesForPeriod(
            $actor,
            sprintf('%d-01-01', $year),
            sprintf('%d-12-31', $year),
            $filters,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseTransactionQuery(User $actor, string $from, string $to, array $filters = []): Builder
    {
        $visibility = $this->resolveEffectiveVisibility($actor);

        $query = $this->scopeService->applyVisibility(PosTransaction::query(), $visibility)
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to);

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
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
     * @return array<string, mixed>
     */
    private function returnsForPeriod(User $actor, string $from, string $to, array $filters = []): array
    {
        $base = $this->baseReturnQuery($actor, $from, $to, $filters);

        return [
            'count' => (clone $base)->count(),
            'total' => $this->returnsTotalForPeriod($actor, $from, $to, $filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function returnsTotalForPeriod(User $actor, string $from, string $to, array $filters = []): float
    {
        return (float) $this->baseReturnQuery($actor, $from, $to, $filters)->sum('total_amount');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseReturnQuery(User $actor, string $from, string $to, array $filters = []): Builder
    {
        $visibility = $this->resolveEffectiveVisibility($actor);

        $query = PosReturn::query()
            ->whereDate('returned_at', '>=', $from)
            ->whereDate('returned_at', '<=', $to)
            ->whereHas('transaction', function (Builder $txQuery) use ($visibility): void {
                $this->scopeService->applyVisibility($txQuery, $visibility);
            });

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

        return $query;
    }
}
