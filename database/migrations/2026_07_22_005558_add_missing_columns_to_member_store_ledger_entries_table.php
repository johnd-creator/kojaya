<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = 'member_store_ledger_entries';

        if (! Schema::hasColumn($tableName, 'purchaser_name')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('purchaser_name', 120)->nullable()->after('delegate_id');
            });
        }

        if (! Schema::hasColumn($tableName, 'purchase_note')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->text('purchase_note')->nullable()->after('purchaser_name');
            });
        }

        if (! Schema::hasColumn($tableName, 'transaction_no')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('transaction_no', 80)->nullable()->after('purchase_note');
            });
        }

        if (! Schema::hasIndex($tableName, 'member_store_ledger_entries_account_id_transaction_no_index')) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->index(['account_id', 'transaction_no'], 'member_store_ledger_entries_account_id_transaction_no_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally no-op.
        // These columns and the index are part of the canonical table schema.
        // This migration only repairs databases that ran an older version
        // of the original create migration.
    }
};
