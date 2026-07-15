<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Provides the legacy ERP organization scope for models outside the
 * cooperative authorization registry.
 */
trait HasOrganizationScope
{
    /**
     * @var list<string>
     */
    protected static array $allAccessRoles = [
        'System Admin',
        'Admin Pusat',
        'HR Pusat',
        'Finance Pusat',
    ];

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

        if ($user->hasAnyRole(static::$allAccessRoles)) {
            return $query;
        }

        return $query->where(
            $this->getTable().'.organization_id',
            $user->organization_id,
        );
    }

    /**
     * Scope: filter records by a specific organization (and optionally its
     * children). Useful for Admin Pusat "switch view" feature.
     */
    public function scopeForOrganization(Builder $query, string $organizationId): Builder
    {
        return $query->where(
            $this->getTable().'.organization_id',
            $organizationId,
        );
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
        $activeOrgId = session('active_organization_id');

        if (! $activeOrgId) {
            return $this->scopeForUser($query);
        }

        return $query->where(
            $this->getTable().'.organization_id',
            $activeOrgId,
        );
    }
}
