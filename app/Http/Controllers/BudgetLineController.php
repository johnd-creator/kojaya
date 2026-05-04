<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertBudgetLineRequest;
use App\Models\Budget;
use App\Models\BudgetLine;
use Illuminate\Support\Facades\Auth;

class BudgetLineController extends Controller
{
    protected function allAccessRoles(): array
    {
        return [
            'System Admin',
            'Admin Pusat',
            'Finance Pusat',
            'HR Pusat',
        ];
    }

    public function store(UpsertBudgetLineRequest $request, Budget $budget)
    {
        $this->authorizeBudgetAccess($budget);

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
        $this->authorizeBudgetAccess($budget);

        if ($budget->status !== 'DRAFT') {
            return back()->with('error', 'Only DRAFT budgets can be modified.');
        }

        if ($line->budget_id !== $budget->id) {
            abort(404);
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
        $this->authorizeBudgetAccess($budget);

        if ($budget->status !== 'DRAFT') {
            return back()->with('error', 'Only DRAFT budgets can be modified.');
        }

        if ($line->budget_id !== $budget->id) {
            abort(404);
        }

        $line->delete();

        return back()->with('success', 'Budget line deleted.');
    }

    protected function authorizeBudgetAccess(Budget $budget): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        if ($user->hasAnyRole($this->allAccessRoles())) {
            return;
        }

        if ($budget->organization_id !== $user->organization_id) {
            abort(403);
        }
    }
}
