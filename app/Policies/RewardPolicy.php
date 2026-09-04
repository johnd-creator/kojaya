<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Reward;
use App\Models\User;

class RewardPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value);
    }

    public function view(User $user, Reward $reward): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value)
            && $this->sameOrganization($user, $reward);
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value);
    }

    public function update(User $user, Reward $reward): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value)
            && $this->sameOrganization($user, $reward);
    }

    public function delete(User $user, Reward $reward): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value)
            && $this->sameOrganization($user, $reward);
    }
}
