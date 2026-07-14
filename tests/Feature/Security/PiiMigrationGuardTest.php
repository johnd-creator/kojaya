<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $before = Schema::getColumnListing('cooperative_members');
        $migration = require base_path('database/migrations/2026_07_14_062726_add_version_metadata_to_cooperative_member_pii_columns.php');
        $exception = null;

        try {
            $migration->down();
        } catch (RuntimeException $caught) {
            $exception = $caught;
        }

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame($before, Schema::getColumnListing('cooperative_members'));
    }
}
