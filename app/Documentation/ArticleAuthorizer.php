<?php

declare(strict_types=1);

namespace App\Documentation;

use App\Models\User;

/**
 * Authorization helper for the documentation center.
 *
 * The documentation center follows the same authority as the rest of
 * the application:
 *
 *  - role filtering uses {@see DocumentationRoleResolver} for the
 *    user's *primary/effective* documentation role, not a Spatie
 *    role union. Multi-role users are routed to a single bucket
 *    (e.g. Admin Koperasi + Manajer → `manajer_koperasi` because
 *    Manajer is the higher-priority role per PrimaryRoleResolver).
 *  - `roles: [all]` and `roles: [shared]` articles are readable by
 *    every authenticated user.
 *  - System Admin sees every published article regardless of role.
 *  - permission filtering uses the user's actual permission set,
 *    with `permission_mode` of `all` (default, safer) or `any`.
 */
final class ArticleAuthorizer
{
    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly DocumentationRoleResolver $roleResolver,
    ) {}

    public function canView(User $user, Article $article): bool
    {
        if (! $article->isPublished()) {
            return false;
        }

        $docRole = $this->roleResolver->resolve($user);

        // System Admin sees everything; the application grants them the
        // same power via Gate::before for all other abilities.
        if ($docRole === DocumentationRoleResolver::ROLE_SYSTEM_ADMIN) {
            return true;
        }

        // `all` and `shared` are readable by every authenticated user.
        foreach ($article->roles() as $role) {
            if ($role === 'all' || $role === 'shared') {
                return $this->permissionsAllow($user, $article);
            }
        }

        // Otherwise the article must explicitly list the user's doc role.
        $roleMatch = false;
        foreach ($article->roles() as $role) {
            if ($docRole === DocumentationRoleResolver::ROLE_ADMIN_PUSAT) {
                // Platform admin only sees shared content via this path.
                continue;
            }
            if ($docRole !== DocumentationRoleResolver::ROLE_GENERIC && $role === $docRole) {
                $roleMatch = true;
                break;
            }
        }

        if (! $roleMatch) {
            return false;
        }

        return $this->permissionsAllow($user, $article);
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
        if ($this->roleResolver->resolve($user) === DocumentationRoleResolver::ROLE_SYSTEM_ADMIN) {
            return $this->articles->published();
        }

        return $this->articles->all()->filter(fn (Article $a): bool => $this->canView($user, $a))->values();
    }

    private function permissionsAllow(User $user, Article $article): bool
    {
        $required = $article->permissions();
        if ($required === []) {
            return true;
        }

        $userPermissions = $user->getAllPermissions()->pluck('name')->all();

        return $article->permissionMode() === 'all'
            ? $this->userHasAll($userPermissions, $required)
            : $this->userHasAny($userPermissions, $required);
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
