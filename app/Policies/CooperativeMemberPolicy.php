<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Auth\Access\AuthorizationException;

class CooperativeMemberPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_VIEW->value);
    }

    public function view(User $user, CooperativeMember $cooperativeMember): bool
    {
        return ($this->can($user, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value) && $this->visibleTo($user, $cooperativeMember))
            || ($this->can($user, PermissionEnum::COOPERATIVE_MEMBER_VIEW->value)
                && $cooperativeMember->user_id === $user->id
                && $this->sameOrganization($user, $cooperativeMember));
    }

    public function create(User $user): bool
    {
        if (! $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value)) {
            return false;
        }

        try {
            app(OrganizationScopeService::class)->visibilityFor($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }

    public function update(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value)
            && $this->visibleTo($user, $cooperativeMember);
    }

    public function activate(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->update($user, $cooperativeMember);
    }

    public function updateSensitiveData(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_PII_WRITE->value)
            && $this->visibleTo($user, $cooperativeMember);
    }

    public function resign(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->update($user, $cooperativeMember);
    }

    public function posCredit(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_POS_ACCESS->value)
            && $this->visibleTo($user, $cooperativeMember);
    }

    public function delete(User $user, CooperativeMember $cooperativeMember): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value)
            && $this->visibleTo($user, $cooperativeMember);
    }

    public function export(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_EXPORT->value);
    }

    public function exportSensitive(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_PII_EXPORT->value);
    }

    private function visibleTo(User $user, CooperativeMember $member): bool
    {
        return $this->sameOrganization($user, $member);
    }
}
