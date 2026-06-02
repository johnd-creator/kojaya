<?php

namespace Tests\Feature;

use App\Jobs\ProcessNotificationOutbox;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\NotificationOutbox;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Integrations\PushNotificationService;
use App\Services\Integrations\WhatsAppNotificationService;
use App\Services\NotificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Sprint6WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_whatsapp_dues_reminder_command_queues_only_opted_in_members(): void
    {
        Queue::fake();

        $optedInUser = User::factory()->create();
        $optedInMember = CooperativeMember::factory()->active()->create([
            'user_id' => $optedInUser->id,
            'phone' => '081234567890',
        ]);
        NotificationPreference::query()->create([
            'user_id' => $optedInUser->id,
            'whatsapp_enabled' => true,
            'whatsapp_phone' => '081234567890',
        ]);

        $optedOutUser = User::factory()->create();
        $optedOutMember = CooperativeMember::factory()->active()->create([
            'user_id' => $optedOutUser->id,
            'phone' => '081111111111',
        ]);
        NotificationPreference::query()->create([
            'user_id' => $optedOutUser->id,
            'whatsapp_enabled' => false,
            'whatsapp_phone' => '081111111111',
        ]);

        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'SAVINGS',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        foreach ([$optedInMember, $optedOutMember] as $member) {
            CooperativeDuesInvoice::query()->create([
                'cooperative_member_id' => $member->id,
                'cooperative_contribution_type_id' => $type->id,
                'period' => now()->format('Y-m'),
                'amount' => 100000,
                'paid_amount' => 0,
                'due_date' => today()->addDays(2),
                'status' => 'UNPAID',
            ]);
        }

        $this->artisan('notifications:whatsapp-dues-reminders --days=3')
            ->expectsOutput('Queued 1 WhatsApp dues reminder notifications.')
            ->assertSuccessful();

        $this->assertDatabaseHas('notification_outboxes', [
            'user_id' => $optedInUser->id,
            'event_type' => 'cooperative.dues.reminder',
            'channel' => 'whatsapp',
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('notification_outboxes', [
            'user_id' => $optedOutUser->id,
            'channel' => 'whatsapp',
        ]);
    }

    public function test_whatsapp_outbox_delivery_posts_to_configured_provider(): void
    {
        config([
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.endpoint' => 'https://graph.facebook.test/v20.0',
        ]);

        Http::fake([
            'graph.facebook.test/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.test'],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        NotificationPreference::query()->create([
            'user_id' => $user->id,
            'whatsapp_enabled' => true,
            'whatsapp_phone' => '081234567890',
        ]);

        $outbox = NotificationOutbox::factory()->create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'event_type' => 'test.whatsapp',
            'payload' => [
                'title' => 'Judul pesan',
                'message' => 'Isi pesan WhatsApp',
                'data' => ['reference' => 'WA-1'],
            ],
            'available_at' => now(),
        ]);

        (new ProcessNotificationOutbox($outbox->id))->handle(
            app(NotificationService::class),
            app(PushNotificationService::class),
            app(WhatsAppNotificationService::class),
        );

        $this->assertSame('sent', $outbox->refresh()->status);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://graph.facebook.test/v20.0/123456789/messages'
            && $request['to'] === '6281234567890'
            && $request['type'] === 'text'
            && str_contains($request['text']['body'], 'Isi pesan WhatsApp'));
    }

    public function test_leave_status_update_queues_whatsapp_notification_for_employee(): void
    {
        Queue::fake();

        $approver = User::factory()->create();
        $approver->givePermissionTo('approve_leave');

        $employeeUser = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $employeeUser->id]);
        NotificationPreference::query()->create([
            'user_id' => $employeeUser->id,
            'whatsapp_enabled' => true,
            'whatsapp_phone' => '081298765432',
        ]);

        $leave = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => LeaveType::factory()->create(['name' => 'Cuti Tahunan'])->id,
            'status' => 'Pending',
        ]);

        $this->actingAs($approver)
            ->put(route('leaves.update-status', $leave), ['status' => 'Approved'])
            ->assertRedirect(route('leaves.index'));

        $this->assertDatabaseHas('notification_outboxes', [
            'user_id' => $employeeUser->id,
            'event_type' => 'ess.leave.status',
            'channel' => 'whatsapp',
            'status' => 'pending',
        ]);
    }

    public function test_whatsapp_preferences_can_be_updated_as_opt_out_control(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/notifications/preferences', [
                'whatsapp_enabled' => true,
                'whatsapp_phone' => '081234567890',
            ])
            ->assertOk()
            ->assertJsonPath('data.whatsapp_enabled', true)
            ->assertJsonPath('data.whatsapp_phone', '081234567890');
    }
}
