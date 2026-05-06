<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeFamilyRequest;
use App\Http\Requests\UpdateEmployeeFamilyRequest;
use App\Models\Employee;
use App\Models\EmployeeFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class EmployeeFamilyController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeFamilyRequest $request): RedirectResponse
    {
        $this->authorizePermission('manage_employee_family');

        $validated = $request->validated();

        $this->enforceFamilyConstraints($validated['employee_id'], $validated);

        EmployeeFamily::create($validated);

        return back()->with('success', 'Family member added successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeFamilyRequest $request, EmployeeFamily $employeeFamily): RedirectResponse
    {
        $this->authorizePermission('manage_employee_family');

        $validated = $request->validated();

        $this->enforceFamilyConstraints($employeeFamily->employee_id, $validated, $employeeFamily->id);

        $employeeFamily->update($validated);

        return back()->with('success', 'Family member updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeFamily $employeeFamily): RedirectResponse
    {
        $this->authorizePermission('manage_employee_family');

        $employeeFamily->delete();

        return back()->with('success', 'Family member deleted successfully.');
    }

    /**
     * Enforce business logic constraints (Max 1 Spouse, Max 3 Children, Shared limit).
     */
    protected function enforceFamilyConstraints(int $employeeId, array $data, ?int $ignoreId = null): void
    {
        $relationship = $data['relationship'];

        if (in_array($relationship, ['Husband', 'Wife'])) {
            $spouseCount = EmployeeFamily::where('employee_id', $employeeId)
                ->whereIn('relationship', ['Husband', 'Wife'])
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->count();

            if ($spouseCount >= 1) {
                throw ValidationException::withMessages([
                    'relationship' => 'An employee can only have a maximum of 1 registered spouse.',
                ]);
            }
        }

        if ($relationship === 'Child') {
            // Find spouse to check shared quota
            // 1. Current employee has a spouse registered who works here
            $spouseRecord = EmployeeFamily::where('employee_id', $employeeId)
                ->whereIn('relationship', ['Husband', 'Wife'])
                ->where('is_working_here', true)
                ->whereNotNull('related_employee_id')
                ->first();

            $relatedEmployeeId = $spouseRecord ? $spouseRecord->related_employee_id : null;

            // 2. Or current employee IS the related_employee_id of someone else's spouse record
            if (! $relatedEmployeeId) {
                $reverseSpouseRecord = EmployeeFamily::where('related_employee_id', $employeeId)
                    ->whereIn('relationship', ['Husband', 'Wife'])
                    ->first();
                $relatedEmployeeId = $reverseSpouseRecord ? $reverseSpouseRecord->employee_id : null;
            }

            $employeeIdsToCheck = array_filter([$employeeId, $relatedEmployeeId]);

            $childCount = EmployeeFamily::whereIn('employee_id', $employeeIdsToCheck)
                ->where('relationship', 'Child')
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->count();

            if ($childCount >= 3) {
                throw ValidationException::withMessages([
                    'shared_quota' => 'The combined maximum number of children allowed for you and your spouse is 3.',
                ]);
            }
        }
    }
}
