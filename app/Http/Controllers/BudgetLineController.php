<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertBudgetLineRequest;
use App\Models\Budget;
use App\Models\BudgetLine;

class BudgetLineController extends Controller
{
    public function store(UpsertBudgetLineRequest $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        if ($budget->status !== 'DRAFT') {
            return back()->with('error', 'Only DRAFT budgets can be modified.');
        }

        $validated = $request->validated();

        BudgetLine::create([
            'budget_id' => $budget->id,
            'cost_center' => $validated['cost_center'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'gl_account' => $validated['gl_account'],
            'category' => $validated['category'],
            'allocated_amount' => $validated['allocated_amount'],
            'committed_amount' => 0,
            'realized_amount' => 0,
        ]);

        return back()->with('success', 'Budget line added.');
    }

    public function update(UpsertBudgetLineRequest $request, Budget $budget, BudgetLine $line)
    {
        $this->authorize('update', $budget);

        if ($line->budget_id !== $budget->id) {
            abort(404);
        }

        if ($budget->status !== 'DRAFT') {
            return back()->with('error', 'Only DRAFT budgets can be modified.');
        }

        $validated = $request->validated();

        $line->update([
            'cost_center' => $validated['cost_center'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'gl_account' => $validated['gl_account'],
            'category' => $validated['category'],
            'allocated_amount' => $validated['allocated_amount'],
        ]);

        return back()->with('success', 'Budget line updated.');
    }

    public function destroy(Budget $budget, BudgetLine $line)
    {
        $this->authorize('update', $budget);

        if ($line->budget_id !== $budget->id) {
            abort(404);
        }

        if ($budget->status !== 'DRAFT') {
            return back()->with('error', 'Only DRAFT budgets can be modified.');
        }

        $line->delete();

        return back()->with('success', 'Budget line deleted.');
    }
}
