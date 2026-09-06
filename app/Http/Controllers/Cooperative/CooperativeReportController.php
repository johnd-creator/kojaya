<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Enums\CooperativeShuPeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeShuPeriod;
use App\Models\PointTransaction;
use App\Models\PosMemberPoint;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\NplTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeReportController extends Controller
{
    public function __construct(
        private OrganizationScopedQueryService $scopeService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->can('view_cooperative_report'), 403);

        $this->scopeService->visibilityFor($user);

        return Inertia::render('Cooperative/Reports', [
            'summary' => Inertia::defer(fn (): array => $this->summaryData($user), 'summary'),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->can('view_cooperative_report'), 403);

        $this->scopeService->visibilityFor($user);

        return response()->json(['data' => $this->summaryData($user)]);
    }

    public function sales(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->can('view_pos_reports'), 403);

        $query = $this->scopeService->scopeVisibleTo(
            PosTransaction::query()->where('status', 'COMPLETED'),
            $user
        );

        if ($request->filled('cashier_id')) {
            $query->where('cashier_id', $request->input('cashier_id'));
        }

        $dateFrom = $request->input('date_from') ?? $request->input('from');
        if ($dateFrom) {
            $query->whereDate('sold_at', '>=', $dateFrom);
        }

        $dateTo = $request->input('date_to') ?? $request->input('to');
        if ($dateTo) {
            $query->whereDate('sold_at', '<=', $dateTo);
        }

        $totalCount = (int) (clone $query)->count();
        $totalRevenue = (float) (clone $query)->sum('total_amount');
        $totalGrossProfit = (float) (clone $query)->sum('gross_profit');

        $byCashier = (clone $query)
            ->whereNotNull('cashier_id')
            ->selectRaw('cashier_id, count(*) as count, sum(total_amount) as revenue, sum(gross_profit) as gross_profit')
            ->groupBy('cashier_id')
            ->with('cashier:id,name,email')
            ->get()
            ->map(fn ($row): array => [
                'cashier_id' => $row->cashier_id,
                'cashier_name' => $row->cashier?->name ?? 'Kasir',
                'count' => (int) $row->count,
                'revenue' => (float) $row->revenue,
                'gross_profit' => (float) $row->gross_profit,
            ])
            ->values()
            ->all();

        $dailyData = (clone $query)
            ->selectRaw('date(sold_at) as date, count(*) as transactions, sum(total_amount) as total')
            ->groupByRaw('date(sold_at)')
            ->orderByDesc('date')
            ->limit(31)
            ->get();

        return response()->json([
            'data' => $dailyData,
            'count' => $totalCount,
            'revenue' => $totalRevenue,
            'gross_profit' => $totalGrossProfit,
            'by_cashier' => $byCashier,
            'summary' => [
                'count' => $totalCount,
                'revenue' => $totalRevenue,
                'gross_profit' => $totalGrossProfit,
            ],
        ]);
    }

    public function nplAging(Request $request, NplTrackingService $service): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user?->can('view_cooperative_report') || $user?->can('view_loan_report'),
            403
        );

        $this->scopeService->visibilityFor($user);

        $asOf = $request->filled('as_of')
            ? \Illuminate\Support\Carbon::parse($request->input('as_of'))
            : null;

        return response()->json([
            'data' => $service->agingReport($user, $asOf),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryData(User $actor): array
    {
        $visibility = $this->scopeService->visibilityFor($actor);
        $isGlobal = $visibility->global;
        $orgId = $visibility->organizationId;
        $year = now()->year;

        $memberQuery = $this->scopeService->scopeVisibleTo(CooperativeMember::query(), $actor);

        $activeMembers = (int) (clone $memberQuery)->where('status', 'ACTIVE')->count();
        $totalOutstanding = (float) (clone $memberQuery)->sum('outstanding_balance');

        $returningCustomersQuery = (clone $memberQuery)->where('status', 'ACTIVE');
        if (! $isGlobal) {
            $returningCustomersQuery->whereHas('posTransactions', function ($q) use ($orgId): void {
                $q->where('status', 'COMPLETED')
                    ->where('organization_id', $orgId);
            }, '>=', 2);
        } else {
            $returningCustomersQuery->whereHas('posTransactions', function ($q): void {
                $q->where('status', 'COMPLETED')
                    ->whereColumn('pos_transactions.organization_id', 'cooperative_members.organization_id');
            }, '>=', 2);
        }
        $returningCustomers = (int) $returningCustomersQuery->count();

        $ledgerQuery = $this->scopeService->scopeVisibleTo(CooperativeLedgerEntry::query(), $actor);
        $savingBalance = (float) (clone $ledgerQuery)->sum('credit');
        $memberCreditBalance = (float) (clone $ledgerQuery)->sum('debit');

        $duesQuery = $this->scopeService->scopeVisibleTo(CooperativeDuesInvoice::query(), $actor)
            ->whereIn('status', ['UNPAID', 'PARTIAL']);
        $unpaidDues = (float) $duesQuery->sum('amount');

        $posTxQuery = $this->scopeService->scopeVisibleTo(PosTransaction::query(), $actor)
            ->where('status', 'COMPLETED');

        $todaySales = (float) (clone $posTxQuery)->whereDate('sold_at', today())->sum('total_amount');
        $monthlySales = (float) (clone $posTxQuery)->whereBetween('sold_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_amount');
        $annualPosProfit = (float) (clone $posTxQuery)->whereYear('sold_at', $year)->sum('gross_profit');
        $totalRevenue = (float) (clone $posTxQuery)->sum('total_amount');
        $grossProfit = (float) (clone $posTxQuery)->sum('gross_profit');

        $productQuery = PosProduct::query()
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->when(! $isGlobal, fn ($q) => $q->where('organization_id', $orgId));
        $lowStockProducts = (int) $productQuery->count();

        $pointsQuery = PosMemberPoint::query()
            ->when(! $isGlobal, fn ($q) => $q->whereHas('member', fn ($mq) => $mq->where('organization_id', $orgId)));
        $annualPosPoints = (int) (clone $pointsQuery)->where('year', $year)->sum('points');

        $pointTxQuery = PointTransaction::query()
            ->when(! $isGlobal, fn ($q) => $q->whereHas('member', fn ($mq) => $mq->where('organization_id', $orgId)));
        $earnedPoints = (int) (clone $pointTxQuery)->where('transaction_type', 'EARNED')->sum('points');
        if ($earnedPoints === 0) {
            $earnedPoints = (int) (clone $pointsQuery)->sum('points');
        }
        $redeemedPoints = (int) (clone $pointTxQuery)->where('transaction_type', 'REDEEMED')->sum('points');

        $activePointMembersQuery = (clone $memberQuery);
        $activePointMembers = (int) $activePointMembersQuery->where(function ($q): void {
            $q->whereHas('pointTransactions')
                ->orWhereHas('posMemberPoints');
        })->count();

        if ($isGlobal) {
            $latestClosedShu = CooperativeShuPeriod::query()
                ->whereIn('status', [CooperativeShuPeriodStatus::Closed->value, CooperativeShuPeriodStatus::ClosedRevised->value])
                ->latest('year')
                ->first();
            $latestShuYear = $latestClosedShu?->year;
            $latestShuTotal = $latestClosedShu
                ? (float) ($latestClosedShu->cooperative_pool + $latestClosedShu->pos_profit_pool)
                : 0.0;
        } else {
            $latestShuYear = null;
            $latestShuTotal = 0.0;
        }

        return [
            'active_members' => $activeMembers,
            'saving_balance' => $savingBalance,
            'member_credit_balance' => $memberCreditBalance,
            'unpaid_dues' => $unpaidDues,
            'today_sales' => $todaySales,
            'monthly_sales' => $monthlySales,
            'low_stock_products' => $lowStockProducts,
            'annual_pos_profit' => $annualPosProfit,
            'annual_pos_points' => $annualPosPoints,
            'latest_shu_year' => $latestShuYear,
            'latest_shu_total' => $latestShuTotal,
            'returning_customers' => $returningCustomers,
            'members' => [
                'count' => $activeMembers,
            ],
            'members_count' => $activeMembers,
            'points' => [
                'active_members' => $activePointMembers,
                'earned' => $earnedPoints,
                'redeemed' => $redeemedPoints,
            ],
            'active_points_members' => $activePointMembers,
            'earned_points' => $earnedPoints,
            'redeemed_points' => $redeemedPoints,
            'financial' => [
                'total_revenue' => $totalRevenue,
                'gross_profit' => $grossProfit,
                'total_outstanding' => $totalOutstanding,
            ],
            'total_revenue' => $totalRevenue,
            'gross_profit' => $grossProfit,
            'total_outstanding' => $totalOutstanding,
        ];
    }
}
