<?php

namespace Tests\Unit;

use App\Support\DatabaseMigrationSafety;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseMigrationSafetyTest extends TestCase
{
    public function test_isolated_qa_postgresql_target_is_allowed_only_with_explicit_force(): void
    {
        DatabaseMigrationSafety::assertAllowed('qa', true, 'pgsql', 'kojaya_qa_rc02', true, true);

        $this->expectException(RuntimeException::class);
        DatabaseMigrationSafety::assertAllowed('qa', true, 'pgsql', 'kojaya_qa_rc02', true, false);
    }

    public function test_explicit_test_database_is_allowed(): void
    {
        DatabaseMigrationSafety::assertAllowed('testing', true, 'sqlite', ':memory:', true, true);
        $this->addToAssertionCount(1);
    }

    public function test_test_environment_cannot_migrate_a_shared_or_ambiguous_database(): void
    {
        $this->expectException(RuntimeException::class);
        DatabaseMigrationSafety::assertAllowed('testing', true, 'pgsql', 'kojaya_erp', true, true);
    }

    public function test_production_migration_follows_explicit_force_deployment_policy(): void
    {
        DatabaseMigrationSafety::assertAllowed('production', true, 'pgsql', 'kojaya_production', true, true);

        $this->expectException(RuntimeException::class);
        DatabaseMigrationSafety::assertAllowed('production', true, 'pgsql', 'kojaya_production', true, false);
    }

    #[DataProvider('unsafeTargets')]
    public function test_template_or_missing_targets_are_rejected(string $database, bool $explicit): void
    {
        $this->expectException(RuntimeException::class);
        DatabaseMigrationSafety::assertAllowed('local', true, 'pgsql', $database, $explicit, true);
    }

    public static function unsafeTargets(): array
    {
        return [
            'missing database' => ['', false],
            'implicit default' => ['laravel', false],
            'shared legacy default' => ['kojaya_erp', true],
            'template sqlite path' => ['database/database.sqlite', false],
            'explicit but unconfigured template' => ['kojaya_qa_unconfigured', true],
        ];
    }

    public function test_qa_rejects_non_postgresql_and_non_qa_named_databases(): void
    {
        foreach ([['sqlite', 'qa.sqlite'], ['pgsql', 'kojaya_erp'], ['pgsql', 'latest_production']] as [$connection, $database]) {
            try {
                DatabaseMigrationSafety::assertAllowed('qa', true, $connection, $database, true, true);
                $this->fail('The QA migration target should have been rejected.');
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_missing_environment_and_unknown_environment_are_rejected(): void
    {
        foreach ([['qa', false], ['unknown', true]] as [$environment, $explicit]) {
            try {
                DatabaseMigrationSafety::assertAllowed($environment, $explicit, 'pgsql', 'kojaya_qa', true, true);
                $this->fail('The ambiguous environment should have been rejected.');
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }
    }
}
