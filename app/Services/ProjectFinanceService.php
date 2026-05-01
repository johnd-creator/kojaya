<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Reimbursement;

class ProjectFinanceService
{
    /**
     * Calculate total revenue from paid invoices
     */
    public function calculateRevenue(Project $project): float
    {
        return (float) $project->invoices()
            ->where('status', 'PAID')
            ->sum('total_amount');
    }

    /**
     * Calculate direct costs (Expenses + Labor)
     */
    public function calculateDirectCosts(Project $project): array
    {
        // 1. Expense: Paid Reimbursements
        $reimbursementCost = (float) $project->reimbursements()
            ->whereIn('status', ['PAID', 'APPROVED']) // Approved considered as committed cost
            ->sum('total_amount');

        // 2. Expense: Petty Cash (Credit/Expense transactions)
        $pettyCashCost = (float) $project->pettyCashTransactions()
            ->where('type', 'CREDIT')
            ->where('status', 'APPROVED')
            ->sum('amount');

        // 3. Labor: Payroll Allocations
        $laborCost = (float) $project->payrollAllocations()
            ->sum('amount');

        // 4. Material/Procurement (Future: from POs) - Currently placeholder or manual entry via budget items logic if needed
        // For now, we assume materials are covered in Petty Cash or Reimbursements,
        // OR we can add a logic to fetch from a future PurchaseOrder model.

        $totalExpense = $reimbursementCost + $pettyCashCost;

        return [
            'total' => $totalExpense + $laborCost,
            'breakdown' => [
                'reimbursements' => $reimbursementCost,
                'petty_cash' => $pettyCashCost,
                'labor' => $laborCost,
            ],
        ];
    }

    /**
     * Get Profit & Loss Statement
     */
    public function getProfitAndLoss(Project $project): array
    {
        $revenue = $this->calculateRevenue($project);
        $costs = $this->calculateDirectCosts($project);
        $totalCost = $costs['total'];

        $grossProfit = $revenue - $totalCost;
        $grossMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        return [
            'revenue' => $revenue,
            'cogs' => $totalCost, // Cost of Goods Sold / Cost of Services
            'gross_profit' => $grossProfit,
            'gross_margin' => round($grossMargin, 2),
            'cost_breakdown' => $costs['breakdown'],
        ];
    }

    /**
     * Get Budget vs Actual Analysis
     */
    public function getBudgetVsActual(Project $project): array
    {
        $budgetItems = $project->budgetItems()->get();

        $totalBudget = $budgetItems->sum('planned_amount');

        // Calculate actuals per category mapping
        // This is a simplified mapping. In a real world scenario, you'd tag expenses with categories.
        // For now, we map based on source:
        // LABOR -> Payroll Allocations
        // MATERIAL/OTHERS -> Reimbursements + Petty Cash

        $costs = $this->calculateDirectCosts($project);

        // Group budget items by category
        $groupedBudget = $budgetItems->groupBy('category')->map(function ($items) {
            return $items->sum('planned_amount');
        });

        // Actual mapping (Simplified)
        $actuals = [
            'LABOR' => $costs['breakdown']['labor'],
            'MATERIAL' => 0, // Needs specific tagging in expenses
            'OVERHEAD' => 0,
            'OTHERS' => $costs['breakdown']['reimbursements'] + $costs['breakdown']['petty_cash'],
        ];

        // If we want more precise actuals per category, we would need to add 'category' field to Reimbursement/PettyCash
        // For MVP, we present total vs total.

        return [
            'total_budget' => $totalBudget,
            'total_actual' => $costs['total'],
            'variance' => $totalBudget - $costs['total'],
            'percentage_used' => $totalBudget > 0 ? ($costs['total'] / $totalBudget) * 100 : 0,
            'items' => $budgetItems,
        ];
    }
}
