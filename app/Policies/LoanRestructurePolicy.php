<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Loan;
use App\Models\LoanRestructure;
use App\Models\User;

class LoanRestructurePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAny($user, [
            PermissionEnum::COOPERATIVE_LOAN_MANAGE->value,
            PermissionEnum::COOPERATIVE_LOAN_APPROVE->value,
        ]);
    }

    public function view(User $user, LoanRestructure $loanRestructure): bool
    {
        return $this->viewAny($user)
            || ($this->can($user, PermissionEnum::COOPERATIVE_LOAN_VIEW->value) && $loanRestructure->member?->user_id === $user->id);
    }

    public function create(User $user, Loan $loan): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_LOAN_MANAGE->value)
            || ($this->can($user, PermissionEnum::COOPERATIVE_LOAN_VIEW->value) && $loan->member?->user_id === $user->id);
    }

    public function approve(User $user, LoanRestructure $loanRestructure): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_LOAN_APPROVE->value);
    }

    public function reject(User $user, LoanRestructure $loanRestructure): bool
    {
        return $this->approve($user, $loanRestructure);
    }
}
