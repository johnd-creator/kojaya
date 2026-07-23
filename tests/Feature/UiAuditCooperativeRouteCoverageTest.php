<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class UiAuditCooperativeRouteCoverageTest extends TestCase
{
    /**
     * @return array{entries: array<int, array<string, mixed>>}
     */
    private function registry(): array
    {
        $contents = file_get_contents(base_path('tests/visual/coverage/cooperative-pages.json'));

        $this->assertIsString($contents);

        $registry = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($registry);
        $this->assertIsArray($registry['entries'] ?? null);

        return $registry;
    }

    /**
     * @return array{entries: array<int, array<string, string>>}
     */
    private function exclusions(): array
    {
        $contents = file_get_contents(base_path('tests/visual/coverage/cooperative-route-exclusions.json'));

        $this->assertIsString($contents);

        $exclusions = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($exclusions);
        $this->assertIsArray($exclusions['entries'] ?? null);

        return $exclusions;
    }

    /**
     * @return array<int, Route>
     */
    private function scopedRoutes(): array
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(function (Route $route): bool {
                if (! in_array('GET', $route->methods(), true) || $route->getName() === null) {
                    return false;
                }

                $uri = ltrim($route->uri(), '/');
                $name = $route->getName();

                return $uri === 'dashboard'
                    || $uri === 'settings/profile'
                    || str_starts_with($uri, 'cooperative/')
                    || str_starts_with($uri, 'member/')
                    || str_starts_with($name, 'cooperative.')
                    || str_starts_with($name, 'member.');
            })
            ->values()
            ->all();
    }

    public function test_every_renderable_cooperative_and_member_route_has_one_registry_owner(): void
    {
        $registry = $this->registry()['entries'];
        $exclusions = $this->exclusions()['entries'];
        $routes = $this->scopedRoutes();

        $registryByRoute = [];
        foreach ($registry as $entry) {
            $this->assertNotEmpty($entry['id'] ?? null);
            $this->assertNotEmpty($entry['route_name'] ?? null);
            $this->assertNotEmpty($entry['path_template'] ?? null);
            $this->assertNotEmpty($entry['role'] ?? null);
            $this->assertNotEmpty($entry['auth_state'] ?? null);
            $this->assertNotEmpty($entry['fixture'] ?? null);
            $this->assertNotEmpty($entry['viewport_policy'] ?? null);
            $this->assertContains('desktop', $entry['viewport_policy']);
            $this->assertTrue((bool) ($entry['visual'] ?? false));

            $registryByRoute[$entry['route_name']][] = $entry;
        }

        $exclusionByRoute = [];
        foreach ($exclusions as $exclusion) {
            $this->assertNotEmpty($exclusion['route_name'] ?? null);
            $this->assertNotEmpty($exclusion['reason'] ?? null);
            $this->assertNotContains(strtolower(trim($exclusion['reason'])), ['not needed', 'skip for now', 'too difficult']);
            $exclusionByRoute[$exclusion['route_name']] = $exclusion;
        }

        $this->assertCount(count($registry), array_unique(array_column($registry, 'id')), 'Duplicate audit scenario ID.');
        $this->assertCount(count($exclusions), array_unique(array_column($exclusions, 'route_name')), 'Duplicate exclusion route name.');

        foreach ($routes as $route) {
            $name = $route->getName();
            $this->assertNotNull($name);

            $hasRegistryOwner = isset($registryByRoute[$name]);
            $hasExclusionOwner = isset($exclusionByRoute[$name]);

            $this->assertNotSame($hasRegistryOwner, $hasExclusionOwner, "Route {$name} must have exactly one audit owner.");
        }

        $routeNames = array_map(static fn (Route $route): string => (string) $route->getName(), $routes);
        foreach (array_keys($registryByRoute) as $name) {
            $this->assertContains($name, $routeNames, "Stale registry route: {$name}");
        }
        foreach (array_keys($exclusionByRoute) as $name) {
            $this->assertContains($name, $routeNames, "Stale exclusion route: {$name}");
        }

        foreach ($registryByRoute as $name => $entries) {
            $states = array_map(static fn (array $entry): string => (string) $entry['state'], $entries);
            $this->assertCount(count($states), array_unique($states), "Route {$name} has duplicate registry states.");

            $route = collect($routes)->first(fn (Route $candidate): bool => $candidate->getName() === $name);
            $this->assertInstanceOf(Route::class, $route);
            foreach ($entries as $entry) {
                $expectedPath = '/'.ltrim(explode('?', (string) $route->uri(), 2)[0], '/');
                $actualPath = '/'.ltrim(explode('?', (string) $entry['path_template'], 2)[0], '/');
                $this->assertSame(
                    preg_replace('/\{[^}]+\}/', '{}', $expectedPath),
                    preg_replace('/\{[^}]+\}/', '{}', $actualPath),
                );
            }
        }
    }

    public function test_exclusions_do_not_own_renderable_html_surfaces(): void
    {
        $exclusions = $this->exclusions()['entries'];

        foreach ($exclusions as $exclusion) {
            $this->assertContains($exclusion['category'], ['download', 'pdf', 'api-json']);
        }
    }
}
