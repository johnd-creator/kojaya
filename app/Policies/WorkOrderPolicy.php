<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Project Manager', 'Site Manager', 'Admin Unit', 'Technician']);
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return (string) $workOrder->assigned_to === (string) $user->id
            || $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Project Manager'])
            || ($this->hasAnyRole($user, ['Site Manager', 'Admin Unit']) && $this->sameOrganization($user, $workOrder));
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $this->view($user, $workOrder);
    }
}
