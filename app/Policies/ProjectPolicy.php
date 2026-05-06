<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_project_all')
            || $this->can($user, 'view_project_unit');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->can($user, 'view_project_all')
            || ($this->can($user, 'view_project_unit') && $this->sameOrganization($user, $project));
    }

    public function update(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }
}
