<?php

namespace App\Contracts;

use App\Enums\PermissionEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides consistent organization-level data isolation for cooperative queries.
 *
 * Centralized so that list, detail, statistics, export, and batch operations
 * all derive their scope from a single source of truth.
 */
class OrganizationScopedQueryService
{
    /**
     * Apply organization scoping to a cooperative query builder.
     *
     * Users with the `view_cooperative_all` permission can see all organizations.
     * All other users are restricted to their own `organization_id`.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can(PermissionEnum::COOPERATIVE_VIEW_ALL->value)) {
            return $query;
        }

        if (method_exists($query->getModel(), 'member') && ! in_array('organization_id', $query->getModel()->getFillable(), true)) {
            return $query->whereHas('member', function (Builder $memberQuery) use ($user): void {
                if ($user->organization_id === null) {
                    $memberQuery->whereNull('organization_id');

                    return;
                }

                $memberQuery->where('organization_id', $user->organization_id);
            });
        }

        if ($user->organization_id === null) {
            return $query->whereNull('organization_id');
        }

        return $query->where('organization_id', $user->organization_id);
    }

    /**
     * Determine whether the user can see all organizations.
     */
    public function canViewAllOrganizations(User $user): bool
    {
        return $user->can(PermissionEnum::COOPERATIVE_VIEW_ALL->value);
    }

    /**
     * Resolve the organization scope for the given user.
     * Returns null when the user can see all organizations (no filter needed).
     */
    public function scopeOrganizationIdFor(User $user): ?string
    {
        if ($this->canViewAllOrganizations($user)) {
            return null;
        }

        return $user->organization_id;
    }
}
