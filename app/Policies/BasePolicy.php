<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    protected function can(User $user, string $permission): bool
    {
        return $user->can($permission);
    }

    protected function canAny(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    protected function hasAnyRole(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    protected function sameOrganization(User $user, Model $model): bool
    {
        $organizationId = $model->getAttribute('organization_id');

        return $organizationId !== null && (string) $organizationId === (string) $user->organization_id;
    }
}
