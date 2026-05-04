<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionEnum::PO_VIEW_ALL->value);
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->can($user, PermissionEnum::PO_VIEW_ALL->value);
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionEnum::PO_CREATE->value);
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->can($user, PermissionEnum::PO_CREATE->value);
    }
}
