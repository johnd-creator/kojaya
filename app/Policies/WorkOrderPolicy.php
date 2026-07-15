<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_work_order_all')
            || $this->can($user, 'view_work_order_unit');
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return (
            (string) $workOrder->assigned_to === (string) $user->id
            || $this->canAny($user, ['view_work_order_all', 'view_work_order_unit'])
        ) && $this->sameOrganization($user, $workOrder);
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $this->view($user, $workOrder);
    }
}
