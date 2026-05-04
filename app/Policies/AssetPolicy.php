<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Project Manager', 'Site Manager', 'Admin Unit', 'Technician']);
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Project Manager'])
            || ($this->hasAnyRole($user, ['Site Manager', 'Admin Unit', 'Technician']) && $this->sameOrganization($user, $asset));
    }

    public function update(User $user, Asset $asset): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Project Manager', 'Admin Unit'])
            && ($this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Project Manager']) || $this->sameOrganization($user, $asset));
    }
}
