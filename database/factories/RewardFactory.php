<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reward>
 */
class RewardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => \App\Models\Organization::factory(),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['BARANG', 'DISKON', 'LAYANAN']),
            'description' => fake()->sentence(),
            'points_required' => fake()->numberBetween(100, 5000),
            'stock' => fake()->numberBetween(5, 100),
            'valid_until' => now()->addMonths(6)->toDateString(),
            'is_active' => true,
        ];
    }
}
