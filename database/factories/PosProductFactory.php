<?php

namespace Database\Factories;

use App\Models\PosCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PosProduct>
 */
class PosProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pos_category_id' => PosCategory::factory(),
            'sku' => 'SKU-'.fake()->unique()->numerify('######'),
            'barcode' => fake()->optional()->ean13(),
            'name' => fake()->words(3, true),
            'cost_price' => fake()->numberBetween(5000, 25000),
            'sale_price' => fake()->numberBetween(26000, 50000),
            'stock' => fake()->numberBetween(0, 100),
            'minimum_stock' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn () => [
            'stock' => 1,
            'minimum_stock' => 5,
        ]);
    }
}
