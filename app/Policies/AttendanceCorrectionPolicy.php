<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\User;

class AttendanceCorrectionPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->can($user, PermissionEnum::ATTENDANCE_VIEW_ALL->value)) {
            return true;
        }

        if ($this->can($user, PermissionEnum::ATTENDANCE_VIEW_UNIT->value) && ! empty($user->organization_id)) {
            return true;
        }

        return false;
    }

    public function approve(User $user, AttendanceCorrection $correction): bool
    {
        if (! $this->can($user, PermissionEnum::ATTENDANCE_APPROVE->value)) {
            return false;
        }

        // Self-approval prohibition
        if ($correction->requested_by === $user->id) {
            return false;
        }

        if ($user->employee && $user->employee->id === $correction->employee_id) {
            return false;
        }

        // Must validate consistent employee and tenant
        $employee = $correction->employee ?: Employee::find($correction->employee_id);
        if (! $employee || (string) $correction->organization_id !== (string) $employee->organization_id) {
            return false;
        }

        if ($this->can($user, PermissionEnum::ATTENDANCE_VIEW_ALL->value)) {
            return true;
        }

        if ($this->can($user, PermissionEnum::ATTENDANCE_VIEW_UNIT->value) && ! empty($user->organization_id)) {
            return (string) $user->organization_id === (string) $employee->organization_id;
        }

        return false;
    }
}
