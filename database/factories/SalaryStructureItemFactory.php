<?php

namespace Database\Factories;

use App\Models\SalaryComponentType;
use App\Models\SalaryStructure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalaryStructureItem>
 */
class SalaryStructureItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'salary_structure_id' => SalaryStructure::factory(),
            'salary_component_type_id' => SalaryComponentType::factory(),
            'amount' => fake()->randomFloat(2, 500000, 5000000),
        ];
    }
}
