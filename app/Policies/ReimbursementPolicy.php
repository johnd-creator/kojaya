<?php

namespace App\Policies;

use App\Models\Reimbursement;
use App\Models\User;

class ReimbursementPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Finance Pusat', 'Finance Unit', 'Employee']);
    }

    public function update(User $user, Reimbursement $reimbursement): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Finance Pusat', 'Finance Unit']);
    }

    public function approve(User $user, Reimbursement $reimbursement): bool
    {
        return $this->update($user, $reimbursement);
    }
}
