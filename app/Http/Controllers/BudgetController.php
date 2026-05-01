<?php

namespace App\Http\Controllers;

use App\Imports\BudgetLinesImport;
use App\Models\Budget;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class BudgetController extends Controller
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

    public function index(Request $request): Response
    {
        $user = $request->user();

        $budgets = Budget::query()
            ->with('organization')
            ->withCount('lines')
            ->when(! $user->hasAnyRole($this->allAccessRoles()), function ($q) use ($user) {
                $q->where('organization_id', $user->organization_id);
            })
            ->when($request->filled('organization_id') && $user->hasAnyRole($this->allAccessRoles()), function ($q) use ($request) {
                $q->where('organization_id', $request->input('organization_id'));
            })
            ->when($request->filled('year'), function ($q) use ($request) {
                $q->where('year', $request->input('year'));
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->orderByDesc('year')
            ->orderBy('period')
            ->paginate(20)
            ->withQueryString();

        $organizations = $user->hasAnyRole($this->allAccessRoles())
            ? Organization::orderBy('name')->get(['id', 'code', 'name'])
            : Organization::whereKey($user->organization_id)->get(['id', 'code', 'name']);

        return Inertia::render('Budget/Index', [
            'budgets' => $budgets,
            'organizations' => $organizations,
            'filters' => $request->only(['organization_id', 'year', 'status']),
            'can' => [
                'selectOrganization' => $user->hasAnyRole($this->allAccessRoles()),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'year' => ['required', 'digits:4'],
            'period' => ['required', Rule::in(['ANNUAL', 'Q1', 'Q2', 'Q3', 'Q4'])],
            'status' => ['nullable', Rule::in(['DRAFT', 'ACTIVE', 'CLOSED'])],
        ]);

        $organizationId = $user->hasAnyRole($this->allAccessRoles())
            ? ($validated['organization_id'] ?? $user->organization_id)
            : $user->organization_id;

        $exists = Budget::query()
            ->where('organization_id', $organizationId)
            ->where('year', $validated['year'])
            ->where('period', $validated['period'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'year' => 'Budget for this year and period already exists for the selected organization.',
            ]);
        }

        $budget = Budget::create([
            'organization_id' => $organizationId,
            'year' => $validated['year'],
            'period' => $validated['period'],
            'status' => $validated['status'] ?? 'DRAFT',
        ]);

        return redirect()->route('budgets.show', $budget)->with('success', 'RKAP created.');
    }

    public function show(Budget $budget): Response
    {
        $this->authorizeAccess($budget);

        $budget->load([
            'organization:id,code,name',
            'lines' => fn ($q) => $q->with('project:id,project_code,name')->orderBy('gl_account'),
        ]);

        return Inertia::render('Budget/Show', [
            'budget' => $budget,
            'projects' => Project::query()
                ->where('organization_id', $budget->organization_id)
                ->orderBy('project_code')
                ->get(['id', 'project_code', 'name']),
            'can' => [
                'edit' => Auth::user()?->hasAnyRole($this->allAccessRoles()) || $budget->organization_id === Auth::user()?->organization_id,
                'editLines' => $budget->status === 'DRAFT',
            ],
        ]);
    }

    public function update(Request $request, Budget $budget)
    {
        $this->authorizeAccess($budget);

        if ($budget->status !== 'DRAFT') {
            return back()->with('error', 'Only DRAFT budgets can be edited.');
        }

        $user = $request->user();

        $validated = $request->validate([
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'year' => ['required', 'digits:4'],
            'period' => ['required', Rule::in(['ANNUAL', 'Q1', 'Q2', 'Q3', 'Q4'])],
            'status' => ['required', Rule::in(['DRAFT', 'ACTIVE', 'CLOSED'])],
        ]);

        $organizationId = $user->hasAnyRole($this->allAccessRoles())
            ? ($validated['organization_id'] ?? $budget->organization_id)
            : $budget->organization_id;

        $exists = Budget::query()
            ->whereKeyNot($budget->id)
            ->where('organization_id', $organizationId)
            ->where('year', $validated['year'])
            ->where('period', $validated['period'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'year' => 'Budget for this year and period already exists for the selected organization.',
            ]);
        }

        $budget->update([
            'organization_id' => $organizationId,
            'year' => $validated['year'],
            'period' => $validated['period'],
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'RKAP updated.');
    }

    public function destroy(Budget $budget)
    {
        $this->authorizeAccess($budget);

        if ($budget->status !== 'DRAFT') {
            return back()->with('error', 'Only DRAFT budgets can be deleted.');
        }

        $budget->delete();

        return redirect()->route('budgets.index')->with('success', 'RKAP deleted.');
    }

    public function import(Request $request, Budget $budget)
    {
        $this->authorizeAccess($budget);

        if ($budget->status !== 'DRAFT') {
            return back()->with('error', 'Only DRAFT budgets can be modified.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv'],
        ]);

        try {
            Excel::import(new BudgetLinesImport($budget), $request->file('file'));

            return back()->with('success', 'Budget lines imported successfully.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = 'Row '.$failure->row().': '.implode(', ', $failure->errors());
            }

            return back()->withErrors(['file' => $messages]);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    protected function authorizeAccess(Budget $budget): void
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
