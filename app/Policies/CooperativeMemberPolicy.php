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
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value)
            || ($this->can($user, PermissionEnum::COOPERATIVE_MEMBER_VIEW->value) && $cooperativeMember->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);
    }

    public function update(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);
    }

    public function activate(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->update($user, $cooperativeMember);
    }

    public function resign(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->update($user, $cooperativeMember);
    }

    public function delete(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);
    }
}
