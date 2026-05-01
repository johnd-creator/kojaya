<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Attendance::query()
            ->with(['employee', 'organization']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        $attendances = $query->orderByDesc('date')
            ->orderBy('employee_id')
            ->paginate(20)
            ->withQueryString();

        $organizations = Organization::orderBy('name')->get();
        $employees = Employee::where('status', 'ACTIVE')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_code']);

        $todayCount = Attendance::whereDate('date', today())
            ->where('status', 'PRESENT')
            ->count();

        return Inertia::render('Attendance/Index', [
            'attendances' => $attendances,
            'organizations' => $organizations,
            'employees' => $employees,
            'filters' => $request->only(['employee_id', 'organization_id', 'status', 'date_from', 'date_to']),
            'stats' => [
                'today_present' => $todayCount,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'organization_id' => 'required|uuid|exists:organizations,id',
            'date' => 'required|date',
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i|after:clock_in',
            'status' => 'required|in:PRESENT,ABSENT,SICK,LEAVE,OFF',
            'notes' => 'nullable|string|max:500',
        ]);

        Attendance::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'date' => $validated['date']],
            $validated
        );

        return redirect()->route('attendances.index')->with('success', 'Attendance recorded successfully.');
    }

    public function selfService(Request $request): Response
    {
        $user = $request->user();
        $user->load('employee.workShift');

        $employee = $user->employee;
        $todayAttendance = null;
        $todayRoster = null;

        if ($employee) {
            $todayAttendance = Attendance::where('employee_id', $employee->id)
                ->where('date', today()->toDateString())
                ->first();

            if ($employee->shift_group) {
                $todayRoster = \App\Models\ShiftRoster::todayFor($employee->shift_group);
            }
        }

        return Inertia::render('Attendance/SelfService', [
            'employee' => $employee,
            'todayAttendance' => $todayAttendance,
            'todayRoster' => $todayRoster,
        ]);
    }

    public function checkIn(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->employee) {
            return back()->with('error', 'You do not have an active employee profile.');
        }

        $employee = $user->employee;
        $today = now()->toDateString();
        $org = $employee->organization;

        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($org && $org->latitude && $org->longitude && $org->radius) {
            $lat1 = $request->input('latitude');
            $lon1 = $request->input('longitude');

            if (! $lat1 || ! $lon1) {
                return back()->with('error', 'Location is required to check in at this unit.');
            }

            $earthRadius = 6371000; // meters
            $latFrom = deg2rad($org->latitude);
            $lonFrom = deg2rad($org->longitude);
            $latTo = deg2rad($lat1);
            $lonTo = deg2rad($lon1);

            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;

            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            $distance = $angle * $earthRadius;

            if ($distance > $org->radius) {
                return back()->with('error', 'You are outside the permitted check-in area. ('.round($distance).'m away)');
            }
        }

        // Resolve effective work shift from roster (if group-based) or employee default
        $workShift = null;
        if ($employee->shift_group) {
            $roster = \App\Models\ShiftRoster::todayFor($employee->shift_group);
            if ($roster && $roster->is_off_day) {
                return back()->with('error', "Hari ini adalah hari istirahat jadwal Group {$employee->shift_group}. Tidak perlu absensi.");
            }
            $workShift = $roster?->workShift ?? $employee->workShift;
        } else {
            $workShift = $employee->workShift;
        }

        $existing = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->whereNotNull('clock_in')
            ->first();

        if ($existing) {
            return back()->with('error', 'You have already checked in today.');
        }

        $scheduledEndTime = null;
        if ($workShift) {
            if ($workShift->is_flexible) {
                // Flexible time: Check-out is 9 hours from check-in
                $scheduledEndTime = now()->addHours(9)->format('H:i');
            } else {
                $scheduledEndTime = $workShift->end_time;
            }
        }

        Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $today],
            [
                'organization_id' => $employee->organization_id,
                'clock_in' => now()->format('H:i:s'),
                'status' => 'PRESENT',
                'work_shift_id' => $workShift ? $workShift->id : null,
                'scheduled_end_time' => $scheduledEndTime,
            ]
        );

        return back()->with('success', 'Checked in successfully.');
    }

    public function checkOut(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->employee) {
            return back()->with('error', 'You do not have an active employee profile.');
        }

        $employee = $user->employee;
        $today = now()->toDateString();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if (! $attendance || ! $attendance->clock_in) {
            return back()->with('error', 'You have not checked in today.');
        }

        if ($attendance->clock_out) {
            return back()->with('error', 'You have already checked out today.');
        }

        $clockOut = now();
        $isOvertime = false;
        $overtimeHours = 0;

        if ($attendance->scheduled_end_time) {
            $scheduledEnd = now()->setTimeFromTimeString($attendance->scheduled_end_time);

            // Check if checkout is after scheduled end time
            if ($clockOut->greaterThan($scheduledEnd)) {
                $isOvertime = true;
                $diffInMinutes = $scheduledEnd->diffInMinutes($clockOut);
                $overtimeHours = round($diffInMinutes / 60, 2);
            }
        }

        $attendance->update([
            'clock_out' => $clockOut->format('H:i:s'),
            'is_overtime' => $isOvertime,
            'overtime_hours' => $overtimeHours,
        ]);

        return back()->with('success', 'Checked out successfully.');
    }

    public function checkInApi(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->employee) {
            return response()->json(['ok' => false, 'error' => 'No active employee profile'], 403);
        }

        $employee = $user->employee;
        $today = now()->toDateString();
        $org = $employee->organization;

        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric', // meters
            'device_id' => 'nullable|string|max:100',
        ]);

        if ($org && $org->latitude && $org->longitude && $org->radius) {
            $distance = $this->haversineDistanceMeters(
                $validated['latitude'],
                $validated['longitude'],
                $org->latitude,
                $org->longitude
            );

            $accuracy = $validated['accuracy'] ?? null;
            if ($accuracy !== null && $accuracy > max(50, $org->radius)) {
                return response()->json(['ok' => false, 'error' => 'GPS accuracy too low', 'accuracy' => $accuracy], 422);
            }

            if ($distance > $org->radius) {
                return response()->json(['ok' => false, 'error' => 'Outside geofence', 'distance' => round($distance)], 422);
            }
        }

        $existing = \App\Models\Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->whereNotNull('clock_in')
            ->first();
        if ($existing) {
            return response()->json(['ok' => false, 'error' => 'Already checked in'], 409);
        }

        \App\Models\Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $today],
            [
                'organization_id' => $employee->organization_id,
                'clock_in' => now()->format('H:i:s'),
                'status' => 'PRESENT',
                'notes' => $validated['device_id'] ? ('device:'.$validated['device_id']) : null,
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function checkOutApi(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->employee) {
            return response()->json(['ok' => false, 'error' => 'No active employee profile'], 403);
        }

        $employee = $user->employee;
        $today = now()->toDateString();
        $org = $employee->organization;

        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
            'device_id' => 'nullable|string|max:100',
        ]);

        if ($org && $org->latitude && $org->longitude && $org->radius) {
            $distance = $this->haversineDistanceMeters(
                $validated['latitude'],
                $validated['longitude'],
                $org->latitude,
                $org->longitude
            );

            $accuracy = $validated['accuracy'] ?? null;
            if ($accuracy !== null && $accuracy > max(50, $org->radius)) {
                return response()->json(['ok' => false, 'error' => 'GPS accuracy too low', 'accuracy' => $accuracy], 422);
            }

            if ($distance > $org->radius) {
                return response()->json(['ok' => false, 'error' => 'Outside geofence', 'distance' => round($distance)], 422);
            }
        }

        $attendance = \App\Models\Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();
        if (! $attendance || ! $attendance->clock_in) {
            return response()->json(['ok' => false, 'error' => 'Not checked in'], 409);
        }
        if ($attendance->clock_out) {
            return response()->json(['ok' => false, 'error' => 'Already checked out'], 409);
        }

        $attendance->update([
            'clock_out' => now()->format('H:i:s'),
        ]);

        return response()->json(['ok' => true]);
    }

    public function geofence(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->employee || ! $user->employee->organization) {
            return response()->json(['ok' => false, 'error' => 'No organization context'], 403);
        }
        $org = $user->employee->organization;

        return response()->json([
            'ok' => true,
            'latitude' => $org->latitude,
            'longitude' => $org->longitude,
            'radius' => $org->radius,
        ]);
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
