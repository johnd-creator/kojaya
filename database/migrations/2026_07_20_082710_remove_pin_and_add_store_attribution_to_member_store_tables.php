<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('member_store_delegates', 'pin_hash')) {
            Schema::table('member_store_delegates', function (Blueprint $table): void {
                $table->dropColumn('pin_hash');
            });
        }

        if (! Schema::hasColumn('member_store_ledger_entries', 'purchaser_name')) {
            Schema::table('member_store_ledger_entries', function (Blueprint $table): void {
                $table->string('purchaser_name', 120)->nullable()->after('delegate_id');
                $table->text('purchase_note')->nullable()->after('purchaser_name');
                $table->string('transaction_no', 80)->nullable()->after('purchase_note');
            });
        }

        Schema::table('member_store_ledger_entries', function (Blueprint $table): void {
            $table->index(['account_id', 'transaction_no'], 'member_store_ledger_account_transaction_index');
        });
    }

    public function down(): void
    {
        Schema::table('member_store_ledger_entries', function (Blueprint $table): void {
            $table->dropIndex('member_store_ledger_entries_account_id_transaction_no_index');
            $table->dropIndex('member_store_ledger_account_transaction_index');
            $table->dropColumn(['purchaser_name', 'purchase_note', 'transaction_no']);
        });

        Schema::table('member_store_delegates', function (Blueprint $table): void {
            $table->string('pin_hash', 255)->nullable();
        });
    }
};
