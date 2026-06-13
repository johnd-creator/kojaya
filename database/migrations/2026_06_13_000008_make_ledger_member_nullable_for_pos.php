<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'sqlite' => $this->forSqlite(),
            'mysql', 'mariadb' => $this->forMysql(),
            'pgsql' => $this->forPgsql(),
            default => throw new RuntimeException("Unsupported driver for POS ledger migration: {$driver}"),
        };

        Schema::table('cooperative_ledger_entries', function (Blueprint $table) {
            $table->unique(['source_type', 'source_id', 'entry_type'], 'coop_ledger_source_entry_unique');
        });
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        // Drop the unique index regardless of driver.
        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS coop_ledger_source_entry_unique');
        } else {
            Schema::table('cooperative_ledger_entries', function (Blueprint $table) {
                $table->dropUnique('coop_ledger_source_entry_unique');
            });
        }

        // Restoring the NOT NULL constraint is intentionally a no-op because
        // entries with null cooperative_member_id may have been written after up().
        // Production rollback should be done manually after cleaning nulls if needed.
    }

    private function forSqlite(): void
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
        if ($rows->isNotEmpty()) {
            DB::table('cooperative_ledger_entries_temp')->insert(
                $rows->map(fn ($r) => (array) $r)->all()
            );
        }
        DB::statement('DROP TABLE cooperative_ledger_entries');
        DB::statement('ALTER TABLE cooperative_ledger_entries_temp RENAME TO cooperative_ledger_entries');
        // Reapply indexes that previous migrations created on this table.
        DB::statement('CREATE INDEX cooperative_ledger_entries_cooperative_member_id_posted_at_index ON cooperative_ledger_entries(cooperative_member_id, posted_at)');
        DB::statement('CREATE INDEX coop_ledger_member_scope_posted_idx ON cooperative_ledger_entries(cooperative_member_id, ledger_scope, posted_at)');
        DB::statement('CREATE INDEX coop_ledger_type_posted_idx ON cooperative_ledger_entries(cooperative_contribution_type_id, posted_at)');
        DB::statement('CREATE INDEX coop_ledger_scope_type_idx ON cooperative_ledger_entries(ledger_scope, entry_type)');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function forMysql(): void
    {
        DB::statement('ALTER TABLE cooperative_ledger_entries DROP FOREIGN KEY cooperative_ledger_entries_cooperative_member_id_foreign');
        DB::statement('ALTER TABLE cooperative_ledger_entries MODIFY cooperative_member_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE cooperative_ledger_entries ADD CONSTRAINT cooperative_ledger_entries_cooperative_member_id_foreign FOREIGN KEY (cooperative_member_id) REFERENCES cooperative_members(id) ON DELETE SET NULL');
    }

    private function forPgsql(): void
    {
        // PostgreSQL: drop the FK, change the column to nullable, re-add the FK.
        DB::statement('ALTER TABLE cooperative_ledger_entries DROP CONSTRAINT IF EXISTS cooperative_ledger_entries_cooperative_member_id_foreign');
        DB::statement('ALTER TABLE cooperative_ledger_entries ALTER COLUMN cooperative_member_id DROP NOT NULL');
        DB::statement('ALTER TABLE cooperative_ledger_entries ADD CONSTRAINT cooperative_ledger_entries_cooperative_member_id_foreign FOREIGN KEY (cooperative_member_id) REFERENCES cooperative_members(id) ON DELETE SET NULL');
    }
};
