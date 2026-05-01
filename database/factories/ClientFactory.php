<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('CLI######'),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'tax_id' => fake()->numerify('NPWP#############'),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'client_type' => fake()->randomElement(['PLN', 'PRIVATE']),
            'organization_id' => \App\Models\Organization::factory(),
        ];
    }

    public function pln(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_type' => 'PLN',
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_type' => 'PRIVATE',
        ]);
    }
}
