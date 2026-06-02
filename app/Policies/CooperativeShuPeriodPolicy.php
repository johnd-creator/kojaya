<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\CooperativeShuPeriod;
use App\Models\User;

class CooperativeShuPeriodPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_SHU_MANAGE->value)
            || $this->can($user, PermissionEnum::POS_SHU_MANAGE->value);
    }

    public function view(User $user, CooperativeShuPeriod $cooperativeShuPeriod): bool
    {
        return $this->viewAny($user);
    }

    public function close(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_SHU_MANAGE->value);
    }
}
