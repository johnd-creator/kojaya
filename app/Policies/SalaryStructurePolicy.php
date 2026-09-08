<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\SalaryStructure;
use App\Models\User;

class SalaryStructurePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
    }

    public function view(User $user, SalaryStructure $salaryStructure): bool
    {
        return $this->can($user, PermissionEnum::SALARY_STRUCTURES_MANAGE->value)
            && $this->sameOrganization($user, $salaryStructure);
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::SALARY_STRUCTURES_MANAGE->value)
            && ($user->can(PermissionEnum::PAYROLL_VIEW_ALL->value) || ! empty($user->organization_id));
    }

    public function update(User $user, SalaryStructure $salaryStructure): bool
    {
        return $this->can($user, PermissionEnum::SALARY_STRUCTURES_MANAGE->value)
            && $this->sameOrganization($user, $salaryStructure);
    }

    public function delete(User $user, SalaryStructure $salaryStructure): bool
    {
        return $this->can($user, PermissionEnum::SALARY_STRUCTURES_MANAGE->value)
            && $this->sameOrganization($user, $salaryStructure);
    }
}
