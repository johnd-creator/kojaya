<?php

namespace Tests\Feature\Cooperative;

use App\Models\Organization;
use App\Models\PosInventoryLocation;
use App\Models\PosProduct;
use App\Models\User;
use App\Services\Cooperative\PosTransactionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosProductOrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Organization $otherOrganization;

    private User $admin;

    private User $otherAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create();
        $this->otherOrganization = Organization::factory()->create();
        $this->admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $this->otherAdmin = User::factory()->create(['organization_id' => $this->otherOrganization->id]);
        $this->admin->assignRole('Admin Koperasi');
        $this->otherAdmin->assignRole('Admin Koperasi');
    }

    public function test_admin_can_only_read_and_mutate_owned_products(): void
    {
        $product = $this->product($this->organization, ['name' => 'Produk Organisasi A']);
        $otherProduct = $this->product($this->otherOrganization, ['name' => 'Produk Organisasi B']);
        $legacyProduct = PosProduct::factory()->create(['organization_id' => null, 'name' => 'Produk Legacy Shared']);

        $index = $this->actingAs($this->admin)->get(route('cooperative.pos-products.index'));

        $index->assertOk();
        $this->assertStringContainsString($product->name, $index->getContent());
        $this->assertStringNotContainsString($otherProduct->name, $index->getContent());
        $this->assertStringNotContainsString($legacyProduct->name, $index->getContent());

        $this->actingAs($this->admin)
            ->get(route('cooperative.pos-products.show', $otherProduct))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->put(route('cooperative.pos-products.update', $otherProduct), $this->productPayload($otherProduct))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->delete(route('cooperative.pos-products.destroy', $otherProduct))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post(route('cooperative.pos-products.adjust-stock', $otherProduct), [
                'movement_type' => 'ADJUSTMENT_IN',
                'quantity' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post(route('cooperative.pos.transactions.store'), [
                'items' => [['pos_product_id' => $otherProduct->id, 'quantity' => 1]],
                'payments' => [['payment_method' => 'CASH', 'amount' => $otherProduct->sale_price]],
            ])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->put(route('cooperative.pos-products.update', $legacyProduct), $this->productPayload($legacyProduct))
            ->assertForbidden();

        $otherIndex = $this->actingAs($this->otherAdmin)->get(route('cooperative.pos-products.index'));
        $otherIndex->assertOk();
        $this->assertStringContainsString($otherProduct->name, $otherIndex->getContent());
        $this->assertStringNotContainsString($product->name, $otherIndex->getContent());
    }

    public function test_new_products_take_the_active_organization_and_ignore_client_ownership(): void
    {
        $category = \App\Models\PosCategory::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('cooperative.pos-products.store'), [
                'organization_id' => $this->otherOrganization->id,
                'pos_category_id' => $category->id,
                'sku' => 'ORG-A-OWNED-001',
                'name' => 'Owned By A',
                'cost_price' => 1000,
                'sale_price' => 1500,
                'stock' => 0,
                'minimum_stock' => 1,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pos_products', [
            'sku' => 'ORG-A-OWNED-001',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_global_user_without_target_organization_cannot_create_null_owned_product(): void
    {
        $globalUser = User::factory()->create(['organization_id' => null]);
        $globalUser->assignRole('System Admin');
        $category = \App\Models\PosCategory::factory()->create();

        $this->actingAs($globalUser)
            ->post(route('cooperative.pos-products.store'), [
                'pos_category_id' => $category->id,
                'sku' => 'GLOBAL-UNSCOPED-001',
                'name' => 'Unscoped Product',
                'sale_price' => 1500,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('pos_products', [
            'sku' => 'GLOBAL-UNSCOPED-001',
        ]);
    }

    public function test_global_user_creates_in_the_existing_active_organization_context(): void
    {
        $globalUser = User::factory()->create(['organization_id' => null]);
        $globalUser->assignRole('System Admin');
        $category = \App\Models\PosCategory::factory()->create();

        $this->actingAs($globalUser)
            ->withSession(['active_organization_id' => $this->otherOrganization->id])
            ->post(route('cooperative.pos-products.store'), [
                'pos_category_id' => $category->id,
                'sku' => 'GLOBAL-SCOPED-001',
                'name' => 'Scoped Global Product',
                'sale_price' => 1500,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pos_products', [
            'sku' => 'GLOBAL-SCOPED-001',
            'organization_id' => $this->otherOrganization->id,
        ]);
    }

    public function test_pos_reports_only_expose_the_active_organization(): void
    {
        $this->admin->givePermissionTo('view_pos_reports');
        $product = $this->product($this->organization, ['name' => 'Laporan Organisasi A', 'sale_price' => 1000, 'stock' => 10]);
        $otherProduct = $this->product($this->otherOrganization, ['name' => 'Laporan Organisasi B', 'sale_price' => 9000, 'stock' => 1]);

        app(PosTransactionService::class)->create([
            'client_reference' => 'ORG-REPORT-A',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 1000, 'cash_received' => 1000]],
        ], $this->admin);
        app(PosTransactionService::class)->create([
            'client_reference' => 'ORG-REPORT-B',
            'items' => [['pos_product_id' => $otherProduct->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 9000, 'cash_received' => 9000]],
        ], $this->otherAdmin);

        $this->actingAs($this->admin)
            ->get(route('cooperative.pos.reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('products', fn ($products): bool => $products->pluck('name')->contains($product->name)
                    && ! $products->pluck('name')->contains($otherProduct->name))
                ->loadDeferredProps('analytics', fn ($page) => $page
                    ->where('analytics.summary.transactions', 1)
                    ->where('analytics.summary.gross_sales', 1000)
                )
            );
    }

    public function test_inventory_receipts_transfers_and_counts_reject_foreign_and_legacy_products(): void
    {
        $otherProduct = $this->product($this->otherOrganization, ['stock' => 10]);
        $legacyProduct = PosProduct::factory()->create(['organization_id' => null, 'stock' => 10]);
        $location = PosInventoryLocation::query()->create([
            'code' => 'A-MAIN',
            'name' => 'Organization A Main',
            'location_type' => 'STORE',
            'is_active' => true,
            'is_default' => false,
        ]);
        $otherLocation = PosInventoryLocation::query()->create([
            'code' => 'A-WAREHOUSE',
            'name' => 'Organization A Warehouse',
            'location_type' => 'WAREHOUSE',
            'is_active' => true,
            'is_default' => false,
        ]);

        foreach ([$otherProduct, $legacyProduct] as $product) {
            $payload = [
                'pos_inventory_location_id' => $location->id,
                'received_at' => now()->toDateString(),
                'items' => [['pos_product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 1000]],
            ];
            $this->actingAs($this->admin)
                ->post(route('cooperative.pos.inventory.receipts.store'), $payload)
                ->assertForbidden();

            $this->actingAs($this->admin)
                ->post(route('cooperative.pos.inventory.transfers.store'), [
                    'from_location_id' => $location->id,
                    'to_location_id' => $otherLocation->id,
                    'transferred_at' => now()->toDateString(),
                    'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
                ])
                ->assertForbidden();

            $this->actingAs($this->admin)
                ->post(route('cooperative.pos.inventory.counts.store'), [
                    'pos_inventory_location_id' => $location->id,
                    'items' => [['pos_product_id' => $product->id, 'counted_qty' => 1]],
                ])
                ->assertForbidden();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(Organization $organization, array $attributes = []): PosProduct
    {
        return PosProduct::factory()->create([
            'organization_id' => $organization->id,
            ...$attributes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(PosProduct $product): array
    {
        return [
            'pos_category_id' => $product->pos_category_id,
            'sku' => $product->sku,
            'name' => $product->name.' updated',
            'cost_price' => $product->cost_price,
            'sale_price' => $product->sale_price,
            'minimum_stock' => $product->minimum_stock,
            'is_active' => true,
        ];
    }
}
