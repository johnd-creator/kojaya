<?php

namespace App\Services\Hr;

use App\Enums\AttendanceCorrectionStatus;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function request(Employee $employee, User $requester, array $data): AttendanceCorrection
    {
        return AttendanceCorrection::query()->create([
            'employee_id' => $employee->id,
            'organization_id' => $employee->organization_id,
            'requested_by' => $requester->id,
            'date' => $data['date'],
            'corrected_clock_in' => $data['corrected_clock_in'] ?? null,
            'corrected_clock_out' => $data['corrected_clock_out'] ?? null,
            'reason' => $data['reason'],
            'evidence_path' => $data['evidence_path'] ?? null,
            'status' => AttendanceCorrectionStatus::Pending,
        ]);
    }

    public function approve(AttendanceCorrection $correction, User $reviewer, ?string $reviewNote = null): AttendanceCorrection
    {
        return DB::transaction(function () use ($correction, $reviewer, $reviewNote): AttendanceCorrection {
            $lockedCorrection = AttendanceCorrection::query()
                ->whereKey($correction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedCorrection->status !== AttendanceCorrectionStatus::Pending) {
                throw ValidationException::withMessages([
                    'correction' => ['Only pending attendance corrections can be approved.'],
                ]);
            }

            if (! $reviewer->can(\App\Enums\PermissionEnum::ATTENDANCE_APPROVE->value)) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Reviewer does not have approve_attendance permission.');
            }

            $hasGlobal = $reviewer->can(\App\Enums\PermissionEnum::ATTENDANCE_VIEW_ALL->value);
            $hasUnit = $reviewer->can(\App\Enums\PermissionEnum::ATTENDANCE_VIEW_UNIT->value) && ! empty($reviewer->organization_id);
            if (! $hasGlobal && ! $hasUnit) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Reviewer does not have valid attendance visibility scope.');
            }

            // Self-approval prohibition
            if ($lockedCorrection->requested_by === $reviewer->id) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Self-approval is not allowed.');
            }
            if ($reviewer->employee && $reviewer->employee->id === $lockedCorrection->employee_id) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Self-approval is not allowed.');
            }

            // Authoritative employee validation
            $employee = Employee::query()->find($lockedCorrection->employee_id);
            if (! $employee) {
                throw ValidationException::withMessages([
                    'employee' => ['Employee for attendance correction not found.'],
                ]);
            }

            // Corrupt correction relationship defense
            if ((string) $lockedCorrection->organization_id !== (string) $employee->organization_id) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Correction organization does not match employee organization.');
            }

            // Tenant boundary
            if (! $hasGlobal) {
                if ((string) $reviewer->organization_id !== (string) $employee->organization_id) {
                    throw new \Illuminate\Auth\Access\AuthorizationException('Reviewer cannot approve correction outside their organization.');
                }
            }

            $attendanceData = [
                'organization_id' => $employee->organization_id,
                'status' => 'PRESENT',
                'notes' => trim(($reviewNote ?: '').' Corrected attendance.'),
            ];

            if ($lockedCorrection->corrected_clock_in !== null) {
                $attendanceData['clock_in'] = $lockedCorrection->corrected_clock_in;
            }

            if ($lockedCorrection->corrected_clock_out !== null) {
                $attendanceData['clock_out'] = $lockedCorrection->corrected_clock_out;
            }

            $attendance = Attendance::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $lockedCorrection->date->toDateString(),
                ],
                $attendanceData,
            );

            $attendance->update([
                'mobile_audit' => array_merge($attendance->mobile_audit ?? [], [
                    'correction' => [
                        'attendance_correction_id' => $lockedCorrection->id,
                        'approved_by' => $reviewer->id,
                        'approved_at' => now()->toIso8601String(),
                    ],
                ]),
            ]);

            $lockedCorrection->update([
                'attendance_id' => $attendance->id,
                'reviewed_by' => $reviewer->id,
                'status' => AttendanceCorrectionStatus::Approved,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
            ]);

            return $lockedCorrection->refresh()->load('attendance');
        });
    }
}
