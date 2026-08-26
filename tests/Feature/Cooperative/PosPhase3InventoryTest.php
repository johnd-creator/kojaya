<?php

namespace Tests\Feature\Cooperative;

use App\Models\Organization;
use App\Models\PosInventoryLocation;
use App\Models\PosInventoryStock;
use App\Models\PosProduct;
use App\Models\PosStockCount;
use App\Models\PosStockMovement;
use App\Models\PosStockReceipt;
use App\Models\PosStockTransfer;
use App\Models\PosSupplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PosPhase3InventoryTest extends TestCase
{
    use DatabaseMigrations;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->organization = Organization::factory()->create();
    }

    public function test_default_location_is_ensured_on_first_access(): void
    {
        $service = app(\App\Services\Cooperative\PosInventoryService::class);
        $default = $service->ensureDefaultLocation();

        $this->assertTrue($default->is_default);
        $this->assertSame('MAIN', $default->code);

        $again = $service->ensureDefaultLocation();
        $this->assertSame($default->id, $again->id);
    }

    public function test_receipt_increases_stock_at_location_and_records_movement(): void
    {
        $cashier = $this->cashier();
        $product = $this->product([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 0,
        ]);
        $supplier = PosSupplier::query()->create([
            'code' => 'SUP-001',
            'name' => 'Supplier Beras',
            'is_active' => true,
        ]);
        $service = app(\App\Services\Cooperative\PosInventoryService::class);
        $location = $service->ensureDefaultLocation();

        $this->actingAs($cashier)->post(route('cooperative.pos.inventory.receipts.store'), [
            'pos_supplier_id' => $supplier->id,
            'pos_inventory_location_id' => $location->id,
            'reference_no' => 'PO-001',
            'received_at' => now()->toDateString(),
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 1000],
            ],
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame(10, (int) $product->stock);
        $this->assertSame(10, $service->getStockAt($product, $location->id));
        $this->assertSame(1, PosStockReceipt::query()->count());
        $this->assertSame(1, PosStockMovement::query()
            ->where('pos_product_id', $product->id)
            ->where('movement_type', 'RECEIPT')
            ->count());
    }

    public function test_transfer_moves_stock_between_locations(): void
    {
        $cashier = $this->cashier();
        $service = app(\App\Services\Cooperative\PosInventoryService::class);
        $main = $service->ensureDefaultLocation();
        $warehouse = PosInventoryLocation::query()->create([
            'code' => 'WH',
            'name' => 'Gudang',
            'is_active' => true,
        ]);
        $product = $this->product([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 20,
        ]);
        $service->createReceipt([
            'pos_inventory_location_id' => $main->id,
            'received_at' => now()->toDateString(),
            'items' => [['pos_product_id' => $product->id, 'quantity' => 20, 'unit_cost' => 1000]],
        ], $cashier);

        $this->actingAs($cashier)->post(route('cooperative.pos.inventory.transfers.store'), [
            'from_location_id' => $main->id,
            'to_location_id' => $warehouse->id,
            'transferred_at' => now()->toDateString(),
            'items' => [['pos_product_id' => $product->id, 'quantity' => 5]],
        ])->assertRedirect();

        $this->assertSame(15, $service->getStockAt($product, $main->id));
        $this->assertSame(5, $service->getStockAt($product, $warehouse->id));
        $this->assertSame(1, PosStockTransfer::query()->count());
    }

    public function test_transfer_with_insufficient_stock_is_rejected(): void
    {
        $cashier = $this->cashier();
        $service = app(\App\Services\Cooperative\PosInventoryService::class);
        $main = $service->ensureDefaultLocation();
        $warehouse = PosInventoryLocation::query()->create([
            'code' => 'WH',
            'name' => 'Gudang',
            'is_active' => true,
        ]);
        $product = $this->product([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 2,
        ]);

        $response = $this->actingAs($cashier)->postJson(route('cooperative.pos.inventory.transfers.store'), [
            'from_location_id' => $main->id,
            'to_location_id' => $warehouse->id,
            'transferred_at' => now()->toDateString(),
            'items' => [['pos_product_id' => $product->id, 'quantity' => 5]],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
        $this->assertSame(0, PosStockTransfer::query()->count());
    }

    public function test_stock_opname_creates_draft_with_differences(): void
    {
        $cashier = $this->cashier();
        $service = app(\App\Services\Cooperative\PosInventoryService::class);
        $location = $service->ensureDefaultLocation();
        $product = $this->product([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);
        PosInventoryStock::query()->create([
            'pos_product_id' => $product->id,
            'pos_inventory_location_id' => $location->id,
            'quantity' => 10,
        ]);

        $this->actingAs($cashier)->post(route('cooperative.pos.inventory.counts.store'), [
            'pos_inventory_location_id' => $location->id,
            'items' => [['pos_product_id' => $product->id, 'counted_qty' => 8, 'notes' => 'Ada yang hilang']],
        ])->assertRedirect();

        $count = PosStockCount::query()->firstOrFail();
        $this->assertSame(PosStockCount::STATUS_DRAFT, $count->status);
        $this->assertSame(-2, (int) $count->items()->first()->difference);
    }

    public function test_stock_opname_approval_adjusts_stock_to_match_counted(): void
    {
        $cashier = $this->cashier();
        $supervisor = $this->supervisor();
        $service = app(\App\Services\Cooperative\PosInventoryService::class);
        $location = $service->ensureDefaultLocation();
        $product = $this->product([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);
        PosInventoryStock::query()->create([
            'pos_product_id' => $product->id,
            'pos_inventory_location_id' => $location->id,
            'quantity' => 10,
        ]);

        $count = $service->createCount($location->id, [
            ['pos_product_id' => $product->id, 'counted_qty' => 7, 'notes' => 'Hilang 3'],
        ], $cashier);
        $service->submitForReview($count);

        $this->actingAs($supervisor)->post(route('cooperative.pos.inventory.counts.approve', $count->id))
            ->assertRedirect();

        $product->refresh();
        $this->assertSame(7, (int) $product->stock);
        $this->assertSame(7, $service->getStockAt($product, $location->id));
        $count->refresh();
        $this->assertSame(PosStockCount::STATUS_APPROVED, $count->status);
    }

    private function cashier(): User
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->givePermissionTo(['access_cooperative_pos', 'manage_pos_products']);

        return $user;
    }

    private function supervisor(): User
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->givePermissionTo(['access_cooperative_pos', 'manage_pos_products']);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(array $attributes = []): PosProduct
    {
        return PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            ...$attributes,
        ]);
    }
}
