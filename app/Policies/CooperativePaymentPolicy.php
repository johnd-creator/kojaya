<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\CooperativePayment;
use App\Models\User;

class CooperativePaymentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_PAYMENT_MANAGE->value);
    }

    public function view(User $user, CooperativePayment $cooperativePayment): bool
    {
        return ($this->can($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value)
            || ($this->can($user, PermissionEnum::COOPERATIVE_PAYMENT_MANAGE->value)
                && $this->sameOrganization($user, $cooperativePayment)))
            || ($this->can($user, PermissionEnum::COOPERATIVE_MEMBER_VIEW->value)
                && $cooperativePayment->member?->user_id === $user->id
                && $this->sameOrganization($user, $cooperativePayment));
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_PAYMENT_MANAGE->value);
    }

    public function approve(User $user, CooperativePayment $cooperativePayment): bool
    {
        return $this->can($user, PermissionEnum::COOPERATIVE_PAYMENT_MANAGE->value)
            && ($this->can($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value) || $this->sameOrganization($user, $cooperativePayment));
    }
}
