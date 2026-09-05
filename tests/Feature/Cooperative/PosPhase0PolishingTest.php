<?php

namespace Tests\Feature\Cooperative;

use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PosPhase0PolishingTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pos_product_can_be_created_with_brand_variant_unit_and_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'organization_id' => Organization::factory()->create()->id,
        ]);
        $user->assignRole('System Admin');
        $category = PosCategory::factory()->create();

        $image = UploadedFile::fake()->image('product.jpg', 400, 400);

        $response = $this->actingAs($user)->post(route('cooperative.pos-products.store'), [
            'pos_category_id' => $category->id,
            'sku' => 'SKU-PHASE0-1',
            'barcode' => '8999999000001',
            'name' => 'Indomie Goreng',
            'image' => $image,
            'brand' => 'Indomie',
            'variant' => 'Goreng Original',
            'unit' => 'pcs',
            'rack_location' => 'A1',
            'cost_price' => 2500,
            'sale_price' => 3500,
            'stock' => 50,
            'minimum_stock' => 5,
            'is_active' => true,
            'is_discontinued' => false,
        ]);

        $response->assertRedirect();
        $product = PosProduct::query()->where('sku', 'SKU-PHASE0-1')->firstOrFail();

        $this->assertSame('Indomie', $product->brand);
        $this->assertSame('pcs', $product->unit);
        $this->assertSame('A1', $product->rack_location);
        $this->assertNotEmpty($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertNotNull($product->image_url);
    }

    public function test_image_can_be_removed_from_pos_product(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $product = PosProduct::factory()->create([
            'image_path' => 'pos-products/existing.jpg',
        ]);
        Storage::disk('public')->put('pos-products/existing.jpg', 'fake');

        $this->actingAs($user)->put(route('cooperative.pos-products.update', $product), [
            'pos_category_id' => $product->pos_category_id,
            'sku' => $product->sku,
            'name' => $product->name,
            'sale_price' => $product->sale_price,
            'minimum_stock' => $product->minimum_stock,
            'is_active' => true,
            'remove_image' => true,
        ])->assertRedirect();

        $product->refresh();
        $this->assertNull($product->image_path);
        Storage::disk('public')->assertMissing('pos-products/existing.jpg');
    }

    public function test_image_can_be_updated_from_inventory_form_payload(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $product = PosProduct::factory()->create([
            'image_path' => 'pos-products/old.jpg',
        ]);
        Storage::disk('public')->put('pos-products/old.jpg', 'fake');

        $image = UploadedFile::fake()->image('new-product.webp', 400, 400);

        $this->actingAs($user)->post(route('cooperative.pos-products.update', $product), [
            '_method' => 'PUT',
            'pos_category_id' => $product->pos_category_id,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'brand' => $product->brand,
            'variant' => $product->variant,
            'unit' => $product->unit,
            'rack_location' => $product->rack_location,
            'cost_price' => $product->cost_price,
            'sale_price' => $product->sale_price,
            'minimum_stock' => $product->minimum_stock,
            'is_active' => true,
            'is_discontinued' => false,
            'remove_image' => false,
            'image' => $image,
        ])->assertRedirect();

        $product->refresh();

        $this->assertNotNull($product->image_path);
        $this->assertNotSame('pos-products/old.jpg', $product->image_path);
        Storage::disk('public')->assertMissing('pos-products/old.jpg');
        Storage::disk('public')->assertExists($product->image_path);

        $this->actingAs($user)
            ->get(route('cooperative.pos-products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Inventory/Products/Index')
                ->where('products.data.0.image_url', $product->image_url)
            );
    }

    public function test_discount_amount_cannot_exceed_subtotal(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('System Admin');
        $product = PosProduct::factory()->create([
            'organization_id' => $organization->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($user)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE0-DISC',
            'payment_method' => 'CASH',
            'discount_amount' => 99999,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['discount_amount']);
    }

    public function test_cash_received_must_cover_total(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('System Admin');
        $product = PosProduct::factory()->create([
            'organization_id' => $organization->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($user)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE0-CASH',
            'payment_method' => 'CASH',
            'cash_received' => 1000,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cash_received']);
    }

    public function test_successful_cash_transaction_records_change(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('System Admin');
        $product = PosProduct::factory()->create([
            'organization_id' => $organization->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE0-CHANGE',
            'payment_method' => 'CASH',
            'cash_received' => 10000,
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect();
        $transaction = PosTransaction::query()
            ->where('client_reference', 'PHASE0-CHANGE')
            ->firstOrFail();

        $this->assertSame(10000.0, (float) $transaction->cash_received);
        $this->assertSame(0.0, (float) $transaction->cash_change);
    }

    public function test_quantity_exceeding_stock_is_rejected(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('System Admin');
        $product = PosProduct::factory()->create([
            'organization_id' => $organization->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 2,
        ]);

        $response = $this->actingAs($user)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE0-QTY',
            'payment_method' => 'CASH',
            'cash_received' => 50000,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 5],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    public function test_discontinued_product_cannot_be_sold(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('System Admin');
        $product = PosProduct::factory()->discontinued()->create([
            'organization_id' => $organization->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($user)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE0-DISC-PROD',
            'payment_method' => 'CASH',
            'cash_received' => 10000,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    public function test_transaction_history_filters_by_member_cashier_method(): void
    {
        $organization = Organization::factory()->create();
        $cashierA = User::factory()->create(['name' => 'Kasir A', 'organization_id' => $organization->id]);
        $cashierB = User::factory()->create(['name' => 'Kasir B', 'organization_id' => $organization->id]);
        $cashierA->givePermissionTo('access_cooperative_pos');
        $cashierB->givePermissionTo('access_cooperative_pos');
        $product = PosProduct::factory()->create([
            'organization_id' => $organization->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 100,
        ]);

        $transactionA = $this->createPosTransaction($cashierA, $product, 'CASH', 'PHASE0-FA');
        $transactionB = $this->createPosTransaction($cashierB, $product, 'TRANSFER', 'PHASE0-FB');

        $responseA = $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.index', ['cashier_id' => $cashierA->id]));
        $responseA->assertOk();
        $responseA->assertInertia(fn ($page) => $page
            ->component('Cooperative/Pos/Transactions/Index')
            ->where('transactions.data.0.id', $transactionA->id)
            ->has('transactions.data', 1)
        );

        $responseB = $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.index', ['payment_method' => 'TRANSFER']));
        $responseB->assertOk();
        $responseB->assertInertia(fn ($page) => $page
            ->component('Cooperative/Pos/Transactions/Index')
            ->where('transactions.data.0.id', $transactionB->id)
        );

        $responseC = $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.index', ['transaction_no' => $transactionA->transaction_no]));
        $responseC->assertOk();
        $responseC->assertInertia(fn ($page) => $page
            ->component('Cooperative/Pos/Transactions/Index')
            ->where('transactions.data.0.id', $transactionA->id)
            ->has('transactions.data', 1)
        );
    }

    private function createPosTransaction(User $cashier, PosProduct $product, string $method, string $ref): \App\Models\PosTransaction
    {
        $cashier->givePermissionTo('access_cooperative_pos');
        $this->actingAs($cashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => $ref,
            'payment_method' => $method,
            'cash_received' => $method === 'CASH' ? 10000 : null,
            'amount' => $method === 'CASH' ? null : 5000,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        return PosTransaction::query()->where('client_reference', $ref)->firstOrFail();
    }
}
