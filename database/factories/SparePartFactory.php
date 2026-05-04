<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SparePart>
 */
class SparePartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('SP-#####'),
            'name' => fake()->words(2, true),
            'specification' => fake()->sentence(),
            'unit' => fake()->randomElement(['PCS', 'BOX', 'LITER', 'KG']),
            'min_stock' => fake()->randomFloat(2, 1, 10),
            'max_stock' => fake()->randomFloat(2, 20, 100),
            'reorder_level' => fake()->randomFloat(2, 5, 15),
            'category' => fake()->randomElement(['ELECTRICAL', 'MECHANICAL', 'GENERAL']),
            'organization_id' => Organization::factory(),
            'is_active' => true,
        ];
    }
}
