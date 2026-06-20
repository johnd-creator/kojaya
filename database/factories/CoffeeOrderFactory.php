<?php

namespace Database\Factories;

use App\Models\CoffeeOrder;
use App\Models\CooperativeMember;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoffeeOrder>
 */
class CoffeeOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cooperative_member_id' => CooperativeMember::factory()->active(),
            'pos_product_id' => PosProduct::factory(),
            'pos_transaction_id' => fn (array $attributes): int => PosTransaction::query()->create([
                'transaction_no' => 'POS-'.fake()->unique()->numerify('########'),
                'cooperative_member_id' => $attributes['cooperative_member_id'],
                'subtotal' => 18000,
                'discount_amount' => 0,
                'total_amount' => 18000,
                'status' => 'COMPLETED',
                'sold_at' => now(),
            ])->id,
            'quantity' => fake()->numberBetween(1, 3),
            'status' => CoffeeOrder::STATUS_RECEIVED,
            'customization' => [
                'sugar_level' => fake()->randomElement(['Normal', 'Less Sugar', 'No Sugar']),
                'ice_level' => fake()->randomElement(['Normal', 'Less Ice', 'Warm']),
                'cup_size' => fake()->randomElement(['Reguler', 'Large']),
            ],
            'received_at' => now(),
        ];
    }
}
