<?php

namespace App\Policies;

use App\Models\OvertimeRequest;
use App\Models\User;

class OvertimeRequestPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'HR Pusat', 'HR Unit', 'Admin Unit', 'Employee']);
    }

    public function update(User $user, OvertimeRequest $overtimeRequest): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'HR Pusat', 'HR Unit']);
    }

    public function approve(User $user, OvertimeRequest $overtimeRequest): bool
    {
        return $this->update($user, $overtimeRequest);
    }

    public function delete(User $user, OvertimeRequest $overtimeRequest): bool
    {
        return $this->update($user, $overtimeRequest);
    }
}
