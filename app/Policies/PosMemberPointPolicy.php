<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\PosMemberPoint;
use App\Models\User;

class PosMemberPointPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_POINTS_MANAGE->value)
            || $this->can($user, PermissionEnum::POS_REPORTS_VIEW->value);
    }

    public function view(User $user, PosMemberPoint $posMemberPoint): bool
    {
        return ($this->can($user, PermissionEnum::COOPERATIVE_POINTS_MANAGE->value)
            || $this->can($user, PermissionEnum::POS_REPORTS_VIEW->value))
            && $this->sameOrganization($user, $posMemberPoint);
    }
}
