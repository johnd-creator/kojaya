<?php

namespace Tests\Feature\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CooperativeLedgerMetadataMigrationRollbackTest extends TestCase
{
    use DatabaseMigrations;

    public function test_postgresql_round_trip_preserves_the_earlier_unique_constraint(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('This regression exercises PostgreSQL constraint ownership.');
        }

        $this->assertSame('kojaya_test', DB::selectOne('SELECT current_database() AS name')->name);
        $this->assertSame(1, $this->constraintCount());
        $this->assertSame(1, $this->indexCount());
        $this->assertTrue(Schema::hasColumn('cooperative_ledger_entries', 'metadata'));

        $migration = require database_path('migrations/2026_06_23_010000_add_metadata_to_cooperative_ledger_entries_table.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('cooperative_ledger_entries', 'metadata'));
        $this->assertSame(1, $this->constraintCount());
        $this->assertSame(1, $this->indexCount());

        $migration->up();
        $this->assertTrue(Schema::hasColumn('cooperative_ledger_entries', 'metadata'));
        $this->assertSame(1, $this->constraintCount());
        $this->assertSame(1, $this->indexCount());
    }

    public function test_postgresql_round_trip_removes_an_index_created_by_this_migration(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('This regression exercises PostgreSQL standalone index ownership.');
        }

        $this->assertSame('kojaya_test', DB::selectOne('SELECT current_database() AS name')->name);
        $migration = require database_path('migrations/2026_06_23_010000_add_metadata_to_cooperative_ledger_entries_table.php');

        DB::beginTransaction();

        try {
            Schema::table('cooperative_ledger_entries', function (Blueprint $table): void {
                $table->dropUnique('coop_ledger_source_entry_unique');
            });

            $this->assertSame(0, $this->constraintCount());
            $this->assertSame(0, $this->indexCount());

            $migration->down();
            $migration->up();
            $this->assertTrue(Schema::hasColumn('cooperative_ledger_entries', 'metadata'));
            $this->assertSame(0, $this->constraintCount());
            $this->assertSame(1, $this->indexCount());

            $migration->down();
            $this->assertFalse(Schema::hasColumn('cooperative_ledger_entries', 'metadata'));
            $this->assertSame(0, $this->constraintCount());
            $this->assertSame(0, $this->indexCount());

            $migration->up();
            $this->assertSame(1, $this->indexCount());
        } finally {
            DB::rollBack();
        }

        $this->assertSame(1, $this->constraintCount());
        $this->assertSame(1, $this->indexCount());
        $this->assertTrue(Schema::hasColumn('cooperative_ledger_entries', 'metadata'));
    }

    private function constraintCount(): int
    {
        return (int) DB::selectOne(<<<'SQL'
            SELECT count(*) AS total FROM pg_constraint
            WHERE conrelid = 'public.cooperative_ledger_entries'::regclass
              AND conname = 'coop_ledger_source_entry_unique'
        SQL)->total;
    }

    private function indexCount(): int
    {
        return (int) DB::selectOne(<<<'SQL'
            SELECT count(*) AS total FROM pg_index
            WHERE indrelid = 'public.cooperative_ledger_entries'::regclass
              AND indexrelid = to_regclass('public.coop_ledger_source_entry_unique')
        SQL)->total;
    }
}
