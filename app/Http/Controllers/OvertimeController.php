<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectOvertimeRequest;
use App\Http\Requests\StoreOvertimeRequest;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\OvertimeRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OvertimeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', OvertimeRequest::class);

        $user = Auth::user();
        $query = OvertimeRequest::query()
            ->with(['employee', 'overtimeRule', 'approvedBy']);

        // Employee Scope: Only see own requests unless they have admin roles
        if ($user->hasRole('Employee') && ! $user->hasAnyRole(['System Admin', 'Admin Pusat', 'HR Pusat', 'HR Unit', 'Admin Unit'])) {
            $employee = $user->employee;
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                // If user has Employee role but no employee record linked, show nothing
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->organization_id && ! $user->hasRole('System Admin') && ! $user->hasRole('Admin Pusat')) {
            // Unit Admins/HR see their organization's requests
            $query->where('organization_id', $user->organization_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        $overtimeRequests = $query->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Overtime/Index', [
            'overtimeRequests' => $overtimeRequests,
            'filters' => $request->only(['status', 'organization_id', 'date_from', 'date_to']),
        ]);
    }

    public function create(): Response
    {
        $rules = OvertimeRule::where('is_active', true)->get();
        $employees = Employee::where('status', 'ACTIVE')->get();

        return Inertia::render('Overtime/Create', [
            'rules' => $rules,
            'employees' => $employees,
        ]);
    }

    public function store(StoreOvertimeRequest $request)
    {
        $validated = $request->validated();

        $employee = Employee::find($validated['employee_id']);
        $rule = OvertimeRule::find($validated['overtime_rule_id']);

        $startTime = \Carbon\Carbon::parse($validated['start_time']);
        $endTime = \Carbon\Carbon::parse($validated['end_time']);
        $totalHours = round($startTime->diffInMinutes($endTime) / 60, 2);

        if ($rule->min_hours > 0 && $totalHours < $rule->min_hours) {
            return back()->withErrors(['total_hours' => "Minimum overtime hours is {$rule->min_hours}"]);
        }

        if ($rule->max_hours_daily && $totalHours > $rule->max_hours_daily) {
            return back()->withErrors(['total_hours' => "Maximum daily overtime is {$rule->max_hours_daily} hours"]);
        }

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('overtime-evidence', 'public');
        }

        OvertimeRequest::create([
            'id' => Str::uuid()->toString(),
            'employee_id' => $validated['employee_id'],
            'organization_id' => $employee->organization_id,
            'overtime_rule_id' => $validated['overtime_rule_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'total_hours' => $totalHours,
            'reason' => $validated['reason'] ?? null,
            'evidence_path' => $evidencePath,
            'status' => $rule->requires_approval ? 'PENDING' : 'APPROVED',
        ]);

        return redirect()->route('overtime.index')
            ->with('success', 'Overtime request created successfully.');
    }

    public function approve(Request $request, OvertimeRequest $overtimeRequest)
    {
        $this->authorize('approve', $overtimeRequest);

        if ($overtimeRequest->status !== 'PENDING') {
            return back()->withErrors(['status' => 'Only pending requests can be approved.']);
        }

        $overtimeRequest->update([
            'status' => 'APPROVED',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Overtime request approved.');
    }

    public function reject(RejectOvertimeRequest $request, OvertimeRequest $overtimeRequest)
    {
        $validated = $request->validated();

        if ($overtimeRequest->status !== 'PENDING') {
            return back()->withErrors(['status' => 'Only pending requests can be rejected.']);
        }

        $overtimeRequest->update([
            'status' => 'REJECTED',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', 'Overtime request rejected.');
    }

    public function destroy(OvertimeRequest $overtimeRequest)
    {
        $this->authorize('delete', $overtimeRequest);

        if ($overtimeRequest->status === 'APPROVED') {
            return back()->withErrors(['status' => 'Cannot delete approved overtime requests.']);
        }

        $overtimeRequest->delete();

        return back()->with('success', 'Overtime request deleted.');
    }
}
