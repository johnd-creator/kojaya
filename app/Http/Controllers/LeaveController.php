<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Http\Requests\UpdateLeaveStatusRequest;
use App\Models\Leave;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    /**
     * Display the ESS (Self Service) Leave Page for the authenticated user.
     */
    public function selfService(Request $request): Response
    {
        $user = $request->user();
        if (! $user->employee) {
            abort(403, 'You are not linked to an employee profile.');
        }

        $employeeId = $user->employee->id;

        $leaves = Leave::with(['type', 'approver:id,name'])
            ->where('employee_id', $employeeId)
            ->orderByDesc('created_at')
            ->paginate(10);

        return Inertia::render('Leave/SelfService', [
            'leaves' => $leaves,
            'leaveTypes' => LeaveType::orderBy('name')->get(),
            'employee' => $user->employee,
        ]);
    }

    /**
     * Store a new leave request (ESS).
     */
    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->employee) {
            return back()->with('error', 'You are not linked to an employee profile.');
        }

        $validated = $request->validated();

        $leaveType = LeaveType::find($validated['leave_type_id']);

        if ($leaveType->requires_attachment && ! $request->hasFile('attachment')) {
            return back()->withErrors(['attachment' => 'Attachment is required for this leave type.'])->withInput();
        }

        // Calculate working days (skip weekends)
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $totalDays = 0;

        $currentDate = $start->copy();
        while ($currentDate <= $end) {
            if (! $currentDate->isWeekend()) {
                $totalDays++;
            }
            $currentDate->addDay();
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leaves/attachments', 'public');
        }

        Leave::create([
            'employee_id' => $user->employee->id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'attachment_path' => $attachmentPath,
            'status' => 'Pending',
        ]);

        return back()->with('success', 'Leave request submitted successfully.');
    }

    /**
     * Admin Index: view all leaves for approval.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Leave::class);

        $query = Leave::with(['employee:id,first_name,last_name,employee_code', 'type', 'approver:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $leaves = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Leave/AdminIndex', [
            'leaves' => $leaves,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Update the leave status (Approve/Reject).
     */
    public function updateStatus(UpdateLeaveStatusRequest $request, Leave $leave): RedirectResponse
    {
        $validated = $request->validated();

        $leave->update([
            'status' => $validated['status'],
            'approver_id' => $request->user()->id,
        ]);

        return redirect()->route('leaves.index')->with('success', 'Leave request '.strtolower($validated['status']).' successfully.');
    }
}
