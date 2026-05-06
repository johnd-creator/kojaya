<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;

class LeavePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_leave_all')
            || $this->can($user, 'view_leave_unit');
    }

    public function update(User $user, Leave $leave): bool
    {
        return $this->can($user, 'approve_leave');
    }

    public function approve(User $user, Leave $leave): bool
    {
        return $this->update($user, $leave);
    }
}
