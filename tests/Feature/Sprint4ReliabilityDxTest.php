<?php

namespace Tests\Feature;

use App\Contracts\Cooperative\LoanServiceContract;
use App\Contracts\Integrations\PaymentGatewayProvider;
use App\Jobs\ProcessNotificationOutbox;
use App\Models\MobileDeviceToken;
use App\Models\NotificationOutbox;
use App\Models\User;
use App\Services\Cooperative\LoanService;
use App\Services\Integrations\MidtransPaymentProvider;
use App\Services\Integrations\PushNotificationService;
use App\Services\Integrations\WhatsAppNotificationService;
use App\Services\Monitoring\MetricsService;
use App\Services\NotificationOutboxService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Sprint4ReliabilityDxTest extends TestCase
{
    public function test_notification_outbox_retries_failed_push_delivery_without_manual_intervention(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        MobileDeviceToken::query()->create([
            'user_id' => $user->id,
            'app' => 'member',
            'device_id' => 'device-1',
            'platform' => 'android',
            'push_token' => 'fcm-token',
            'last_seen_at' => now(),
        ]);

        $outbox = NotificationOutbox::factory()->create([
            'user_id' => $user->id,
            'channel' => 'push',
            'max_attempts' => 3,
            'available_at' => now(),
        ]);

        (new ProcessNotificationOutbox($outbox->id))->handle(
            app(NotificationService::class),
            app(PushNotificationService::class),
            app(WhatsAppNotificationService::class),
        );

        $outbox->refresh();

        $this->assertSame('pending', $outbox->status);
        $this->assertSame(1, $outbox->attempts);
        $this->assertNotNull($outbox->available_at);
        $this->assertStringContainsString('Push notification delivery failed', $outbox->last_error);

        Queue::assertPushed(ProcessNotificationOutbox::class);
    }

    public function test_exhausted_outbox_failures_are_reported_to_monitoring(): void
    {
        $failed = NotificationOutbox::factory()->create([
            'status' => 'failed',
            'failed_at' => now(),
        ]);

        NotificationOutbox::factory()->create([
            'user_id' => $failed->user_id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->assertSame(1, app(MetricsService::class)->failedNotificationOutboxCount());
        $this->assertSame(1, app(\App\Monitoring\Health::class)->counts()['failed_notification_outboxes']);
    }

    public function test_notification_outbox_service_dispatches_after_enqueue(): void
    {
        Queue::fake();

        $outbox = app(NotificationOutboxService::class)->enqueueDatabase(
            User::factory()->create(),
            'cooperative.payment.approved',
            [
                'title' => 'Pembayaran disetujui',
                'message' => 'Pembayaran koperasi berhasil disetujui.',
            ],
        );

        $this->assertSame('pending', $outbox->status);

        Queue::assertPushed(ProcessNotificationOutbox::class, fn (ProcessNotificationOutbox $job): bool => $job->outboxId === $outbox->id);
    }

    public function test_sprint_4_service_contracts_are_bound_to_current_implementations(): void
    {
        $this->assertInstanceOf(LoanService::class, app(LoanServiceContract::class));
        $this->assertInstanceOf(MidtransPaymentProvider::class, app(PaymentGatewayProvider::class));
        $this->assertInstanceOf(MidtransPaymentProvider::class, app(\App\Services\Integrations\PaymentGatewayProvider::class));
    }

    public function test_openapi_wrapper_and_ci_quality_gates_are_configured(): void
    {
        $this->assertFileExists(base_path('bin/openapi.sh'));
        $this->assertStringContainsString('openapi:snapshot --check', file_get_contents(base_path('bin/openapi.sh')));

        $workflow = file_get_contents(base_path('.github/workflows/ci.yml'));

        $this->assertStringContainsString('bin/openapi.sh check', $workflow);
        $this->assertStringContainsString('php artisan test --compact --parallel --profile --coverage --min=70', $workflow);
        $this->assertStringContainsString('coverage: xdebug', $workflow);

        $this->assertStringContainsString(
            "Schedule::command('notifications:outbox:process --limit=100')->everyThirtySeconds()",
            file_get_contents(base_path('routes/console.php')),
        );
    }
}
