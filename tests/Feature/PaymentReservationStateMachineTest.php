<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Services\Cooperative\MemberOrderIntentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentReservationStateMachineTest extends TestCase
{
    use DatabaseMigrations;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config(['services.midtrans.server_key' => '']);
    }

    // ── PAY-2: Intent Service ──────────────────────────────────────────

    public function test_resolve_or_create_is_idempotent_with_same_payload(): void
    {
        $member = $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $service = app(MemberOrderIntentService::class);

        $items = [['pos_product_id' => $product->id, 'quantity' => 2, 'unit_price' => 10000, 'line_total' => 20000]];

        $first = $service->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: 'TEST-IDEMPOTENT-001',
            canonicalRequest: [
                'user_id' => $member->user_id,
                'amount' => 20000,
                'channel' => 'QRIS',
                'items' => $items,
            ],
            rawItems: $items,
        );

        $second = $service->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: 'TEST-IDEMPOTENT-001',
            canonicalRequest: [
                'user_id' => $member->user_id,
                'amount' => 20000,
                'channel' => 'QRIS',
                'items' => $items,
            ],
            rawItems: $items,
        );

        $this->assertTrue($first->wasCreated());
        $this->assertFalse($second->wasCreated());
        $this->assertTrue($second->wasReused());
        $this->assertSame($first->intent->id, $second->intent->id);
        $this->assertDatabaseCount('member_payment_intents', 1);
    }

    public function test_resolve_or_create_rejects_different_amount_same_reference(): void
    {
        $member = $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $service = app(MemberOrderIntentService::class);

        $items1 = [['pos_product_id' => $product->id, 'quantity' => 2, 'unit_price' => 10000, 'line_total' => 20000]];

        $service->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: 'TEST-CONFLICT-001',
            canonicalRequest: ['amount' => 20000, 'channel' => 'QRIS', 'items' => $items1],
            rawItems: $items1,
        );

        $items2 = [['pos_product_id' => $product->id, 'quantity' => 3, 'unit_price' => 10000, 'line_total' => 30000]];

        $this->expectException(\App\Exceptions\PaymentIntentConflictException::class);

        $service->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: 'TEST-CONFLICT-001',
            canonicalRequest: ['amount' => 30000, 'channel' => 'QRIS', 'items' => $items2],
            rawItems: $items2,
        );
    }

    public function test_resolve_or_create_rejects_different_channel_same_reference(): void
    {
        $member = $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $service = app(MemberOrderIntentService::class);

        $items = [['pos_product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10000, 'line_total' => 10000]];

        $service->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: 'TEST-CHANNEL-001',
            canonicalRequest: ['amount' => 10000, 'channel' => 'QRIS', 'items' => $items],
            rawItems: $items,
        );

        $this->expectException(\App\Exceptions\PaymentIntentConflictException::class);

        $service->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: 'TEST-CHANNEL-001',
            canonicalRequest: ['amount' => 10000, 'channel' => 'VA', 'items' => $items],
            rawItems: $items,
        );
    }

    public function test_resolve_or_create_stores_request_fingerprint(): void
    {
        $member = $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 15000, 10);

        $service = app(MemberOrderIntentService::class);

        $items = [['pos_product_id' => $product->id, 'quantity' => 2, 'unit_price' => 15000, 'line_total' => 30000]];

        $resolution = $service->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: 'TEST-FINGERPRINT-001',
            canonicalRequest: ['amount' => 30000, 'channel' => 'QRIS', 'items' => $items],
            rawItems: $items,
        );

        $this->assertNotNull($resolution->intent->request_fingerprint);
        $this->assertSame(64, strlen($resolution->intent->request_fingerprint));
    }

    // ── PAY-4: State Service ───────────────────────────────────────────

    public function test_duplicate_paid_webhook_does_not_double_settle(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'TEST-DUP-PAID-001',
        ])->assertCreated();

        $reference = $response->json('data.charge.reference');

        $this->postJson('/api/payments/webhook', [
            'reference' => $reference,
            'status' => 'PAID',
        ])->assertOk();

        $this->postJson('/api/payments/webhook', [
            'reference' => $reference,
            'status' => 'PAID',
        ])->assertOk();

        $this->assertDatabaseCount('pos_transactions', 1);
    }

    public function test_paid_webhook_then_expired_webhook_does_not_release(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'TEST-PAID-EXPIRED-001',
        ])->assertCreated();

        $reference = $response->json('data.charge.reference');

        $this->postJson('/api/payments/webhook', [
            'reference' => $reference,
            'status' => 'PAID',
        ])->assertOk();

        $this->postJson('/api/payments/webhook', [
            'reference' => $reference,
            'status' => 'EXPIRED',
        ])->assertOk();

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $this->assertSame('PAID', $intent->gateway_status);

        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'reserved' => 0,
        ]);
    }

    public function test_cancelled_webhook_releases_reservation(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 3]],
            'client_reference' => 'TEST-CANCEL-001',
        ])->assertCreated();

        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'CANCELLED',
        ])->assertOk();

        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'reserved' => 0,
        ]);

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $this->assertSame('CANCELLED', $intent->gateway_status);
        $this->assertSame(MemberPaymentIntent::RESERVATION_RELEASED, $intent->reservation_status);
    }

    // ── PAY-5: Settlement Guard ────────────────────────────────────────

    public function test_settlement_requires_paid_gateway_status(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'TEST-SETTLE-NOPAID-001',
        ])->assertCreated();

        $intent = MemberPaymentIntent::query()->firstOrFail();

        $this->assertSame('PENDING', $intent->gateway_status);
        $this->assertNull($intent->settled_at);
        $this->assertDatabaseCount('pos_transactions', 0);
    }

    public function test_successful_settlement_marks_consumed_and_settled(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'TEST-SETTLE-OK-001',
        ])->assertCreated();

        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'PAID',
        ])->assertOk();

        $intent = MemberPaymentIntent::query()->firstOrFail();

        $this->assertSame('PAID', $intent->gateway_status);
        $this->assertSame(MemberPaymentIntent::RESERVATION_CONSUMED, $intent->reservation_status);
        $this->assertSame('SETTLED', $intent->settlement_status);
        $this->assertNotNull($intent->settled_at);
        $this->assertNotNull($intent->settled_by_service);
    }

    // ── PAY-6: Canonical Lock Ordering ─────────────────────────────────

    public function test_duplicate_products_in_single_order_aggregate_quantity(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 3],
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
            'client_reference' => 'TEST-DUP-PRODUCT-001',
        ])->assertCreated();

        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'reserved' => 5,
        ]);
    }

    public function test_multiple_products_reserved_correctly(): void
    {
        $this->actingMember(['member:write']);
        $productA = $this->createProduct('Product A', 10000, 20);
        $productB = $this->createProduct('Product B', 15000, 10);

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [
                ['pos_product_id' => $productB->id, 'quantity' => 2],
                ['pos_product_id' => $productA->id, 'quantity' => 3],
            ],
            'client_reference' => 'TEST-MULTI-PRODUCT-001',
        ])->assertCreated();

        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $productA->id,
            'reserved' => 3,
        ]);
        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $productB->id,
            'reserved' => 2,
        ]);
    }

    // ── PAY-7: Expiry Worker ───────────────────────────────────────────

    public function test_expiry_worker_skips_paid_intents(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'TEST-EXPIRY-PAID-001',
        ])->assertCreated();

        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'PAID',
        ])->assertOk();

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $intent->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->artisan('orders:expire-reservations', ['--limit' => 10])
            ->expectsOutputToContain('expired 0')
            ->assertExitCode(0);

        $this->assertSame('PAID', $intent->refresh()->gateway_status);
    }

    public function test_recovery_command_resets_stale_charge_creating(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('Test Product', 10000, 20);

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'TEST-STALE-CHARGE-001',
        ])->assertCreated();

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $intent->forceFill([
            'gateway_status' => 'CHARGE_CREATING',
            'updated_at' => now()->subMinutes(10),
        ])->save();

        $this->artisan('orders:recover-stale-charges', ['--minutes' => 5])
            ->expectsOutputToContain('1 recovered')
            ->assertExitCode(0);

        $this->assertSame('PENDING', $intent->refresh()->gateway_status);
    }

    // ── PAY-1: Model State Combination Validation ──────────────────────

    public function test_model_is_state_combination_valid_for_normal_flow(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'gateway_status' => 'PENDING',
            'reservation_status' => 'RESERVED',
            'settlement_status' => 'NOT_SETTLED',
        ]);

        $this->assertTrue($intent->isStateCombinationValid());
    }

    public function test_model_detects_paid_plus_expired_reservation_as_invalid(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'gateway_status' => 'PAID',
            'reservation_status' => 'EXPIRED',
            'settlement_status' => 'NOT_SETTLED',
        ]);

        $this->assertFalse($intent->isStateCombinationValid());
    }

    public function test_model_detects_settled_without_paid_as_invalid(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'gateway_status' => 'PENDING',
            'reservation_status' => 'RESERVED',
            'settlement_status' => 'SETTLED',
            'settled_at' => now(),
        ]);

        $this->assertFalse($intent->isStateCombinationValid());
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function createProduct(string $name, float $price, int $stock): PosProduct
    {
        $category = PosCategory::factory()->create([
            'name' => 'Test',
            'slug' => 'test-'.uniqid(),
        ]);

        return PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $category->id,
            'name' => $name.uniqid(),
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
        $this->organization = Organization::factory()->create();
        $user = \App\Models\User::factory()->create(['organization_id' => $this->organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user, $abilities);

        return $member;
    }
}
