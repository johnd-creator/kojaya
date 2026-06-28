<?php

namespace App\Contracts\Cooperative;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\User;

interface LoanServiceContract
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function apply(array $data, ?User $actor = null): Loan;

    public function managerReview(Loan $loan, ?User $actor = null, ?string $note = null): Loan;

    public function approve(Loan $loan, ?User $actor = null, ?string $note = null): Loan;

    public function reject(Loan $loan, ?User $actor = null, string $reason = ''): Loan;

    public function disburse(Loan $loan, ?User $actor = null, ?string $referenceNo = null): Loan;

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordPayment(Loan $loan, array $data, ?User $actor = null): LoanPayment;

    public function writeOff(Loan $loan, ?User $actor = null, ?string $note = null): Loan;
}
