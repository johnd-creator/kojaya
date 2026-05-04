<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;

class LeavePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'HR Pusat', 'HR Unit']);
    }

    public function update(User $user, Leave $leave): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'HR Pusat', 'HR Unit']);
    }

    public function approve(User $user, Leave $leave): bool
    {
        return $this->update($user, $leave);
    }
}
