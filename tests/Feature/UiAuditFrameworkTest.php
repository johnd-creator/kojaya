<?php

namespace Tests\Feature;

use App\Http\Controllers\UiAuditFixtureController;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Date;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UiAuditFrameworkTest extends TestCase
{
    public function test_visual_registry_has_unique_screen_ids(): void
    {
        $registry = json_decode((string) file_get_contents(base_path('tests/visual/coverage/cooperative-pages.json')), true, 512, JSON_THROW_ON_ERROR);
        $ids = array_column($registry['entries'], 'id');

        $this->assertNotEmpty($ids);
        $this->assertCount(count($ids), array_unique($ids));
    }

    public function test_visual_registry_has_unique_screenshot_names(): void
    {
        $registry = json_decode((string) file_get_contents(base_path('tests/visual/coverage/cooperative-pages.json')), true, 512, JSON_THROW_ON_ERROR);
        $names = array_map(
            static fn (array $entry): string => implode('--', [$entry['module'], $entry['screen'], $entry['state']]),
            array_filter($registry['entries'], static fn (array $entry): bool => (bool) ($entry['visual'] ?? false)),
        );

        $this->assertCount(count($names), array_unique($names), 'Duplicate screenshot name in UI audit registry.');
    }

    public function test_registered_desktop_accessibility_screens_have_one_owner(): void
    {
        $registry = json_decode((string) file_get_contents(base_path('tests/visual/coverage/cooperative-pages.json')), true, 512, JSON_THROW_ON_ERROR);
        $inventoryOwned = collect($registry['entries'])
            ->filter(static fn (array $entry): bool => (bool) ($entry['accessibility'] ?? false)
                && ($entry['state'] ?? null) === 'default'
                && in_array('desktop', $entry['viewport_policy'] ?? [], true))
            ->pluck('id')
            ->all();

        $duplicates = [];
        foreach (glob(base_path('tests/visual/accessibility/*.spec.ts')) ?: [] as $path) {
            if (basename($path) === 'inventory.accessibility.spec.ts') {
                continue;
            }

            preg_match_all('/screen\("([^"]+)"\)/', (string) file_get_contents($path), $matches);
            foreach ($matches[1] ?? [] as $screenId) {
                if (in_array($screenId, $inventoryOwned, true)) {
                    $duplicates[] = $screenId.' ('.basename($path).')';
                }
            }
        }

        $this->assertSame([], $duplicates, 'A registered desktop accessibility screen has duplicate owners.');
        $this->assertStringContainsString('assertUniqueAccessibilityOwners()', (string) file_get_contents(base_path('tests/visual/accessibility/inventory.accessibility.spec.ts')));
    }

    public function test_manifest_and_runtime_outputs_are_generated_by_the_harness(): void
    {
        $teardown = file_get_contents(base_path('tests/visual/global-teardown.ts'));
        $manifestHelper = file_get_contents(base_path('tests/visual/helpers/audit-manifest.ts'));

        $this->assertStringContainsString('manifest.json', (string) $teardown);
        $this->assertStringContainsString('JSON.stringify(', (string) $teardown);
        $this->assertStringContainsString('ui-audit-output/runtime', (string) file_get_contents(base_path('tests/visual/helpers/runtime-health.ts')));
        $this->assertStringContainsString('screenshot:', (string) $manifestHelper);
    }

    public function test_accessibility_metrics_are_node_based_and_non_negative(): void
    {
        $helper = (string) file_get_contents(base_path('tests/visual/helpers/accessibility.ts'));

        foreach ([
            'blocking_rule_count',
            'blocking_node_count',
            'known_node_count',
            'new_node_count',
            'stale_finding_count',
        ] as $metric) {
            $this->assertStringContainsString($metric, $helper);
        }

        $this->assertStringContainsString('Math.max(0', $helper);
        $this->assertStringNotContainsString('blockingViolations.length - newViolations.length', $helper);
    }

    public function test_playwright_auth_state_and_sensitive_outputs_are_ignored(): void
    {
        $gitignore = file_get_contents(base_path('.gitignore'));

        $this->assertStringContainsString('/tests/visual/.auth/', (string) $gitignore);
        $this->assertStringContainsString('/ui-audit-output/', (string) $gitignore);
        $this->assertStringContainsString('/.env.playwright', (string) $gitignore);
    }

    public function test_playwright_environment_uses_asia_jakarta_and_the_fixed_clock_only(): void
    {
        $originalEnvironment = app()->environment();
        $originalTimezone = date_default_timezone_get();

        try {
            $this->app->instance('env', 'playwright');
            config(['app.timezone' => 'UTC']);
            date_default_timezone_set('UTC');
            Date::setTestNow(null);
            $this->invokeUiAuditClock();

            $this->assertSame('Asia/Jakarta', config('app.timezone'));
            $this->assertSame('Asia/Jakarta', date_default_timezone_get());
            $this->assertSame('2026-01-15 09:30:00', now('Asia/Jakarta')->format('Y-m-d H:i:s'));

            foreach (['testing', 'production'] as $environment) {
                $this->app->instance('env', $environment);
                config(['app.timezone' => 'UTC']);
                date_default_timezone_set('UTC');
                Date::setTestNow(null);
                $this->invokeUiAuditClock();

                $this->assertSame('UTC', config('app.timezone'));
                $this->assertSame('UTC', date_default_timezone_get());
                $this->assertNull(Date::getTestNow());
            }
        } finally {
            $this->app->instance('env', $originalEnvironment);
            config(['app.timezone' => 'UTC']);
            date_default_timezone_set($originalTimezone);
            Date::setTestNow(null);
        }
    }

    public function test_application_timezone_default_is_utc_and_pr_metadata_is_event_scoped(): void
    {
        $this->assertSame('UTC', config('app.timezone'));

        $teardown = file_get_contents(base_path('tests/visual/global-teardown.ts'));

        $this->assertIsString($teardown);
        $this->assertStringContainsString('pull_request_number: pullRequestNumber()', $teardown);
        $this->assertStringContainsString('eventName !== "pull_request"', $teardown);
        $this->assertStringNotContainsString('?? 21', $teardown);
    }

    public function test_fixture_endpoint_controller_rejects_production_like_environment(): void
    {
        config(['app.env' => 'production']);

        try {
            (new UiAuditFixtureController)();
            $this->fail('The UI audit fixture endpoint must reject production-like environments.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        } finally {
            config(['app.env' => 'testing']);
        }
    }

    private function invokeUiAuditClock(): void
    {
        $method = new ReflectionMethod(AppServiceProvider::class, 'configureUiAuditClock');
        $method->invoke(new AppServiceProvider($this->app));
    }
}
