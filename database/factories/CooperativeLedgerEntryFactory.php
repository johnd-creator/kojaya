<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CooperativeLedgerEntry>
 */
class CooperativeLedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cooperative_member_id' => \App\Models\CooperativeMember::factory(),
            'cooperative_payment_id' => null,
            'source_type' => null,
            'source_id' => null,
            'entry_type' => fake()->randomElement(['SAVINGS_DEPOSIT', 'LOAN_PAYMENT', 'LOAN_DISBURSEMENT']),
            'debit' => 0,
            'credit' => fake()->randomFloat(2, 50000, 500000),
            'period' => now()->format('Y-m'),
            'description' => fake()->sentence(),
            'posted_at' => now()->toDateString(),
        ];
    }
}
