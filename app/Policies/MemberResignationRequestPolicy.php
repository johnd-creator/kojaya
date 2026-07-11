<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\MemberResignationRequest;
use App\Models\User;

class MemberResignationRequestPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_RESIGNATION_REVIEW->value);
    }

    public function view(User $user, MemberResignationRequest $resignationRequest): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_RESIGNATION_REVIEW->value)
            || $resignationRequest->member?->user_id === $user->id;
    }

    public function approve(User $user, MemberResignationRequest $resignationRequest): bool
    {
        return $this->canAny($user, [
            PermissionEnum::COOPERATIVE_RESIGNATION_REVIEW->value,
            PermissionEnum::COOPERATIVE_MEMBER_APPROVE->value,
        ]);
    }
}
