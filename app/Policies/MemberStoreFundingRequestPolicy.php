<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\MemberStoreFundingRequest;
use App\Models\User;

class MemberStoreFundingRequestPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAny($user, [
            PermissionEnum::STORE_CREDIT_MANAGE->value,
            PermissionEnum::STORE_CREDIT_CASHIER->value,
            PermissionEnum::STORE_CREDIT_APPROVE_TRANSFER->value,
            PermissionEnum::STORE_CREDIT_VIEW->value,
        ]);
    }

    public function view(User $user, MemberStoreFundingRequest $funding): bool
    {
        if ($this->isOwner($user, $funding) && $this->sameOrganization($user, $funding)) {
            return true;
        }

        return $this->canAny($user, [
            PermissionEnum::STORE_CREDIT_MANAGE->value,
            PermissionEnum::STORE_CREDIT_CASHIER->value,
            PermissionEnum::STORE_CREDIT_APPROVE_TRANSFER->value,
            PermissionEnum::STORE_CREDIT_VIEW->value,
        ]) && $this->sameOrganization($user, $funding);
    }

    public function submit(User $user, MemberStoreFundingRequest $funding): bool
    {
        return $this->sameOrganization($user, $funding);
    }

    public function approve(User $user, MemberStoreFundingRequest $funding): bool
    {
        return $this->can($user, PermissionEnum::STORE_CREDIT_APPROVE_TRANSFER->value)
            && $this->sameOrganization($user, $funding);
    }

    public function reject(User $user, MemberStoreFundingRequest $funding): bool
    {
        return $this->can($user, PermissionEnum::STORE_CREDIT_APPROVE_TRANSFER->value)
            && $this->sameOrganization($user, $funding);
    }

    public function cashPosting(User $user, MemberStoreFundingRequest $funding): bool
    {
        return $this->can($user, PermissionEnum::STORE_CREDIT_CASHIER->value)
            && $this->sameOrganization($user, $funding);
    }

    public function isOwner(User $user, MemberStoreFundingRequest $funding): bool
    {
        $account = $funding->account;

        return $account !== null
            && $account->member !== null
            && $account->member->user_id === $user->id;
    }
}
