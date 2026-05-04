<?php

namespace Database\Factories;

use App\Enums\LoanStatus;
use App\Models\CooperativeMember;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $loanType = LoanType::factory();
        $member = CooperativeMember::factory()->state(['organization_id' => $organization]);
        $principal = 3000000;
        $termMonths = 6;
        $interestRate = 1.5;
        $monthlyInterest = $principal * ($interestRate / 100);
        $installmentAmount = round(($principal / $termMonths) + $monthlyInterest, 2);
        $totalInterest = round($monthlyInterest * $termMonths, 2);
        $adminFee = 25000;
        $totalAmount = round($principal + $totalInterest + $adminFee, 2);

        return [
            'cooperative_member_id' => $member,
            'organization_id' => $organization,
            'loan_type_id' => $loanType,
            'user_id' => User::factory()->state(['organization_id' => $organization]),
            'principal_amount' => $principal,
            'interest_rate' => $interestRate,
            'admin_fee' => $adminFee,
            'late_fee_per_day' => 2500,
            'term_months' => $termMonths,
            'installment_amount' => $installmentAmount,
            'total_interest_amount' => $totalInterest,
            'total_amount' => $totalAmount,
            'outstanding_amount' => $totalAmount,
            'applied_at' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'status' => LoanStatus::Applied,
            'purpose' => fake()->sentence(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => LoanStatus::Active]);
    }
}
