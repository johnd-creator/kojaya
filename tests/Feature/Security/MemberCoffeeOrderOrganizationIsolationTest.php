<?php

namespace Tests\Feature\Security;

use App\Models\CoffeeOrder;
use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosInventoryLocation;
use App\Models\PosInventoryStock;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\MemberOrderReservationService;
use App\Services\Cooperative\PosInventoryService;
use App\Services\Integrations\MemberPaymentSettlementService;
use App\Support\CanonicalOrderItem;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberCoffeeOrderOrganizationIsolationTest extends TestCase
{
    use DatabaseMigrations;

    private Organization $orgA;

    private Organization $orgB;

    private User $userA;

    private User $userB;

    private CooperativeMember $memberA;

    private CooperativeMember $memberB;

    private PosProduct $productA;

    private PosProduct $productB;

    private PosInventoryLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config([
            'services.midtrans.server_key' => '',
            'services.payment_gateway.allow_simulation' => true,
        ]);

        $this->orgA = Organization::factory()->create(['name' => 'Koperasi A']);
        $this->orgB = Organization::factory()->create(['name' => 'Koperasi B']);

        $this->userA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->memberA = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'user_id' => $this->userA->id,
        ]);

        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);
        $this->memberB = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgB->id,
            'user_id' => $this->userB->id,
        ]);

        $catA = PosCategory::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Signature',
            'slug' => 'signature-a',
        ]);
        $this->productA = PosProduct::factory()->create([
            'organization_id' => $this->orgA->id,
            'pos_category_id' => $catA->id,
            'name' => 'Kopi Susu Aren A',
            'cost_price' => 10000,
            'sale_price' => 20000,
            'stock' => 10,
        ]);

        $catB = PosCategory::factory()->create([
            'organization_id' => $this->orgB->id,
            'name' => 'Signature',
            'slug' => 'signature-b',
        ]);
        $this->productB = PosProduct::factory()->create([
            'organization_id' => $this->orgB->id,
            'pos_category_id' => $catB->id,
            'name' => 'Kopi Susu Pandan B',
            'cost_price' => 12000,
            'sale_price' => 25000,
            'stock' => 10,
        ]);

        $this->location = app(PosInventoryService::class)->ensureDefaultLocation();
        app(PosInventoryService::class)->syncDefaultLocationStocks($this->location->id);
    }

    public function test_menu_shows_own_org_products_and_hides_foreign_products(): void
    {
        Sanctum::actingAs($this->userA, ['member:read']);

        $response = $this->getJson('/api/v1/member/coffee/menu')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Kopi Susu Aren A'])
            ->assertJsonMissing(['name' => 'Kopi Susu Pandan B']);

        $items = $response->json('data.items');
        $itemIds = array_column($items, 'id');

        $this->assertContains((string) $this->productA->id, $itemIds);
        $this->assertNotContains((string) $this->productB->id, $itemIds);
    }

    public function test_menu_fails_closed_when_member_has_null_organization(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->change();
        });

        $userNull = User::factory()->create(['organization_id' => null]);
        $memberNull = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'user_id' => $userNull->id,
        ]);
        $memberNull->forceFill(['organization_id' => null])->saveQuietly();

        Sanctum::actingAs($userNull, ['member:read']);

        $this->getJson('/api/v1/member/coffee/menu')
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'Organisasi koperasi tidak ditemukan.']);
    }

    public function test_single_product_order_accepts_own_org_product(): void
    {
        Sanctum::actingAs($this->userA, ['member:write']);

        $response = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productA->id,
            'quantity' => 2,
            'client_reference' => 'TEST-OWN-COFFEE-01',
        ])->assertCreated();

        $this->assertSame('PENDING_PAYMENT', $response->json('data.status'));
        $this->assertEquals(40000, $response->json('data.transaction.total_amount'));

        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $this->productA->id,
            'pos_inventory_location_id' => $this->location->id,
            'reserved' => 2,
        ]);
        $this->assertDatabaseCount('member_payment_intents', 1);
    }

    public function test_single_product_order_rejects_foreign_org_product_with_oracle_resistant_error(): void
    {
        Sanctum::actingAs($this->userA, ['member:write']);

        $reservedBBefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        $intentsBefore = MemberPaymentIntent::count();
        $coffeeOrdersBefore = CoffeeOrder::count();
        $posTransactionsBefore = PosTransaction::count();

        $response = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productB->id,
            'quantity' => 1,
            'client_reference' => 'TEST-FOREIGN-COFFEE-01',
        ])->assertUnprocessable();

        $response->assertJsonValidationErrors(['pos_product_id']);
        $this->assertStringContainsString('Menu kopi tidak tersedia.', $response->json('errors.pos_product_id.0'));

        $reservedBAfter = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        $this->assertSame($reservedBBefore, $reservedBAfter, 'Foreign product reserved stock must remain unchanged.');
        $this->assertSame($intentsBefore, MemberPaymentIntent::count(), 'No payment intent may be created.');
        $this->assertSame($coffeeOrdersBefore, CoffeeOrder::count(), 'No coffee order may be created.');
        $this->assertSame($posTransactionsBefore, PosTransaction::count(), 'No POS transaction may be created.');
    }

    public function test_single_product_order_rejects_nonexistent_product_with_identical_error_message(): void
    {
        Sanctum::actingAs($this->userA, ['member:write']);

        $nonExistentId = 999999;
        $this->assertDatabaseMissing('pos_products', ['id' => $nonExistentId]);

        $responseForeign = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productB->id,
            'quantity' => 1,
        ])->assertUnprocessable();

        $responseNonExistent = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $nonExistentId,
            'quantity' => 1,
        ])->assertUnprocessable();

        $this->assertSame(
            $responseForeign->json('errors.pos_product_id'),
            $responseNonExistent->json('errors.pos_product_id'),
            'Foreign product and nonexistent product must return identical error responses.'
        );
        $this->assertStringContainsString('Menu kopi tidak tersedia.', $responseNonExistent->json('errors.pos_product_id.0'));
    }

    public function test_items_array_order_rejects_foreign_org_product(): void
    {
        Sanctum::actingAs($this->userA, ['member:write']);

        $reservedBBefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        $response = $this->postJson('/api/v1/member/coffee/orders', [
            'items' => [
                ['pos_product_id' => $this->productB->id, 'quantity' => 1],
            ],
            'client_reference' => 'TEST-ITEMS-FOREIGN-01',
        ])->assertUnprocessable();

        $response->assertJsonValidationErrors(['items.0.pos_product_id']);
        $errors = $response->json('errors');
        $this->assertArrayHasKey('items.0.pos_product_id', $errors);
        $this->assertStringContainsString('Menu kopi tidak tersedia.', $errors['items.0.pos_product_id'][0]);

        $reservedBAfter = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        $this->assertSame($reservedBBefore, $reservedBAfter);
        $this->assertDatabaseCount('member_payment_intents', 0);
    }

    public function test_mixed_basket_rolls_back_atomically_without_partial_reservation(): void
    {
        Sanctum::actingAs($this->userA, ['member:write']);

        $reservedABefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productA->id)
            ->value('reserved');
        $reservedBBefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        $response = $this->postJson('/api/v1/member/coffee/orders', [
            'items' => [
                ['pos_product_id' => $this->productA->id, 'quantity' => 2],
                ['pos_product_id' => $this->productB->id, 'quantity' => 1],
            ],
            'client_reference' => 'TEST-MIXED-BASKET-01',
        ])->assertUnprocessable();

        $response->assertJsonValidationErrors(['items.1.pos_product_id']);

        $reservedAAfter = PosInventoryStock::query()
            ->where('pos_product_id', $this->productA->id)
            ->value('reserved');
        $reservedBAfter = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        $this->assertSame($reservedABefore, $reservedAAfter, 'Product A reserved stock must remain unchanged after mixed basket rejection.');
        $this->assertSame($reservedBBefore, $reservedBAfter, 'Product B reserved stock must remain unchanged after mixed basket rejection.');
        $this->assertDatabaseCount('member_payment_intents', 0);
        $this->assertDatabaseCount('coffee_orders', 0);
        $this->assertDatabaseCount('pos_transactions', 0);
    }

    public function test_direct_reservation_service_rejects_foreign_product(): void
    {
        $reservationService = app(MemberOrderReservationService::class);

        $reservedBBefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        $canonicalItems = [
            new CanonicalOrderItem(
                posProductId: (string) $this->productB->id,
                quantity: 2,
                unitPrice: 25000,
            ),
        ];

        $this->expectException(AuthorizationException::class);

        try {
            $reservationService->reserve($canonicalItems, (string) $this->orgA->id);
        } finally {
            $reservedBAfter = PosInventoryStock::query()
                ->where('pos_product_id', $this->productB->id)
                ->value('reserved');

            $this->assertSame($reservedBBefore, $reservedBAfter, 'Direct reservation bypass must not mutate foreign reserved stock.');
        }
    }

    public function test_direct_reservation_service_rejects_null_or_empty_organization(): void
    {
        $reservationService = app(MemberOrderReservationService::class);

        $canonicalItems = [
            new CanonicalOrderItem(
                posProductId: (string) $this->productA->id,
                quantity: 1,
                unitPrice: 20000,
            ),
        ];

        $this->expectException(AuthorizationException::class);
        $reservationService->reserve($canonicalItems, null);
    }

    public function test_order_fails_closed_when_member_organization_is_null(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->change();
        });

        $userNull = User::factory()->create(['organization_id' => null]);
        $memberNull = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'user_id' => $userNull->id,
        ]);
        $memberNull->forceFill(['organization_id' => null])->saveQuietly();

        Sanctum::actingAs($userNull, ['member:write']);

        $reservedABefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productA->id)
            ->value('reserved');

        $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productA->id,
            'quantity' => 1,
        ])->assertForbidden()
            ->assertJsonFragment(['message' => 'Organisasi koperasi tidak ditemukan.']);

        $reservedAAfter = PosInventoryStock::query()
            ->where('pos_product_id', $this->productA->id)
            ->value('reserved');

        $this->assertSame($reservedABefore, $reservedAAfter);
        $this->assertDatabaseCount('member_payment_intents', 0);
    }

    public function test_case_a_authenticated_user_without_cooperative_member_cannot_probe_product_existence_oracle(): void
    {
        $userWithoutMember = User::factory()->create(['organization_id' => $this->orgA->id]);
        Sanctum::actingAs($userWithoutMember, ['member:write']);

        $reservedABefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productA->id)
            ->value('reserved');
        $reservedBBefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        $resOrgA = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productA->id,
            'quantity' => 1,
        ]);

        $resOrgB = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productB->id,
            'quantity' => 1,
        ]);

        $resNonExistent = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => 999999,
            'quantity' => 1,
        ]);

        $resOrgA->assertForbidden();
        $resOrgB->assertForbidden();
        $resNonExistent->assertForbidden();

        $this->assertSame($resOrgA->status(), $resOrgB->status());
        $this->assertSame($resOrgA->status(), $resNonExistent->status());
        $this->assertSame($this->normalizedErrorPayload($resOrgA), $this->normalizedErrorPayload($resOrgB), 'Actor org product and foreign org product must produce identical responses.');
        $this->assertSame($this->normalizedErrorPayload($resOrgA), $this->normalizedErrorPayload($resNonExistent), 'Actor org product and nonexistent product must produce identical responses.');

        $reservedAAfter = PosInventoryStock::query()
            ->where('pos_product_id', $this->productA->id)
            ->value('reserved');
        $reservedBAfter = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        $this->assertSame($reservedABefore, $reservedAAfter);
        $this->assertSame($reservedBBefore, $reservedBAfter);
        $this->assertDatabaseCount('member_payment_intents', 0);
        $this->assertDatabaseCount('coffee_orders', 0);
        $this->assertDatabaseCount('pos_transactions', 0);
    }

    public function test_case_a_authenticated_user_with_inactive_cooperative_member_cannot_probe_product_existence_oracle(): void
    {
        $userInactive = User::factory()->create(['organization_id' => $this->orgA->id]);
        CooperativeMember::factory()->create([
            'organization_id' => $this->orgA->id,
            'user_id' => $userInactive->id,
            'status' => 'INACTIVE',
            'validation_status' => 'PENDING',
        ]);

        Sanctum::actingAs($userInactive, ['member:write']);

        $resOrgA = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productA->id,
            'quantity' => 1,
        ]);

        $resOrgB = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productB->id,
            'quantity' => 1,
        ]);

        $resNonExistent = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => 999999,
            'quantity' => 1,
        ]);

        $resOrgA->assertForbidden();
        $resOrgB->assertForbidden();
        $resNonExistent->assertForbidden();

        $this->assertSame($resOrgA->status(), $resOrgB->status());
        $this->assertSame($resOrgA->status(), $resNonExistent->status());
        $this->assertSame($this->normalizedErrorPayload($resOrgA), $this->normalizedErrorPayload($resOrgB));
        $this->assertSame($this->normalizedErrorPayload($resOrgA), $this->normalizedErrorPayload($resNonExistent));

        $this->assertDatabaseCount('member_payment_intents', 0);
        $this->assertDatabaseCount('coffee_orders', 0);
        $this->assertDatabaseCount('pos_transactions', 0);
    }

    public function test_case_b_active_member_with_null_organization_and_user_with_valid_org_fails_closed_before_product_existence_lookup(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->change();
        });

        $userWithOrg = User::factory()->create(['organization_id' => $this->orgA->id]);
        $memberWithNullOrg = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'user_id' => $userWithOrg->id,
        ]);
        $memberWithNullOrg->forceFill(['organization_id' => null])->saveQuietly();

        Sanctum::actingAs($userWithOrg, ['member:read', 'member:write']);

        // 1. Catalog must fail closed (no catalog exposure for null member organization)
        $this->getJson('/api/v1/member/coffee/menu')
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'Organisasi koperasi tidak ditemukan.']);

        $reservedABefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productA->id)
            ->value('reserved');
        $reservedBBefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        // 2. Orders must fail closed before product existence lookup with identical 403 responses
        $resOrgA = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productA->id,
            'quantity' => 1,
        ]);

        $resOrgB = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productB->id,
            'quantity' => 1,
        ]);

        $resNonExistent = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => 999999,
            'quantity' => 1,
        ]);

        $resOrgA->assertForbidden()->assertJsonFragment(['message' => 'Organisasi koperasi tidak ditemukan.']);
        $resOrgB->assertForbidden()->assertJsonFragment(['message' => 'Organisasi koperasi tidak ditemukan.']);
        $resNonExistent->assertForbidden()->assertJsonFragment(['message' => 'Organisasi koperasi tidak ditemukan.']);

        $this->assertSame($resOrgA->status(), $resOrgB->status());
        $this->assertSame($resOrgA->status(), $resNonExistent->status());
        $this->assertSame($this->normalizedErrorPayload($resOrgA), $this->normalizedErrorPayload($resOrgB));
        $this->assertSame($this->normalizedErrorPayload($resOrgA), $this->normalizedErrorPayload($resNonExistent));

        $reservedAAfter = PosInventoryStock::query()
            ->where('pos_product_id', $this->productA->id)
            ->value('reserved');
        $reservedBAfter = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        $this->assertSame($reservedABefore, $reservedAAfter);
        $this->assertSame($reservedBBefore, $reservedBAfter);
        $this->assertDatabaseCount('member_payment_intents', 0);
        $this->assertDatabaseCount('coffee_orders', 0);
        $this->assertDatabaseCount('pos_transactions', 0);
    }

    public function test_case_c_completely_null_organization_authority_fails_closed_across_catalog_and_orders(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->change();
        });

        $userNull = User::factory()->create(['organization_id' => null]);
        $memberNull = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->orgA->id,
            'user_id' => $userNull->id,
        ]);
        $memberNull->forceFill(['organization_id' => null])->saveQuietly();

        Sanctum::actingAs($userNull, ['member:read', 'member:write']);

        // 1. Zero catalog exposure
        $this->getJson('/api/v1/member/coffee/menu')
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'Organisasi koperasi tidak ditemukan.']);

        $reservedABefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productA->id)
            ->value('reserved');
        $reservedBBefore = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        // 2. Zero product existence oracle: all return identical 403
        $resOrgA = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productA->id,
            'quantity' => 1,
        ]);

        $resOrgB = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $this->productB->id,
            'quantity' => 1,
        ]);

        $resNonExistent = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => 999999,
            'quantity' => 1,
        ]);

        $resOrgA->assertForbidden()->assertJsonFragment(['message' => 'Organisasi koperasi tidak ditemukan.']);
        $resOrgB->assertForbidden()->assertJsonFragment(['message' => 'Organisasi koperasi tidak ditemukan.']);
        $resNonExistent->assertForbidden()->assertJsonFragment(['message' => 'Organisasi koperasi tidak ditemukan.']);

        $this->assertSame($this->normalizedErrorPayload($resOrgA), $this->normalizedErrorPayload($resOrgB));
        $this->assertSame($this->normalizedErrorPayload($resOrgA), $this->normalizedErrorPayload($resNonExistent));

        // 3. Zero reservation, zero payment intents, zero orders, zero transactions
        $reservedAAfter = PosInventoryStock::query()
            ->where('pos_product_id', $this->productA->id)
            ->value('reserved');
        $reservedBAfter = PosInventoryStock::query()
            ->where('pos_product_id', $this->productB->id)
            ->value('reserved');

        $this->assertSame($reservedABefore, $reservedAAfter);
        $this->assertSame($reservedBBefore, $reservedBAfter);
        $this->assertDatabaseCount('member_payment_intents', 0);
        $this->assertDatabaseCount('coffee_orders', 0);
        $this->assertDatabaseCount('pos_transactions', 0);
    }

    public function test_settlement_defense_rejects_tampered_legacy_coffee_intent_with_foreign_product(): void
    {
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $this->memberA->id,
            'user_id' => $this->userA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_COFFEE_ORDER,
            'amount' => 25000,
            'channel' => 'QRIS',
            'gateway_status' => 'PAID',
            'reservation_status' => MemberPaymentIntent::RESERVATION_RESERVED,
            'settlement_status' => 'NOT_SETTLED',
            'metadata' => [
                'client_reference' => 'TAMPERED-COFFEE-01',
                'items' => [
                    [
                        'pos_product_id' => $this->productB->id,
                        'quantity' => 1,
                        'unit_price' => 25000,
                        'line_total' => 25000,
                    ],
                ],
            ],
        ]);

        $settlementService = app(MemberPaymentSettlementService::class);

        $this->expectException(ValidationException::class);

        try {
            $settlementService->settle($intent);
        } finally {
            $this->assertDatabaseCount('pos_transactions', 0);
            $this->assertDatabaseCount('coffee_orders', 0);
            $this->assertFalse($intent->refresh()->isSettled());
        }
    }

    public function test_settlement_defense_rejects_tampered_legacy_store_intent_with_foreign_product(): void
    {
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $this->memberA->id,
            'user_id' => $this->userA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'amount' => 25000,
            'channel' => 'QRIS',
            'gateway_status' => 'PAID',
            'reservation_status' => MemberPaymentIntent::RESERVATION_RESERVED,
            'settlement_status' => 'NOT_SETTLED',
            'metadata' => [
                'client_reference' => 'TAMPERED-STORE-01',
                'items' => [
                    [
                        'pos_product_id' => $this->productB->id,
                        'quantity' => 1,
                        'unit_price' => 25000,
                        'line_total' => 25000,
                    ],
                ],
            ],
        ]);

        $settlementService = app(MemberPaymentSettlementService::class);

        $this->expectException(ValidationException::class);

        try {
            $settlementService->settle($intent);
        } finally {
            $this->assertDatabaseCount('pos_transactions', 0);
            $this->assertFalse($intent->refresh()->isSettled());
        }
    }

    public function test_member_store_order_regression_same_org_passes_and_foreign_org_fails(): void
    {
        Sanctum::actingAs($this->userA, ['member:read', 'member:write']);

        $categoryA = PosCategory::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Sembako A',
            'slug' => 'sembako-a',
        ]);
        $storeProductA = PosProduct::factory()->create([
            'organization_id' => $this->orgA->id,
            'pos_category_id' => $categoryA->id,
            'name' => 'Beras Organik A',
            'cost_price' => 60000,
            'sale_price' => 70000,
            'stock' => 20,
        ]);

        $categoryB = PosCategory::factory()->create([
            'organization_id' => $this->orgB->id,
            'name' => 'Sembako B',
            'slug' => 'sembako-b',
        ]);
        $storeProductB = PosProduct::factory()->create([
            'organization_id' => $this->orgB->id,
            'pos_category_id' => $categoryB->id,
            'name' => 'Beras Organik B',
            'cost_price' => 60000,
            'sale_price' => 70000,
            'stock' => 20,
        ]);

        app(PosInventoryService::class)->syncDefaultLocationStocks($this->location->id);

        // 1. Same-org store order passes
        $this->postJson('/api/v1/member/store/orders', [
            'items' => [
                ['pos_product_id' => $storeProductA->id, 'quantity' => 1],
            ],
            'client_reference' => 'REGRESSION-STORE-01',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'PENDING_PAYMENT');

        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $storeProductA->id,
            'reserved' => 1,
        ]);

        // 2. Foreign-org store order fails
        $reservedStoreBBefore = PosInventoryStock::query()
            ->where('pos_product_id', $storeProductB->id)
            ->value('reserved');

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [
                ['pos_product_id' => $storeProductB->id, 'quantity' => 1],
            ],
            'client_reference' => 'REGRESSION-STORE-02',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.pos_product_id']);

        $reservedStoreBAfter = PosInventoryStock::query()
            ->where('pos_product_id', $storeProductB->id)
            ->value('reserved');

        $this->assertSame($reservedStoreBBefore, $reservedStoreBAfter);
    }

    /**
     * @param  \Illuminate\Testing\TestResponse  $response
     * @return array<string, mixed>
     */
    private function normalizedErrorPayload($response): array
    {
        return Arr::except($response->json(), ['request_id']);
    }
}
