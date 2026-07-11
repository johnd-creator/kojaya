<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use Database\Seeders\CooperativeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberCoffeeOrderApiTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config(['services.midtrans.server_key' => '']);
    }

    public function test_member_can_view_coffee_menu(): void
    {
        $member = $this->actingMember(['member:read']);
        $category = PosCategory::factory()->create([
            'name' => 'Signature',
            'slug' => 'signature',
        ]);

        PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'name' => 'Kopi Susu Gula Aren',
            'sale_price' => 22000,
            'stock' => 10,
        ]);

        $this->getJson('/api/v1/member/coffee/menu')
            ->assertOk()
            ->assertJsonPath('data.categories.1', 'Signature')
            ->assertJsonPath('data.items.0.name', 'Kopi Susu Gula Aren')
            ->assertJsonPath('data.items.0.category', 'Signature');

        $this->assertSame('ACTIVE', $member->status);
    }

    public function test_seeded_coffee_products_are_available_in_member_menu(): void
    {
        $this->actingMember(['member:read']);
        $this->seed(CooperativeSeeder::class);

        $this->getJson('/api/v1/member/coffee/menu')
            ->assertOk()
            ->assertJsonPath('data.items.0.name', 'Cappuccino Velvet')
            ->assertJsonFragment(['name' => 'Kopi Susu Gula Aren'])
            ->assertJsonFragment(['category' => 'Signature'])
            ->assertJsonFragment(['category' => 'Espresso'])
            ->assertJsonFragment(['category' => 'Non-Coffee']);
    }

    public function test_member_can_place_coffee_order(): void
    {
        $member = $this->actingMember(['member:write']);
        $category = PosCategory::factory()->create([
            'name' => 'Espresso',
            'slug' => 'espresso',
        ]);
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'name' => 'Espresso Kojaya',
            'cost_price' => 8000,
            'sale_price' => 18000,
            'stock' => 10,
        ]);

        $response = $this->postJson('/api/v1/member/coffee/orders', [
            'items' => [
                [
                    'pos_product_id' => $product->id,
                    'quantity' => 2,
                    'sugar_level' => 'Less Sugar',
                    'ice_level' => 'Warm',
                    'cup_size' => 'Large',
                ],
            ],
            'client_reference' => 'MOBILE-COFFEE-001',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'PENDING_PAYMENT')
            ->assertJsonPath('data.transaction.total_amount', 36000)
            ->assertJsonPath('data.items.0.sugar_level', 'Less Sugar')
            ->assertJsonPath('data.items.0.cup_size', 'Large')
            ->assertJsonPath('data.charge.provider', 'internal');

        $this->assertDatabaseCount('pos_transactions', 0);
        $this->assertSame(10, (int) $product->refresh()->stock);
        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'quantity' => 10,
            'reserved' => 2,
        ]);

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'PAID',
        ])->assertOk()
            ->assertJsonPath('data.gateway_status', 'PAID');

        $transaction = PosTransaction::query()->where('client_reference', 'MOBILE-COFFEE-001')->with(['items', 'payments'])->firstOrFail();

        $this->assertSame($member->id, $transaction->cooperative_member_id);
        $this->assertSame('QRIS', $transaction->payments->first()->payment_method);
        $this->assertSame(2, (int) $transaction->items->first()->quantity);
        $this->assertSame(8, (int) $product->refresh()->stock);
        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'quantity' => 8,
            'reserved' => 0,
        ]);
        $this->assertNotNull($intent->refresh()->settled_at);
        $this->assertSame('MOBILE-COFFEE-001', $intent->client_reference);
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
