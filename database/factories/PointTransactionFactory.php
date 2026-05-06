<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointTransaction>
 */
class PointTransactionFactory extends Factory
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
            'transaction_type' => fake()->randomElement(['EARNED', 'REDEEMED', 'EXPIRED']),
            'points' => fake()->numberBetween(10, 500),
            'balance_before' => 0,
            'balance_after' => fake()->numberBetween(10, 500),
            'source_type' => null,
            'source_id' => null,
            'reference_number' => null,
            'description' => fake()->sentence(),
            'posted_at' => now()->toDateString(),
            'expires_at' => now()->addYear()->toDateString(),
            'metadata' => null,
        ];
    }
}
