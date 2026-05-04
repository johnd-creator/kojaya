<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Finance Pusat', 'Finance Unit']);
    }

    public function view(User $user, Budget $budget): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Finance Pusat'])
            || ($this->hasAnyRole($user, ['Finance Unit']) && $this->sameOrganization($user, $budget));
    }

    public function update(User $user, Budget $budget): bool
    {
        return $this->view($user, $budget);
    }
}
