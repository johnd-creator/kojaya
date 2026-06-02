<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::ORGANIZATIONS_MANAGE->value)
            || $this->can($user, PermissionEnum::ORG_VIEW_ALL->value)
            || $this->can($user, PermissionEnum::ORG_VIEW_UNIT->value);
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->can($user, PermissionEnum::ORGANIZATIONS_MANAGE->value)
            || $this->can($user, PermissionEnum::ORG_VIEW_ALL->value)
            || ($this->can($user, PermissionEnum::ORG_VIEW_UNIT->value) && (string) $user->organization_id === (string) $organization->id);
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::ORGANIZATIONS_MANAGE->value)
            || $this->can($user, PermissionEnum::ORG_CREATE->value);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->can($user, PermissionEnum::ORGANIZATIONS_MANAGE->value)
            || $this->can($user, PermissionEnum::ORG_EDIT->value);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->can($user, PermissionEnum::ORGANIZATIONS_MANAGE->value)
            || $this->can($user, PermissionEnum::ORG_DELETE->value);
    }
}
