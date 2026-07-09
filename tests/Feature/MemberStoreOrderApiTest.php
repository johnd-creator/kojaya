<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberStoreOrderApiTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config(['services.midtrans.server_key' => '']);
    }

    public function test_member_can_view_store_catalog(): void
    {
        $this->actingMember(['member:read']);
        $category = PosCategory::factory()->create([
            'name' => 'Sembako',
            'slug' => 'sembako',
        ]);

        PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'name' => 'Beras Premium 5kg',
            'sale_price' => 75000,
            'stock' => 20,
        ]);

        $this->getJson('/api/v1/member/store/catalog')
            ->assertOk()
            ->assertJsonPath('data.categories.1', 'Sembako')
            ->assertJsonPath('data.items.0.name', 'Beras Premium 5kg')
            ->assertJsonPath('data.items.0.category', 'Sembako');
    }

    public function test_member_can_place_store_order(): void
    {
        $member = $this->actingMember(['member:read', 'member:write']);
        $category = PosCategory::factory()->create(['name' => 'Sembako', 'slug' => 'sembako']);
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'name' => 'Minyak Goreng 2L',
            'cost_price' => 25000,
            'sale_price' => 32000,
            'stock' => 15,
        ]);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
            'client_reference' => 'MOBILE-STORE-001',
            'fulfillment_method' => 'PICKUP',
            'pickup_location' => 'Kantor Koperasi',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'PENDING_PAYMENT')
            ->assertJsonPath('data.total_amount', 64000)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.fulfillment_method', 'PICKUP')
            ->assertJsonPath('data.charge.provider', 'internal');

        $this->assertDatabaseCount('pos_transactions', 0);
        $this->assertSame(15, (int) $product->refresh()->stock);

        $intentId = $response->json('data.payment_intent_id');

        $this->getJson("/api/v1/member/payment-intents/{$intentId}")
            ->assertOk()
            ->assertJsonPath('data.is_paid', false)
            ->assertJsonPath('data.is_settled', false)
            ->assertJsonPath('data.payable_type', 'store_order');

        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'PAID',
        ])->assertOk()
            ->assertJsonPath('data.gateway_status', 'PAID');

        $transaction = PosTransaction::query()
            ->where('client_reference', 'MOBILE-STORE-001')
            ->with(['items', 'payments'])
            ->firstOrFail();

        $this->assertSame($member->id, $transaction->cooperative_member_id);
        $this->assertSame('QRIS', $transaction->payments->first()->payment_method);
        $this->assertSame(2, (int) $transaction->items->first()->quantity);
        $this->assertSame(13, (int) $product->refresh()->stock);

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $this->assertNotNull($intent->refresh()->settled_at);

        $this->getJson("/api/v1/member/payment-intents/{$intentId}")
            ->assertOk()
            ->assertJsonPath('data.is_settled', true)
            ->assertJsonPath('data.settled_resource.type', 'store_transaction')
            ->assertJsonPath('data.settled_resource.id', $transaction->id);
    }

    public function test_store_order_is_idempotent_via_client_reference(): void
    {
        $this->actingMember(['member:write']);
        $category = PosCategory::factory()->create(['name' => 'ATK', 'slug' => 'atk']);
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'name' => 'Buku Tulis',
            'cost_price' => 3000,
            'sale_price' => 5000,
            'stock' => 50,
        ]);

        $payload = [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 3]],
            'client_reference' => 'MOBILE-STORE-IDEMPOTENT',
        ];

        $first = $this->postJson('/api/v1/member/store/orders', $payload)->assertCreated();
        $second = $this->postJson('/api/v1/member/store/orders', $payload)->assertCreated();

        $this->assertSame($first->json('data.payment_intent_id'), $second->json('data.payment_intent_id'));
        $this->assertDatabaseCount('member_payment_intents', 1);
    }

    public function test_store_order_rejects_insufficient_stock(): void
    {
        $this->actingMember(['member:write']);
        $category = PosCategory::factory()->create(['name' => 'ATK', 'slug' => 'atk']);
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'name' => 'Pulpen',
            'cost_price' => 1000,
            'sale_price' => 2500,
            'stock' => 1,
        ]);

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 5]],
            'client_reference' => 'MOBILE-STORE-STOCK',
        ])->assertStatus(422);

        $this->assertDatabaseCount('member_payment_intents', 0);
    }

    /**
     * @param  list<string>  $abilities
     */
    private function actingMember(array $abilities): CooperativeMember
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user, $abilities);

        return $member;
    }
}
