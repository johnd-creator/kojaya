<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PurchaseRequest;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'unit_id' => $organization,
            'purchase_request_id' => PurchaseRequest::factory()->state([
                'organization_id' => $organization,
                'unit_id' => $organization,
            ]),
            'vendor_id' => Vendor::factory()->state(['organization_id' => $organization]),
            'warehouse_id' => Warehouse::factory()->state(['organization_id' => $organization]),
            'po_no' => fake()->unique()->numerify('PO-######'),
            'status' => fake()->randomElement(['ISSUED', 'APPROVED', 'CLOSED']),
            'total_amount' => fake()->randomFloat(2, 1000000, 100000000),
            'issued_at' => now(),
        ];
    }
}
