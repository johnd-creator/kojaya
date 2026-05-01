<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 1000000, 50000000);
        $taxAmount = round($amount * 0.11, 2);
        $totalAmount = round($amount + $taxAmount, 2);

        return [
            'organization_id' => \App\Models\Organization::factory(),
            'unit_id' => \App\Models\Organization::factory(),
            'client_id' => \App\Models\Client::factory(),
            'invoice_no' => fake()->unique()->numerify('INV-######'),
            'invoice_date' => fake()->date(),
            'due_date' => fake()->dateTimeBetween('+30 days', '+90 days')->format('Y-m-d'),
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'status' => fake()->randomElement(['DRAFT', 'PENDING', 'APPROVED', 'PAID']),
            'notes' => fake()->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'DRAFT',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDING',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'APPROVED',
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PAID',
        ]);
    }
}
