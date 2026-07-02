<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\MemberResignationRequest;
use App\Models\User;

class MemberResignationRequestPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAny($user, [
            PermissionEnum::COOPERATIVE_MEMBER_VIEW->value,
            PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value,
            PermissionEnum::COOPERATIVE_MEMBER_APPROVE->value,
        ]);
    }

    public function view(User $user, MemberResignationRequest $resignationRequest): bool
    {
        return $this->viewAny($user)
            || $resignationRequest->member?->user_id === $user->id;
    }

    public function approve(User $user, MemberResignationRequest $resignationRequest): bool
    {
        return $this->canAny($user, [
            PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value,
            PermissionEnum::COOPERATIVE_MEMBER_APPROVE->value,
        ]);
    }
}
