<?php

namespace App\Support;

use RuntimeException;

class DatabaseMigrationSafety
{
    public static function assertAllowed(
        string $environment,
        bool $environmentExplicit,
        string $connection,
        string $database,
        bool $databaseExplicit,
        bool $force,
    ): void {
        $target = strtolower(str_replace('\\', '/', trim($database)));
        $unsafeTargets = ['', 'laravel', 'kojaya_erp', 'database.sqlite', 'database/database.sqlite'];

        if (! $environmentExplicit
            || $environment === ''
            || ! $databaseExplicit
            || in_array($target, $unsafeTargets, true)
            || str_contains($target, 'unconfigured')) {
            throw new RuntimeException('Migration blocked: configure an explicit, non-template application environment and database target first.');
        }

        if (in_array($environment, ['testing', 'playwright'], true)) {
            $isolatedTestTarget = $target === ':memory:'
                || preg_match('/(?:^|[_\\/\\-])(?:test|testing|playwright)(?:$|[._\\/\\-])/', $target) === 1;

            if (! $isolatedTestTarget) {
                throw new RuntimeException('Test migrations require an isolated in-memory or test-named database target.');
            }

            return;
        }

        if ($environment === 'production') {
            if (! $force) {
                throw new RuntimeException('Production migrations require the established explicit --force deployment flow.');
            }

            return;
        }

        if (in_array($environment, ['qa', 'staging'], true)) {
            $qaNamedTarget = preg_match('/(?:^|[_\\-])(?:qa|test|testing|staging)(?:$|[_\\-])/', $target) === 1;

            if ($connection !== 'pgsql' || ! $qaNamedTarget || ! $force) {
                throw new RuntimeException('QA/staging migrations require an isolated PostgreSQL QA-named database and explicit --force.');
            }

            return;
        }

        if ($environment !== 'local') {
            throw new RuntimeException('Migration blocked: unsupported or ambiguous APP_ENV.');
        }
    }
}
