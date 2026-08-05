<?php

declare(strict_types=1);

namespace App\Documentation;

use App\Models\User;
use App\Services\Authorization\PrimaryRoleResolver;

/**
 * Authorization helper for the documentation center.
 *
 * The documentation center follows the same authority as the rest of
 * the application:
 *  - role filtering uses {@see PrimaryRoleResolver} for the primary
 *    role, and {@see ArticleRepository::resolveTargetRoles()} for the
 *    full multi-role mapping;
 *  - permission filtering uses the user's actual permission set (no
 *    short-circuit or any-match that hides the underlying `all` vs
 *    `any` distinction);
 *  - member access lifecycle (e.g. onboarding/validation) does not
 *    grant access to articles whose `target_role` does not include
 *    the user — it only affects which menu items appear in the
 *    member portal.
 */
final class ArticleAuthorizer
{
    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly PrimaryRoleResolver $roleResolver,
    ) {}

    public function canView(User $user, Article $article): bool
    {
        if (! $article->isPublished()) {
            return false;
        }

        // System Admin sees everything; the application grants them
        // the same power via Gate::before for all other abilities.
        if ($user->hasRole('System Admin')) {
            return true;
        }

        $targets = $this->articles->resolveTargetRoles($user->getRoleNames()->all());
        $roleMatch = false;
        foreach ($article->roles() as $role) {
            if ($role === 'all' || in_array($role, $targets, true)) {
                $roleMatch = true;
                break;
            }
        }

        if (! $roleMatch) {
            return false;
        }

        $required = $article->permissions();
        if ($required === []) {
            return true;
        }

        $userPermissions = $user->getAllPermissions()->pluck('name')->all();

        return $article->permissionMode() === 'all'
            ? $this->userHasAll($userPermissions, $required)
            : $this->userHasAny($userPermissions, $required);
    }

    public function canViewAny(User $user): bool
    {
        // Anyone authenticated may open the landing page; per-article
        // filtering happens in {@see self::filterVisible()}.
        return $user !== null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Article>
     */
    public function filterVisible(User $user): \Illuminate\Support\Collection
    {
        if ($user->hasRole('System Admin')) {
            return $this->articles->published();
        }

        return $this->articles
            ->visibleTo(
                $user->getRoleNames()->all(),
                $user->getAllPermissions()->pluck('name')->all(),
            );
    }

    /**
     * @param  list<string>  $userPermissions
     * @param  list<string>  $required
     */
    private function userHasAll(array $userPermissions, array $required): bool
    {
        $owned = array_flip(array_map('strval', $userPermissions));
        foreach ($required as $permission) {
            if (! isset($owned[$permission])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $userPermissions
     * @param  list<string>  $required
     */
    private function userHasAny(array $userPermissions, array $required): bool
    {
        $owned = array_flip(array_map('strval', $userPermissions));
        foreach ($required as $permission) {
            if (isset($owned[$permission])) {
                return true;
            }
        }

        return false;
    }
}
