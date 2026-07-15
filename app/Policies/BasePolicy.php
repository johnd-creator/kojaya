<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
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

    protected function sameOrganization(User $user, Model $model): bool
    {
        try {
            app(OrganizationScopeService::class)->assertVisible($user, $model);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
