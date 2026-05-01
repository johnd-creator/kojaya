<?php

namespace App\Services\Procurement;

use App\Models\User;

class ApprovalService
{
    public function requiredLevels(float $amount): array
    {
        if ($amount <= 50000000) {
            return [1];
        }
        if ($amount <= 200000000) {
            return [1, 2];
        }

        return [1, 2, 3];
    }

    public function canApprove(User $user, int $level): bool
    {
        if ($level === 1) {
            return $user->hasAnyRole(['Supervisor', 'Manager', 'Director', 'System Admin']);
        }
        if ($level === 2) {
            return $user->hasAnyRole(['Manager', 'Director', 'System Admin']);
        }

        return $user->hasAnyRole(['Director', 'System Admin']);
    }
}
