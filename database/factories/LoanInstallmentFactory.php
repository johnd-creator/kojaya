<?php

namespace Database\Factories;

use App\Enums\InstallmentStatus;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanInstallment>
 */
class LoanInstallmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'installment_no' => 1,
            'due_date' => now()->addMonth()->toDateString(),
            'principal_amount' => 500000,
            'interest_amount' => 45000,
            'fee_amount' => 25000,
            'penalty_amount' => 0,
            'amount_due' => 570000,
            'amount_paid' => 0,
            'status' => InstallmentStatus::Pending,
        ];
    }
}
