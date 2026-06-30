<?php

namespace Tests\Feature;

use App\Models\CoffeeOrder;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CoffeeOrderWorkflowTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config(['services.midtrans.server_key' => '']);
    }

    public function test_member_order_creates_trackable_coffee_order(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->posAdmin($organization);
        $member = $this->actingMember(['member:read', 'member:write'], $organization);
        $product = $this->coffeeProduct();

        $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $product->id,
            'quantity' => 1,
            'client_reference' => 'COFFEE-TRACK-001',
            'sugar_level' => 'No Sugar',
            'ice_level' => 'Warm',
            'cup_size' => 'Reguler',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'PENDING_PAYMENT')
            ->assertJsonPath('data.step', 0)
            ->assertJsonPath('data.items.0.sugar_level', 'No Sugar');

        $this->assertDatabaseCount('coffee_orders', 0);

        $this->postJson('/api/payments/webhook', [
            'reference' => \App\Models\MemberPaymentIntent::query()->firstOrFail()->gateway_reference,
            'status' => 'PAID',
        ])->assertOk();

        $order = CoffeeOrder::query()->with('transaction')->firstOrFail();
        $this->assertSame($member->id, $order->cooperative_member_id);
        $this->assertSame($product->id, $order->pos_product_id);
        $this->assertSame('COFFEE-TRACK-001', $order->transaction->client_reference);

        $this->assertTrue($member->user->notifications()->where('data->event_type', 'member.coffee_order.received')->exists());
        $this->assertTrue($admin->notifications()->where('data->event_type', 'admin.coffee_order.received')->exists());

        $this->getJson('/api/v1/member/notifications/recent?limit=5')
            ->assertOk()
            ->assertJsonPath('data.0.event_type', 'member.coffee_order.received')
            ->assertJsonPath('data.0.action.url', '/member/transactions');
    }

    public function test_admin_status_update_is_visible_to_member_status_endpoint(): void
    {
        $organization = Organization::factory()->create();
        $member = $this->actingMember(['member:read', 'member:write'], $organization);
        $product = $this->coffeeProduct();

        $response = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $product->id,
            'quantity' => 1,
            'client_reference' => 'COFFEE-TRACK-002',
        ])->assertCreated();

        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'PAID',
        ])->assertOk();

        $orderId = CoffeeOrder::query()->firstOrFail()->id;
        $admin = $this->posAdmin($organization);

        $this->actingAs($admin)
            ->put(route('cooperative.pos.coffee-orders.update-status', $orderId), [
                'status' => CoffeeOrder::STATUS_BREWING,
            ])
            ->assertRedirect();

        Sanctum::actingAs($member->user, ['member:read']);
        $this->getJson("/api/v1/member/coffee/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('data.status', CoffeeOrder::STATUS_BREWING)
            ->assertJsonPath('data.step', 1)
            ->assertJsonPath('data.status_label', 'Kopi Sedang Diseduh');

        $this->assertTrue(
            $member->user
                ->notifications()
                ->where('data->event_type', 'member.coffee_order.status_changed')
                ->where('data->metadata->status', CoffeeOrder::STATUS_BREWING)
                ->exists()
        );
    }

    public function test_member_cannot_view_another_members_coffee_order(): void
    {
        $member = $this->actingMember(['member:write']);
        $product = $this->coffeeProduct();

        $response = $this->postJson('/api/v1/member/coffee/orders', [
            'pos_product_id' => $product->id,
            'quantity' => 1,
            'client_reference' => 'COFFEE-TRACK-003',
        ])->assertCreated();

        $this->postJson('/api/payments/webhook', [
            'reference' => $response->json('data.charge.reference'),
            'status' => 'PAID',
        ])->assertOk();

        $orderId = CoffeeOrder::query()->firstOrFail()->id;

        $otherUser = User::factory()->create();
        CooperativeMember::factory()->active()->create(['user_id' => $otherUser->id]);
        Sanctum::actingAs($otherUser, ['member:read']);

        $this->getJson('/api/v1/member/coffee/orders/'.$orderId)
            ->assertForbidden();

        $this->assertSame($member->id, CoffeeOrder::query()->firstOrFail()->cooperative_member_id);
    }

    private function coffeeProduct(): PosProduct
    {
        $category = PosCategory::factory()->create([
            'name' => 'Espresso',
            'slug' => 'espresso',
        ]);

        return PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'name' => 'Espresso Kojaya',
            'cost_price' => 8000,
            'sale_price' => 18000,
            'stock' => 10,
        ]);
    }

    /**
     * @param  list<string>  $abilities
     */
    private function actingMember(array $abilities, ?Organization $organization = null): CooperativeMember
    {
        $user = User::factory()->create([
            'organization_id' => $organization?->id,
        ]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization?->id ?? Organization::factory(),
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user, $abilities);

        return $member;
    }

    private function posAdmin(?Organization $organization = null): User
    {
        $user = User::factory()->create([
            'organization_id' => $organization?->id,
        ]);
        $user->assignRole('Admin Koperasi');
        $user->givePermissionTo('access_cooperative_pos');

        return $user;
    }
}
