<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RewardRedemption>
 */
class RewardRedemptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reward_id' => \App\Models\Reward::factory(),
            'cooperative_member_id' => \App\Models\CooperativeMember::factory(),
            'point_transaction_id' => null,
            'quantity' => 1,
            'points_used' => fake()->numberBetween(100, 1000),
            'delivery_address' => fake()->optional()->address(),
            'status' => 'PENDING',
            'notes' => null,
            'redeemed_at' => now(),
            'processed_at' => null,
        ];
    }
}
