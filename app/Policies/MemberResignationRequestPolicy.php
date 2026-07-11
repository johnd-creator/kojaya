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
        return ($this->can($user, PermissionEnum::COOPERATIVE_RESIGNATION_REVIEW->value) && $this->visibleTo($user, $resignationRequest))
            || ($resignationRequest->member?->user_id === $user->id
                && $resignationRequest->member !== null
                && $this->sameOrganization($user, $resignationRequest->member));
    }

    public function approve(User $user, MemberResignationRequest $resignationRequest): bool
    {
        return $this->canAny($user, [
            PermissionEnum::COOPERATIVE_RESIGNATION_REVIEW->value,
            PermissionEnum::COOPERATIVE_MEMBER_APPROVE->value,
        ]) && $this->visibleTo($user, $resignationRequest);
    }

    private function visibleTo(User $user, MemberResignationRequest $request): bool
    {
        $member = $request->member;

        return $member !== null && ($this->can($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value)
            || $this->sameOrganization($user, $member));
    }
}
