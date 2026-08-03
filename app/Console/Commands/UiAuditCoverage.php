<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

class UiAuditCoverage extends Command
{
    protected $signature = 'ui-audit:coverage';

    protected $description = 'Validate the inventory-driven UI audit route coverage.';

    public function handle(): int
    {
        $registry = $this->readJson(base_path('tests/visual/coverage/cooperative-pages.json'));
        $exclusions = $this->readJson(base_path('tests/visual/coverage/cooperative-route-exclusions.json'));
        $routes = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => $this->isScopedGetRoute($route))
            ->values();
        $discovered = $routes->map(fn (Route $route): string => (string) $route->getName())->unique()->values();
        $audited = collect($registry['entries'] ?? [])->pluck('route_name')->unique()->values();
        $excluded = collect($exclusions['entries'] ?? [])->pluck('route_name')->unique()->values();
        $uncovered = $discovered->diff($audited)->diff($excluded)->values()->all();
        $staleRegistry = $audited->diff($discovered)->values()->all();
        $staleExclusions = $excluded->diff($discovered)->values()->all();
        $screenIds = collect($registry['entries'] ?? [])->pluck('id');

        $summary = [
            'discovered_get_routes' => $discovered->count(),
            'renderable_routes' => $discovered->diff($excluded)->count(),
            'audited_routes' => $audited->count(),
            'excluded_routes' => $excluded->count(),
            'uncovered_routes' => count($uncovered),
            'stale_registry_routes' => count($staleRegistry),
            'stale_exclusion_routes' => count($staleExclusions),
            'duplicate_screen_ids' => $screenIds->count() - $screenIds->unique()->count(),
            'desktop_expected_screens' => collect($registry['entries'] ?? [])
                ->filter(fn (array $entry): bool => ($entry['visual'] ?? false) && in_array('desktop', $entry['viewport_policy'] ?? [], true))
                ->count(),
            'desktop_executed_screens' => 0,
            'desktop_passed_screens' => 0,
            'desktop_failed_screens' => 0,
            'desktop_skipped_screens' => 0,
            'modules' => collect($registry['entries'] ?? [])->pluck('module')->unique()->sort()->values()->all(),
        ];

        $output = base_path('ui-audit-output/coverage');
        if (! is_dir($output)) {
            mkdir($output, 0755, true);
        }
        file_put_contents($output.'/cooperative-route-coverage.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        file_put_contents($output.'/cooperative-route-coverage.md', implode(PHP_EOL, [
            '# Cooperative route coverage',
            '',
            '- Discovered named GET routes: '.$summary['discovered_get_routes'],
            '- Renderable routes: '.$summary['renderable_routes'],
            '- Audited routes: '.$summary['audited_routes'],
            '- Approved exclusions: '.$summary['excluded_routes'],
            '- Uncovered routes: '.$summary['uncovered_routes'],
            '- Stale registry routes: '.$summary['stale_registry_routes'],
            '- Stale exclusions: '.$summary['stale_exclusion_routes'],
            '- Desktop expected screenshots: '.$summary['desktop_expected_screens'],
            '',
        ]));

        if ($uncovered !== [] || $staleRegistry !== [] || $staleExclusions !== [] || $summary['duplicate_screen_ids'] > 0) {
            $this->error(json_encode(compact('uncovered', 'staleRegistry', 'staleExclusions'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::FAILURE;
        }

        $this->info(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function readJson(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    private function isScopedGetRoute(Route $route): bool
    {
        if (! in_array('GET', $route->methods(), true) || $route->getName() === null) {
            return false;
        }

        $uri = ltrim($route->uri(), '/');
        $name = $route->getName();

        return $uri === 'dashboard'
            || $uri === 'settings/profile'
            || Str::startsWith($uri, ['cooperative/', 'member/'])
            || Str::startsWith($name, ['cooperative.', 'member.']);
    }
}
