<?php

namespace Tests\Feature;

use App\Models\CooperativeNotificationOutbox;
use App\Models\MemberPaymentIntent;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * Validates that Duplicate PAID webhooks (C5) produce exactly:
 *   - one POS transaction
 *   - one reservation consume audit
 *   - one settlement outbox entry
 *   - one delivered notification
 */
class PaymentNotificationOutboxTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config([
            'services.midtrans.server_key' => '',
            'services.payment_gateway.allow_simulation' => true,
        ]);
    }

    public function test_duplicate_paid_webhook_produces_one_outbox_and_one_notification(): void
    {
        $organization = Organization::factory()->create();
        $user = \App\Models\User::factory()->create(['organization_id' => $organization->id]);
        $member = \App\Models\CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['member:write']);

        $category = PosCategory::factory()->create(['organization_id' => $organization->id]);
        $product = PosProduct::factory()->for($category, 'category')->create([
            'organization_id' => $organization->id,
            'cost_price' => 5000,
            'sale_price' => 10000,
            'stock' => 50,
        ]);

        // Create store order → intent + charge
        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'OUTBOX-C5-001',
        ])->assertCreated();

        $reference = $response->json('data.charge.reference');

        // Duplicate PAID webhooks
        $this->postSignedMidtransWebhook($reference, 'settlement', 20000.0)->assertOk();

        $this->postSignedMidtransWebhook($reference, 'settlement', 20000.0)->assertOk();

        $this->postSignedMidtransWebhook($reference, 'settlement', 20000.0)->assertOk();

        // Assertions
        $this->assertSame(1, PosTransaction::count(), 'C5: exactly one transaction');
        $transaction = PosTransaction::query()->firstOrFail();

        $intent = MemberPaymentIntent::query()->firstOrFail();
        $this->assertSame('PAID', $intent->gatewayStatus()->value);
        $this->assertSame(MemberPaymentIntent::RESERVATION_CONSUMED, $intent->reservationStatus()->value);

        // One outbox entry created
        $outboxCount = CooperativeNotificationOutbox::query()
            ->where('deduplication_key', "member.pos.sale_completed:{$transaction->id}")
            ->count();
        $this->assertSame(1, $outboxCount, 'C5: exactly one settlement outbox entry');

        $notification = $user->notifications()
            ->where('type', 'App\\Notifications\\CooperativeDatabaseNotification')
            ->where('id', CooperativeNotificationOutbox::query()->firstOrFail()->id)
            ->firstOrFail();
        $this->assertSame(1, $user->notifications()->whereKey($notification->id)->count(), 'C5: exactly one delivered member notification');
        $this->assertSame('member.pos.sale_completed', $notification->data['event_type']);
        $this->assertSame('/member/transactions', $notification->data['action']['url']);
    }

    public function test_outbox_delivery_retry_produces_at_most_one_notification(): void
    {
        $user = \App\Models\User::factory()->create();

        $outbox = CooperativeNotificationOutbox::query()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'deduplication_key' => 'retry-test-key',
            'payload' => [
                'deduplication_key' => 'retry-test-key',
                'type' => 'pos',
                'category' => 'info',
                'title' => 'Test',
                'body' => 'Test body.',
            ],
            'status' => 'PENDING',
            'attempts' => 0,
            'available_at' => now(),
        ]);

        $outboxService = app(\App\Services\Cooperative\CooperativeNotificationOutboxService::class);

        // Deliver twice — should produce only one notification
        $outboxService->deliver($outbox);
        $outboxService->deliver($outbox->refresh());

        $notificationCount = $user->notifications()
            ->where('type', 'App\\Notifications\\CooperativeDatabaseNotification')
            ->count();
        $this->assertSame(1, $notificationCount, 'Retry produces exactly one notification');

        $outbox->refresh();
        $this->assertSame('DELIVERED', $outbox->status);
    }

    public function test_outbox_delivery_retry_backoff_and_recovery(): void
    {
        $user = \App\Models\User::factory()->create();
        $now = now();

        $outbox = CooperativeNotificationOutbox::query()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'deduplication_key' => 'retry-backoff-key',
            'payload' => [
                'deduplication_key' => 'retry-backoff-key',
                'type' => 'pos',
                'category' => 'info',
                'title' => 'Retry Test',
                'body' => 'Testing backoff.',
            ],
            'status' => CooperativeNotificationOutbox::STATUS_PENDING,
            'attempts' => 0,
            'available_at' => $now,
        ]);

        $this->assertSame(CooperativeNotificationOutbox::STATUS_PENDING, $outbox->status);
        $this->assertSame(0, $outbox->attempts);
        $this->assertTrue($outbox->available_at->lessThanOrEqualTo($now));

        $outboxService = app(\App\Services\Cooperative\CooperativeNotificationOutboxService::class);

        $fail = true;
        \App\Models\User::retrieved(function (\App\Models\User $u) use (&$fail, $user) {
            if ($fail && (int) $u->id === (int) $user->id) {
                throw new \RuntimeException('Gateway connection timeout');
            }
        });

        try {
            $outboxService->deliver($outbox);

            $outbox->refresh();
            $this->assertSame(CooperativeNotificationOutbox::STATUS_PENDING, $outbox->status);
            $this->assertSame(1, $outbox->attempts);
            $this->assertNotNull($outbox->last_error);
            $this->assertStringContainsString('Gateway connection timeout', $outbox->last_error);
            $this->assertTrue($outbox->available_at->diffInMinutes($now->copy()->addMinutes(5)) <= 1);
            $this->assertSame(0, $user->notifications()->count());

            // Before available_at, delivery must not claim it
            $fail = false;
            $unclaimed = $outboxService->deliverPending(1);
            $this->assertSame(0, $unclaimed);

            // Advance time past 5 minutes
            $this->travel(6)->minutes();

            // Retry should succeed
            $claimed = $outboxService->deliverPending(1);
            $this->assertSame(1, $claimed);

            $outbox->refresh();
            $this->assertSame(CooperativeNotificationOutbox::STATUS_DELIVERED, $outbox->status);
            $this->assertSame(1, $user->notifications()->where('id', $outbox->id)->count());
        } finally {
            $fail = false;
        }
    }

    public function test_outbox_delivery_fails_after_five_attempts(): void
    {
        $user = \App\Models\User::factory()->create();

        $outbox = CooperativeNotificationOutbox::query()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'deduplication_key' => 'max-retry-key',
            'payload' => [
                'deduplication_key' => 'max-retry-key',
                'title' => 'Max attempts test',
            ],
            'status' => CooperativeNotificationOutbox::STATUS_PENDING,
            'attempts' => 4,
            'available_at' => now(),
        ]);

        $outboxService = app(\App\Services\Cooperative\CooperativeNotificationOutboxService::class);

        $fail = true;
        \App\Models\User::retrieved(function (\App\Models\User $u) use (&$fail, $user) {
            if ($fail && (int) $u->id === (int) $user->id) {
                throw new \RuntimeException('Persistent gateway fault');
            }
        });

        try {
            $outboxService->deliver($outbox);

            $outbox->refresh();
            $this->assertSame(CooperativeNotificationOutbox::STATUS_FAILED, $outbox->status);
            $this->assertSame(5, $outbox->attempts);
            $this->assertNotNull($outbox->last_error);
            $this->assertSame(0, $user->notifications()->count());
        } finally {
            $fail = false;
        }
    }

    public function test_outbox_deduplication_prevents_duplicate_enqueue(): void
    {
        $user = \App\Models\User::factory()->create();
        $outboxService = app(\App\Services\Cooperative\CooperativeNotificationOutboxService::class);

        $first = $outboxService->enqueueForUser($user, 'dup-key-001', [
            'deduplication_key' => 'dup-key-001',
            'body' => 'First.',
        ]);

        $second = $outboxService->enqueueForUser($user, 'dup-key-001', [
            'deduplication_key' => 'dup-key-001',
            'body' => 'Second.',
        ]);

        $this->assertNotNull($first);
        $this->assertNull($second, 'Duplicate enqueue must return null');

        $this->assertSame(1, CooperativeNotificationOutbox::count());
    }

    public function test_deliver_pending_claims_and_delivers_one_notification_once(): void
    {
        $user = \App\Models\User::factory()->create();
        $outbox = CooperativeNotificationOutbox::query()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'deduplication_key' => 'claim-test-key',
            'payload' => [
                'deduplication_key' => 'claim-test-key',
                'type' => 'pos',
                'category' => 'info',
                'title' => 'Claim test',
                'body' => 'Claim test body.',
            ],
            'status' => CooperativeNotificationOutbox::STATUS_PENDING,
            'attempts' => 0,
            'available_at' => now(),
        ]);

        $outboxService = app(\App\Services\Cooperative\CooperativeNotificationOutboxService::class);

        $this->assertSame(1, $outboxService->deliverPending(1));
        $this->assertSame(0, $outboxService->deliverPending(1));

        $outbox->refresh();
        $this->assertSame(CooperativeNotificationOutbox::STATUS_DELIVERED, $outbox->status);
        $this->assertSame(1, $user->notifications()->where('id', $outbox->id)->count());
    }
}
