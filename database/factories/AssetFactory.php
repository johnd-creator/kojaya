<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('AST-#####'),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['MACHINE', 'VEHICLE', 'BUILDING', 'TOOL']),
            'organization_id' => Organization::factory(),
            'status' => fake()->randomElement(['ACTIVE', 'INACTIVE', 'UNDER_MAINTENANCE']),
            'purchase_date' => fake()->date(),
            'serial_number' => fake()->bothify('SN-####-????'),
        ];
    }
}
