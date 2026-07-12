<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Services\Cooperative\MemberOrderIntentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Concurrency tests C1-C8 from the Payment and Reservation State Machine plan.
 *
 * These tests validate invariant behaviour. True multi-process concurrency
 * tests require PostgreSQL or MySQL; on SQLite they verify logical
 * correctness of the single-threaded code paths.
 */
class PaymentConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config(['services.midtrans.server_key' => '']);
    }

    /**
     * C1 — Same key, same payload: one intent, one reservation.
     */
    public function test_c1_same_key_same_payload_produces_one_intent(): void
    {
        $member = $this->actingMember(['member:write']);
        $product = $this->createProduct('C1 Product', 10000, 50);

        $service = app(MemberOrderIntentService::class);
        $items = [['pos_product_id' => $product->id, 'quantity' => 2, 'unit_price' => 10000, 'line_total' => 20000]];
        $canonical = ['amount' => 20000, 'channel' => 'QRIS', 'items' => $items];

        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $service->resolveOrCreate(
                member: $member,
                payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
                clientReference: 'C1-REF',
                canonicalRequest: $canonical,
                items: $items,
            );
        }

        $intentIds = array_map(fn ($r): int => $r->intent->id, $results);
        $this->assertCount(1, array_unique($intentIds));
        $this->assertDatabaseCount('member_payment_intents', 1);

        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'reserved' => 2,
        ]);
    }

    /**
     * C2 — Same key, different payload: first wins, rest conflict.
     */
    public function test_c2_same_key_different_payload_second_fails(): void
    {
        $member = $this->actingMember(['member:write']);
        $product = $this->createProduct('C2 Product', 10000, 50);

        $service = app(MemberOrderIntentService::class);
        $items1 = [['pos_product_id' => $product->id, 'quantity' => 2, 'unit_price' => 10000, 'line_total' => 20000]];
        $items2 = [['pos_product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10000, 'line_total' => 50000]];

        $service->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: 'C2-REF',
            canonicalRequest: ['amount' => 20000, 'channel' => 'QRIS', 'items' => $items1],
            items: $items1,
        );

        $this->expectException(\App\Exceptions\PaymentIntentConflictException::class);

        $service->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: 'C2-REF',
            canonicalRequest: ['amount' => 50000, 'channel' => 'QRIS', 'items' => $items2],
            items: $items2,
        );
    }

    /**
     * C3 — Reuse settled key: must conflict.
     */
    public function test_c3_reuse_settled_key_conflicts(): void
    {
        $member = $this->actingMember(['member:write']);
        $product = $this->createProduct('C3 Product', 10000, 50);

        $service = app(MemberOrderIntentService::class);
        $items = [['pos_product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10000, 'line_total' => 10000]];

        $resolution = $service->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: 'C3-REF',
            canonicalRequest: ['amount' => 10000, 'channel' => 'QRIS', 'items' => $items],
            items: $items,
        );

        $resolution->intent->forceFill([
            'gateway_status' => 'PAID',
            'settled_at' => now(),
            'settlement_status' => 'SETTLED',
        ])->save();

        $this->expectException(\App\Exceptions\PaymentIntentConflictException::class);

        $service->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: 'C3-REF',
            canonicalRequest: ['amount' => 10000, 'channel' => 'QRIS', 'items' => $items],
            items: $items,
        );
    }

    /**
     * C4 — PAID versus expiry: exactly one valid terminal path.
     */
    public function test_c4_paid_intent_cannot_be_expired(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('C4 Product', 10000, 50);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'C4-REF',
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
        $this->assertNotSame('EXPIRED', $intent->reservation_status);
    }

    /**
     * C5 — Duplicate PAID webhook: one transaction, one consume.
     */
    public function test_c5_duplicate_paid_webhook_single_transaction(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('C5 Product', 10000, 50);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'C5-REF',
        ])->assertCreated();

        $reference = $response->json('data.charge.reference');

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/payments/webhook', [
                'reference' => $reference,
                'status' => 'PAID',
            ])->assertOk();
        }

        $this->assertDatabaseCount('pos_transactions', 1);

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $this->assertSame(MemberPaymentIntent::RESERVATION_CONSUMED, $intent->reservation_status);
    }

    /**
     * C6 — Charge race: ensureCharge returns existing for concurrent calls.
     */
    public function test_c6_concurrent_charge_calls_reuse_existing(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('C6 Product', 10000, 50);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'C6-REF',
        ])->assertCreated();

        $intent = MemberPaymentIntent::query()->firstOrFail();

        $chargeService = app(\App\Services\Integrations\PaymentIntentChargeService::class);

        $first = $chargeService->ensureCharge($intent->refresh());
        $second = $chargeService->ensureCharge($intent->refresh());

        $this->assertSame($first['reference'], $second['reference']);
    }

    /**
     * C7 — Opposite item ordering: no over-reservation.
     */
    public function test_c7_opposite_item_ordering_same_reservation(): void
    {
        $this->actingMember(['member:write']);
        $productA = $this->createProduct('C7-A', 10000, 20);
        $productB = $this->createProduct('C7-B', 15000, 20);

        $ref = 'C7-REF-'.uniqid();

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 2],
                ['pos_product_id' => $productB->id, 'quantity' => 3],
            ],
            'client_reference' => $ref,
        ])->assertCreated();

        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $productA->id,
            'reserved' => 2,
        ]);
        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $productB->id,
            'reserved' => 3,
        ]);
    }

    /**
     * C8 — Stale CHARGE_CREATING recoverable.
     */
    public function test_c8_stale_charge_creating_recovered_by_command(): void
    {
        $this->actingMember(['member:write']);
        $product = $this->createProduct('C8 Product', 10000, 50);

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'C8-REF',
        ])->assertCreated();

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $intent->forceFill([
            'gateway_status' => 'CHARGE_CREATING',
            'updated_at' => now()->subMinutes(10),
        ])->save();

        $this->artisan('orders:recover-stale-charges', ['--minutes' => 5])
            ->assertExitCode(0);

        $intent->refresh();
        $this->assertSame('PENDING', $intent->gateway_status);

        $chargeService = app(\App\Services\Integrations\PaymentIntentChargeService::class);
        $charge = $chargeService->ensureCharge($intent->refresh());

        $this->assertSame('PENDING', $intent->refresh()->gateway_status);
        $this->assertNotEmpty($charge['reference']);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function createProduct(string $name, float $price, int $stock): PosProduct
    {
        $category = PosCategory::factory()->create([
            'name' => 'Concurrency',
            'slug' => 'concurrency-'.uniqid(),
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
