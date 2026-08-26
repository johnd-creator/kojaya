<?php

namespace App\Services\Cooperative;

use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

class PosProductAccessService
{
    /**
     * @param  Builder<PosProduct>  $query
     * @return Builder<PosProduct>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($this->isGlobalOperator($user)) {
            return $query;
        }

        return $query->where('organization_id', $this->organizationIdFor($user));
    }

    public function assertCanOperate(User $user, PosProduct $product): void
    {
        if ($this->isGlobalOperator($user)) {
            return;
        }

        $organizationId = $this->organizationIdFor($user);

        if ($product->organization_id === null || (string) $product->organization_id !== $organizationId) {
            throw new AuthorizationException('The product is outside the user organization.');
        }
    }

    public function assertCanCreate(User $user): string
    {
        if (! $this->isGlobalOperator($user)) {
            return $this->organizationIdFor($user);
        }

        $organizationId = session('active_organization_id') ?? $user->organization_id;

        if ($organizationId === null || $organizationId === '') {
            throw new AuthorizationException('An explicit target organization is required to create a POS product.');
        }

        if (! Organization::query()->whereKey($organizationId)->exists()) {
            throw new AuthorizationException('The target organization is invalid.');
        }

        return (string) $organizationId;
    }

    private function isGlobalOperator(User $user): bool
    {
        return $user->can('view_cooperative_all');
    }

    private function organizationIdFor(User $user): string
    {
        if ($user->organization_id === null || $user->organization_id === '') {
            throw new AuthorizationException('A cooperative organization is required for this operation.');
        }

        return (string) $user->organization_id;
    }
}
