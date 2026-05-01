<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255|unique:employees,email',
            'employee_code' => 'required|string|max:50|unique:employees,employee_code',
            'organization_id' => 'required|uuid|exists:organizations,id',
            'gender' => 'required|in:M,F',
            'birth_date' => 'nullable|date',
            'hire_date' => 'required|date',
            'status' => 'required|string',
            'employee_type' => 'required|in:Organic,TKWT',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'job_grade_id' => 'nullable|exists:job_grades,id',
            'work_shift_id' => 'nullable|exists:work_shifts,id',
            'shift_group' => 'nullable|in:A,B,C,D',
        ]);

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

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255|unique:employees,email,'.$employee->id,
            'employee_code' => 'required|string|max:50|unique:employees,employee_code,'.$employee->id,
            'organization_id' => 'required|uuid|exists:organizations,id',
            'gender' => 'required|in:M,F',
            'birth_date' => 'nullable|date',
            'hire_date' => 'required|date',
            'status' => 'required|string',
            'employee_type' => 'required|in:Organic,TKWT',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'job_grade_id' => 'nullable|exists:job_grades,id',
            'work_shift_id' => 'nullable|exists:work_shifts,id',
            'shift_group' => 'nullable|in:A,B,C,D',
        ]);

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $employee->update(['status' => 'TERMINATED']);

        return redirect()->route('employees.index')->with('success', 'Employee terminated successfully.');
    }

    public function enableEssAccess(Employee $employee)
    {
        if ($employee->user_id) {
            return back()->with('info', 'This employee already has an ESS account.');
        }

        if (! $employee->email) {
            return back()->with('error', 'Employee must have an email address before enabling ESS access.');
        }

        if (User::where('email', $employee->email)->exists()) {
            return back()->with('error', 'A user with this email already exists. Please link manually.');
        }

        $user = User::create([
            'name' => trim($employee->first_name.' '.$employee->last_name),
            'email' => $employee->email,
            'password' => Hash::make($employee->employee_code),
            'organization_id' => $employee->organization_id,
        ]);

        $user->assignRole('Employee');
        $employee->update(['user_id' => $user->id]);

        return back()->with('success', 'ESS access enabled. Default password is the Employee Code.');
    }

    public function revokeEssAccess(Employee $employee)
    {
        if (! $employee->user_id) {
            return back()->with('info', 'No ESS account is linked to this employee.');
        }

        $employee->update(['user_id' => null]);

        return back()->with('success', 'ESS access has been revoked.');
    }
}
