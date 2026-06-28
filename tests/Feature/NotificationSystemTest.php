<?php

namespace Tests\Feature;

use App\Models\NotificationPreference;
use App\Models\User;
use Database\Factories\NotificationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up session for authentication
        $this->withSession([]);
    }

    public function test_user_can_fetch_notifications(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'notification_type',
                        'event_type',
                        'category',
                        'severity',
                        'title',
                        'message',
                        'subject',
                        'action',
                        'metadata',
                        'data',
                        'read_at',
                        'created_at',
                        'is_read',
                    ],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_user_can_fetch_unread_count(): void
    {
        $user = User::factory()->create();

        NotificationFactory::new()->forUser($user)->count(2)->create(['read_at' => null]);
        NotificationFactory::new()->forUser($user)->read()->create();

        $this->actingAs($user)
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJson(['count' => 2]);
    }

    public function test_user_can_fetch_recent_notifications_and_summary(): void
    {
        $user = User::factory()->create();

        NotificationFactory::new()->forUser($user)->create([
            'data' => [
                'event_type' => 'manager.loan.review_required',
                'category' => 'loan',
                'severity' => 'warning',
                'title' => 'Review pinjaman diperlukan',
                'message' => 'Pengajuan pinjaman menunggu review.',
            ],
            'read_at' => null,
        ]);
        NotificationFactory::new()->forUser($user)->create([
            'data' => [
                'event_type' => 'admin.payment.approval_required',
                'category' => 'payment',
                'severity' => 'warning',
                'title' => 'Pembayaran perlu diverifikasi',
                'message' => 'Bukti pembayaran masuk.',
            ],
            'read_at' => null,
        ]);
        NotificationFactory::new()->forUser($user)->read()->create([
            'data' => [
                'event_type' => 'member.payment.approved',
                'category' => 'payment',
                'severity' => 'success',
                'title' => 'Pembayaran disetujui',
                'message' => 'Pembayaran selesai.',
            ],
        ]);

        $this->actingAs($user)
            ->getJson('/api/notifications/recent?limit=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.unread_count', 2);

        $this->actingAs($user)
            ->getJson('/api/notifications/summary')
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonPath('by_category.loan', 1)
            ->assertJsonPath('by_category.payment', 1)
            ->assertJsonPath('by_severity.warning', 2);
    }

    public function test_user_can_filter_notifications_by_status_category_and_severity(): void
    {
        $user = User::factory()->create();

        NotificationFactory::new()->forUser($user)->create([
            'data' => [
                'category' => 'loan',
                'severity' => 'warning',
                'title' => 'Loan',
            ],
            'read_at' => null,
        ]);
        NotificationFactory::new()->forUser($user)->create([
            'data' => [
                'category' => 'payment',
                'severity' => 'info',
                'title' => 'Payment',
            ],
            'read_at' => null,
        ]);
        NotificationFactory::new()->forUser($user)->read()->create([
            'data' => [
                'category' => 'loan',
                'severity' => 'warning',
                'title' => 'Read Loan',
            ],
        ]);

        $this->actingAs($user)
            ->getJson('/api/notifications?status=unread&category=loan&severity=warning')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Loan');
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = NotificationFactory::new()->forUser($otherUser)->create();

        $this->actingAs($user)
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('success', false);

        $this->assertEquals(1, $otherUser->fresh()->unreadNotifications()->count());
    }

    public function test_user_can_fetch_notification_preferences(): void
    {
        $user = User::factory()->create();

        NotificationPreference::create([
            'user_id' => $user->id,
            'email_enabled' => false,
            'database_enabled' => true,
            'push_enabled' => true,
            'categories' => [
                'loan' => ['database', 'push'],
                'payment' => ['database'],
            ],
            'channels' => ['database', 'push'],
        ]);

        $this->actingAs($user)
            ->getJson('/api/notifications/preferences')
            ->assertOk()
            ->assertJsonPath('data.email_enabled', false)
            ->assertJsonPath('data.database_enabled', true)
            ->assertJsonPath('data.push_enabled', true)
            ->assertJsonPath('data.categories.loan.0', 'database')
            ->assertJsonPath('data.categories.loan.1', 'push')
            ->assertJsonPath('data.channels.0', 'database')
            ->assertJsonPath('data.channels.1', 'push');
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->putJson('/api/notifications/preferences', [
                'email_enabled' => false,
                'database_enabled' => true,
                'push_enabled' => false,
                'categories' => [
                    'loan' => ['database'],
                ],
                'channels' => ['database'],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify preferences were updated
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'email_enabled' => false,
            'database_enabled' => true,
        ]);
    }

    public function test_member_token_notifications_contract_matches_session_api(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['member:read', 'member:write']);

        NotificationFactory::new()->forUser($user)->create([
            'data' => [
                'event_type' => 'member.loan.applied',
                'category' => 'loan',
                'severity' => 'info',
                'title' => 'Pengajuan pinjaman terkirim',
                'message' => 'Pengajuan terkirim.',
            ],
        ]);

        $this->getJson('/api/v1/member/notifications/recent')
            ->assertOk()
            ->assertJsonPath('data.0.event_type', 'member.loan.applied');

        $this->getJson('/api/v1/member/notifications/summary')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    public function test_admin_token_notifications_contract_is_available(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['cooperative:read', 'cooperative:write']);

        NotificationFactory::new()->forUser($user)->create([
            'data' => [
                'event_type' => 'admin.payment.approval_required',
                'category' => 'payment',
                'severity' => 'warning',
                'title' => 'Pembayaran perlu diverifikasi',
                'message' => 'Bukti pembayaran masuk.',
            ],
        ]);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.event_type', 'admin.payment.approval_required');

        $this->getJson('/api/v1/notifications/summary')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification = NotificationFactory::new()->forUser($user)->create();

        $response = $this->actingAs($user)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify notification is now read
        $user->refresh();
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();

        NotificationFactory::new()->forUser($user)->count(3)->create(['read_at' => null]);

        $response = $this->actingAs($user)
            ->postJson('/api/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify all notifications are now read
        $user->refresh();
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }
}
