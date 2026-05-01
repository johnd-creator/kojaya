<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_type' => 'required|in:TKWT,Organic',
            'job_grade_id' => 'required|exists:job_grades,id',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
            'min_tenure_months' => 'integer|min:0',
            'max_tenure_months' => 'nullable|integer|min:0|gte:min_tenure_months',
            'effective_from' => 'required|date',
            'effective_until' => 'nullable|date|after:effective_from',
            'items' => 'required|array|min:1',
            'items.*.component_type_id' => 'required|exists:salary_component_types,id',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

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

    public function update(Request $request, SalaryStructure $salaryStructure)
    {
        $validated = $request->validate([
            'employee_type' => 'required|in:TKWT,Organic',
            'job_grade_id' => 'required|exists:job_grades,id',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
            'min_tenure_months' => 'integer|min:0',
            'max_tenure_months' => 'nullable|integer|min:0',
            'effective_from' => 'required|date',
            'effective_until' => 'nullable|date|after:effective_from',
            'items' => 'required|array|min:1',
            'items.*.component_type_id' => 'required|exists:salary_component_types,id',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

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
        $salaryStructure->delete();

        return redirect()->route('salary-structures.index')->with('success', 'Salary structure deleted.');
    }
}
