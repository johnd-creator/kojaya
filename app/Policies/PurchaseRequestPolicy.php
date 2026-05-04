<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::PR_VIEW_ALL->value);
    }

    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $this->can($user, PermissionEnum::PR_VIEW_ALL->value);
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::PR_CREATE->value);
    }

    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $this->can($user, PermissionEnum::PR_CREATE->value);
    }

    public function approve(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $this->can($user, PermissionEnum::PR_APPROVE->value);
    }
}
