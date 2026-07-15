<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_invoice_all');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->canAny($user, ['view_invoice_all', 'manage_chart_of_accounts'])
            && $this->sameOrganization($user, $invoice);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
