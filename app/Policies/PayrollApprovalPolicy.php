<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\PayrollApproval;
use App\Models\User;

class PayrollApprovalPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAny($user, [
            PermissionEnum::PAYROLL_VIEW_ALL->value,
            PermissionEnum::PAYROLL_VIEW_UNIT->value,
            PermissionEnum::PAYROLL_APPROVE->value,
        ]);
    }

    public function view(User $user, PayrollApproval $approval): bool
    {
        return $this->viewAny($user) && $this->sameOrganization($user, $approval);
    }

    public function approve(User $user, PayrollApproval $approval): bool
    {
        return $this->can($user, PermissionEnum::PAYROLL_APPROVE->value)
            && $this->sameOrganization($user, $approval);
    }

    public function reject(User $user, PayrollApproval $approval): bool
    {
        return $this->approve($user, $approval);
    }
}
