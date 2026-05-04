<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Employee;
use App\Models\User;

class EmployeePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAny($user, [PermissionEnum::EMPLOYEE_VIEW_ALL->value, PermissionEnum::EMPLOYEE_VIEW_UNIT->value]);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->can($user, PermissionEnum::EMPLOYEE_VIEW_ALL->value)
            || ($this->can($user, PermissionEnum::EMPLOYEE_VIEW_UNIT->value) && $this->sameOrganization($user, $employee));
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::EMPLOYEE_CREATE->value);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->can($user, PermissionEnum::EMPLOYEE_EDIT->value)
            && ($this->can($user, PermissionEnum::EMPLOYEE_VIEW_ALL->value) || $this->sameOrganization($user, $employee));
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $this->can($user, PermissionEnum::EMPLOYEE_DELETE->value)
            && ($this->can($user, PermissionEnum::EMPLOYEE_VIEW_ALL->value) || $this->sameOrganization($user, $employee));
    }

    public function manageEssAccess(User $user, Employee $employee): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'HR Pusat', 'HR Unit'])
            && ($this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'HR Pusat']) || $this->sameOrganization($user, $employee));
    }
}
