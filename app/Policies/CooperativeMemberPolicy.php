<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use App\Models\User;

class CooperativeMemberPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_VIEW->value);
    }

    public function view(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);
    }

    public function update(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);
    }

    public function delete(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);
    }
}
