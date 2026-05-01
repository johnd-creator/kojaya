<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'code' => fake()->unique()->numerify('ORG#####'),
            'name' => fake()->company(),
            'level' => 'L0',
            'type' => 'HEAD_OFFICE',
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'radius' => 100,
            'is_active' => true,
        ];
    }
}
