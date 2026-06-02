<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EssLeaveRequest;
use App\Http\Requests\Api\EssOvertimeRequest;
use App\Http\Requests\Api\EssReimbursementRequest;
use App\Http\Requests\Api\StoreAttendanceCorrectionRequest;
use App\Http\Requests\AttendanceApiLocationRequest;
use App\Http\Requests\UpdateEssProfileRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\OvertimeRequest;
use App\Models\OvertimeRule;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\ReimbursementItem;
use App\Models\ShiftRoster;
use App\Services\Hr\AttendanceCorrectionService;
use App\Services\Hr\ThrEntitlementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EssController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);
        $latestPayroll = Payroll::query()
            ->where('employee_id', $employee->id)
            ->latest('period')
            ->first();
        $todayAttendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', today())
            ->first();

        return response()->json([
            'data' => [
                'employee' => $employee->load(['department', 'position', 'organization']),
                'today_attendance' => $todayAttendance,
                'today_shift' => $employee->todayRoster(),
                'stats' => [
                    'attendance_this_month' => Attendance::query()
                        ->where('employee_id', $employee->id)
                        ->whereMonth('date', now()->month)
                        ->count(),
                    'pending_leaves' => Leave::query()
                        ->where('employee_id', $employee->id)
                        ->where('status', 'Pending')
                        ->count(),
                    'approved_leaves_this_year' => Leave::query()
                        ->where('employee_id', $employee->id)
                        ->where('status', 'Approved')
                        ->whereYear('start_date', now()->year)
                        ->count(),
                    'latest_payroll_period' => $latestPayroll?->period,
                    'latest_net_salary' => $latestPayroll?->net_salary,
                    'expiring_certificates' => $employee->certificates()->expiring(60)->count(),
                    'due_medical_checkups' => $employee->medicalCheckups()->due(30)->count(),
                    'pending_overtime' => OvertimeRequest::query()
                        ->where('employee_id', $employee->id)
                        ->where('status', 'PENDING')
                        ->count(),
                    'pending_reimbursements' => Reimbursement::query()
                        ->where('user_id', $request->user()->id)
                        ->where('status', 'SUBMITTED')
                        ->count(),
                ],
            ],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => $request->user(),
                'employee' => $this->employeeOrAbort($request)->load(['department', 'position', 'organization']),
            ],
        ]);
    }

    public function updateProfile(UpdateEssProfileRequest $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $employee->update([
            'email' => $validated['email'],
            'birth_date' => $validated['birth_date'] ?? $employee->birth_date,
            'gender' => $validated['gender'] ?? $employee->gender,
        ]);

        return response()->json([
            'data' => [
                'user' => $user->refresh(),
                'employee' => $employee->refresh(),
            ],
        ]);
    }

    public function todayAttendance(Request $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);

        return response()->json([
            'data' => Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', today())
                ->first(),
        ]);
    }

    public function attendanceHistory(Request $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);

        return response()->json(Attendance::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->paginate($request->integer('per_page', 15)));
    }

    public function checkIn(AttendanceApiLocationRequest $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);
        $validated = $request->validated();
        $org = $employee->organization;

        $this->assertInsideGeofence($org, $validated);

        $existing = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', today())
            ->whereNotNull('clock_in')
            ->first();

        if ($existing) {
            return response()->json(['ok' => false, 'error' => 'Already checked in'], 409);
        }

        $attendance = Attendance::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'date' => today()->toDateString()],
            [
                'organization_id' => $employee->organization_id,
                'clock_in' => now()->format('H:i:s'),
                'clock_in_latitude' => $validated['latitude'],
                'clock_in_longitude' => $validated['longitude'],
                'clock_in_accuracy' => $validated['accuracy'] ?? null,
                'clock_in_device_id' => $validated['device_id'] ?? null,
                'status' => 'PRESENT',
                'mobile_audit' => [
                    'check_in' => [
                        'at' => now()->toIso8601String(),
                        'device_id' => $validated['device_id'] ?? null,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ],
                ],
            ]
        );

        return response()->json(['ok' => true, 'data' => $attendance]);
    }

    public function checkOut(AttendanceApiLocationRequest $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);
        $validated = $request->validated();
        $org = $employee->organization;

        $this->assertInsideGeofence($org, $validated);

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', today())
            ->first();

        if (! $attendance || ! $attendance->clock_in) {
            return response()->json(['ok' => false, 'error' => 'Not checked in'], 409);
        }

        if ($attendance->clock_out) {
            return response()->json(['ok' => false, 'error' => 'Already checked out'], 409);
        }

        $attendance->update([
            'clock_out' => now()->format('H:i:s'),
            'clock_out_latitude' => $validated['latitude'],
            'clock_out_longitude' => $validated['longitude'],
            'clock_out_accuracy' => $validated['accuracy'] ?? null,
            'clock_out_device_id' => $validated['device_id'] ?? null,
            'mobile_audit' => array_merge($attendance->mobile_audit ?? [], [
                'check_out' => [
                    'at' => now()->toIso8601String(),
                    'device_id' => $validated['device_id'] ?? null,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]),
        ]);

        return response()->json(['ok' => true, 'data' => $attendance->refresh()]);
    }

    public function shiftRoster(Request $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);

        abort_unless($employee->shift_group, 404, 'Karyawan belum terhubung ke grup shift.');

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : today();
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : today()->copy()->addDays(14);

        return response()->json(ShiftRoster::query()
            ->with('workShift')
            ->where('shift_group', $employee->shift_group)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get());
    }

    public function thrEntitlement(Request $request, ThrEntitlementService $service): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);
        $year = $request->integer('year', now()->year);
        $entitlement = $service->calculateForEmployee($employee, $year);

        return response()->json([
            'data' => [
                'id' => $entitlement->id,
                'year' => $entitlement->year,
                'months_worked' => $entitlement->months_worked,
                'base_salary' => (float) $entitlement->base_salary,
                'amount' => (float) $entitlement->amount,
                'status' => $entitlement->status,
                'calculated_at' => $entitlement->calculated_at?->toIso8601String(),
                'calculation_breakdown' => $entitlement->calculation_breakdown,
            ],
        ]);
    }

    public function requestAttendanceCorrection(
        StoreAttendanceCorrectionRequest $request,
        AttendanceCorrectionService $service,
    ): JsonResponse {
        $employee = $this->employeeOrAbort($request);
        $correction = $service->request($employee, $request->user(), $request->validated());

        return response()->json(['data' => $correction], 201);
    }

    public function approveAttendanceCorrection(
        Request $request,
        AttendanceCorrection $attendanceCorrection,
        AttendanceCorrectionService $service,
    ): JsonResponse {
        $correction = $service->approve(
            $attendanceCorrection,
            $request->user(),
            $request->string('review_note')->toString() ?: null,
        );

        return response()->json(['data' => $correction]);
    }

    public function leaves(Request $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);

        return response()->json([
            'data' => Leave::query()
                ->with(['type', 'approver:id,name'])
                ->where('employee_id', $employee->id)
                ->latest()
                ->paginate($request->integer('per_page', 15)),
            'balance' => $this->leaveBalance($employee),
        ]);
    }

    public function storeLeave(EssLeaveRequest $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);
        $validated = $request->validated();
        $leaveType = LeaveType::query()->findOrFail($validated['leave_type_id']);

        if ($leaveType->requires_attachment && ! $request->hasFile('attachment')) {
            return response()->json([
                'message' => 'Attachment is required for this leave type.',
                'errors' => ['attachment' => ['Attachment is required for this leave type.']],
            ], 422);
        }

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('leaves/attachments', 'public')
            : null;

        $leave = Leave::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $this->workingDays($validated['start_date'], $validated['end_date']),
            'reason' => $validated['reason'],
            'attachment_path' => $attachmentPath,
            'status' => 'Pending',
        ]);

        return response()->json(['data' => $leave->load('type')], 201);
    }

    public function cancelLeave(Request $request, Leave $leave): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);

        abort_unless($leave->employee_id === $employee->id, 403);

        if ($leave->status !== 'Pending') {
            return response()->json(['ok' => false, 'error' => 'Only pending leave requests can request cancellation.'], 409);
        }

        $leave->update([
            'cancel_requested_at' => now(),
            'cancel_requested_by' => $request->user()->id,
            'cancel_reason' => $request->string('reason')->toString() ?: null,
        ]);

        return response()->json(['ok' => true, 'data' => $leave->refresh()->load('type')]);
    }

    public function overtime(Request $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);

        return response()->json(OvertimeRequest::query()
            ->with('overtimeRule')
            ->where('employee_id', $employee->id)
            ->latest()
            ->paginate($request->integer('per_page', 15)));
    }

    public function storeOvertime(EssOvertimeRequest $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);
        $validated = $request->validated();
        $rule = OvertimeRule::query()
            ->where('id', $validated['overtime_rule_id'])
            ->where('organization_id', $employee->organization_id)
            ->where('is_active', true)
            ->firstOrFail();

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);
        $totalHours = round($startTime->diffInMinutes($endTime) / 60, 2);

        if ((float) $rule->min_hours > 0 && $totalHours < (float) $rule->min_hours) {
            return response()->json(['message' => "Minimum overtime hours is {$rule->min_hours}"], 422);
        }

        if ($rule->max_hours_daily && $totalHours > (float) $rule->max_hours_daily) {
            return response()->json(['message' => "Maximum daily overtime is {$rule->max_hours_daily} hours"], 422);
        }

        $evidencePath = $request->hasFile('evidence')
            ? $request->file('evidence')->store('overtime-evidence', 'public')
            : null;

        $overtime = OvertimeRequest::query()->create([
            'employee_id' => $employee->id,
            'organization_id' => $employee->organization_id,
            'overtime_rule_id' => $rule->id,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'total_hours' => $totalHours,
            'reason' => $validated['reason'] ?? null,
            'evidence_path' => $evidencePath,
            'status' => $rule->requires_approval ? 'PENDING' : 'APPROVED',
        ]);

        return response()->json(['data' => $overtime->load('overtimeRule')], 201);
    }

    public function reimbursements(Request $request): JsonResponse
    {
        return response()->json(Reimbursement::query()
            ->with('items')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->integer('per_page', 15)));
    }

    public function storeReimbursement(EssReimbursementRequest $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);
        $validated = $request->validated();

        $reimbursement = DB::transaction(function () use ($employee, $request, $validated): Reimbursement {
            $totalAmount = collect($validated['items'])->sum('amount');
            $reimbursement = Reimbursement::query()->create([
                'organization_id' => $employee->organization_id,
                'user_id' => $request->user()->id,
                'submission_date' => $validated['submission_date'] ?? today()->toDateString(),
                'total_amount' => $totalAmount,
                'status' => 'SUBMITTED',
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($validated['items'] as $index => $itemData) {
                $path = $request->hasFile("items.{$index}.receipt_file")
                    ? $request->file("items.{$index}.receipt_file")->store('reimbursements', 'public')
                    : null;

                ReimbursementItem::query()->create([
                    'reimbursement_id' => $reimbursement->id,
                    'category' => $itemData['category'],
                    'description' => $itemData['description'],
                    'amount' => $itemData['amount'],
                    'receipt_date' => $itemData['receipt_date'],
                    'receipt_file_path' => $path,
                ]);
            }

            return $reimbursement;
        });

        return response()->json(['data' => $reimbursement->load('items')], 201);
    }

    public function payslips(Request $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);

        return response()->json(Payroll::query()
            ->with('components')
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['PROCESSED', 'PAID'])
            ->latest('period')
            ->paginate($request->integer('per_page', 12)));
    }

    public function downloadPayslip(Request $request, Payroll $payroll): Response
    {
        $employee = $this->employeeOrAbort($request);

        abort_unless($payroll->employee_id === $employee->id, 403);
        abort_unless(in_array($payroll->status, ['PROCESSED', 'PAID'], true), 404);

        $payroll->load(['employee.organization', 'organization', 'components']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.paystub', compact('payroll'));
        $filename = 'Slip-Gaji-'.$payroll->period.'-'.str_replace(' ', '-', $payroll->employee->first_name).'.pdf';

        return $pdf->download($filename);
    }

    public function compliance(Request $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);

        return response()->json([
            'data' => [
                'certificates' => $employee->certificates()->latest('expiry_date')->get(),
                'medical_checkups' => $employee->medicalCheckups()->latest('next_checkup_date')->get(),
            ],
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        return response()->json($request->user()
            ->notifications()
            ->latest()
            ->paginate($request->integer('per_page', 15)));
    }

    public function geofence(Request $request): JsonResponse
    {
        $employee = $this->employeeOrAbort($request);
        $org = $employee->organization;

        abort_unless($org, 403, 'No organization context.');

        return response()->json([
            'data' => [
                'latitude' => $org->latitude,
                'longitude' => $org->longitude,
                'radius' => $org->radius,
            ],
        ]);
    }

    private function employeeOrAbort(Request $request): Employee
    {
        $employee = $request->user()?->employee;

        abort_unless($employee, 403, 'Akun ini belum terhubung ke profil karyawan.');

        return $employee;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function leaveBalance(Employee $employee): array
    {
        return LeaveType::query()
            ->orderBy('name')
            ->get()
            ->map(function (LeaveType $type) use ($employee): array {
                $used = Leave::query()
                    ->where('employee_id', $employee->id)
                    ->where('leave_type_id', $type->id)
                    ->where('status', 'Approved')
                    ->whereYear('start_date', now()->year)
                    ->sum('total_days');

                return [
                    'leave_type_id' => $type->id,
                    'name' => $type->name,
                    'allowance' => $type->default_days_allowance,
                    'used' => (int) $used,
                    'remaining' => max(0, (int) $type->default_days_allowance - (int) $used),
                    'requires_attachment' => $type->requires_attachment,
                    'is_paid' => $type->is_paid,
                ];
            })
            ->values()
            ->all();
    }

    private function workingDays(string $startDate, string $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $totalDays = 0;

        while ($start <= $end) {
            if (! $start->isWeekend()) {
                $totalDays++;
            }

            $start->addDay();
        }

        return $totalDays;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertInsideGeofence(?\App\Models\Organization $org, array $validated): void
    {
        if (! $org || ! $org->latitude || ! $org->longitude || ! $org->radius) {
            return;
        }

        $distance = $this->haversineDistanceMeters(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            (float) $org->latitude,
            (float) $org->longitude
        );

        $accuracy = $validated['accuracy'] ?? null;
        if ($accuracy !== null && (float) $accuracy > max(50, (float) $org->radius)) {
            abort(response()->json(['ok' => false, 'error' => 'GPS accuracy too low', 'accuracy' => $accuracy], 422));
        }

        if ($distance > (float) $org->radius) {
            abort(response()->json(['ok' => false, 'error' => 'Outside geofence', 'distance' => round($distance)], 422));
        }
    }

    private function haversineDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $latFrom = deg2rad($lat2);
        $lonFrom = deg2rad($lon2);
        $latTo = deg2rad($lat1);
        $lonTo = deg2rad($lon1);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}
