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
        if ($correction->status !== AttendanceCorrectionStatus::Pending) {
            throw ValidationException::withMessages([
                'correction' => ['Only pending attendance corrections can be approved.'],
            ]);
        }

        return DB::transaction(function () use ($correction, $reviewer, $reviewNote): AttendanceCorrection {
            $attendanceData = [
                'organization_id' => $correction->organization_id,
                'status' => 'PRESENT',
                'notes' => trim(($reviewNote ?: '').' Corrected attendance.'),
            ];

            if ($correction->corrected_clock_in !== null) {
                $attendanceData['clock_in'] = $correction->corrected_clock_in;
            }

            if ($correction->corrected_clock_out !== null) {
                $attendanceData['clock_out'] = $correction->corrected_clock_out;
            }

            $attendance = Attendance::query()->updateOrCreate(
                [
                    'employee_id' => $correction->employee_id,
                    'date' => $correction->date->toDateString(),
                ],
                $attendanceData,
            );

            $attendance->update([
                'mobile_audit' => array_merge($attendance->mobile_audit ?? [], [
                    'correction' => [
                        'attendance_correction_id' => $correction->id,
                        'approved_by' => $reviewer->id,
                        'approved_at' => now()->toIso8601String(),
                    ],
                ]),
            ]);

            $correction->update([
                'attendance_id' => $attendance->id,
                'reviewed_by' => $reviewer->id,
                'status' => AttendanceCorrectionStatus::Approved,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
            ]);

            return $correction->refresh()->load('attendance');
        });
    }
}
