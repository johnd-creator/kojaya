<?php

namespace Tests\Feature;

use App\Listeners\FailedJobListener;
use App\Models\User;
use App\Monitoring\Health;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseD1ObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_returns_enriched_status(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $response = $this->actingAs($user)->get('/monitoring/health');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Monitoring/Health')
            ->has('health.status')
            ->has('health.components.database')
            ->has('health.components.queue')
            ->has('health.components.storage')
            ->has('health.components.app')
            ->has('health.timestamp')
        );
    }

    public function test_health_check_requires_permission(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $response = $this->actingAs($user)->get('/monitoring/health');

        $response->assertForbidden();
    }

    public function test_metrics_page_requires_permission(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $response = $this->actingAs($user)->get('/monitoring/metrics');

        $response->assertForbidden();
    }

    public function test_metrics_endpoint_returns_data(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $response = $this->actingAs($user)->get('/monitoring/metrics');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Monitoring/Metrics')
            ->has('metrics.pending_approvals')
            ->has('metrics.overdue_loan_ratio')
            ->has('metrics.queue_failures')
            ->has('metrics.generated_at')
        );
    }

    public function test_correlation_id_middleware_adds_header(): void
    {
        $response = $this->getJson('/api/openapi.json', [
            'X-Correlation-ID' => 'test-correlation-123',
        ]);

        $response->assertHeader('X-Correlation-ID');
    }

    public function test_api_response_time_header_is_recorded(): void
    {
        $response = $this->getJson('/api/openapi.json');

        $response->assertHeader('X-Response-Time-Ms');
        $this->assertIsNumeric($response->headers->get('X-Response-Time-Ms'));
    }

    public function test_health_failure_response_does_not_expose_exception_message(): void
    {
        DB::shouldReceive('connection')
            ->once()
            ->andThrow(new \RuntimeException('password=synthetic-secret'));

        $result = (new Health)->checkDatabase();
        $encodedResult = json_encode($result, JSON_THROW_ON_ERROR);

        $this->assertSame('error', $result['status']);
        $this->assertSame('DATABASE_UNAVAILABLE', $result['error_code']);
        $this->assertArrayNotHasKey('message', $result);
        $this->assertStringNotContainsString('synthetic-secret', $encodedResult);
    }

    public function test_failed_job_listener_sends_notification(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('System Admin');

        $job = new class
        {
            public function resolveName(): string
            {
                return 'App\\Jobs\\SendPushNotification';
            }

            public function getQueue(): ?string
            {
                return 'default';
            }
        };

        $event = new JobFailed(
            'test-connection',
            $job,
            new \RuntimeException('Test failure for observability check')
        );

        $listener = new FailedJobListener;
        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'type' => 'job_failed',
        ]);
    }

    public function test_failed_job_listener_is_registered_with_event_dispatcher(): void
    {
        $listeners = app(Dispatcher::class)->getListeners(JobFailed::class);

        $this->assertNotEmpty($listeners);
    }

    public function test_metrics_page_contains_operational_data(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $response = $this->actingAs($user)->get('/monitoring/metrics');

        $response->assertOk();
        $props = $response->inertiaProps();
        $metrics = $props['metrics'];

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('pending_approvals', $metrics);
        $this->assertArrayHasKey('overdue_loan_ratio', $metrics);
        $this->assertArrayHasKey('queue_failures', $metrics);
        $this->assertArrayHasKey('generated_at', $metrics);
    }
}
