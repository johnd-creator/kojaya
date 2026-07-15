<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\SavingsWithdrawal;
use App\Models\User;

class SavingsWithdrawalPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAny($user, [
            PermissionEnum::COOPERATIVE_LEDGER_VIEW->value,
            PermissionEnum::COOPERATIVE_LEDGER_MANAGE->value,
        ]);
    }

    public function view(User $user, SavingsWithdrawal $savingsWithdrawal): bool
    {
        return ($this->viewAny($user)
                && ($this->can($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value) || $this->sameOrganization($user, $savingsWithdrawal)))
            || ($savingsWithdrawal->member?->user_id === $user->id
                && $this->sameOrganization($user, $savingsWithdrawal));
    }

    public function approve(User $user, SavingsWithdrawal $savingsWithdrawal): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_LEDGER_MANAGE->value)
            && ($this->can($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value) || $this->sameOrganization($user, $savingsWithdrawal));
    }

    public function process(User $user, SavingsWithdrawal $savingsWithdrawal): bool
    {
        return $this->approve($user, $savingsWithdrawal);
    }
}
