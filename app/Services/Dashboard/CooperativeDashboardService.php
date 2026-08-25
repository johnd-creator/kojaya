<?php

namespace App\Services\Dashboard;

use App\Contracts\OrganizationScopedQueryService;
use App\Enums\CooperativeShuPeriodStatus;
use App\Enums\RoleExperience;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeShuPeriod;
use App\Models\MemberResignationRequest;
use App\Models\PosMemberPoint;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
use App\Services\Authorization\PrimaryRoleResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class CooperativeDashboardService
{
    public function __construct(
        private readonly OrganizationScopedQueryService $scopeService,
        private readonly PrimaryRoleResolver $primaryRoleResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function data(?User $user = null): array
    {
        $user ??= request()->user();

        if ($this->primaryRoleResolver->resolve($user) === RoleExperience::AdminKoperasi) {
            return $this->adminCooperativeData($user);
        }

        return $this->platformData();
    }

    /**
     * @return array<string, mixed>
     */
    private function platformData(): array
    {
        $today = CarbonImmutable::today();
        $now = CarbonImmutable::now();
        $currentPeriod = $now->format('Y-m');
        $year = $now->year;

        $todayTransactions = PosTransaction::query()
            ->whereDate('sold_at', $today);
        $monthlyTransactions = PosTransaction::query()
            ->whereBetween('sold_at', [$now->startOfMonth(), $now->endOfMonth()]);
        $yearlyTransactions = PosTransaction::query()
            ->whereYear('sold_at', $year);
        $monthlyDues = CooperativeDuesInvoice::query()
            ->forActiveMembers()
            ->where('period', $currentPeriod);
        $latestClosedShu = CooperativeShuPeriod::query()
            ->whereIn('status', [CooperativeShuPeriodStatus::Closed->value, CooperativeShuPeriodStatus::ClosedRevised->value])
            ->latest('year')
            ->first();

        $monthlyDuesAmount = (float) (clone $monthlyDues)->sum('amount');
        $monthlyPaidAmount = (float) (clone $monthlyDues)->sum('paid_amount');
        $monthlyOutstandingAmount = max($monthlyDuesAmount - $monthlyPaidAmount, 0);
        $pendingPaymentsCount = CooperativePayment::query()->where('status', 'PENDING')->count();
        $lowStockProductsCount = PosProduct::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();
        $pendingMembersCount = CooperativeMember::query()->where('status', 'PENDING')->count();
        $openDues = CooperativeDuesInvoice::query()
            ->forActiveMembers()
            ->whereIn('status', ['UNPAID', 'PARTIAL']);
        $unpaidDuesCount = (clone $openDues)
            ->count();
        $unpaidDuesAmount = (float) (clone $openDues)
            ->selectRaw('coalesce(sum(amount - paid_amount), 0) as outstanding_amount')
            ->value('outstanding_amount');

        return [
            'workspace' => 'platform',
            'summary' => [
                'today_sales' => (float) (clone $todayTransactions)->sum('total_amount'),
                'today_transactions' => (clone $todayTransactions)->count(),
                'pending_payments' => $pendingPaymentsCount,
                'low_stock_products' => $lowStockProductsCount,
                'active_members' => CooperativeMember::query()->where('status', 'ACTIVE')->count(),
                'unpaid_dues_amount' => $unpaidDuesAmount,
            ],
            'workQueue' => [
                'pending_members' => $pendingMembersCount,
                'pending_payments' => $pendingPaymentsCount,
                'unpaid_dues' => $unpaidDuesCount,
                'low_stock_products' => $lowStockProductsCount,
            ],
            'collections' => [
                'period' => $currentPeriod,
                'total_due' => $monthlyDuesAmount,
                'paid' => $monthlyPaidAmount,
                'outstanding' => $monthlyOutstandingAmount,
                'collection_rate' => $monthlyDuesAmount > 0
                    ? round(($monthlyPaidAmount / $monthlyDuesAmount) * 100, 1)
                    : 0,
                'pending_payment_amount' => (float) CooperativePayment::query()
                    ->where('status', 'PENDING')
                    ->sum('amount'),
                'saving_balance' => (float) CooperativeLedgerEntry::query()->sum('credit'),
                'member_credit_balance' => (float) CooperativeLedgerEntry::query()->sum('debit'),
            ],
            'pos' => [
                'today_sales' => (float) (clone $todayTransactions)->sum('total_amount'),
                'today_transactions' => (clone $todayTransactions)->count(),
                'monthly_sales' => (float) (clone $monthlyTransactions)->sum('total_amount'),
                'monthly_transactions' => (clone $monthlyTransactions)->count(),
                'annual_gross_profit' => (float) (clone $yearlyTransactions)->sum('gross_profit'),
                'member_transactions' => (clone $monthlyTransactions)
                    ->whereNotNull('cooperative_member_id')
                    ->count(),
                'top_products' => $this->topProducts($year),
            ],
            'inventory' => [
                'low_stock_count' => $lowStockProductsCount,
                'critical_products' => $this->criticalProducts(),
            ],
            'members' => [
                'active' => CooperativeMember::query()->where('status', 'ACTIVE')->count(),
                'pending' => $pendingMembersCount,
                'resigned' => CooperativeMember::query()->where('status', 'RESIGNED')->count(),
                'new_this_month' => CooperativeMember::query()
                    ->whereBetween('created_at', [$now->startOfMonth(), $now->endOfMonth()])
                    ->count(),
            ],
            'shu' => [
                'year' => $year,
                'annual_pos_profit' => (float) (clone $yearlyTransactions)->sum('gross_profit'),
                'annual_pos_points' => (int) PosMemberPoint::query()->where('year', $year)->sum('points'),
                'latest_closed_year' => $latestClosedShu?->year,
                'latest_closed_total' => $latestClosedShu
                    ? (float) ($latestClosedShu->cooperative_pool + $latestClosedShu->pos_profit_pool)
                    : 0,
            ],
            'generatedAt' => $now->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminCooperativeData(User $user): array
    {
        $now = CarbonImmutable::now();
        $currentPeriod = $now->format('Y-m');
        $organizationId = $this->scopeService->scopeOrganizationIdFor($user);

        $todayTransactions = PosTransaction::query()
            ->whereDate('sold_at', $now->toDateString());
        $this->scopePosTransactions($todayTransactions, $organizationId);

        $lowStockProducts = PosProduct::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock');
        $this->scopePosProducts($lowStockProducts, $organizationId);

        $members = CooperativeMember::query();
        $this->scopeService->scopeVisibleTo($members, $user);

        $pendingMembers = (clone $members)
            ->where('validation_status', CooperativeMember::VALIDATION_PENDING);
        $revisionMembers = (clone $members)
            ->where('validation_status', CooperativeMember::VALIDATION_REVISION);
        $activeMembers = (clone $members)
            ->where('status', CooperativeMember::VALIDATION_ACTIVE);

        $payments = CooperativePayment::query();
        $this->scopeService->scopeVisibleTo($payments, $user);
        $pendingPayments = (clone $payments)->where('status', 'PENDING');

        $dues = CooperativeDuesInvoice::query()
            ->forSavingsDues()
            ->forActiveMembers();
        $this->scopeService->scopeVisibleTo($dues, $user);
        $openDues = (clone $dues)->whereIn('status', ['UNPAID', 'PARTIAL']);
        $periodDues = (clone $dues)->where('period', $currentPeriod);

        $pendingResignations = null;

        if ($user->can('review_cooperative_resignation')) {
            $resignations = MemberResignationRequest::query();
            $this->scopeService->scopeVisibleTo($resignations, $user);
            $pendingResignations = (clone $resignations)
                ->where('status', MemberResignationRequest::STATUS_PENDING)
                ->count();
        }

        $totalDue = (float) (clone $periodDues)->sum('amount');
        $paid = (float) (clone $periodDues)->sum('paid_amount');
        $outstanding = max($totalDue - $paid, 0);
        $organization = $user->organization;
        $generatedAt = $now->toIso8601String();

        return [
            'workspace' => 'admin-koperasi',
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'code' => $organization->code,
            ] : null,
            'summary' => [
                'today_sales' => (float) (clone $todayTransactions)->sum('total_amount'),
                'today_transactions' => (clone $todayTransactions)->count(),
                'pending_members' => $pendingMembers->count(),
                'revision_members' => $revisionMembers->count(),
                'pending_payments' => $pendingPayments->count(),
                'low_stock_products' => $lowStockProducts->count(),
                'unpaid_dues_count' => $openDues->count(),
                'unpaid_dues_amount' => (float) $openDues
                    ->selectRaw('coalesce(sum(amount - paid_amount), 0) as outstanding_amount')
                    ->value('outstanding_amount'),
                'active_members' => $activeMembers->count(),
            ],
            'work_queue' => [
                'pending_payments' => $pendingPayments->count(),
                'pending_members' => $pendingMembers->count(),
                'revision_members' => $revisionMembers->count(),
                'unpaid_dues' => $openDues->count(),
                'low_stock_products' => $lowStockProducts->count(),
                'pending_resignations' => $pendingResignations,
            ],
            'collections' => [
                'period' => $currentPeriod,
                'total_due' => $totalDue,
                'paid' => $paid,
                'outstanding' => $outstanding,
                'collection_rate' => $totalDue > 0 ? round(($paid / $totalDue) * 100, 1) : 0,
                'pending_payment_amount' => (float) $pendingPayments->sum('amount'),
            ],
            'generated_at' => $generatedAt,
            'generatedAt' => $generatedAt,
        ];
    }

    private function scopePosTransactions(Builder $query, ?string $organizationId): void
    {
        if ($organizationId === null) {
            return;
        }

        $query->where(function (Builder $query) use ($organizationId): void {
            $query
                ->whereHas('cashier', fn (Builder $cashier): Builder => $cashier->where('organization_id', $organizationId))
                ->orWhereHas('member', fn (Builder $member): Builder => $member->where('organization_id', $organizationId));
        });
    }

    private function scopePosProducts(Builder $query, ?string $organizationId): void
    {
        if ($organizationId === null) {
            return;
        }

        $query->where(function (Builder $query) use ($organizationId): void {
            $query
                ->whereNull('organization_id')
                ->orWhere('organization_id', $organizationId);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topProducts(int $year): array
    {
        return PosTransactionItem::query()
            ->selectRaw('pos_product_id, sum(quantity) as quantity, sum(line_total) as revenue, sum(line_profit) as gross_profit')
            ->with('product.category')
            ->whereHas('transaction', fn ($query) => $query->whereYear('sold_at', $year))
            ->groupBy('pos_product_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn (PosTransactionItem $item): array => [
                'id' => $item->pos_product_id,
                'name' => $item->product?->name ?? 'Produk tidak tersedia',
                'category' => $item->product?->category?->name,
                'quantity' => (int) $item->quantity,
                'revenue' => (float) $item->revenue,
                'gross_profit' => (float) $item->gross_profit,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function criticalProducts(): array
    {
        return PosProduct::query()
            ->with('category')
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (PosProduct $product): array => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category?->name,
                'stock' => $product->stock,
                'minimum_stock' => $product->minimum_stock,
            ])
            ->values()
            ->all();
    }
}
