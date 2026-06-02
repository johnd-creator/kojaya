<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Organization;
use App\Services\Hr\EmployeeEssProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Employee::query()
            ->forUser()
            ->with(['organization', 'user', 'department', 'position', 'jobGrade', 'workShift']);

        // Filtering
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Proper use of Indexes: order by status, then hire_date (or created_at)
        $employees = $query->orderBy('status')->orderByDesc('hire_date')->paginate(15)->withQueryString();

        // Fetch orgs for the filter dropdown
        $organizations = Organization::orderBy('name')->get();

        return Inertia::render('Employee/Index', [
            'employees' => $employees,
            'organizations' => $organizations,
            'departments' => \App\Models\Department::orderBy('name')->get(),
            'positions' => \App\Models\Position::orderBy('name')->get(),
            'jobGrades' => \App\Models\JobGrade::orderBy('level')->get(),
            'workShifts' => \App\Models\WorkShift::orderBy('name')->get(),
            'filters' => $request->only(['search', 'organization_id', 'status']),
            'stats' => [
                'total_active' => Employee::forUser()->where('status', 'ACTIVE')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Employee/Create', [
            'organizations' => Organization::orderBy('name')->get(),
            'departments' => \App\Models\Department::orderBy('name')->get(),
            'positions' => \App\Models\Position::orderBy('name')->get(),
            'jobGrades' => \App\Models\JobGrade::orderBy('level')->get(),
            'workShifts' => \App\Models\WorkShift::orderBy('name')->get(),
        ]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $validated = $request->validated();

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee): Response
    {
        $employee->load(['families', 'families.relatedEmployee']);

        // Find spouse to check for shared children
        $spouseRecord = $employee->families->whereIn('relationship', ['Husband', 'Wife'])
            ->where('is_working_here', true)
            ->whereNotNull('related_employee_id')
            ->first();

        $relatedEmployeeId = $spouseRecord ? $spouseRecord->related_employee_id : null;

        if (! $relatedEmployeeId) {
            $reverseSpouseRecord = \App\Models\EmployeeFamily::where('related_employee_id', $employee->id)
                ->whereIn('relationship', ['Husband', 'Wife'])
                ->first();
            $relatedEmployeeId = $reverseSpouseRecord ? $reverseSpouseRecord->employee_id : null;
        }

        if ($relatedEmployeeId) {
            $sharedChildren = \App\Models\EmployeeFamily::where('employee_id', $relatedEmployeeId)
                ->where('relationship', 'Child')
                ->with('relatedEmployee')
                ->get()
                ->map(function ($child) {
                    $child->is_shared = true;

                    return $child;
                });

            // Merge shared children into the employee's families collection
            $employee->setRelation('families', $employee->families->merge($sharedChildren));
        }

        return Inertia::render('Employee/Edit', [
            'employee' => $employee,
            'organizations' => Organization::orderBy('name')->get(),
            'departments' => \App\Models\Department::orderBy('name')->get(),
            'positions' => \App\Models\Position::orderBy('name')->get(),
            'jobGrades' => \App\Models\JobGrade::orderBy('level')->get(),
            'workShifts' => \App\Models\WorkShift::orderBy('name')->get(),
            'allEmployees' => Employee::select('id', 'first_name', 'last_name', 'employee_code')->orderBy('first_name')->get(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $validated = $request->validated();

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $employee->update(['status' => 'TERMINATED']);

        return redirect()->route('employees.index')->with('success', 'Employee terminated successfully.');
    }

    public function enableEssAccess(Request $request, Employee $employee, EmployeeEssProvisioningService $provisioning)
    {
        $this->authorize('manageEssAccess', $employee);

        try {
            $result = $provisioning->enable($employee);
        } catch (ValidationException $e) {
            return back()->with('error', $e->errors()[array_key_first($e->errors())][0] ?? 'Gagal mengaktifkan ESS.');
        }

        $message = 'ESS access enabled. Karyawan harus mengatur password baru lewat tautan reset password.';

        if ($result['reset_link']) {
            // Flash to session so the UI can render a "Salin tautan reset password" toast/dialog
            // exactly once. The link is single-use and tied to the user's email.
            session()->flash('ess_password_reset_link', $result['reset_link']);
        }

        return back()->with('success', $message);
    }

    public function revokeEssAccess(Request $request, Employee $employee, EmployeeEssProvisioningService $provisioning)
    {
        $this->authorize('manageEssAccess', $employee);

        try {
            $provisioning->disable($employee);
        } catch (ValidationException $e) {
            return back()->with('info', $e->errors()[array_key_first($e->errors())][0] ?? 'Tidak ada akun ESS yang ditautkan.');
        }

        return back()->with('success', 'ESS access has been revoked.');
    }
}
