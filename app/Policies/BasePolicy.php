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

    protected function sameOrganization(User $user, Model $model): bool
    {
        $organizationId = $model->getAttribute('organization_id');

        if ($organizationId === null && method_exists($model, 'member')) {
            $member = $model->relationLoaded('member')
                ? $model->getRelation('member')
                : $model->member()->first(['organization_id']);
            $organizationId = $member?->organization_id;
        }

        return ($organizationId === null && $user->organization_id === null)
            || ($organizationId !== null && (string) $organizationId === (string) $user->organization_id);
    }
}
