<?php

namespace Database\Factories;

use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanPayment>
 */
class LoanPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'loan_installment_id' => LoanInstallment::factory(),
            'cooperative_member_id' => CooperativeMember::factory(),
            'user_id' => User::factory(),
            'amount' => 570000,
            'principal_amount' => 500000,
            'interest_amount' => 45000,
            'fee_amount' => 25000,
            'penalty_amount' => 0,
            'paid_at' => now()->toDateString(),
            'payment_method' => 'CASH',
            'status' => 'APPROVED',
        ];
    }
}
