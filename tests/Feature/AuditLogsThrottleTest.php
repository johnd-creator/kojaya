<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuditLogsThrottleTest extends TestCase
{
    use DatabaseMigrations;

    public function test_audit_logs_api_routes_have_throttle_middleware(): void
    {
        $auditApiRoutes = collect(Route::getRoutes())
            ->filter(fn ($route) => str_contains($route->uri(), 'api/audit-logs') || str_starts_with($route->uri(), 'audit-logs/'))
            ->filter(fn ($route) => ! in_array(strtolower($route->methods()[0] ?? ''), ['head', 'options'], true));

        $this->assertGreaterThan(0, $auditApiRoutes->count(), 'Expected audit-logs API routes to be registered.');

        foreach ($auditApiRoutes as $route) {
            $middleware = collect($route->gatherMiddleware());

            $hasThrottle = $middleware->contains(fn ($m) => str_starts_with($m, 'throttle:'));

            $this->assertTrue(
                $hasThrottle,
                "Audit-logs API route {$route->uri()} must have a throttle middleware to prevent abuse.",
            );
        }
    }

    public function test_audit_log_export_route_uses_dedicated_export_throttle(): void
    {
        $exportRoute = collect(Route::getRoutes())
            ->first(fn ($route) => str_contains($route->uri(), 'audit-logs/export'));

        $this->assertNotNull($exportRoute, 'Expected audit-logs/export route to exist.');

        $middleware = collect($exportRoute->gatherMiddleware());

        $this->assertTrue(
            $middleware->contains('throttle:audit-export'),
            'Export endpoint must use the tighter audit-export throttle (5/min) to discourage bulk scraping.',
        );
    }
}
