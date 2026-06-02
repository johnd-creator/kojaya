<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\User;
use App\Models\Vendor;

class VendorPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAny($user, [
            PermissionEnum::VENDORS_MANAGE->value,
            PermissionEnum::PO_VIEW_ALL->value,
            PermissionEnum::PR_VIEW_ALL->value,
        ]);
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $this->viewAny($user) && $this->sameOrganization($user, $vendor);
    }

    public function manage(User $user): bool
    {
        return $this->can($user, PermissionEnum::VENDORS_MANAGE->value);
    }
}
