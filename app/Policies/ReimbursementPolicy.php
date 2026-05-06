<?php

namespace App\Policies;

use App\Models\Reimbursement;
use App\Models\User;

class ReimbursementPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'manage_reimbursement')
            || $this->can($user, 'access_ess_portal');
    }

    public function update(User $user, Reimbursement $reimbursement): bool
    {
        return $this->can($user, 'manage_reimbursement');
    }

    public function approve(User $user, Reimbursement $reimbursement): bool
    {
        return $this->can($user, 'approve_reimbursement');
    }
}
