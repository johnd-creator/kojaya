<?php

namespace Tests\Feature\Cooperative;

use App\Models\Organization;
use App\Models\PosInventoryLocation;
use App\Models\PosInventoryStock;
use App\Models\PosProduct;
use App\Models\PosStockMovement;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\User;
use App\Services\Cooperative\PosInventoryService;
use App\Services\Cooperative\PosReturnService;
use App\Services\Cooperative\PosTransactionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PosSprint2MultiLocationTest extends TestCase
{
    use DatabaseMigrations;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->organization = Organization::factory()->create();
    }

    public function test_default_location_backfills_old_product_stock(): void
    {
        $product = $this->product([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 12,
        ]);

        $service = app(PosInventoryService::class);
        $service->ensureDefaultLocation();

        $this->assertSame(12, $service->getStockAt($product, $service->ensureDefaultLocation()->id));
    }

    public function test_receipt_sale_return_void_keeps_location_and_global_stock_consistent(): void
    {
        $cashier = $this->cashier();
        $supervisor = $this->supervisor();
        $service = app(PosInventoryService::class);
        $location = $service->ensureDefaultLocation();

        $product = $this->product([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 0,
        ]);

        $service->createReceipt([
            'pos_inventory_location_id' => $location->id,
            'received_at' => now()->toDateString(),
            'items' => [['pos_product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 1000]],
        ], $cashier);

        $product->refresh();
        $this->assertSame(10, (int) $product->stock);
        $this->assertSame(10, $service->getStockAt($product, $location->id));

        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S2-FLOW-SALE',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 3]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 15000, 'cash_received' => 15000]],
        ], $cashier);

        $product->refresh();
        $this->assertSame(7, (int) $product->stock);
        $this->assertSame(7, $service->getStockAt($product, $location->id));
        $saleMovement = PosStockMovement::query()
            ->where('source_type', PosTransaction::class)
            ->where('source_id', $tx->id)
            ->first();
        $this->assertNotNull($saleMovement->pos_inventory_location_id);

        $returnService = app(PosReturnService::class);
        $return = $returnService->create([
            'pos_transaction_id' => $tx->id,
            'reason' => 'Pelanggan mengembalikan 1 item',
            'items' => [
                ['pos_transaction_item_id' => $tx->items->first()->id, 'quantity' => 1],
            ],
        ], $cashier);

        $product->refresh();
        $this->assertSame(8, (int) $product->stock);
        $this->assertSame(8, $service->getStockAt($product, $location->id));

        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $tx->id), [
            'reason' => 'Test void',
        ])->assertRedirect();
        $voidRequest = PosVoidRequest::query()->where('pos_transaction_id', $tx->id)->firstOrFail();

        $this->actingAs($supervisor)->post(route('cooperative.pos.void-requests.process', $voidRequest->id), [
            'decision' => 'APPROVE',
        ])->assertRedirect();

        $product->refresh();
        $expectedStock = 10 - 3 + 1 + 3;
        $this->assertSame($expectedStock, (int) $product->stock);
        $this->assertSame($expectedStock, $service->getStockAt($product, $location->id));

        $movements = PosStockMovement::query()
            ->where('pos_product_id', $product->id)
            ->where('pos_inventory_location_id', $location->id)
            ->get();

        $this->assertGreaterThanOrEqual(4, $movements->count());
        $movements->each(function (PosStockMovement $movement) use ($location): void {
            $this->assertSame($location->id, (int) $movement->pos_inventory_location_id);
        });

        $this->assertSame(-3, (int) $movements->where('movement_type', 'SALE')->sum('quantity'));
        $this->assertSame(1, (int) $movements->where('movement_type', 'RETURN')->sum('quantity'));
        $this->assertSame(3, (int) $movements->where('movement_type', 'VOID')->sum('quantity'));
    }

    public function test_sale_records_movement_at_shift_location_when_shift_present(): void
    {
        $cashier = $this->cashier();
        $service = app(PosInventoryService::class);
        $main = $service->ensureDefaultLocation();
        $warehouse = PosInventoryLocation::query()->create([
            'code' => 'WH',
            'name' => 'Gudang',
            'is_active' => true,
        ]);

        $product = $this->product([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 0,
        ]);

        $service->createReceipt([
            'pos_inventory_location_id' => $warehouse->id,
            'received_at' => now()->toDateString(),
            'items' => [['pos_product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 1000]],
        ], $cashier);

        $shift = \App\Models\PosCashierShift::query()->create([
            'shift_no' => 'SHF-1',
            'cashier_id' => $cashier->id,
            'pos_inventory_location_id' => $warehouse->id,
            'shift_date' => now()->toDateString(),
            'opened_at' => now(),
            'opening_cash' => 0,
            'status' => \App\Models\PosCashierShift::STATUS_OPEN,
        ]);

        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S2-SHIFT-SALE',
            'pos_cashier_shift_id' => $shift->id,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashier);

        $movement = PosStockMovement::query()
            ->where('source_type', PosTransaction::class)
            ->where('source_id', $tx->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame($warehouse->id, (int) $movement->pos_inventory_location_id);
        $this->assertSame(3, $service->getStockAt($product, $warehouse->id));
        $this->assertSame(0, $service->getStockAt($product, $main->id));
    }

    public function test_sell_stock_below_available_raises_validation_error(): void
    {
        $cashier = $this->cashier();
        $service = app(PosInventoryService::class);
        $location = $service->ensureDefaultLocation();

        $product = $this->product([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 2,
        ]);
        PosInventoryStock::query()->create([
            'pos_product_id' => $product->id,
            'pos_inventory_location_id' => $location->id,
            'quantity' => 2,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $service->sellStock(
            product: $product,
            location: $location,
            quantity: 5,
            sourceType: PosTransaction::class,
            sourceId: 1,
            referenceNo: 'TEST',
        );
    }

    private function cashier(): User
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->givePermissionTo(['access_cooperative_pos', 'manage_pos_products', 'view_pos_reports']);

        return $user;
    }

    private function supervisor(): User
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->givePermissionTo(['access_cooperative_pos', 'manage_pos_products', 'approve_pos_void']);

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
