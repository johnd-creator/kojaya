<?php

namespace Tests\Feature;

use App\Models\NotificationPreference;
use App\Models\User;
use Database\Factories\NotificationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_user_can_fetch_notification_preferences(): void
    {
        $user = User::factory()->create();

        NotificationPreference::create([
            'user_id' => $user->id,
            'email_enabled' => false,
            'database_enabled' => true,
            'push_enabled' => true,
            'channels' => ['database', 'push'],
        ]);

        $this->actingAs($user)
            ->getJson('/api/notifications/preferences')
            ->assertOk()
            ->assertJsonPath('data.email_enabled', false)
            ->assertJsonPath('data.database_enabled', true)
            ->assertJsonPath('data.push_enabled', true)
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
