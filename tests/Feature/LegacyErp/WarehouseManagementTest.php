<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SparePart;
use App\Models\SparePartStock;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WarehouseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function storageManager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Project Manager');

        return $user;
    }

    public function test_user_can_create_warehouse(): void
    {
        $user = $this->storageManager();
        $organization = Organization::factory()->create();

        $this->actingAs($user)
            ->from(route('warehouses.create'))
            ->post(route('warehouses.store'), [
                'code' => 'WH-OPS',
                'name' => 'Gudang Operasional',
                'organization_id' => $organization->id,
                'location' => 'Bandung',
                'type' => 'STORAGE',
            ])
            ->assertRedirect(route('warehouses.index'));

        $this->assertDatabaseHas('warehouses', [
            'code' => 'WH-OPS',
            'name' => 'Gudang Operasional',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_warehouse_code_must_be_unique(): void
    {
        $user = $this->storageManager();
        $organization = Organization::factory()->create();
        Warehouse::factory()->create([
            'code' => 'WH-EXIST',
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->from(route('warehouses.create'))
            ->post(route('warehouses.store'), [
                'code' => 'WH-EXIST',
                'name' => 'Gudang Duplikat',
                'organization_id' => $organization->id,
                'location' => 'Jakarta',
                'type' => 'REPAIR',
            ])
            ->assertRedirect(route('warehouses.create'))
            ->assertSessionHasErrors(['code']);
    }

    public function test_user_can_view_warehouse_with_stock_relations(): void
    {
        $user = $this->storageManager();
        $organization = Organization::factory()->create();
        $warehouse = Warehouse::factory()->create(['organization_id' => $organization->id]);
        $sparePart = SparePart::factory()->create(['organization_id' => $organization->id]);
        SparePartStock::create([
            'spare_part_id' => $sparePart->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 12,
            'reserved_quantity' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('warehouses.show', $warehouse->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Warehouses/Show')
                ->where('warehouse.id', $warehouse->id)
                ->has('warehouse.stocks', 1)
            );
    }
}
