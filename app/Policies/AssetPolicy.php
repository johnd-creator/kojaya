<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_asset_all')
            || $this->can($user, 'view_asset_unit');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->can($user, 'view_asset_all')
            || ($this->can($user, 'view_asset_unit') && $this->sameOrganization($user, $asset));
    }

    public function update(User $user, Asset $asset): bool
    {
        return $this->can($user, 'manage_asset')
            && ($this->can($user, 'view_asset_all') || $this->sameOrganization($user, $asset));
    }
}
