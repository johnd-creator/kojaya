<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->addMetadataForSqlite();

            return;
        }

        if (! Schema::hasColumn('cooperative_ledger_entries', 'metadata')) {
            Schema::table('cooperative_ledger_entries', function (Blueprint $table) {
                $table->json('metadata')->nullable()->after('description');
            });
        }

        $this->ensureUniqueSourceEntryIndex();
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->removeMetadataForSqlite();

            return;
        }

        $this->dropUniqueSourceEntryIndex();

        if (Schema::hasColumn('cooperative_ledger_entries', 'metadata')) {
            Schema::table('cooperative_ledger_entries', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
        }
    }

    /**
     * Hapus unique index `coop_ledger_source_entry_unique` untuk driver
     * non-SQLite secara idempotent agar rollback migration bersih.
     */
    private function dropUniqueSourceEntryIndex(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS coop_ledger_source_entry_unique');

            return;
        }

        if (Schema::hasIndex('cooperative_ledger_entries', 'coop_ledger_source_entry_unique')) {
            Schema::table('cooperative_ledger_entries', function (Blueprint $table) {
                $table->dropUnique('coop_ledger_source_entry_unique');
            });
        }
    }

    /**
     * Idempotently create the unique guard (source_type, source_id, entry_type)
     * yang dipakai service opening balance agar race condition/double-click
     * tidak bisa menulis dua entry OPENING_BALANCE untuk satu line.
     */
    private function ensureUniqueSourceEntryIndex(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // Index sudah diciptakan oleh addMetadataForSqlite.
            return;
        }

        $indexName = 'coop_ledger_source_entry_unique';
        $exists = collect(DB::select(
            $driver === 'pgsql'
                ? 'SELECT 1 FROM pg_indexes WHERE indexname = ?'
                : 'SHOW INDEX FROM cooperative_ledger_entries WHERE Key_name = ?',
            [$indexName]
        ))->isNotEmpty();

        if ($exists) {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX coop_ledger_source_entry_unique
                 ON cooperative_ledger_entries (source_type, source_id, entry_type)'
            );
        } else {
            Schema::table('cooperative_ledger_entries', function (Blueprint $table) {
                $table->unique(
                    ['source_type', 'source_id', 'entry_type'],
                    'coop_ledger_source_entry_unique'
                );
            });
        }
    }

    private function addMetadataForSqlite(): void
    {
        $rows = DB::table('cooperative_ledger_entries')->get();
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('DROP TABLE IF EXISTS cooperative_ledger_entries_temp');
        DB::statement('CREATE TABLE cooperative_ledger_entries_temp (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cooperative_member_id BIGINT NULL,
            cooperative_payment_id BIGINT NULL,
            cooperative_contribution_type_id BIGINT NULL,
            source_type VARCHAR(255) NULL,
            source_id BIGINT NULL,
            entry_type VARCHAR(40) NOT NULL,
            debit DECIMAL(15, 2) DEFAULT 0,
            credit DECIMAL(15, 2) DEFAULT 0,
            ledger_scope VARCHAR(30) NULL,
            category_snapshot VARCHAR(30) NULL,
            period VARCHAR(7) NULL,
            description TEXT NULL,
            metadata TEXT NULL,
            posted_at DATE NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )');
        if ($rows->isNotEmpty()) {
            DB::table('cooperative_ledger_entries_temp')->insert(
                $rows->map(fn ($r) => (array) $r)->all()
            );
        }
        DB::statement('DROP TABLE cooperative_ledger_entries');
        DB::statement('ALTER TABLE cooperative_ledger_entries_temp RENAME TO cooperative_ledger_entries');
        DB::statement('CREATE INDEX cooperative_ledger_entries_cooperative_member_id_posted_at_index ON cooperative_ledger_entries(cooperative_member_id, posted_at)');
        DB::statement('CREATE INDEX coop_ledger_member_scope_posted_idx ON cooperative_ledger_entries(cooperative_member_id, ledger_scope, posted_at)');
        DB::statement('CREATE INDEX coop_ledger_type_posted_idx ON cooperative_ledger_entries(cooperative_contribution_type_id, posted_at)');
        DB::statement('CREATE INDEX coop_ledger_scope_type_idx ON cooperative_ledger_entries(ledger_scope, entry_type)');
        DB::statement('CREATE UNIQUE INDEX coop_ledger_source_entry_unique ON cooperative_ledger_entries(source_type, source_id, entry_type)');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function removeMetadataForSqlite(): void
    {
        $rows = DB::table('cooperative_ledger_entries')->get();
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('DROP TABLE IF EXISTS cooperative_ledger_entries_temp');
        DB::statement('CREATE TABLE cooperative_ledger_entries_temp (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cooperative_member_id BIGINT NULL,
            cooperative_payment_id BIGINT NULL,
            cooperative_contribution_type_id BIGINT NULL,
            source_type VARCHAR(255) NULL,
            source_id BIGINT NULL,
            entry_type VARCHAR(40) NOT NULL,
            debit DECIMAL(15, 2) DEFAULT 0,
            credit DECIMAL(15, 2) DEFAULT 0,
            ledger_scope VARCHAR(30) NULL,
            category_snapshot VARCHAR(30) NULL,
            period VARCHAR(7) NULL,
            description TEXT NULL,
            posted_at DATE NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )');
        $columns = ['id', 'cooperative_member_id', 'cooperative_payment_id',
            'cooperative_contribution_type_id', 'source_type', 'source_id',
            'entry_type', 'debit', 'credit', 'ledger_scope', 'category_snapshot',
            'period', 'description', 'posted_at', 'created_at', 'updated_at'];
        if ($rows->isNotEmpty()) {
            $payload = $rows->map(fn ($r) => array_intersect_key((array) $r, array_flip($columns)))->all();
            DB::table('cooperative_ledger_entries_temp')->insert($payload);
        }
        DB::statement('DROP TABLE cooperative_ledger_entries');
        DB::statement('ALTER TABLE cooperative_ledger_entries_temp RENAME TO cooperative_ledger_entries');
        DB::statement('CREATE INDEX cooperative_ledger_entries_cooperative_member_id_posted_at_index ON cooperative_ledger_entries(cooperative_member_id, posted_at)');
        DB::statement('CREATE INDEX coop_ledger_member_scope_posted_idx ON cooperative_ledger_entries(cooperative_member_id, ledger_scope, posted_at)');
        DB::statement('CREATE INDEX coop_ledger_type_posted_idx ON cooperative_ledger_entries(cooperative_contribution_type_id, posted_at)');
        DB::statement('CREATE INDEX coop_ledger_scope_type_idx ON cooperative_ledger_entries(ledger_scope, entry_type)');
        DB::statement('CREATE UNIQUE INDEX coop_ledger_source_entry_unique ON cooperative_ledger_entries(source_type, source_id, entry_type)');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
