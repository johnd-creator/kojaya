<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Support\CanonicalOrderItem;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * P0-1 Regression Tests: Canonical Item and Reservation Metadata.
 */
class PaymentCanonicalItemTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config(['services.midtrans.server_key' => '']);
    }

    /**
     * P0-1 Test 1: Duplicate product 3+2: reserved only 5, metadata one row qty 5.
     */
    public function test_duplicate_product_3_plus_2_reserves_only_5(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Dup Product', 10000, 20);

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 3],
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
            'client_reference' => 'DUP-3-2-TEST',
        ])->assertCreated();

        // Stock reserved should be exactly 5, not 10
        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'reserved' => 5,
        ]);

        // Metadata should have exactly one row with qty 5
        $intent = MemberPaymentIntent::query()->firstOrFail();
        $items = $intent->metadata['items'];
        $this->assertCount(1, $items);
        $this->assertSame(5, $items[0]['quantity']);
    }

    /**
     * P0-1 Test 1b: Settlement creates transaction with qty 5, not 10.
     */
    public function test_duplicate_product_settlement_qty_5(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Dup Product', 10000, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 3],
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
            'client_reference' => 'DUP-SETTLE-TEST',
        ])->assertCreated();

        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'PAID',
        ])->assertOk();

        $transaction = PosTransaction::query()
            ->where('client_reference', 'DUP-SETTLE-TEST')
            ->with('items')
            ->firstOrFail();

        // Only one transaction item line with qty 5
        $this->assertCount(1, $transaction->items);
        $this->assertSame(5, (int) $transaction->items->first()->quantity);
    }

    /**
     * P0-1 Test 2: Two active orders for same product; release order A; reserved must be 4 not 0.
     */
    public function test_release_order_a_does_not_affect_order_b(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Shared Product', 10000, 20);

        // Order A: reserve 5
        $responseA = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 5]],
            'client_reference' => 'ORDER-A',
        ])->assertCreated();

        // Order B: reserve 4
        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 4]],
            'client_reference' => 'ORDER-B',
        ])->assertCreated();

        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'reserved' => 9,
        ]);

        // Release order A via webhook CANCELLED
        $this->postJson('/api/payments/webhook', [
            'reference' => $responseA->json('data.charge.reference'),
            'status' => 'CANCELLED',
        ])->assertOk();

        // After release of A, reserved must be 4 (B's reservation), not 0
        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'reserved' => 4,
        ]);
    }

    /**
     * P0-1 Test 3: Duplicate items with different ordering produce same fingerprint.
     */
    public function test_different_ordering_same_fingerprint(): void
    {
        $productA = PosProduct::factory()->create(['sale_price' => 10000, 'stock' => 50]);
        $productB = PosProduct::factory()->create(['sale_price' => 15000, 'stock' => 50]);

        $items1 = [
            ['pos_product_id' => $productA->id, 'quantity' => 2, 'unit_price' => '10000.00', 'line_total' => '20000.00'],
            ['pos_product_id' => $productB->id, 'quantity' => 3, 'unit_price' => '15000.00', 'line_total' => '45000.00'],
        ];
        $items2 = [
            ['pos_product_id' => $productB->id, 'quantity' => 3, 'unit_price' => '15000.00', 'line_total' => '45000.00'],
            ['pos_product_id' => $productA->id, 'quantity' => 2, 'unit_price' => '10000.00', 'line_total' => '20000.00'],
        ];

        $canonical1 = CanonicalOrderItem::canonicalise($items1);
        $canonical2 = CanonicalOrderItem::canonicalise($items2);

        $this->assertSame(count($canonical1), count($canonical2));
        for ($i = 0; $i < count($canonical1); $i++) {
            $this->assertSame($canonical1[$i]->posProductId, $canonical2[$i]->posProductId);
            $this->assertSame($canonical1[$i]->quantity, $canonical2[$i]->quantity);
            $this->assertSame($canonical1[$i]->unitPrice, $canonical2[$i]->unitPrice);
        }
    }

    /**
     * P0-1 Test 4: One row qty 5 vs two rows qty 3+2 are canonical equivalent.
     */
    public function test_single_row_qty_5_equals_split_3_plus_2(): void
    {
        $product = PosProduct::factory()->create(['sale_price' => 10000, 'stock' => 50]);

        $single = [['pos_product_id' => $product->id, 'quantity' => 5, 'unit_price' => '10000.00', 'line_total' => '50000.00']];
        $split = [
            ['pos_product_id' => $product->id, 'quantity' => 3, 'unit_price' => '10000.00', 'line_total' => '30000.00'],
            ['pos_product_id' => $product->id, 'quantity' => 2, 'unit_price' => '10000.00', 'line_total' => '20000.00'],
        ];

        $canonical1 = CanonicalOrderItem::canonicalise($single);
        $canonical2 = CanonicalOrderItem::canonicalise($split);

        $this->assertCount(1, $canonical1);
        $this->assertCount(1, $canonical2);
        $this->assertSame($canonical1[0]->quantity, $canonical2[0]->quantity);
        $this->assertSame(5, $canonical1[0]->quantity);
        $this->assertSame(5, $canonical2[0]->quantity);
    }

    /**
     * P0-3 Test 1: PENDING + RELEASED with same key must 409.
     */
    public function test_pending_released_intent_conflicts_on_reuse(): void
    {
        $member = $this->actingMember(['member:write']);
        $product = $this->createProduct('Test', 10000, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'RELEASED-REUSE',
        ])->assertCreated();

        // Release reservation
        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'CANCELLED',
        ])->assertOk();

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $this->assertSame('RELEASED', $intent->reservation_status);

        // Reuse attempt must conflict
        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'RELEASED-REUSE',
        ])->assertConflict();
    }

    /**
     * P0-3 Test 4: Legacy null fingerprint fail closed.
     */
    public function test_legacy_null_fingerprint_fails_closed(): void
    {
        $member = $this->actingMember(['member:write']);
        $product = $this->createProduct('Test', 10000, 20);

        // Create an intent with null fingerprint manually (simulating legacy)
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'client_reference' => 'LEGACY-NULL-FP',
            'request_fingerprint' => null,
            'gateway_status' => 'PENDING',
            'reservation_status' => 'RESERVED',
            'settlement_status' => 'NOT_SETTLED',
            'settled_at' => null,
            'expires_at' => now()->addMinutes(30),
        ]);

        // Reuse must conflict due to null fingerprint
        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'client_reference' => 'LEGACY-NULL-FP',
        ])->assertConflict();
    }

    /**
     * P0-3 Test 5: Same key with different pickup_location must conflict.
     */
    public function test_different_pickup_location_conflicts(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test', 10000, 20);

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'client_reference' => 'PICKUP-TEST',
            'pickup_location' => 'Location A',
        ])->assertCreated();

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'client_reference' => 'PICKUP-TEST',
            'pickup_location' => 'Location B',
        ])->assertConflict();
    }

    /**
     * P0-4 Test 1: Signed PAID with different amount does not settle, creates incident.
     */
    public function test_paid_webhook_amount_mismatch_creates_incident(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test', 10000, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'AMOUNT-MISMATCH',
        ])->assertCreated();

        // Send webhook with different amount via internal webhook (no provider configured)
        // For internal mode, provider amount is null so this test uses a direct state service call
        $stateService = app(\App\Services\Integrations\MemberPaymentIntentStateService::class);
        $intent = MemberPaymentIntent::query()->firstOrFail();

        $stateService->applyGatewayEvent(
            $intent->gateway_reference,
            'PAID',
            ['status' => 'PAID', 'amount' => 99999],
            null,
            99999.00, // wrong amount (backward-compat float)
        );

        $intent->refresh();
        // Should NOT have PAID gateway status
        $this->assertNotSame('PAID', $intent->gateway_status);
        // Should have a reconciliation incident
        $this->assertDatabaseHas('payment_reconciliation_incidents', [
            'member_payment_intent_id' => $intent->id,
            'incident_type' => 'amount_mismatch',
            'status' => 'OPEN',
        ]);
        // Should NOT have a POS transaction
        $this->assertDatabaseCount('pos_transactions', 0);
    }

    /**
     * P0-4 Test 2: PAID after reservation expired creates incident, no illegal state.
     */
    public function test_paid_after_reservation_released_no_illegal_state(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test', 10000, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'PAID-AFTER-RELEASE',
        ])->assertCreated();

        // Cancel (release reservation)
        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'CANCELLED',
        ])->assertOk();

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $this->assertSame('CANCELLED', $intent->gateway_status);
        $this->assertSame('RELEASED', $intent->reservation_status);

        // Now send PAID - should NOT persist PAID on the authoritative intent
        $stateService = app(\App\Services\Integrations\MemberPaymentIntentStateService::class);
        $stateService->applyGatewayEvent(
            $intent->gateway_reference,
            'PAID',
            ['status' => 'PAID'],
            \App\Support\Money\MinorAmount::fromDecimal($intent->amount),
        );

        $intent->refresh();
        // Gateway status should remain CANCELLED, not PAID
        $this->assertSame('CANCELLED', $intent->gateway_status);
        $this->assertSame('RELEASED', $intent->reservation_status);
        // State combination must be valid
        $this->assertTrue($intent->isStateCombinationValid());

        // Should have incident
        $this->assertDatabaseHas('payment_reconciliation_incidents', [
            'member_payment_intent_id' => $intent->id,
            'incident_type' => 'paid_after_release',
        ]);

        // No transaction created
        $this->assertDatabaseCount('pos_transactions', 0);
    }

    /**
     * P0-4 Test 3: Invalid status string does not fall back to PENDING.
     */
    public function test_invalid_status_string_throws_domain_exception(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'gateway_status' => 'GARBLED_STATUS',
        ]);

        $this->expectException(\DomainException::class);
        $intent->gatewayStatus();
    }

    /**
     * P0-1 Test 1c: Fingerprint includes fulfillment_method and pickup_location.
     */
    public function test_fingerprint_includes_pickup_location(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test', 10000, 20);

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'client_reference' => 'FP-PICKUP-1',
            'pickup_location' => 'Warehouse A',
        ])->assertCreated();

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'client_reference' => 'FP-PICKUP-2',
            'pickup_location' => 'Warehouse B',
        ])->assertCreated();

        $intents = MemberPaymentIntent::query()->orderBy('id')->get();
        $this->assertCount(2, $intents);
        $this->assertNotSame($intents[0]->request_fingerprint, $intents[1]->request_fingerprint);
    }

    /**
     * P0-6 Test 1: Same product + different sugar_level → two separate order lines.
     */
    public function test_same_product_different_sugar_keeps_separate_lines(): void
    {
        $canonical = CanonicalOrderItem::canonicalise([
            ['pos_product_id' => 10, 'quantity' => 1, 'unit_price' => '10000.00', 'sugar_level' => 'Normal'],
            ['pos_product_id' => 10, 'quantity' => 1, 'unit_price' => '10000.00', 'sugar_level' => 'Less Sugar'],
        ]);

        $this->assertCount(2, $canonical, 'Different sugar_level must produce two canonical lines');
        $this->assertSame(1, $canonical[0]->quantity);
        $this->assertSame(1, $canonical[1]->quantity);
    }

    /**
     * P0-6 Test 2: Same product + different cup_size → two separate order lines.
     */
    public function test_same_product_different_cup_size_keeps_separate_lines(): void
    {
        $canonical = CanonicalOrderItem::canonicalise([
            ['pos_product_id' => 10, 'quantity' => 1, 'unit_price' => '10000.00', 'cup_size' => 'Reguler'],
            ['pos_product_id' => 10, 'quantity' => 1, 'unit_price' => '10000.00', 'cup_size' => 'Large'],
        ]);

        $this->assertCount(2, $canonical, 'Different cup_size must produce two canonical lines');
    }

    /**
     * P0-6 Test 3: Same product + same customization → aggregate allowed.
     */
    public function test_same_product_same_customization_aggregates(): void
    {
        $canonical = CanonicalOrderItem::canonicalise([
            ['pos_product_id' => 10, 'quantity' => 1, 'unit_price' => '10000.00', 'sugar_level' => 'Normal', 'ice_level' => 'Normal'],
            ['pos_product_id' => 10, 'quantity' => 2, 'unit_price' => '10000.00', 'sugar_level' => 'Normal', 'ice_level' => 'Normal'],
        ]);

        $this->assertCount(1, $canonical, 'Same product + same customization must aggregate');
        $this->assertSame(3, $canonical[0]->quantity);
    }

    /**
     * P0-6 Test 4: Fingerprint changes when customization changes.
     */
    public function test_fingerprint_changes_with_customization(): void
    {
        $product = PosProduct::factory()->create(['sale_price' => 10000, 'stock' => 50]);

        $itemsNormal = [['pos_product_id' => $product->id, 'quantity' => 1, 'unit_price' => '10000.00', 'sugar_level' => 'Normal']];
        $itemsLess = [['pos_product_id' => $product->id, 'quantity' => 1, 'unit_price' => '10000.00', 'sugar_level' => 'Less Sugar']];

        $fp1 = hash('sha256', json_encode(CanonicalOrderItem::canonicalise($itemsNormal)[0]->toArray()));
        $fp2 = hash('sha256', json_encode(CanonicalOrderItem::canonicalise($itemsLess)[0]->toArray()));

        $this->assertNotSame($fp1, $fp2, 'Fingerprint must differ when customization differs');
    }

    /**
     * P0-7 Test 1: API response includes product snapshot in items.
     */
    public function test_store_order_response_includes_product_snapshot(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'SNAPSHOT-TEST',
        ])->assertCreated();

        $items = $response->json('data.items');
        $this->assertNotEmpty($items, 'Response must have items');
        $this->assertArrayHasKey('product', $items[0], 'Item must include product snapshot');
        $this->assertSame($product->name, $items[0]['product']['name'] ?? null);
    }

    /**
     * P0-7 Test 2: Coffee order response includes item (not null).
     */
    public function test_coffee_order_response_item_not_null(): void
    {
        $user = \App\Models\User::factory()->create();
        $member = CooperativeMember::factory()->active()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['member:write']);

        $category = PosCategory::factory()->create(['name' => 'Signature']);
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'sale_price' => 25000,
            'stock' => 20,
        ]);

        $response = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $product->id,
            'quantity' => 1,
            'sugar_level' => 'Normal',
            'ice_level' => 'Normal',
            'cup_size' => 'Reguler',
            'client_reference' => 'COFFEE-SNAPSHOT',
        ])->assertCreated();

        $this->assertNotNull($response->json('data.item'), 'Coffee response item must not be null');
        $this->assertSame($product->name, $response->json('data.item.name'));
    }

    /**
     * P0-7 Test 3: Reused intent response is identical to created response for product.
     */
    public function test_reused_intent_product_snapshot_matches(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Reuse Product', 10000, 20);

        $created = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'REUSE-SNAPSHOT',
        ])->assertCreated();

        // Reuse same client_reference
        $reused = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'REUSE-SNAPSHOT',
        ])->assertCreated();

        $this->assertSame(
            $created->json('data.items.0.product.name'),
            $reused->json('data.items.0.product.name'),
            'Reused response product snapshot must match created'
        );
    }

    /**
     * P0-8 Test 1: Invalid settlement status throws DomainException (fail closed).
     */
    public function test_invalid_settlement_status_throws_domain_exception(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'settlement_status' => 'GARBLED_SETTLEMENT',
            'settled_at' => null,
        ]);

        $this->expectException(\DomainException::class);
        $intent->settlementStatus();
    }

    /**
     * P0-8 Test 2: Invalid gateway status throws DomainException (fail closed).
     */
    public function test_invalid_gateway_status_throws_domain_exception(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'gateway_status' => 'GARBLED_GATEWAY',
        ]);

        $this->expectException(\DomainException::class);
        $intent->gatewayStatus();
    }

    /**
     * P0-8 Test 3: Invalid reservation status throws DomainException (fail closed).
     */
    public function test_invalid_reservation_status_throws_domain_exception(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'reservation_status' => 'GARBLED_RESERVATION',
        ]);

        $this->expectException(\DomainException::class);
        $intent->reservationStatus();
    }

    /**
     * P1-1 Test: PENDING + RESERVED + SETTLING must conflict (409).
     */
    public function test_pending_reserved_settling_conflicts(): void
    {
        $member = $this->actingMember(['member:write']);
        $product = $this->createProduct('Test', 10000, 20);

        // Create an intent in PENDING + RESERVED + SETTLING
        MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'client_reference' => 'SETTLING-TEST',
            'request_fingerprint' => hash('sha256', 'settling-fp'),
            'amount' => 10000,
            'channel' => 'QRIS',
            'gateway_status' => 'PENDING',
            'reservation_status' => 'RESERVED',
            'settlement_status' => 'SETTLING',
            'settled_at' => null,
            'expires_at' => now()->addMinutes(30),
        ]);

        // Reuse must conflict because SETTLING is not NOT_SETTLED
        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'client_reference' => 'SETTLING-TEST',
        ])->assertConflict();
    }

    /**
     * P1-3 Test: Duplicate webhook creates exactly one incident (idempotent).
     */
    public function test_duplicate_webhook_creates_one_incident(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test', 10000, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'DEDUP-INCIDENT',
        ])->assertCreated();

        // Release reservation
        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'CANCELLED',
        ])->assertOk();

        $intent = MemberPaymentIntent::query()->firstOrFail();

        // Send PAID twice (should create exactly one incident)
        $stateService = app(\App\Services\Integrations\MemberPaymentIntentStateService::class);
        $stateService->applyGatewayEvent(
            $intent->gateway_reference,
            'PAID',
            ['status' => 'PAID'],
            \App\Support\Money\MinorAmount::fromDecimal($intent->amount),
        );
        $stateService->applyGatewayEvent(
            $intent->gateway_reference,
            'PAID',
            ['status' => 'PAID'],
            \App\Support\Money\MinorAmount::fromDecimal($intent->amount),
        );

        $incidentCount = \App\Models\PaymentReconciliationIncident::query()
            ->where('member_payment_intent_id', $intent->id)
            ->count();
        $this->assertSame(1, $incidentCount, 'Duplicate webhook must create exactly one incident (idempotent)');
    }

    /**
     * P1-2 Test: Decimal boundary amount comparison (no float precision loss).
     */
    public function test_webhook_amount_decimal_boundary_no_float_loss(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test', 19999, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'client_reference' => 'DECIMAL-TEST',
        ])->assertCreated();

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $stateService = app(\App\Services\Integrations\MemberPaymentIntentStateService::class);

        // Use integer minor amount to avoid float precision issues
        $expectedMinor = (int) bcmul((string) $intent->amount, '100', 0);
        $stateService->applyGatewayEvent(
            $intent->gateway_reference,
            'PAID',
            ['status' => 'PAID'],
            $expectedMinor,
        );

        $intent->refresh();
        $this->assertSame('PAID', $intent->gateway_status, 'Exact minor amount must match');
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function createProduct(string $name, float $price, int $stock): PosProduct
    {
        $category = PosCategory::factory()->create([
            'name' => 'Test',
            'slug' => 'test-'.uniqid(),
        ]);

        return PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'name' => $name.'-'.uniqid(),
            'cost_price' => $price * 0.5,
            'sale_price' => $price,
            'stock' => $stock,
        ]);
    }

    /**
     * @param  list<string>  $abilities
     */
    private function actingMember(array $abilities): CooperativeMember
    {
        $user = \App\Models\User::factory()->create();
        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user, $abilities);

        return $member;
    }
}
