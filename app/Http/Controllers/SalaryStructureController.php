<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertSalaryStructureRequest;
use App\Models\JobGrade;
use App\Models\Organization;
use App\Models\SalaryComponentType;
use App\Models\SalaryStructure;
use App\Models\SalaryStructureItem;
use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalaryStructureController extends Controller
{
    public function index(Request $request, OrganizationScopeService $scopeService): Response
    {
        $this->authorize('viewAny', SalaryStructure::class);

        $query = SalaryStructure::query()
            ->with(['jobGrade', 'organization', 'items.componentType']);

        $query = $scopeService->scopeVisibleTo($query, $request->user(), 'view_payroll_all');

        if ($request->filled('employee_type')) {
            $query->where('employee_type', $request->input('employee_type'));
        }

        if ($request->filled('job_grade_id')) {
            $query->where('job_grade_id', $request->input('job_grade_id'));
        }

        if ($request->filled('organization_id')) {
            $targetOrgId = $scopeService->resolveTargetOrganization($request->user(), $request->input('organization_id'), 'view_payroll_all');
            $query->where('organization_id', $targetOrgId);
        }

        $structures = $query->orderBy('employee_type')
            ->orderBy('job_grade_id')
            ->orderByDesc('effective_from')
            ->paginate(20)
            ->withQueryString();

        $organizations = $request->user()->can('view_payroll_all')
            ? Organization::orderBy('name')->get()
            : Organization::where('id', $request->user()->organization_id)->orderBy('name')->get();

        return Inertia::render('SalaryStructure/Index', [
            'structures' => $structures,
            'jobGrades' => JobGrade::orderBy('level')->get(),
            'organizations' => $organizations,
            'componentTypes' => SalaryComponentType::where('is_active', true)->orderBy('sort_order')->get(),
            'filters' => $request->only(['employee_type', 'job_grade_id', 'organization_id']),
        ]);
    }

    public function store(UpsertSalaryStructureRequest $request, OrganizationScopeService $scopeService)
    {
        $this->authorize('create', SalaryStructure::class);

        $validated = $request->validated();
        $targetOrgId = $scopeService->resolveTargetOrganization($request->user(), $validated['organization_id'] ?? null, 'view_payroll_all');

        $structure = SalaryStructure::create([
            'employee_type' => $validated['employee_type'],
            'job_grade_id' => $validated['job_grade_id'],
            'organization_id' => $targetOrgId,
            'min_tenure_months' => $validated['min_tenure_months'] ?? 0,
            'max_tenure_months' => $validated['max_tenure_months'] ?? null,
            'effective_from' => $validated['effective_from'],
            'effective_until' => $validated['effective_until'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            SalaryStructureItem::create([
                'salary_structure_id' => $structure->id,
                'salary_component_type_id' => $item['component_type_id'],
                'amount' => $item['amount'],
            ]);
        }

        return redirect()->route('salary-structures.index')->with('success', 'Salary structure created.');
    }

    public function update(UpsertSalaryStructureRequest $request, string $salaryStructure, OrganizationScopeService $scopeService)
    {
        /** @var SalaryStructure $structureModel */
        $structureModel = $scopeService->resolveVisible(SalaryStructure::class, $request->user(), $salaryStructure, 'view_payroll_all');

        $this->authorize('update', $structureModel);

        $validated = $request->validated();
        $targetOrgId = $scopeService->resolveTargetOrganization($request->user(), $validated['organization_id'] ?? null, 'view_payroll_all');

        $authoritativeOrgId = (string) $scopeService->organizationIdForModel($structureModel);
        if ($authoritativeOrgId !== $targetOrgId) {
            throw new AuthorizationException('Target organization does not match salary structure organization.');
        }

        $structureModel->update([
            'employee_type' => $validated['employee_type'],
            'job_grade_id' => $validated['job_grade_id'],
            'organization_id' => $targetOrgId,
            'min_tenure_months' => $validated['min_tenure_months'] ?? 0,
            'max_tenure_months' => $validated['max_tenure_months'] ?? null,
            'effective_from' => $validated['effective_from'],
            'effective_until' => $validated['effective_until'] ?? null,
        ]);

        // Replace all items
        $structureModel->items()->delete();
        foreach ($validated['items'] as $item) {
            SalaryStructureItem::create([
                'salary_structure_id' => $structureModel->id,
                'salary_component_type_id' => $item['component_type_id'],
                'amount' => $item['amount'],
            ]);
        }

        return redirect()->route('salary-structures.index')->with('success', 'Salary structure updated.');
    }

    public function destroy(Request $request, string $salaryStructure, OrganizationScopeService $scopeService)
    {
        /** @var SalaryStructure $structureModel */
        $structureModel = $scopeService->resolveVisible(SalaryStructure::class, $request->user(), $salaryStructure, 'view_payroll_all');

        $this->authorize('delete', $structureModel);

        $structureModel->delete();

        return redirect()->route('salary-structures.index')->with('success', 'Salary structure deleted.');
    }
}
