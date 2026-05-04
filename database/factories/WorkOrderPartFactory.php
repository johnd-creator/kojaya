<?php

namespace Database\Factories;

use App\Models\SparePart;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkOrderPart>
 */
class WorkOrderPartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'work_order_id' => WorkOrder::factory(),
            'spare_part_id' => SparePart::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity_used' => fake()->randomFloat(2, 1, 5),
            'notes' => fake()->optional()->sentence(),
            'used_by' => null,
            'used_at' => null,
        ];
    }
}
