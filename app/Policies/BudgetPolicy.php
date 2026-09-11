<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->can('view_budget_all')) {
            return true;
        }

        return $user->can('manage_budget') && ! empty($user->organization_id);
    }

    public function view(User $user, Budget $budget): bool
    {
        if ($user->can('view_budget_all')) {
            return true;
        }

        return $user->can('manage_budget')
            && ! empty($user->organization_id)
            && (string) $user->organization_id === (string) $budget->organization_id;
    }

    public function create(User $user): bool
    {
        if (! $user->can('manage_budget')) {
            return false;
        }

        if ($user->can('view_budget_all')) {
            return true;
        }

        return ! empty($user->organization_id);
    }

    public function update(User $user, Budget $budget): bool
    {
        if (! $user->can('manage_budget')) {
            return false;
        }

        if ($user->can('view_budget_all')) {
            return true;
        }

        return ! empty($user->organization_id)
            && (string) $user->organization_id === (string) $budget->organization_id;
    }

    public function delete(User $user, Budget $budget): bool
    {
        return $this->update($user, $budget);
    }

    public function manageLines(User $user, Budget $budget): bool
    {
        return $this->update($user, $budget);
    }

    public function import(User $user, Budget $budget): bool
    {
        return $this->update($user, $budget);
    }
}
