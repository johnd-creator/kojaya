<?php

namespace App\Services\Authorization;

use App\Enums\PermissionEnum;
use App\Models\User;

class CooperativeLedgerCorrectionAuthorizer
{
    public function canCorrect(?User $user): bool
    {
        return $user !== null
            && $user->hasRole('System Admin')
            && $user->hasPermissionTo(PermissionEnum::COOPERATIVE_LEDGER_MANAGE->value);
    }
}
