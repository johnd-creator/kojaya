<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PiiMigrationGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_disposable_sqlite_migration_has_all_pii_metadata_columns(): void
    {
        $columns = Schema::getColumnListing('cooperative_members');

        foreach ([
            'identity_number_key_version',
            'identity_number_bidx_version',
            'identity_number_migrated_at',
            'npwp_key_version',
            'npwp_bidx_version',
            'npwp_migrated_at',
            'no_rekening_key_version',
            'no_rekening_bidx_version',
            'no_rekening_migrated_at',
        ] as $column) {
            $this->assertContains($column, $columns);
        }
    }

    public function test_metadata_rollback_fails_before_modifying_the_schema(): void
    {
        $previous = config('security.pii_allow_schema_rollback');
        config(['security.pii_allow_schema_rollback' => false]);
        $before = Schema::getColumnListing('cooperative_members');
        $migration = require base_path('database/migrations/2026_07_14_062726_add_version_metadata_to_cooperative_member_pii_columns.php');
        $exception = null;

        try {
            $migration->down();
        } catch (RuntimeException $caught) {
            $exception = $caught;
        } finally {
            config(['security.pii_allow_schema_rollback' => $previous]);
        }

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame($before, Schema::getColumnListing('cooperative_members'));
    }

    public function test_explicit_test_rollback_removes_only_metadata_columns(): void
    {
        $previous = config('security.pii_allow_schema_rollback');
        config(['security.pii_allow_schema_rollback' => true]);
        $migration = require base_path('database/migrations/2026_07_14_062726_add_version_metadata_to_cooperative_member_pii_columns.php');
        $metadataColumns = [
            'identity_number_key_version',
            'identity_number_bidx_version',
            'identity_number_migrated_at',
            'npwp_key_version',
            'npwp_bidx_version',
            'npwp_migrated_at',
            'no_rekening_key_version',
            'no_rekening_bidx_version',
            'no_rekening_migrated_at',
        ];
        $rolledBack = false;

        try {
            $migration->down();
            $rolledBack = true;

            foreach ($metadataColumns as $column) {
                $this->assertFalse(Schema::hasColumn('cooperative_members', $column));
            }

            foreach ([
                'identity_number',
                'identity_number_enc',
                'identity_number_bidx',
                'npwp',
                'npwp_enc',
                'npwp_bidx',
                'no_rekening',
                'no_rekening_enc',
                'no_rekening_bidx',
            ] as $column) {
                $this->assertTrue(Schema::hasColumn('cooperative_members', $column));
            }
        } finally {
            try {
                if ($rolledBack) {
                    $migration->up();
                }
            } finally {
                config(['security.pii_allow_schema_rollback' => $previous]);
            }
        }
    }

    public function test_testing_default_allows_rollback_but_other_environments_default_to_blocked(): void
    {
        $this->assertTrue($this->securityConfigForEnvironment('testing')['pii_allow_schema_rollback']);
        $this->assertFalse($this->securityConfigForEnvironment('production')['pii_allow_schema_rollback']);
        $this->assertFalse($this->securityConfigForEnvironment('staging')['pii_allow_schema_rollback']);
        $this->assertFalse($this->securityConfigForEnvironment('local')['pii_allow_schema_rollback']);
    }

    /**
     * @return array<string, mixed>
     */
    private function securityConfigForEnvironment(string $environment): array
    {
        $previousAppEnv = getenv('APP_ENV');
        $previousRollbackFlag = getenv('PII_ALLOW_SCHEMA_ROLLBACK');
        $hadServerAppEnv = array_key_exists('APP_ENV', $_SERVER);
        $previousServerAppEnv = $_SERVER['APP_ENV'] ?? null;
        $hadEnvAppEnv = array_key_exists('APP_ENV', $_ENV);
        $previousEnvAppEnv = $_ENV['APP_ENV'] ?? null;
        $hadServerRollbackFlag = array_key_exists('PII_ALLOW_SCHEMA_ROLLBACK', $_SERVER);
        $previousServerRollbackFlag = $_SERVER['PII_ALLOW_SCHEMA_ROLLBACK'] ?? null;
        $hadEnvRollbackFlag = array_key_exists('PII_ALLOW_SCHEMA_ROLLBACK', $_ENV);
        $previousEnvRollbackFlag = $_ENV['PII_ALLOW_SCHEMA_ROLLBACK'] ?? null;

        putenv("APP_ENV={$environment}");
        putenv('PII_ALLOW_SCHEMA_ROLLBACK');
        $_SERVER['APP_ENV'] = $environment;
        $_ENV['APP_ENV'] = $environment;
        unset($_SERVER['PII_ALLOW_SCHEMA_ROLLBACK'], $_ENV['PII_ALLOW_SCHEMA_ROLLBACK']);
        Env::disablePutenv();

        try {
            return require config_path('security.php');
        } finally {
            Env::enablePutenv();
            $previousAppEnv === false
                ? putenv('APP_ENV')
                : putenv("APP_ENV={$previousAppEnv}");
            $previousRollbackFlag === false
                ? putenv('PII_ALLOW_SCHEMA_ROLLBACK')
                : putenv("PII_ALLOW_SCHEMA_ROLLBACK={$previousRollbackFlag}");
            if ($hadServerAppEnv) {
                $_SERVER['APP_ENV'] = $previousServerAppEnv;
            } else {
                unset($_SERVER['APP_ENV']);
            }

            if ($hadEnvAppEnv) {
                $_ENV['APP_ENV'] = $previousEnvAppEnv;
            } else {
                unset($_ENV['APP_ENV']);
            }

            if ($hadServerRollbackFlag) {
                $_SERVER['PII_ALLOW_SCHEMA_ROLLBACK'] = $previousServerRollbackFlag;
            } else {
                unset($_SERVER['PII_ALLOW_SCHEMA_ROLLBACK']);
            }

            if ($hadEnvRollbackFlag) {
                $_ENV['PII_ALLOW_SCHEMA_ROLLBACK'] = $previousEnvRollbackFlag;
            } else {
                unset($_ENV['PII_ALLOW_SCHEMA_ROLLBACK']);
            }
        }
    }
}
