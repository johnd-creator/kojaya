<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\User;

class MemberStoreAccountPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAny($user, [
            PermissionEnum::STORE_CREDIT_VIEW->value,
            PermissionEnum::STORE_CREDIT_MANAGE->value,
            PermissionEnum::STORE_CREDIT_REPORT->value,
            PermissionEnum::STORE_CREDIT_CASHIER->value,
        ]);
    }

    public function view(User $user, MemberStoreAccount $account): bool
    {
        if ($this->isOwner($user, $account) && $this->sameOrganization($user, $account)) {
            return true;
        }

        return $this->canAny($user, [
            PermissionEnum::STORE_CREDIT_VIEW->value,
            PermissionEnum::STORE_CREDIT_MANAGE->value,
            PermissionEnum::STORE_CREDIT_REPORT->value,
            PermissionEnum::STORE_CREDIT_CASHIER->value,
        ]) && $this->sameOrganization($user, $account);
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::STORE_CREDIT_MANAGE->value);
    }

    public function manage(User $user, MemberStoreAccount $account): bool
    {
        return $this->can($user, PermissionEnum::STORE_CREDIT_MANAGE->value)
            && $this->sameOrganization($user, $account);
    }

    public function manageLimit(User $user, MemberStoreAccount $account): bool
    {
        return $this->can($user, PermissionEnum::STORE_CREDIT_MANAGE_LIMIT->value)
            && $this->sameOrganization($user, $account);
    }

    public function suspend(User $user, MemberStoreAccount $account): bool
    {
        return $this->can($user, PermissionEnum::STORE_CREDIT_MANAGE->value)
            && $this->sameOrganization($user, $account);
    }

    public function adjust(User $user, MemberStoreAccount $account): bool
    {
        return $this->can($user, PermissionEnum::STORE_CREDIT_ADJUST->value)
            && $this->sameOrganization($user, $account);
    }

    public function cashFund(User $user, MemberStoreAccount $account): bool
    {
        return $this->can($user, PermissionEnum::STORE_CREDIT_CASHIER->value)
            && $this->sameOrganization($user, $account);
    }

    public function report(User $user): bool
    {
        return $this->canAny($user, [
            PermissionEnum::STORE_CREDIT_REPORT->value,
            PermissionEnum::STORE_CREDIT_MANAGE->value,
        ]);
    }

    public function isOwner(User $user, MemberStoreAccount $account): bool
    {
        $member = CooperativeMember::query()
            ->whereKey($account->cooperative_member_id)
            ->value('user_id');

        return $member !== null && $member === $user->id;
    }
}
