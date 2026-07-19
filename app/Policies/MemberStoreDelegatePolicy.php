<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\MemberStoreDelegate;
use App\Models\User;

class MemberStoreDelegatePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAny($user, [
            PermissionEnum::STORE_CREDIT_MANAGE->value,
            PermissionEnum::STORE_CREDIT_VIEW->value,
            PermissionEnum::STORE_CREDIT_CASHIER->value,
        ]);
    }

    public function view(User $user, MemberStoreDelegate $delegate): bool
    {
        if ($this->isOwner($user, $delegate) && $this->sameOrganization($user, $delegate)) {
            return true;
        }

        return $this->canAny($user, [
            PermissionEnum::STORE_CREDIT_MANAGE->value,
            PermissionEnum::STORE_CREDIT_VIEW->value,
            PermissionEnum::STORE_CREDIT_CASHIER->value,
        ]) && $this->sameOrganization($user, $delegate);
    }

    public function manage(User $user, MemberStoreDelegate $delegate): bool
    {
        if ($this->isOwner($user, $delegate) && $this->sameOrganization($user, $delegate)) {
            return true;
        }

        return $this->can($user, PermissionEnum::STORE_CREDIT_MANAGE->value)
            && $this->sameOrganization($user, $delegate);
    }

    public function resetPin(User $user, MemberStoreDelegate $delegate): bool
    {
        return $this->manage($user, $delegate);
    }

    public function isOwner(User $user, MemberStoreDelegate $delegate): bool
    {
        $account = $delegate->account;

        return $account !== null
            && $account->member !== null
            && $account->member->user_id === $user->id;
    }
}
