<?php

namespace App\Contracts;

use App\Enums\PermissionEnum;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use App\Support\OrganizationVisibility;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides consistent organization-level data isolation for cooperative queries.
 *
 * Centralized so that list, detail, statistics, export, and batch operations
 * all derive their scope from a single source of truth.
 */
class OrganizationScopedQueryService
{
    public function __construct(
        private readonly OrganizationScopeService $scopeService,
    ) {}

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
        return $this->scopeService->scopeVisibleTo($query, $user, PermissionEnum::COOPERATIVE_VIEW_ALL->value);
    }

    /**
     * Determine whether the user can see all organizations.
     */
    public function canViewAllOrganizations(User $user): bool
    {
        return $this->scopeService->visibilityFor($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value)->global;
    }

    /**
     * Resolve the organization scope for the given user.
     * Returns null when the user can see all organizations (no filter needed).
     */
    public function scopeOrganizationIdFor(User $user): ?string
    {
        return $this->scopeService->visibilityFor($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value)->organizationId;
    }

    public function visibilityFor(User $user): OrganizationVisibility
    {
        return $this->scopeService->visibilityFor($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value);
    }
}
