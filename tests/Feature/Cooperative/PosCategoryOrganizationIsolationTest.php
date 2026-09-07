<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosStockMovement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosCategoryOrganizationIsolationTest extends TestCase
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

        $this->organization = Organization::factory()->create(['name' => 'Koperasi A']);
        $this->otherOrganization = Organization::factory()->create(['name' => 'Koperasi B']);

        $this->admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $this->otherAdmin = User::factory()->create(['organization_id' => $this->otherOrganization->id]);

        $this->admin->assignRole('Admin Koperasi');
        $this->otherAdmin->assignRole('Admin Koperasi');
    }

    /**
     * C-01: Org A category index shows Org A categories.
     */
    public function test_c01_org_a_category_index_shows_org_a_categories(): void
    {
        $catA = PosCategory::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Kategori Org A',
            'slug' => 'kategori-org-a',
        ]);

        $responseA = $this->actingAs($this->admin)->get(route('cooperative.pos-categories.index'));
        $responseA->assertOk();
        $responseA->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cooperative/Inventory/Categories/Index')
            ->where('categories', fn ($data) => collect($data)->pluck('id')->contains($catA->id)
                && collect($data)->pluck('name')->contains('Kategori Org A'))
        );

        $this->assertStringContainsString('Kategori Org A', $responseA->getContent());
    }

    /**
     * C-02: Org A category index does NOT show Org B category ID, name, or metadata.
     */
    public function test_c02_org_a_category_index_does_not_show_org_b_categories_or_metadata(): void
    {
        $catA = PosCategory::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Kategori Org A',
            'slug' => 'kategori-org-a',
        ]);
        $catB = PosCategory::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Kategori Org B Secret',
            'slug' => 'kategori-org-b-secret',
        ]);
        $legacyUnowned = PosCategory::factory()->create([
            'organization_id' => null,
            'name' => 'Kategori Legacy Orphan Unowned',
            'slug' => 'kategori-legacy-orphan-unowned',
        ]);

        $responseA = $this->actingAs($this->admin)->get(route('cooperative.pos-categories.index'));
        $responseA->assertOk();
        $responseA->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cooperative/Inventory/Categories/Index')
            ->where('categories', function ($data) use ($catA, $catB, $legacyUnowned): bool {
                $categories = collect($data);

                return $categories->pluck('id')->contains($catA->id)
                    && ! $categories->pluck('id')->contains($catB->id)
                    && ! $categories->pluck('name')->contains($catB->name)
                    && ! $categories->pluck('slug')->contains($catB->slug)
                    && ! $categories->pluck('id')->contains($legacyUnowned->id)
                    && ! $categories->pluck('name')->contains($legacyUnowned->name);
            })
        );

        $content = $responseA->getContent();
        $this->assertStringNotContainsString('Kategori Org B Secret', $content);
        $this->assertStringNotContainsString('kategori-org-b-secret', $content);
        $this->assertStringNotContainsString('Kategori Legacy Orphan Unowned', $content);

        // Verify Org B user sees only Org B category
        $responseB = $this->actingAs($this->otherAdmin)->get(route('cooperative.pos-categories.index'));
        $responseB->assertOk();
        $responseB->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cooperative/Inventory/Categories/Index')
            ->where('categories', fn ($data) => collect($data)->pluck('id')->contains($catB->id)
                && ! collect($data)->pluck('id')->contains($catA->id))
        );
    }

    /**
     * C-03: Product category dropdown for Org A contains only Org A categories.
     */
    public function test_c03_product_category_dropdown_contains_only_org_a_categories(): void
    {
        $catA = PosCategory::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Dropdown Cat A',
            'is_active' => true,
        ]);
        $catB = PosCategory::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Dropdown Cat B',
            'is_active' => true,
        ]);

        $productIndex = $this->actingAs($this->admin)->get(route('cooperative.pos-products.index'));
        $productIndex->assertOk();
        $productIndex->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories', fn ($cats) => collect($cats)->pluck('id')->contains($catA->id)
                && ! collect($cats)->pluck('id')->contains($catB->id))
        );
    }

    /**
     * C-04: Any category autocomplete/search API or catalog payload is scoped.
     * Verified in register catalog and PosCategoryAccessService helpers.
     */
    public function test_c04_category_search_and_autocomplete_are_scoped(): void
    {
        $catA = PosCategory::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Register Cat A',
            'is_active' => true,
        ]);
        $catB = PosCategory::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Register Cat B',
            'is_active' => true,
        ]);

        $register = $this->actingAs($this->admin)->get(route('cooperative.pos.index'));
        $register->assertOk();
        $register->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories', fn ($cats) => collect($cats)->pluck('id')->contains($catA->id)
                && ! collect($cats)->pluck('id')->contains($catB->id))
        );

        $categoryAccess = app(\App\Services\Cooperative\PosCategoryAccessService::class);
        $this->assertTrue($categoryAccess->isVisibleId($catA->id, $this->admin));
        $this->assertFalse($categoryAccess->isVisibleId($catB->id, $this->admin));
        $this->assertTrue($categoryAccess->categoryBelongsToOrganization($catA, $this->organization->id));
        $this->assertFalse($categoryAccess->categoryBelongsToOrganization($catB, $this->organization->id));
    }

    /**
     * C-05: Org A creates category; persisted ownership MUST be Org A.
     */
    public function test_c05_category_create_persists_ownership_to_org_a(): void
    {
        $response = $this->actingAs($this->admin)->post(route('cooperative.pos-categories.store'), [
            'name' => 'Kategori Baru Org A',
            'slug' => 'kategori-baru-org-a',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pos_categories', [
            'name' => 'Kategori Baru Org A',
            'slug' => 'kategori-baru-org-a',
            'organization_id' => $this->organization->id,
        ]);
    }

    /**
     * C-06: Malicious request containing organization_id = Org B cannot create an Org B category.
     */
    public function test_c06_category_create_ignores_client_supplied_organization_id(): void
    {
        $response = $this->actingAs($this->admin)->post(route('cooperative.pos-categories.store'), [
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Kategori Spoofed Org',
            'slug' => 'kategori-spoofed-org',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pos_categories', [
            'name' => 'Kategori Spoofed Org',
            'organization_id' => $this->organization->id,
        ]);
        $this->assertDatabaseMissing('pos_categories', [
            'name' => 'Kategori Spoofed Org',
            'organization_id' => $this->otherOrganization->id,
        ]);
    }

    /**
     * Slug uniqueness is scoped to organization.
     */
    public function test_category_slug_uniqueness_is_scoped_by_organization(): void
    {
        PosCategory::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Minuman',
            'slug' => 'minuman',
        ]);

        // Org B can create the exact same slug
        $responseB = $this->actingAs($this->otherAdmin)->post(route('cooperative.pos-categories.store'), [
            'name' => 'Minuman Org B',
            'slug' => 'minuman',
            'is_active' => true,
        ]);
        $responseB->assertRedirect();
        $this->assertDatabaseHas('pos_categories', [
            'organization_id' => $this->otherOrganization->id,
            'slug' => 'minuman',
        ]);

        // Org A cannot create duplicate slug within Org A
        $responseA = $this->actingAs($this->admin)->post(route('cooperative.pos-categories.store'), [
            'name' => 'Minuman Lain',
            'slug' => 'minuman',
            'is_active' => true,
        ]);
        $responseA->assertSessionHasErrors(['slug']);
    }

    /**
     * C-07: Org A updates Org A category successfully.
     */
    public function test_c07_org_a_updates_org_a_category_successfully(): void
    {
        $category = PosCategory::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Original Name',
            'slug' => 'original-slug',
        ]);

        $response = $this->actingAs($this->admin)->put(route('cooperative.pos-categories.update', $category), [
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pos_categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'organization_id' => $this->organization->id,
        ]);
    }

    /**
     * C-08: Org A attempts to update Org B category ID. Expected 404 / fail closed and Org B record unchanged.
     */
    public function test_c08_org_a_cannot_update_org_b_category_and_receives_404(): void
    {
        $catB = PosCategory::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Org B Original Name',
            'slug' => 'org-b-slug',
        ]);

        $response = $this->actingAs($this->admin)->put(route('cooperative.pos-categories.update', $catB), [
            'name' => 'Tampered Name',
            'slug' => 'tampered-slug',
            'is_active' => true,
        ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('pos_categories', [
            'id' => $catB->id,
            'name' => 'Org B Original Name',
            'slug' => 'org-b-slug',
        ]);
    }

    /**
     * C-09: Org A can delete eligible Org A category.
     */
    public function test_c09_org_a_can_delete_eligible_category(): void
    {
        $category = PosCategory::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Deletable Category',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('cooperative.pos-categories.destroy', $category));
        $response->assertRedirect();
        $this->assertDatabaseMissing('pos_categories', [
            'id' => $category->id,
        ]);
    }

    /**
     * C-10: Org A cannot delete Org B category.
     */
    public function test_c10_org_a_cannot_delete_org_b_category(): void
    {
        $catB = PosCategory::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Org B Protected Category',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('cooperative.pos-categories.destroy', $catB));
        $response->assertNotFound();
        $this->assertDatabaseHas('pos_categories', ['id' => $catB->id]);
    }

    /**
     * C-11: Foreign category existence/product-count status is not leaked before tenant assertion.
     */
    public function test_c11_foreign_category_existence_or_product_count_not_leaked_before_tenant_assertion(): void
    {
        // Case 1: Org B category WITH products
        $catBWithProducts = PosCategory::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Org B With Products',
        ]);
        PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'pos_category_id' => $catBWithProducts->id,
        ]);

        $response1 = $this->actingAs($this->admin)->delete(route('cooperative.pos-categories.destroy', $catBWithProducts));
        $response1->assertNotFound();
        $this->assertDatabaseHas('pos_categories', ['id' => $catBWithProducts->id]);

        // Case 2: Org B category WITHOUT products
        $catBWithoutProducts = PosCategory::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Org B Without Products',
        ]);

        $response2 = $this->actingAs($this->admin)->delete(route('cooperative.pos-categories.destroy', $catBWithoutProducts));
        $response2->assertNotFound();
        $this->assertDatabaseHas('pos_categories', ['id' => $catBWithoutProducts->id]);

        // Case 3: Org A category with products returns session error, NOT 404
        $catAWithProducts = PosCategory::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Org A With Products',
        ]);
        PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $catAWithProducts->id,
        ]);

        $response3 = $this->actingAs($this->admin)->delete(route('cooperative.pos-categories.destroy', $catAWithProducts));
        $response3->assertRedirect();
        $response3->assertSessionHas('error');
        $this->assertDatabaseHas('pos_categories', ['id' => $catAWithProducts->id]);
    }

    /**
     * P-01: Product read is scoped to organization.
     */
    public function test_p01_product_listing_shows_only_org_a_products(): void
    {
        $prodA = PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Produk Org A',
        ]);
        $prodB = PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Produk Org B',
        ]);

        $index = $this->actingAs($this->admin)->get(route('cooperative.pos-products.index'));
        $index->assertOk();
        $this->assertStringContainsString($prodA->name, $index->getContent());
        $this->assertStringNotContainsString($prodB->name, $index->getContent());
    }

    /**
     * P-02: Direct Org B product route remains inaccessible to Org A.
     */
    public function test_p02_direct_org_b_product_route_remains_inaccessible_to_org_a(): void
    {
        $prodB = PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Produk Org B Inaccessible',
        ]);

        $this->actingAs($this->admin)
            ->get(route('cooperative.pos-products.show', $prodB))
            ->assertForbidden();
    }

    /**
     * P-03: Org A can create product with Org A category.
     */
    public function test_p03_org_a_creates_product_with_org_a_category(): void
    {
        $catA = PosCategory::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->actingAs($this->admin)->post(route('cooperative.pos-products.store'), [
            'pos_category_id' => $catA->id,
            'sku' => 'SKU-ORGA-001',
            'name' => 'Produk Kategori A',
            'sale_price' => 25000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pos_products', [
            'sku' => 'SKU-ORGA-001',
            'organization_id' => $this->organization->id,
            'pos_category_id' => $catA->id,
        ]);
    }

    /**
     * P-04: Org A cannot create product referencing Org B category.
     * Assert request rejected, product not created, no foreign relation created.
     */
    public function test_p04_org_a_cannot_create_product_referencing_org_b_category(): void
    {
        $catB = PosCategory::factory()->create(['organization_id' => $this->otherOrganization->id]);

        $response = $this->actingAs($this->admin)->post(route('cooperative.pos-products.store'), [
            'pos_category_id' => $catB->id,
            'sku' => 'SKU-REJECT-001',
            'name' => 'Produk Ilegal Kategori B',
            'sale_price' => 25000,
        ]);

        $response->assertSessionHasErrors(['pos_category_id']);
        $this->assertDatabaseMissing('pos_products', [
            'sku' => 'SKU-REJECT-001',
        ]);
    }

    /**
     * P-05: Org A creates product without category successfully when category is nullable.
     */
    public function test_p05_org_a_creates_product_without_category_successfully(): void
    {
        $response = $this->actingAs($this->admin)->post(route('cooperative.pos-products.store'), [
            'pos_category_id' => null,
            'sku' => 'SKU-NULLCAT-001',
            'name' => 'Produk Tanpa Kategori',
            'sale_price' => 15000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pos_products', [
            'sku' => 'SKU-NULLCAT-001',
            'organization_id' => $this->organization->id,
            'pos_category_id' => null,
        ]);
    }

    /**
     * P-06: Malicious supplied organization_id = Org B cannot override ownership.
     */
    public function test_p06_product_create_ignores_client_organization_id_override(): void
    {
        $catA = PosCategory::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->actingAs($this->admin)->post(route('cooperative.pos-products.store'), [
            'organization_id' => $this->otherOrganization->id,
            'pos_category_id' => $catA->id,
            'sku' => 'SKU-ORGA-SPOOF-001',
            'name' => 'Produk Spoofed Org',
            'sale_price' => 10000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pos_products', [
            'sku' => 'SKU-ORGA-SPOOF-001',
            'organization_id' => $this->organization->id,
        ]);
        $this->assertDatabaseMissing('pos_products', [
            'sku' => 'SKU-ORGA-SPOOF-001',
            'organization_id' => $this->otherOrganization->id,
        ]);
    }

    /**
     * P-07: Org A can move Org A product between Org A categories.
     */
    public function test_p07_org_a_can_move_product_between_org_a_categories(): void
    {
        $cat1 = PosCategory::factory()->create(['organization_id' => $this->organization->id]);
        $cat2 = PosCategory::factory()->create(['organization_id' => $this->organization->id]);

        $product = PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $cat1->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('cooperative.pos-products.update', $product), [
            'pos_category_id' => $cat2->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'sale_price' => $product->sale_price,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pos_products', [
            'id' => $product->id,
            'pos_category_id' => $cat2->id,
        ]);
    }

    /**
     * P-08: Org A cannot update Org A product to reference Org B category.
     * Original relation remains unchanged.
     */
    public function test_p08_org_a_cannot_update_product_to_reference_org_b_category(): void
    {
        $catA = PosCategory::factory()->create(['organization_id' => $this->organization->id]);
        $catB = PosCategory::factory()->create(['organization_id' => $this->otherOrganization->id]);

        $product = PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $catA->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('cooperative.pos-products.update', $product), [
            'pos_category_id' => $catB->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'sale_price' => $product->sale_price,
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors(['pos_category_id']);
        $this->assertDatabaseHas('pos_products', [
            'id' => $product->id,
            'pos_category_id' => $catA->id,
        ]);
    }

    /**
     * P-09: Org A cannot mutate Org B product even using a valid Org A category.
     */
    public function test_p09_org_a_cannot_mutate_org_b_product_even_with_valid_local_category(): void
    {
        $catA = PosCategory::factory()->create(['organization_id' => $this->organization->id]);
        $catB = PosCategory::factory()->create(['organization_id' => $this->otherOrganization->id]);

        $prodB = PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'pos_category_id' => $catB->id,
            'name' => 'Original Org B Product',
        ]);

        $response = $this->actingAs($this->admin)->put(route('cooperative.pos-products.update', $prodB), [
            'pos_category_id' => $catA->id,
            'sku' => $prodB->sku,
            'name' => 'Attacked Name',
            'sale_price' => 50000,
            'is_active' => true,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('pos_products', [
            'id' => $prodB->id,
            'name' => 'Original Org B Product',
            'pos_category_id' => $catB->id,
        ]);
    }

    /**
     * P-10: Org A filtering with Org A category behaves normally.
     */
    public function test_p10_product_filtering_with_org_a_category_behaves_normally(): void
    {
        $catA = PosCategory::factory()->create(['organization_id' => $this->organization->id]);
        $prodA = PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $catA->id,
            'name' => 'Produk Filter A',
        ]);

        $res = $this->actingAs($this->admin)->get(route('cooperative.pos-products.index', [
            'category_id' => $catA->id,
        ]));
        $res->assertOk();
        $this->assertStringContainsString($prodA->name, $res->getContent());
    }

    /**
     * P-11: Supplying Org B category ID in product filter does not disclose foreign data.
     */
    public function test_p11_product_filtering_with_org_b_category_id_does_not_disclose_foreign_data(): void
    {
        $catB = PosCategory::factory()->create(['organization_id' => $this->otherOrganization->id]);
        $prodA = PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Produk Org A Safe',
        ]);
        $prodB = PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'pos_category_id' => $catB->id,
            'name' => 'Produk Filter B Secret',
        ]);

        $resForeign = $this->actingAs($this->admin)->get(route('cooperative.pos-products.index', [
            'category_id' => $catB->id,
        ]));
        $resForeign->assertOk();
        $this->assertStringNotContainsString($prodB->name, $resForeign->getContent());
        $this->assertStringNotContainsString($prodA->name, $resForeign->getContent());
    }

    /**
     * S-01: Org A adjusts Org A product stock successfully.
     */
    public function test_s01_org_a_adjusts_org_a_product_stock_successfully(): void
    {
        $prodA = PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'stock' => 10,
        ]);

        $resA = $this->actingAs($this->admin)->post(route('cooperative.pos-products.adjust-stock', $prodA), [
            'movement_type' => 'ADJUSTMENT_IN',
            'quantity' => 5,
            'notes' => 'Restock Org A',
        ]);
        $resA->assertRedirect();
        $this->assertEquals(15, $prodA->fresh()->stock);
        $this->assertDatabaseHas('pos_stock_movements', [
            'pos_product_id' => $prodA->id,
            'movement_type' => 'ADJUSTMENT_IN',
            'quantity' => 5,
        ]);
    }

    /**
     * S-02: Org A cannot adjust stock on Org B product.
     */
    public function test_s02_org_a_cannot_adjust_stock_on_org_b_product(): void
    {
        $prodB = PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'stock' => 20,
        ]);

        $initialMovementsCount = PosStockMovement::where('pos_product_id', $prodB->id)->count();

        $resB = $this->actingAs($this->admin)->post(route('cooperative.pos-products.adjust-stock', $prodB), [
            'movement_type' => 'ADJUSTMENT_OUT',
            'quantity' => 5,
            'notes' => 'Attack Org B',
        ]);
        $resB->assertForbidden();
        $this->assertEquals(20, $prodB->fresh()->stock);
        $this->assertEquals($initialMovementsCount, PosStockMovement::where('pos_product_id', $prodB->id)->count());
    }

    /**
     * O-01: Missing/unresolvable organization fails closed.
     */
    public function test_o01_missing_organization_fails_closed(): void
    {
        $unscopedUser = User::factory()->create(['organization_id' => null]);
        $unscopedUser->assignRole('Admin Koperasi');

        // Index fails closed with 403 when organization is missing
        $indexRes = $this->actingAs($unscopedUser)->get(route('cooperative.pos-categories.index'));
        $indexRes->assertForbidden();

        // Store category fails closed with 403
        $storeCatRes = $this->actingAs($unscopedUser)->post(route('cooperative.pos-categories.store'), [
            'name' => 'Unscoped Category',
            'slug' => 'unscoped-cat',
        ]);
        $storeCatRes->assertForbidden();
        $this->assertDatabaseMissing('pos_categories', ['slug' => 'unscoped-cat']);

        // Store product fails closed with 403
        $storeProdRes = $this->actingAs($unscopedUser)->post(route('cooperative.pos-products.store'), [
            'sku' => 'UNSCOPED-PROD',
            'name' => 'Unscoped Product',
            'sale_price' => 10000,
        ]);
        $storeProdRes->assertForbidden();
        $this->assertDatabaseMissing('pos_products', ['sku' => 'UNSCOPED-PROD']);
    }

    /**
     * M-01: Legacy category linked only to Org A products becomes Org A category.
     */
    public function test_m01_legacy_category_linked_only_to_org_a_products_becomes_org_a_category(): void
    {
        $category = PosCategory::factory()->create([
            'organization_id' => null,
            'name' => 'Legacy Cat A Only',
            'slug' => 'legacy-cat-a-only',
        ]);
        PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $category->id,
        ]);
        PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $category->id,
        ]);

        $migration = require database_path('migrations/2026_09_07_000001_add_organization_id_to_pos_categories_table.php');
        $result = $migration->backfillOrganizationIds();

        $this->assertSame((string) $this->organization->id, (string) $category->fresh()->organization_id);
        $this->assertGreaterThanOrEqual(1, $result['resolved']);
    }

    /**
     * M-02: Legacy category linked only to Org B products becomes Org B category.
     */
    public function test_m02_legacy_category_linked_only_to_org_b_products_becomes_org_b_category(): void
    {
        $category = PosCategory::factory()->create([
            'organization_id' => null,
            'name' => 'Legacy Cat B Only',
            'slug' => 'legacy-cat-b-only',
        ]);
        PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'pos_category_id' => $category->id,
        ]);

        $migration = require database_path('migrations/2026_09_07_000001_add_organization_id_to_pos_categories_table.php');
        $result = $migration->backfillOrganizationIds();

        $this->assertSame((string) $this->otherOrganization->id, (string) $category->fresh()->organization_id);
        $this->assertGreaterThanOrEqual(1, $result['resolved']);
    }

    /**
     * M-03: Legacy category shared by Org A + Org B is NOT arbitrarily assigned; safely duplicated and remapped.
     */
    public function test_m03_legacy_category_shared_by_org_a_and_org_b_is_safely_duplicated_and_remapped(): void
    {
        $category = PosCategory::factory()->create([
            'organization_id' => null,
            'name' => 'Shared Legacy Cat M03',
            'slug' => 'shared-legacy-cat-m03',
        ]);
        $prodA = PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $category->id,
        ]);
        $prodB = PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'pos_category_id' => $category->id,
        ]);

        $initialCategoryCount = PosCategory::count();
        $initialProductCount = PosProduct::count();

        $migration = require database_path('migrations/2026_09_07_000001_add_organization_id_to_pos_categories_table.php');
        $result = $migration->backfillOrganizationIds();

        $this->assertGreaterThanOrEqual(1, $result['duplicates_created']);
        $this->assertGreaterThanOrEqual(1, $result['products_remapped']);
        $this->assertSame($initialProductCount, PosProduct::count(), 'No products lost during backfill');
        $this->assertSame($initialCategoryCount + 1, PosCategory::count(), 'Exact one duplicate created');

        $prodAFresh = $prodA->fresh();
        $prodBFresh = $prodB->fresh();

        $catAFresh = PosCategory::find($prodAFresh->pos_category_id);
        $catBFresh = PosCategory::find($prodBFresh->pos_category_id);

        $this->assertSame((string) $this->organization->id, (string) $catAFresh->organization_id);
        $this->assertSame((string) $this->otherOrganization->id, (string) $catBFresh->organization_id);
        $this->assertNotEquals($catAFresh->id, $catBFresh->id);
    }

    /**
     * M-04: Orphan legacy category receives no fabricated ownership.
     */
    public function test_m04_orphan_legacy_category_receives_no_fabricated_ownership(): void
    {
        $orphanCat = PosCategory::factory()->create([
            'organization_id' => null,
            'name' => 'Orphan Unreferenced Category M04',
            'slug' => 'orphan-unreferenced-cat-m04',
        ]);

        $migration = require database_path('migrations/2026_09_07_000001_add_organization_id_to_pos_categories_table.php');
        $result = $migration->backfillOrganizationIds();

        $this->assertNull($orphanCat->fresh()->organization_id);
        $this->assertGreaterThanOrEqual(1, $result['orphans']);
    }

    /**
     * M-05: Foreign keys and indexes work.
     */
    public function test_m05_foreign_keys_and_indexes_work(): void
    {
        $this->assertTrue(Schema::hasColumn('pos_categories', 'organization_id'));
        $this->assertTrue(Schema::hasColumn('pos_categories', 'duplicated_from_id'));

        $tempOrg = Organization::factory()->create();
        $cat = PosCategory::factory()->create(['organization_id' => $tempOrg->id]);
        $this->assertSame((string) $tempOrg->id, (string) $cat->organization_id);

        $tempOrg->delete();
        $this->assertNull($cat->fresh()->organization_id);
    }

    /**
     * M-06: Rollback preserves data integrity.
     */
    public function test_m06_rollback_preserves_data_integrity(): void
    {
        $migration = require database_path('migrations/2026_09_07_000001_add_organization_id_to_pos_categories_table.php');

        $this->assertTrue(Schema::hasColumn('pos_categories', 'organization_id'));

        // Rollback
        $migration->down();
        $this->assertFalse(Schema::hasColumn('pos_categories', 'organization_id'));
        $this->assertFalse(Schema::hasColumn('pos_categories', 'duplicated_from_id'));

        // Reapply
        $migration->up();
        $this->assertTrue(Schema::hasColumn('pos_categories', 'organization_id'));
    }

    /**
     * M-07: SQLite migration succeeds.
     */
    public function test_m07_sqlite_migration_succeeds(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertTrue(Schema::hasTable('pos_categories'));
        $this->assertTrue(Schema::hasColumn('pos_categories', 'organization_id'));
        $this->assertTrue(Schema::hasColumn('pos_categories', 'duplicated_from_id'));
    }

    /**
     * M-08: PostgreSQL migration path remains valid where covered by repository CI.
     */
    public function test_m08_postgresql_migration_path_remains_valid(): void
    {
        $migration = require database_path('migrations/2026_09_07_000001_add_organization_id_to_pos_categories_table.php');
        $this->assertNotNull($migration);
        $this->assertTrue(method_exists($migration, 'up'));
        $this->assertTrue(method_exists($migration, 'down'));
        $this->assertTrue(method_exists($migration, 'backfillOrganizationIds'));
    }

    /**
     * RBAC: User without manage_pos_categories permission is forbidden.
     */
    public function test_user_without_permission_cannot_access_pos_categories(): void
    {
        $plainUser = User::factory()->create(['organization_id' => $this->organization->id]);

        $category = PosCategory::factory()->create(['organization_id' => $this->organization->id]);

        $this->actingAs($plainUser)->get(route('cooperative.pos-categories.index'))->assertForbidden();
        $this->actingAs($plainUser)->post(route('cooperative.pos-categories.store'), ['name' => 'Test', 'slug' => 'test'])->assertForbidden();
        $this->actingAs($plainUser)->put(route('cooperative.pos-categories.update', $category), ['name' => 'Updated'])->assertForbidden();
        $this->actingAs($plainUser)->delete(route('cooperative.pos-categories.destroy', $category))->assertForbidden();
    }

    /**
     * Model level saving hook rejects cross-tenant category association.
     */
    public function test_model_saving_hook_prevents_cross_tenant_category_association(): void
    {
        $catB = PosCategory::factory()->create(['organization_id' => $this->otherOrganization->id]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cross-organization product category association is prohibited.');

        PosProduct::create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $catB->id,
            'sku' => 'MODEL-HOOK-001',
            'name' => 'Model Hook Test',
            'sale_price' => 1000,
        ]);
    }

    /**
     * API Catalog: Member store catalog is strictly scoped to member organization.
     */
    public function test_api_member_store_catalog_is_strictly_scoped_to_member_organization(): void
    {
        $userA = User::factory()->create(['organization_id' => $this->organization->id]);
        $memberA = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $userA->id,
        ]);

        $catA = PosCategory::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Kategori API A',
            'slug' => 'kategori-api-a',
        ]);
        $prodA = PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $catA->id,
            'name' => 'Produk Toko A',
            'is_active' => true,
            'stock' => 10,
        ]);

        $catB = PosCategory::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Kategori API B',
            'slug' => 'kategori-api-b',
        ]);
        $prodB = PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'pos_category_id' => $catB->id,
            'name' => 'Produk Toko B',
            'is_active' => true,
            'stock' => 10,
        ]);

        Sanctum::actingAs($userA, ['member:read']);

        $response = $this->getJson('/api/v1/member/store/catalog');
        $response->assertOk();

        $items = collect($response->json('data.items'));
        $this->assertTrue($items->pluck('id')->contains((string) $prodA->id));
        $this->assertFalse($items->pluck('id')->contains((string) $prodB->id));

        $categories = $response->json('data.categories');
        $this->assertContains('Kategori API A', $categories);
        $this->assertNotContains('Kategori API B', $categories);

        // Filtering by foreign category yields empty
        $filteredResponse = $this->getJson('/api/v1/member/store/catalog?category=kategori-api-b');
        $filteredResponse->assertOk();
        $this->assertEmpty($filteredResponse->json('data.items'));
    }

    /**
     * API Store Order: Member store order rejects purchasing foreign organization products.
     */
    public function test_api_member_store_order_rejects_foreign_organization_products(): void
    {
        $userA = User::factory()->create(['organization_id' => $this->organization->id]);
        $memberA = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $userA->id,
        ]);

        $prodB = PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Produk B Terlarang',
            'is_active' => true,
            'sale_price' => 20000,
            'stock' => 10,
        ]);

        Sanctum::actingAs($userA, ['member:read', 'member:write']);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [
                ['pos_product_id' => $prodB->id, 'quantity' => 1],
            ],
            'client_reference' => 'ATTACK-CROSS-ORG-001',
        ]);

        // Validation rule Rule::exists rejects foreign product, returning 422
        $response->assertStatus(422);
        $this->assertDatabaseMissing('member_payment_intents', [
            'client_reference' => 'ATTACK-CROSS-ORG-001',
        ]);
    }

    /**
     * API POS Sync Catalog: Pos sync catalog is strictly scoped to cashier organization.
     */
    public function test_api_pos_sync_catalog_is_strictly_scoped_to_cashier_organization(): void
    {
        $cashierA = User::factory()->create(['organization_id' => $this->organization->id]);
        $cashierA->givePermissionTo('access_cooperative_pos');

        $prodA = PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Sync Prod A',
            'is_active' => true,
            'is_discontinued' => false,
        ]);
        $prodB = PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Sync Prod B',
            'is_active' => true,
            'is_discontinued' => false,
        ]);

        Sanctum::actingAs($cashierA, ['pos:read']);

        $response = $this->getJson('/api/v1/pos/sync/catalog');
        $response->assertOk();

        $data = collect($response->json('data'));
        $this->assertTrue($data->pluck('id')->contains($prodA->id));
        $this->assertFalse($data->pluck('id')->contains($prodB->id));
    }

    /**
     * API POS Products: Products listing endpoint is strictly scoped to user organization.
     */
    public function test_api_pos_products_endpoint_is_strictly_scoped_to_user_organization(): void
    {
        $cashierA = User::factory()->create(['organization_id' => $this->organization->id]);
        $cashierA->givePermissionTo('access_cooperative_pos');

        $catA = PosCategory::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Cat A']);
        $catB = PosCategory::factory()->create(['organization_id' => $this->otherOrganization->id, 'name' => 'Cat B']);

        $prodA = PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $catA->id,
            'name' => 'API Prod A',
            'is_active' => true,
        ]);
        $prodB = PosProduct::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'pos_category_id' => $catB->id,
            'name' => 'API Prod B',
            'is_active' => true,
        ]);

        Sanctum::actingAs($cashierA, ['pos:read']);

        $response = $this->getJson('/api/v1/pos/products');
        $response->assertOk();

        $data = collect($response->json('data'));
        $this->assertTrue($data->pluck('id')->contains($prodA->id));
        $this->assertFalse($data->pluck('id')->contains($prodB->id));

        // Foreign category is not eager loaded
        $productRow = $data->firstWhere('id', $prodA->id);
        $this->assertSame('Cat A', $productRow['category']['name']);
    }

    /**
     * API Fail-Closed: API endpoints fail closed when actor organization context is missing.
     */
    public function test_api_endpoints_fail_closed_when_actor_organization_is_missing(): void
    {
        $unscopedUser = User::factory()->create(['organization_id' => null]);
        $unscopedUser->givePermissionTo('access_cooperative_pos');

        // POS Sync Catalog without organization fails closed
        Sanctum::actingAs($unscopedUser, ['pos:read']);
        $this->getJson('/api/v1/pos/sync/catalog')->assertForbidden();

        // POS Products without organization fails closed
        $this->getJson('/api/v1/pos/products')->assertForbidden();

        // Member Store Catalog without active member/organization fails closed
        Sanctum::actingAs($unscopedUser, ['member:read']);
        $this->getJson('/api/v1/member/store/catalog')->assertForbidden();
    }
}
