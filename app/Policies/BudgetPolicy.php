<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_budget_all');
    }

    public function view(User $user, Budget $budget): bool
    {
        return $this->canAny($user, ['view_budget_all', 'manage_budget'])
            && $this->sameOrganization($user, $budget);
    }

    public function update(User $user, Budget $budget): bool
    {
        return $this->view($user, $budget);
    }
}
