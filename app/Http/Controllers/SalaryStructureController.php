<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertSalaryStructureRequest;
use App\Models\JobGrade;
use App\Models\Organization;
use App\Models\SalaryComponentType;
use App\Models\SalaryStructure;
use App\Models\SalaryStructureItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalaryStructureController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizePermission('manage_salary_structures');

        $structures = SalaryStructure::query()
            ->with(['jobGrade', 'organization', 'items.componentType'])
            ->when($request->filled('employee_type'), fn ($q) => $q->where('employee_type', $request->input('employee_type')))
            ->when($request->filled('job_grade_id'), fn ($q) => $q->where('job_grade_id', $request->input('job_grade_id')))
            ->when($request->filled('organization_id'), fn ($q) => $q->where('organization_id', $request->input('organization_id')))
            ->orderBy('employee_type')
            ->orderBy('job_grade_id')
            ->orderByDesc('effective_from')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SalaryStructure/Index', [
            'structures' => $structures,
            'jobGrades' => JobGrade::orderBy('level')->get(),
            'organizations' => Organization::orderBy('name')->get(),
            'componentTypes' => SalaryComponentType::where('is_active', true)->orderBy('sort_order')->get(),
            'filters' => $request->only(['employee_type', 'job_grade_id', 'organization_id']),
        ]);
    }

    public function store(UpsertSalaryStructureRequest $request)
    {
        $this->authorizePermission('manage_salary_structures');

        $validated = $request->validated();

        $structure = SalaryStructure::create([
            'employee_type' => $validated['employee_type'],
            'job_grade_id' => $validated['job_grade_id'],
            'organization_id' => $validated['organization_id'],
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

    public function update(UpsertSalaryStructureRequest $request, SalaryStructure $salaryStructure)
    {
        $this->authorizePermission('manage_salary_structures');

        $validated = $request->validated();

        $salaryStructure->update([
            'employee_type' => $validated['employee_type'],
            'job_grade_id' => $validated['job_grade_id'],
            'organization_id' => $validated['organization_id'],
            'min_tenure_months' => $validated['min_tenure_months'] ?? 0,
            'max_tenure_months' => $validated['max_tenure_months'] ?? null,
            'effective_from' => $validated['effective_from'],
            'effective_until' => $validated['effective_until'] ?? null,
        ]);

        // Replace all items
        $salaryStructure->items()->delete();
        foreach ($validated['items'] as $item) {
            SalaryStructureItem::create([
                'salary_structure_id' => $salaryStructure->id,
                'salary_component_type_id' => $item['component_type_id'],
                'amount' => $item['amount'],
            ]);
        }

        return redirect()->route('salary-structures.index')->with('success', 'Salary structure updated.');
    }

    public function destroy(SalaryStructure $salaryStructure)
    {
        $this->authorizePermission('manage_salary_structures');

        $salaryStructure->delete();

        return redirect()->route('salary-structures.index')->with('success', 'Salary structure deleted.');
    }
}
