<?php

namespace App\Http\Controllers;

use App\Concerns\ResolvesApiPageSize;
use App\Models\Project;
use App\Services\ProjectFinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectFinanceController extends Controller
{
    use ResolvesApiPageSize;

    protected ProjectFinanceService $financeService;

    public function __construct(ProjectFinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public function index(Project $project)
    {
        // 1. Calculate Revenue (Paid Invoices)
        $revenue = $this->financeService->calculateRevenue($project);

        // 2. Calculate Costs
        $costs = $this->financeService->calculateDirectCosts($project);
        $totalCost = $costs['total'];
        $breakdown = $costs['breakdown'];

        // 3. Profit & Margin
        $profit = $revenue - $totalCost;
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;

        // 4. Recent Transactions
        $transactions = $project->invoices()->where('status', 'PAID')->latest('invoice_date')->take(5)->get()->map(function ($i) {
            return [
                'id' => $i->id,
                'description' => 'Invoice #'.$i->invoice_no,
                'date' => $i->invoice_date->format('d M Y'),
                'amount' => $i->total_amount,
                'type' => 'revenue',
            ];
        })->concat(
            $project->reimbursements()->where('status', 'PAID')->latest('submission_date')->take(5)->get()->map(function ($r) {
                return [
                    'id' => $r->id,
                    'description' => 'Reimbursement: '.$r->description,
                    'date' => $r->submission_date->format('d M Y'),
                    'amount' => $r->total_amount,
                    'type' => 'expense',
                ];
            })
        )->concat(
            $project->pettyCashTransactions()->where('type', 'CREDIT')->latest('transaction_date')->take(5)->get()->map(function ($p) {
                return [
                    'id' => $p->id,
                    'description' => 'Petty Cash: '.$p->description,
                    'date' => $p->transaction_date->format('d M Y'),
                    'amount' => $p->amount,
                    'type' => 'expense',
                ];
            })
        )->sortByDesc('date')->values()->take(10);

        // 5. S-Curve Data Generation (Mock Logic for now, ideally strictly time-series)
        // In real app, we would query daily progress logs or milestone completion dates
        $sCurveData = $this->generateSCurveData($project);

        return Inertia::render('ProjectFinance/Index', [
            'project' => $project,
            'financialSummary' => [
                'budget' => $project->budget,
                'revenue' => $revenue,
                'cost' => $totalCost,
                'profit' => $profit,
                'margin' => $margin,
            ],
            'costBreakdown' => array_merge($breakdown, [
                'labor' => 0, // Placeholder until payroll integrated
                'materials' => 0,
                'equipment' => 0,
                'subcontractors' => 0,
                'other' => 0,
            ]),
            'recentTransactions' => $transactions,
            'sCurveData' => $sCurveData,
        ]);
    }

    private function generateSCurveData(Project $project)
    {
        // Simple linear S-Curve simulation based on project duration
        // Real implementation would aggregate planned milestone weights vs actual completion

        $start = $project->start_date;
        $end = $project->end_date ?? now()->addMonths(6);
        $totalDays = $start->diffInDays($end);
        $interval = max(1, floor($totalDays / 10)); // 10 points

        $labels = [];
        $planned = [];
        $actual = [];

        $currentDate = $start->copy();
        $cumulativePlan = 0;

        // Mock current progress (e.g. 35%)
        $currentProgress = 35;

        for ($i = 0; $i <= 10; $i++) {
            $labels[] = $currentDate->format('M d');

            // Sigmoid-like curve for planned
            $t = $i / 10; // 0 to 1
            $s_curve_val = (1 / (1 + exp(-10 * ($t - 0.5)))) * 100; // Sigmoid function
            // Normalize to 0-100 roughly
            // Simple approach: linear for now or simple ease-in-out
            $weight = $i * 10;
            $planned[] = min(100, $weight);

            // Actual (only up to today)
            if ($currentDate <= now()) {
                // Mock: Actual is slightly lagging behind plan
                $actual[] = min($currentProgress, $weight * 0.9);
            }

            $currentDate->addDays($interval);
        }

        return [
            'labels' => $labels,
            'planned' => $planned,
            'actual' => $actual,
        ];
    }

    /**
     * Get financial summary (P&L) for a project
     */
    public function summary(Request $request, Project $project): JsonResponse
    {
        // Check authorization if needed (e.g., project manager or admin)
        // $this->authorize('view', $project);

        $summary = $this->financeService->getProfitAndLoss($project);

        return response()->json($summary);
    }

    /**
     * Get budget vs actual analysis
     */
    public function budgetAnalysis(Request $request, Project $project): JsonResponse
    {
        $analysis = $this->financeService->getBudgetVsActual($project);

        return response()->json($analysis);
    }

    /**
     * Get all financial transactions for a project
     */
    public function transactions(Request $request, Project $project): JsonResponse
    {
        $limit = $this->apiLimit($request, default: 20);

        // 1. Invoices (Revenue)
        $invoices = $project->invoices()
            ->select(['id', 'invoice_no as reference', 'invoice_date as date', 'total_amount as amount', 'status', 'notes as description'])
            ->selectRaw("'INVOICE' as type, 'REVENUE' as category")
            ->orderBy('invoice_date', 'desc')
            ->get();

        // 2. Reimbursements (Expense)
        $reimbursements = $project->reimbursements()
            ->select(['id', 'id as reference', 'submission_date as date', 'total_amount as amount', 'status', 'description'])
            ->selectRaw("'REIMBURSEMENT' as type, 'EXPENSE' as category")
            ->orderBy('submission_date', 'desc')
            ->get();

        // 3. Petty Cash (Expense)
        $pettyCash = $project->pettyCashTransactions()
            ->where('type', 'CREDIT')
            ->select(['id', 'reference_no as reference', 'transaction_date as date', 'amount', 'status', 'description'])
            ->selectRaw("'PETTY_CASH' as type, 'EXPENSE' as category")
            ->orderBy('transaction_date', 'desc')
            ->get();

        // Merge and sort
        $transactions = $invoices->concat($reimbursements)->concat($pettyCash)
            ->sortByDesc('date')
            ->values()
            ->take($limit);

        return response()->json([
            'data' => $transactions,
        ]);
    }
}
