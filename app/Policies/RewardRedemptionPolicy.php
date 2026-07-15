<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\RewardRedemption;
use App\Models\User;

class RewardRedemptionPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_REDEMPTION_MANAGE->value);
    }

    public function view(User $user, RewardRedemption $rewardRedemption): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_REDEMPTION_MANAGE->value)
            && $this->sameOrganization($user, $rewardRedemption);
    }

    public function update(User $user, RewardRedemption $rewardRedemption): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_REDEMPTION_MANAGE->value)
            && $this->sameOrganization($user, $rewardRedemption);
    }
}
