<?php

namespace App\Policies;

use App\Models\OvertimeRequest;
use App\Models\User;

class OvertimeRequestPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_overtime_all')
            || $this->can($user, 'view_overtime_unit')
            || $this->can($user, 'access_ess_portal');
    }

    public function update(User $user, OvertimeRequest $overtimeRequest): bool
    {
        return $this->can($user, 'approve_overtime');
    }

    public function approve(User $user, OvertimeRequest $overtimeRequest): bool
    {
        if (! $this->can($user, 'approve_overtime')) {
            return false;
        }

        if ($overtimeRequest->employee && $overtimeRequest->employee->user_id === $user->id) {
            return false;
        }

        return true;
    }

    public function delete(User $user, OvertimeRequest $overtimeRequest): bool
    {
        return $this->update($user, $overtimeRequest);
    }
}
