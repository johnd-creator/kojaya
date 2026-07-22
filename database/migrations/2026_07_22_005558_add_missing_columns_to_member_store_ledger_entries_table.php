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
        Schema::table('member_store_ledger_entries', function (Blueprint $table): void {
            $addedColumns = false;

            if (! Schema::hasColumn('member_store_ledger_entries', 'purchaser_name')) {
                $table->string('purchaser_name', 120)->nullable()->after('delegate_id');
                $addedColumns = true;
            }

            if (! Schema::hasColumn('member_store_ledger_entries', 'purchase_note')) {
                $table->text('purchase_note')->nullable()->after('purchaser_name');
            }

            if (! Schema::hasColumn('member_store_ledger_entries', 'transaction_no')) {
                $table->string('transaction_no', 80)->nullable()->after('purchase_note');
            }

            // Only add the index when the columns were missing (drifted schema).
            // On fresh installs the create migration already defines it.
            if ($addedColumns) {
                $table->index(['account_id', 'transaction_no'], 'member_store_ledger_entries_account_id_transaction_no_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_store_ledger_entries', function (Blueprint $table): void {
            $table->dropIndex('member_store_ledger_entries_account_id_transaction_no_index');

            if (Schema::hasColumn('member_store_ledger_entries', 'transaction_no')) {
                $table->dropColumn('transaction_no');
            }

            if (Schema::hasColumn('member_store_ledger_entries', 'purchase_note')) {
                $table->dropColumn('purchase_note');
            }

            if (Schema::hasColumn('member_store_ledger_entries', 'purchaser_name')) {
                $table->dropColumn('purchaser_name');
            }
        });
    }
};
