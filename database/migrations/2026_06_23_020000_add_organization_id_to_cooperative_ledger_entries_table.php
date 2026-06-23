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

        if ($driver === 'sqlite') {
            $this->addForSqlite();
        } else {
            Schema::table('cooperative_ledger_entries', function (Blueprint $table) {
                $table->foreignUuid('organization_id')->nullable()->after('cooperative_member_id')->constrained('organizations')->nullOnDelete();
                $table->index(['organization_id', 'posted_at'], 'coop_ledger_organization_idx');
            });
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->dropForSqlite();
        } else {
            Schema::table('cooperative_ledger_entries', function (Blueprint $table) {
                $table->dropIndex('coop_ledger_organization_idx');
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            });
        }
    }

    private function addForSqlite(): void
    {
        $rows = DB::table('cooperative_ledger_entries')->get();
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('DROP TABLE IF EXISTS cooperative_ledger_entries_temp');
        DB::statement('CREATE TABLE cooperative_ledger_entries_temp (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cooperative_member_id BIGINT NULL,
            organization_id VARCHAR(36) NULL,
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
            updated_at TIMESTAMP NULL,
            metadata TEXT NULL
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
        DB::statement('CREATE INDEX coop_ledger_organization_idx ON cooperative_ledger_entries(organization_id, posted_at)');
        DB::statement('CREATE UNIQUE INDEX coop_ledger_source_entry_unique ON cooperative_ledger_entries(source_type, source_id, entry_type)');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function dropForSqlite(): void
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
            updated_at TIMESTAMP NULL,
            metadata TEXT NULL
        )');
        $columns = ['id', 'cooperative_member_id', 'cooperative_payment_id',
            'cooperative_contribution_type_id', 'source_type', 'source_id',
            'entry_type', 'debit', 'credit', 'ledger_scope', 'category_snapshot',
            'period', 'description', 'posted_at', 'created_at', 'updated_at',
            'metadata'];
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
