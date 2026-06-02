<?php

namespace App\Console\Commands;

use App\Services\OpenApi\OpenApiGenerator;
use Illuminate\Console\Command;

class OpenApiSnapshotCommand extends Command
{
    protected $signature = 'openapi:snapshot {--check : Validate against stored snapshot instead of updating} {--validate : Alias for --check}';

    protected $description = 'Generate or validate OpenAPI snapshot';

    public function handle(OpenApiGenerator $generator): int
    {
        $spec = $generator->generate();
        $path = base_path('docs/openapi.json');

        if ($this->option('check') || $this->option('validate')) {
            if (! file_exists($path)) {
                $this->error('No snapshot found. Run "php artisan openapi:snapshot" first.');

                return self::FAILURE;
            }

            $stored = json_decode(file_get_contents($path), true);
            $drift = $this->diff($stored, $spec);

            if (! empty($drift)) {
                $this->error('OpenAPI snapshot drift detected:');
                foreach ($drift as $change) {
                    $this->warn("  - {$change}");
                }

                return self::FAILURE;
            }

            $this->info('OpenAPI snapshot is up to date.');

            return self::SUCCESS;
        }

        file_put_contents($path, json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        $this->info('OpenAPI snapshot saved to docs/openapi.json');

        return self::SUCCESS;
    }

    private function diff(array $old, array $new, string $prefix = ''): array
    {
        $changes = [];

        $oldPaths = $old['paths'] ?? [];
        $newPaths = $new['paths'] ?? [];

        foreach ($newPaths as $path => $methods) {
            if (! isset($oldPaths[$path])) {
                $changes[] = "ADDED path: {$path}";

                continue;
            }
            foreach ($methods as $method => $item) {
                if (! isset($oldPaths[$path][$method])) {
                    $changes[] = "ADDED: {$method} {$path}";
                } elseif (json_encode($item) !== json_encode($oldPaths[$path][$method])) {
                    $changes[] = "CHANGED: {$method} {$path}";
                }
            }
        }

        foreach ($oldPaths as $path => $methods) {
            if (! isset($newPaths[$path])) {
                $changes[] = "REMOVED path: {$path}";

                continue;
            }
            foreach ($methods as $method => $item) {
                if (! isset($newPaths[$path][$method])) {
                    $changes[] = "REMOVED: {$method} {$path}";
                }
            }
        }

        return $changes;
    }
}
