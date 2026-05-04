<?php

namespace Database\Factories;

use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkOrderChecklist>
 */
class WorkOrderChecklistFactory extends Factory
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
            'item_name' => fake()->words(3, true),
            'item_description' => fake()->optional()->sentence(),
            'is_checked' => false,
            'notes' => fake()->optional()->sentence(),
            'checked_by' => null,
            'checked_at' => null,
        ];
    }
}
