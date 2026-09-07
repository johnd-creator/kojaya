<?php

namespace App\Services\Cooperative;

use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class PosCategoryAccessService
{
    /**
     * @param  Builder<PosCategory>  $query
     * @return Builder<PosCategory>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($this->isGlobalOperator($user)) {
            return $query;
        }

        return $query->where('organization_id', $this->organizationIdFor($user));
    }

    /**
     * Resolve a category within the user's visible organization scope,
     * failing closed with a 404 ModelNotFoundException if outside tenant scope.
     */
    public function resolveVisible(PosCategory|int|string $category, User $user): PosCategory
    {
        if ($category instanceof PosCategory) {
            if ($this->isGlobalOperator($user)) {
                return $category;
            }

            $organizationId = $this->organizationIdFor($user);

            if ($category->organization_id === null || (string) $category->organization_id !== (string) $organizationId) {
                throw (new ModelNotFoundException)->setModel(PosCategory::class, [$category->id]);
            }

            return $category;
        }

        return $this->scopeVisibleTo(PosCategory::query(), $user)->findOrFail($category);
    }

    public function assertCanOperate(User $user, PosCategory $category): void
    {
        $this->resolveVisible($category, $user);
    }

    public function assertCanCreate(User $user, ?string $requestOrgId = null): string
    {
        if (! $this->isGlobalOperator($user)) {
            return $this->organizationIdFor($user);
        }

        $organizationId = $requestOrgId ?? session('active_organization_id') ?? $user->organization_id;

        if ($organizationId === null || $organizationId === '') {
            throw new AuthorizationException('An explicit target organization is required to create a POS category.');
        }

        if (! Organization::query()->whereKey($organizationId)->exists()) {
            throw new AuthorizationException('The target organization is invalid.');
        }

        return (string) $organizationId;
    }

    public function assertBelongsToOrganization(?int $categoryId, string $organizationId): void
    {
        if ($categoryId === null) {
            return;
        }

        $category = PosCategory::query()->whereKey($categoryId)->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'pos_category_id' => 'The selected category is invalid.',
            ]);
        }

        if ($category->organization_id !== null && (string) $category->organization_id !== (string) $organizationId) {
            throw ValidationException::withMessages([
                'pos_category_id' => 'The selected category does not belong to the product organization.',
            ]);
        }
    }

    public function categoryBelongsToOrganization(PosCategory|int|string $category, string $organizationId): bool
    {
        $categoryId = $category instanceof PosCategory ? $category->id : $category;

        return PosCategory::query()
            ->whereKey($categoryId)
            ->where('organization_id', $organizationId)
            ->exists();
    }

    public function isVisibleId(int|string $categoryId, User $user): bool
    {
        try {
            return $this->scopeVisibleTo(PosCategory::query(), $user)
                ->whereKey($categoryId)
                ->exists();
        } catch (AuthorizationException) {
            return false;
        }
    }

    public function isGlobalOperator(User $user): bool
    {
        return $user->can('view_cooperative_all');
    }

    public function organizationIdFor(User $user): string
    {
        if ($user->organization_id === null || $user->organization_id === '') {
            throw new AuthorizationException('A cooperative organization is required for this operation.');
        }

        return (string) $user->organization_id;
    }
}
