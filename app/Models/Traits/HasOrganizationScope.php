<?php

namespace App\Models\Traits;

use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Provides the legacy ERP organization scope for models outside the
 * cooperative authorization registry.
 */
trait HasOrganizationScope
{
    /**
     * Scope: filter records by the current user's organization.
     *
     * Usage: Employee::query()->forUser()->get();
     */
    public function scopeForUser(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0'); // guest → no data
        }

        $scope = app(OrganizationScopeService::class);

        return $scope->scopeVisibleTo($query, $user, $scope->globalPermissionFor($this));
    }

    /**
     * Scope: filter records by a specific organization (and optionally its
     * children). Useful for Admin Pusat "switch view" feature.
     */
    public function scopeForOrganization(Builder $query, string $organizationId): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $scope = app(OrganizationScopeService::class);
        $selectedOrganizationId = $scope->assertOrganizationIdentifier($organizationId);
        $visibility = $scope->visibilityFor($user, $scope->globalPermissionFor($this));

        if (! $visibility->global && (string) $visibility->organizationId !== $selectedOrganizationId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('The selected organization is outside the user scope.');
        }

        return $query->where($this->qualifyColumn('organization_id'), $selectedOrganizationId);
    }

    /**
     * Scope: filter by active organization from session or show all for consolidated view.
     *
     * This method reads the active organization from the session and applies the filter.
     * If no active organization is set in session, it returns all records (consolidated view).
     * This is useful for dashboards and reports that support both organization-specific
     * and consolidated views.
     *
     * Usage: Model::query()->forActiveOrganization()->get();
     */
    public function scopeForActiveOrganization(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $scope = app(OrganizationScopeService::class);
        $globalPermission = $scope->globalPermissionFor($this);
        $activeOrgId = session('active_organization_id');

        if (! $activeOrgId) {
            return $scope->scopeVisibleTo($query, $user, $globalPermission);
        }

        $selectedOrganizationId = $scope->assertOrganizationIdentifier((string) $activeOrgId);
        $visibility = $scope->visibilityFor($user, $globalPermission);

        if (! $visibility->global && (string) $visibility->organizationId !== $selectedOrganizationId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('The selected organization is outside the user scope.');
        }

        return $query->where($this->qualifyColumn('organization_id'), $selectedOrganizationId);
    }
}
