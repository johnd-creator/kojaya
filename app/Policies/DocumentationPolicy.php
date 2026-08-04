<?php

namespace App\Policies;

use App\Models\DocumentationArticle;
use App\Models\User;

class DocumentationPolicy extends BasePolicy
{
    /**
     * Any authenticated user may browse the documentation center landing page;
     * article-level filtering happens in the controller via the
     * `visibleTo` scope.
     */
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can read a single article. The role mapping
     * in `DocumentationArticle::targetRolesForUser()` is the authoritative
     * source for which target roles a Spatie role satisfies.
     */
    public function view(User $user, DocumentationArticle $documentationArticle): bool
    {
        if ($documentationArticle->published_at === null) {
            return false;
        }

        $targets = DocumentationArticle::targetRolesForUser(
            $user->getRoleNames()->all(),
        );

        if (! in_array($documentationArticle->target_role, $targets, true)) {
            return false;
        }

        $required = $documentationArticle->required_permissions ?? [];

        if ($required === []) {
            return true;
        }

        foreach ($required as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Only System Admin (superadmin) may author or curate articles. The
     * documentation center is curated content, not user-generated.
     */
    public function create(User $user): bool
    {
        return $this->can($user, 'manage_roles');
    }

    public function update(User $user, DocumentationArticle $documentationArticle): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, DocumentationArticle $documentationArticle): bool
    {
        return $this->create($user);
    }
}
