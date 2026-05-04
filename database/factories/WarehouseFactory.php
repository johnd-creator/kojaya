<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('WH-###'),
            'name' => fake()->words(2, true),
            'organization_id' => Organization::factory(),
            'location' => fake()->city(),
            'type' => fake()->randomElement(['STORAGE', 'REPAIR', 'DISPOSAL']),
            'is_active' => true,
        ];
    }
}
