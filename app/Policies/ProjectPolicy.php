<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Project Manager', 'Site Manager', 'Admin Unit']);
    }

    public function view(User $user, Project $project): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Project Manager'])
            || ($this->hasAnyRole($user, ['Site Manager', 'Admin Unit']) && $this->sameOrganization($user, $project));
    }

    public function update(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }
}
