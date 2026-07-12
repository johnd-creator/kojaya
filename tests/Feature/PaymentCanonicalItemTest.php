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
            99999.00, // wrong amount
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
            (float) $intent->amount,
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
