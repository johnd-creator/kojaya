<?php

namespace Tests\Unit\Services;

use App\Monitoring\Health;
use App\Services\Monitoring\MetricsService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonitoringDefensiveTest extends TestCase
{
    use DatabaseMigrations;

    public function test_health_counts_returns_zero_when_failed_jobs_table_missing(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            Schema::drop('failed_jobs');
        }

        $health = new Health;
        $counts = $health->counts();

        $this->assertSame(0, $counts['failed_jobs'], 'failed_jobs count must default to 0 when the table is absent.');
    }

    public function test_metrics_service_failed_webhook_count_returns_zero_when_table_missing(): void
    {
        if (Schema::hasTable('webhook_logs')) {
            Schema::drop('webhook_logs');
        }

        $metrics = new MetricsService;

        $this->assertSame(0, $metrics->failedWebhookCount());
    }

    public function test_metrics_service_failed_push_count_returns_zero_when_table_missing(): void
    {
        if (Schema::hasTable('push_notification_logs')) {
            Schema::drop('push_notification_logs');
        }

        $metrics = new MetricsService;

        $this->assertSame(0, $metrics->failedPushCount());
    }

    public function test_metrics_service_queue_failures_returns_zero_when_table_missing(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            Schema::drop('failed_jobs');
        }

        $metrics = new MetricsService;

        $this->assertSame(0, $metrics->queueFailureCount());
    }
}
