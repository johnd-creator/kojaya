<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanType>
 */
class LoanTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('LN-###'),
            'name' => fake()->randomElement(['Pinjaman Umum', 'Pinjaman Darurat', 'Pinjaman Pendidikan']),
            'description' => fake()->sentence(),
            'interest_rate' => fake()->randomFloat(2, 0.5, 3),
            'admin_fee' => fake()->randomFloat(2, 10000, 75000),
            'late_fee_per_day' => fake()->randomFloat(2, 1000, 5000),
            'min_amount' => 500000,
            'max_amount' => 25000000,
            'min_term_months' => 3,
            'max_term_months' => 24,
            'eligibility_rules' => null,
            'is_active' => true,
        ];
    }
}
