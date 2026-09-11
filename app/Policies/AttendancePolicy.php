<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy extends BasePolicy
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

    public function view(User $user, Attendance $attendance): bool
    {
        if ($this->can($user, PermissionEnum::ATTENDANCE_VIEW_ALL->value)) {
            return true;
        }

        if ($this->can($user, PermissionEnum::ATTENDANCE_VIEW_UNIT->value) && ! empty($user->organization_id)) {
            return $this->sameOrganization($user, $attendance);
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $this->can($user, PermissionEnum::ATTENDANCE_APPROVE->value)) {
            return false;
        }

        if ($this->can($user, PermissionEnum::ATTENDANCE_VIEW_ALL->value)) {
            return true;
        }

        if ($this->can($user, PermissionEnum::ATTENDANCE_VIEW_UNIT->value) && ! empty($user->organization_id)) {
            return true;
        }

        return false;
    }

    public function update(User $user, Attendance $attendance): bool
    {
        if (! $this->can($user, PermissionEnum::ATTENDANCE_APPROVE->value)) {
            return false;
        }

        if ($this->can($user, PermissionEnum::ATTENDANCE_VIEW_ALL->value)) {
            return true;
        }

        if ($this->can($user, PermissionEnum::ATTENDANCE_VIEW_UNIT->value) && ! empty($user->organization_id)) {
            return $this->sameOrganization($user, $attendance);
        }

        return false;
    }

    public function administer(User $user): bool
    {
        return $this->create($user);
    }
}
