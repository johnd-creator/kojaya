<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\PointTransaction;
use App\Models\User;

class PointTransactionPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_POINTS_MANAGE->value);
    }

    public function view(User $user, PointTransaction $pointTransaction): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_POINTS_MANAGE->value)
            && $this->sameOrganization($user, $pointTransaction);
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_POINTS_MANAGE->value);
    }

    public function update(User $user, PointTransaction $pointTransaction): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_POINTS_MANAGE->value)
            && $this->sameOrganization($user, $pointTransaction);
    }
}
