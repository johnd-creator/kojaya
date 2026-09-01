<?php

namespace Tests\Feature\Cooperative;

use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosSyncRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PosPhase6OfflineSyncTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_enqueue_is_idempotent_by_idempotency_key(): void
    {
        $user = $this->cashier();
        $payload = [
            'idempotency_key' => 'idem-1',
            'device_id' => 'DEVICE-PHASE6',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['x' => 1],
        ];

        $this->actingAs($user)->postJson('/api/v1/pos/sync/enqueue', $payload)->assertStatus(202);
        $this->actingAs($user)->postJson('/api/v1/pos/sync/enqueue', $payload)->assertStatus(202);
        $this->assertSame(1, PosSyncRequest::query()->count());
    }

    public function test_process_dispatches_to_transaction_service_with_replay(): void
    {
        $user = $this->cashier();
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create([
            'organization_id' => $user->organization_id,
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $idem = 'idem-tx-1';
        $payload = [
            'idempotency_key' => $idem,
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'PHASE6-OFFLINE',
                'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
                'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
            ],
        ];
        $this->actingAs($user)->postJson('/api/v1/pos/sync/enqueue', $payload)->assertStatus(202);

        $result = $this->actingAs($user)->postJson("/api/v1/pos/sync/process/{$idem}")->json();
        $this->assertSame(201, $result['status']);
        $this->assertFalse($result['replay']);
        $this->assertSame(9, (int) $product->fresh()->stock);

        $replay = $this->actingAs($user)->postJson("/api/v1/pos/sync/process/{$idem}")->json();
        $this->assertTrue($replay['replay']);
        $this->assertSame(9, (int) $product->fresh()->stock);
    }

    public function test_batch_processing_handles_multiple_keys(): void
    {
        $user = $this->cashier();
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create([
            'organization_id' => $user->organization_id,
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($user)->postJson('/api/v1/pos/sync/enqueue', [
                'idempotency_key' => "idem-batch-{$i}",
                'endpoint' => 'pos.transactions.store',
                'method' => 'POST',
                'payload' => [
                    'client_reference' => "PHASE6-BATCH-{$i}",
                    'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
                    'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
                ],
            ])->assertStatus(202);
        }

        $response = $this->actingAs($user)->postJson('/api/v1/pos/sync/batch', [
            'idempotency_keys' => ['idem-batch-1', 'idem-batch-2', 'idem-batch-3'],
        ])->json();

        $this->assertCount(3, $response['data']);
        foreach ($response['data'] as $r) {
            $this->assertSame(201, $r['status']);
        }
        $this->assertSame(7, (int) $product->fresh()->stock);
    }

    public function test_status_endpoint_reports_sync_state(): void
    {
        $user = $this->cashier();
        $this->actingAs($user)->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-status',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['noop' => true],
        ])->assertStatus(202);

        $response = $this->actingAs($user)->getJson('/api/v1/pos/sync/status/idem-status')->json();
        $this->assertSame(PosSyncRequest::STATUS_PENDING, $response['status']);
    }

    public function test_invalid_idempotency_key_returns_404(): void
    {
        $user = $this->cashier();
        $this->actingAs($user)->getJson('/api/v1/pos/sync/status/does-not-exist')->assertStatus(404);
    }

    public function test_catalog_endpoint_returns_active_products(): void
    {
        $user = $this->cashier();
        $category = PosCategory::factory()->create();
        PosProduct::factory()->count(3)->create([
            'organization_id' => $user->organization_id,
            'pos_category_id' => $category->id,
            'is_active' => true,
            'is_discontinued' => false,
        ]);
        PosProduct::factory()->create([
            'organization_id' => $user->organization_id,
            'pos_category_id' => $category->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/pos/sync/catalog')->json();
        $this->assertCount(3, $response['data']);
        $this->assertNotNull($response['synced_at']);
    }

    private function cashier(): User
    {
        $user = User::factory()->create(['organization_id' => Organization::factory()]);
        $user->givePermissionTo('access_cooperative_pos');
        \Laravel\Sanctum\Sanctum::actingAs($user, ['pos:read', 'pos:write']);

        return $user;
    }
}
