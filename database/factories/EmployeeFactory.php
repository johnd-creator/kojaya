<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
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
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(['M', 'F']),
            'birth_date' => fake()->date(),
            'hire_date' => fake()->date(),
            'status' => 'ACTIVE',
            'phtkp_status' => 'TK/0',
            'is_npwp_available' => true,
            'number_of_dependents' => 0,
        ];
    }
}
