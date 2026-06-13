<?php

namespace Tests\Feature\Cooperative;

use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosSyncRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosSprint4OfflineSyncHardeningTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_same_key_and_same_payload_returns_existing_record(): void
    {
        $user = $this->cashier();
        $payload = [
            'idempotency_key' => 'idem-replay-1',
            'device_id' => 'DEVICE-A',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['noop' => 'yes'],
        ];
        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', $payload)->assertStatus(202);
        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', $payload)->assertStatus(202);

        $this->assertSame(1, PosSyncRequest::query()->count());
    }

    public function test_same_key_different_payload_returns_409(): void
    {
        $user = $this->cashier();
        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-conflict-1',
            'device_id' => 'DEVICE-A',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['amount' => 100],
        ])->assertStatus(202);

        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-conflict-1',
            'device_id' => 'DEVICE-A',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['amount' => 200],
        ])->assertStatus(409);
    }

    public function test_other_user_cannot_status_others_sync_request(): void
    {
        $owner = $this->cashier();
        $attacker = $this->cashier();

        Sanctum::actingAs($owner, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-private',
            'device_id' => 'DEVICE-OWNER',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['noop' => true],
        ])->assertStatus(202);

        Sanctum::actingAs($attacker, ['pos:read']);
        $this->getJson('/api/v1/pos/sync/status/idem-private?device_id=DEVICE-OWNER')->assertStatus(404);

        Sanctum::actingAs($owner, ['pos:read']);
        $this->getJson('/api/v1/pos/sync/status/idem-private?device_id=DEVICE-OWNER')
            ->assertStatus(200)
            ->assertJsonPath('idempotency_key', 'idem-private');
    }

    public function test_other_user_cannot_process_others_sync_request(): void
    {
        $owner = $this->cashier();
        $attacker = $this->cashier();

        Sanctum::actingAs($owner, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-private-process',
            'device_id' => 'DEVICE-OWNER',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['noop' => true],
        ])->assertStatus(202);

        Sanctum::actingAs($attacker, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/process/idem-private-process')
            ->assertStatus(404);
    }

    public function test_batch_only_processes_owned_requests(): void
    {
        $owner = $this->cashier();
        $other = $this->cashier();
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);

        Sanctum::actingAs($owner, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-batch-owner',
            'device_id' => 'DEVICE-OWNER',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'SPRINT4-OWNER',
                'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
                'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
            ],
        ])->assertStatus(202);

        Sanctum::actingAs($other, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-batch-other',
            'device_id' => 'DEVICE-OTHER',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'SPRINT4-OTHER',
                'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
                'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
            ],
        ])->assertStatus(202);

        Sanctum::actingAs($owner, ['pos:write']);
        $response = $this->postJson('/api/v1/pos/sync/batch', [
            'idempotency_keys' => ['idem-batch-owner', 'idem-batch-other'],
            'device_id' => 'DEVICE-OWNER',
        ])->json();

        $this->assertCount(1, $response['data']);
        $this->assertSame('idem-batch-owner', $response['data'][0]['idempotency_key']);
    }

    public function test_unsupported_endpoint_is_rejected_at_enqueue(): void
    {
        $user = $this->cashier();
        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-bad-endpoint',
            'device_id' => 'DEVICE-1',
            'endpoint' => 'pos.inventories.delete',
            'method' => 'DELETE',
            'payload' => ['id' => 1],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['endpoint']);
    }

    public function test_payload_hash_is_stored_for_replay(): void
    {
        $user = $this->cashier();
        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-hash',
            'device_id' => 'DEVICE-1',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['amount' => 100],
        ])->assertStatus(202);

        $syncRequest = PosSyncRequest::query()->firstOrFail();
        $this->assertNotNull($syncRequest->payload_hash);
        $this->assertSame(64, strlen($syncRequest->payload_hash));
    }

    public function test_payload_hash_is_canonicalized_across_key_order(): void
    {
        $user = $this->cashier();
        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-canon-A',
            'device_id' => 'DEVICE-CANON',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['b' => 1, 'a' => 2, 'nested' => ['y' => 3, 'x' => 4]],
        ])->assertStatus(202);

        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-canon-B',
            'device_id' => 'DEVICE-CANON',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['a' => 2, 'nested' => ['x' => 4, 'y' => 3], 'b' => 1],
        ])->assertStatus(202);

        $hashA = PosSyncRequest::query()->where('idempotency_key', 'idem-canon-A')->value('payload_hash');
        $hashB = PosSyncRequest::query()->where('idempotency_key', 'idem-canon-B')->value('payload_hash');
        $this->assertSame($hashA, $hashB);
    }

    public function test_same_user_different_device_cannot_claim_request(): void
    {
        $user = $this->cashier();
        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-device-A',
            'device_id' => 'DEVICE-A',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['noop' => true],
        ])->assertStatus(202);

        Sanctum::actingAs($user, ['pos:write']);
        $response = $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-device-A',
            'device_id' => 'DEVICE-B',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['noop' => true],
        ]);

        $response->assertStatus(409);
    }

    public function test_client_id_is_unique_per_device_with_domain_conflict_response(): void
    {
        $user = $this->cashier();
        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-client-A',
            'client_id' => 'client-fixed-1',
            'device_id' => 'DEVICE-FIXED',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['noop' => true],
        ])->assertStatus(202);

        Sanctum::actingAs($user, ['pos:write']);
        $response = $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-client-B',
            'client_id' => 'client-fixed-1',
            'device_id' => 'DEVICE-FIXED',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['noop' => true],
        ]);

        $response->assertStatus(409)
            ->assertJsonValidationErrors(['client_id']);
    }

    public function test_same_client_id_can_be_used_by_different_devices(): void
    {
        $user = $this->cashier();
        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-client-device-A',
            'client_id' => 'client-fixed-shared',
            'device_id' => 'DEVICE-A',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['noop' => true],
        ])->assertStatus(202);

        Sanctum::actingAs($user, ['pos:write']);
        $this->postJson('/api/v1/pos/sync/enqueue', [
            'idempotency_key' => 'idem-client-device-B',
            'client_id' => 'client-fixed-shared',
            'device_id' => 'DEVICE-B',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => ['noop' => true],
        ])->assertStatus(202);

        $this->assertSame(2, PosSyncRequest::query()->where('client_id', 'client-fixed-shared')->count());
    }

    private function cashier(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('access_cooperative_pos');

        return $user;
    }
}
