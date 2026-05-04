<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SparePart;
use App\Models\SparePartStock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SparePartManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_spare_part(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($user)
            ->from(route('spare-parts.create'))
            ->post(route('spare-parts.store'), [
                'code' => 'SP-1001',
                'name' => 'Filter Oli',
                'specification' => 'Ukuran 20cm',
                'unit' => 'PCS',
                'min_stock' => 5,
                'max_stock' => 50,
                'reorder_level' => 10,
                'category' => 'MECHANICAL',
                'organization_id' => $organization->id,
            ])
            ->assertRedirect(route('spare-parts.index'));

        $this->assertDatabaseHas('spare_parts', [
            'code' => 'SP-1001',
            'name' => 'Filter Oli',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_low_stock_filter_only_returns_parts_below_minimum(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $warehouse = Warehouse::factory()->create(['organization_id' => $organization->id]);
        $lowStockPart = SparePart::factory()->create([
            'organization_id' => $organization->id,
            'min_stock' => 10,
            'reorder_level' => 12,
        ]);
        $healthyPart = SparePart::factory()->create([
            'organization_id' => $organization->id,
            'min_stock' => 5,
            'reorder_level' => 7,
        ]);

        SparePartStock::create([
            'spare_part_id' => $lowStockPart->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 4,
            'reserved_quantity' => 0,
        ]);
        SparePartStock::create([
            'spare_part_id' => $healthyPart->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('spare-parts.index', ['low_stock' => 1]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SpareParts/Index')
                ->has('spareParts', 1)
                ->where('spareParts.0.id', $lowStockPart->id)
            );
    }

    public function test_user_can_update_spare_part_stock(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $warehouse = Warehouse::factory()->create(['organization_id' => $organization->id]);
        $sparePart = SparePart::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->from(route('spare-parts.show', $sparePart->id))
            ->post(route('spare-parts.update-stock', $sparePart->id), [
                'warehouse_id' => $warehouse->id,
                'quantity' => 15,
                'type' => 'IN',
                'notes' => 'Stok awal',
            ])
            ->assertRedirect(route('spare-parts.show', $sparePart->id));

        $this->assertDatabaseHas('spare_part_stocks', [
            'spare_part_id' => $sparePart->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 15,
        ]);

        $this->actingAs($user)
            ->from(route('spare-parts.show', $sparePart->id))
            ->post(route('spare-parts.update-stock', $sparePart->id), [
                'warehouse_id' => $warehouse->id,
                'quantity' => 5,
                'type' => 'OUT',
                'notes' => 'Pemakaian',
            ])
            ->assertRedirect(route('spare-parts.show', $sparePart->id));

        $stock = SparePartStock::query()
            ->where('spare_part_id', $sparePart->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertSame('10.00', $stock->quantity);
    }
}
