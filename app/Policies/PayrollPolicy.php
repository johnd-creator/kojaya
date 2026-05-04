<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAny($user, [PermissionEnum::PAYROLL_VIEW_ALL->value, PermissionEnum::PAYROLL_VIEW_UNIT->value])
            || $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Finance Pusat', 'Finance Unit', 'HR Pusat']);
    }

    public function view(User $user, Payroll $payroll): bool
    {
        return $this->can($user, PermissionEnum::PAYROLL_VIEW_ALL->value)
            || ($this->can($user, PermissionEnum::PAYROLL_VIEW_UNIT->value) && $this->sameOrganization($user, $payroll));
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::PAYROLL_PROCESS->value)
            || $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Finance Pusat', 'Finance Unit', 'HR Pusat']);
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return $this->can($user, PermissionEnum::PAYROLL_PROCESS->value)
            && ($this->can($user, PermissionEnum::PAYROLL_VIEW_ALL->value) || $this->sameOrganization($user, $payroll));
    }

    public function approve(User $user, Payroll $payroll): bool
    {
        return ($this->can($user, PermissionEnum::PAYROLL_APPROVE->value)
            && ($this->can($user, PermissionEnum::PAYROLL_VIEW_ALL->value) || $this->sameOrganization($user, $payroll)))
            || $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Finance Pusat']);
    }

    public function submitForApproval(User $user): bool
    {
        return $this->create($user);
    }

    public function exportBankTransfer(User $user): bool
    {
        return $this->can($user, PermissionEnum::PAYROLL_APPROVE->value)
            || $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Finance Pusat']);
    }
}
