<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberStoreLedgerSchemaRepairTest extends TestCase
{
    use DatabaseMigrations;

    private const TABLE = 'member_store_ledger_entries';

    private const INDEX = 'member_store_ledger_entries_account_id_transaction_no_index';

    public function test_fresh_schema_keeps_canonical_columns_and_index_when_repair_rolls_back(): void
    {
        $this->assertCanonicalSchema();

        $this->repairMigration()->down();

        $this->assertCanonicalSchema();

        $this->repairMigration()->up();
        $this->assertCanonicalSchema();
    }

    public function test_full_drift_restores_all_canonical_columns_and_index(): void
    {
        $this->dropCanonicalIndex();
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn(['purchaser_name', 'purchase_note', 'transaction_no']);
        });

        $this->assertMissingCanonicalColumns();
        $this->assertFalse(Schema::hasIndex(self::TABLE, self::INDEX));

        $this->repairMigration()->up();
        $this->assertCanonicalSchema();

        $this->repairMigration()->up();
        $this->assertCanonicalSchema();
    }

    public function test_transaction_number_drift_restores_transaction_number_and_index(): void
    {
        $this->dropCanonicalIndex();
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn('transaction_no');
        });

        $this->repairMigration()->up();

        $this->assertCanonicalSchema();
    }

    public function test_missing_index_is_repaired_when_all_columns_exist(): void
    {
        $this->dropCanonicalIndex();

        $this->assertCanonicalColumns();
        $this->repairMigration()->up();

        $this->assertCanonicalSchema();
    }

    private function repairMigration(): object
    {
        return require base_path('database/migrations/2026_07_22_005558_add_missing_columns_to_member_store_ledger_entries_table.php');
    }

    private function dropCanonicalIndex(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropIndex(self::INDEX);
        });
    }

    private function assertCanonicalSchema(): void
    {
        $this->assertCanonicalColumns();
        $this->assertTrue(Schema::hasIndex(self::TABLE, self::INDEX));
    }

    private function assertCanonicalColumns(): void
    {
        $this->assertTrue(Schema::hasColumn(self::TABLE, 'purchaser_name'));
        $this->assertTrue(Schema::hasColumn(self::TABLE, 'purchase_note'));
        $this->assertTrue(Schema::hasColumn(self::TABLE, 'transaction_no'));
    }

    private function assertMissingCanonicalColumns(): void
    {
        $this->assertFalse(Schema::hasColumn(self::TABLE, 'purchaser_name'));
        $this->assertFalse(Schema::hasColumn(self::TABLE, 'purchase_note'));
        $this->assertFalse(Schema::hasColumn(self::TABLE, 'transaction_no'));
    }
}
