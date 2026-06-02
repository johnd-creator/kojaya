<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Loan;
use App\Models\User;

class LoanPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_LOAN_VIEW->value);
    }

    public function view(User $user, Loan $loan): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value)
            || $this->can($user, PermissionEnum::COOPERATIVE_LOAN_MANAGE->value)
            || $this->can($user, PermissionEnum::COOPERATIVE_LOAN_APPROVE->value)
            || ($this->can($user, PermissionEnum::COOPERATIVE_LOAN_VIEW->value) && $loan->member?->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_LOAN_MANAGE->value)
            || $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_VIEW->value);
    }

    public function manage(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_LOAN_MANAGE->value);
    }

    public function approve(User $user, Loan $loan): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_LOAN_APPROVE->value);
    }

    public function reject(User $user, Loan $loan): bool
    {
        return $this->approve($user, $loan);
    }

    public function disburse(User $user, Loan $loan): bool
    {
        return $this->approve($user, $loan);
    }

    public function recordPayment(User $user, Loan $loan): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_LOAN_MANAGE->value);
    }

    public function writeOff(User $user, Loan $loan): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_LOAN_APPROVE->value);
    }
}
