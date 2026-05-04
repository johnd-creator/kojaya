<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Finance Pusat', 'Finance Unit']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Finance Pusat'])
            || ($this->hasAnyRole($user, ['Finance Unit']) && $this->sameOrganization($user, $invoice));
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
