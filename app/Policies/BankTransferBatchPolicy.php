<?php

namespace App\Policies;

use App\Models\BankTransferBatch;
use App\Models\User;

class BankTransferBatchPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'manage_bank_batch');
    }

    public function view(User $user, BankTransferBatch $batch): bool
    {
        return $this->can($user, 'manage_bank_batch')
            && ($this->can($user, 'view_bank_batch_all') || $this->sameOrganization($user, $batch));
    }

    public function export(User $user, BankTransferBatch $batch): bool
    {
        return $this->can($user, 'manage_bank_batch')
            && ($this->can($user, 'view_bank_batch_all') || $this->sameOrganization($user, $batch));
    }

    public function viewForReconciliation(User $user, BankTransferBatch $batch): bool
    {
        return $this->can($user, 'manage_bank_reconciliation')
            && ($this->can($user, 'view_bank_batch_all') || $this->sameOrganization($user, $batch));
    }
}
