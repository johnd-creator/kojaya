<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_ledger_entries', function (Blueprint $table) {
            $table->foreignId('cooperative_contribution_type_id')
                ->nullable()
                ->after('cooperative_payment_id')
                ->constrained('cooperative_contribution_types')
                ->nullOnDelete();
            $table->string('ledger_scope', 30)->nullable()->after('entry_type');
            $table->string('category_snapshot', 30)->nullable()->after('ledger_scope');

            $table->index(['cooperative_member_id', 'ledger_scope', 'posted_at'], 'coop_ledger_member_scope_posted_idx');
            $table->index(['cooperative_contribution_type_id', 'posted_at'], 'coop_ledger_type_posted_idx');
            $table->index(['ledger_scope', 'entry_type'], 'coop_ledger_scope_type_idx');
        });

        DB::table('cooperative_ledger_entries')
            ->leftJoin('cooperative_payments', 'cooperative_ledger_entries.cooperative_payment_id', '=', 'cooperative_payments.id')
            ->leftJoin('cooperative_dues_invoices', 'cooperative_payments.cooperative_dues_invoice_id', '=', 'cooperative_dues_invoices.id')
            ->leftJoin('cooperative_contribution_types', 'cooperative_dues_invoices.cooperative_contribution_type_id', '=', 'cooperative_contribution_types.id')
            ->where('cooperative_ledger_entries.entry_type', 'SAVING_PAYMENT')
            ->select([
                'cooperative_ledger_entries.id',
                'cooperative_dues_invoices.cooperative_contribution_type_id',
                'cooperative_contribution_types.category',
            ])
            ->orderBy('cooperative_ledger_entries.id')
            ->each(function (object $entry): void {
                DB::table('cooperative_ledger_entries')
                    ->where('id', $entry->id)
                    ->update([
                        'cooperative_contribution_type_id' => $entry->cooperative_contribution_type_id,
                        'ledger_scope' => 'SAVINGS',
                        'category_snapshot' => $entry->category,
                    ]);
            });

        DB::table('cooperative_ledger_entries')
            ->whereIn('entry_type', ['OPENING_BALANCE', 'SAVING_WITHDRAWAL', 'SAVINGS_DEPOSIT', 'SIMPANAN_SUKARELA', 'SAVINGS_VOLUNTARY', 'VOLUNTARY_SAVING'])
            ->whereNull('ledger_scope')
            ->update(['ledger_scope' => 'SAVINGS']);

        DB::table('cooperative_ledger_entries')
            ->whereIn('entry_type', ['LOAN_DISBURSEMENT', 'LOAN_PAYMENT'])
            ->whereNull('ledger_scope')
            ->update(['ledger_scope' => 'LOAN']);

        DB::table('cooperative_ledger_entries')
            ->whereIn('entry_type', ['POS_MEMBER_CREDIT', 'POS_RETURN'])
            ->whereNull('ledger_scope')
            ->update(['ledger_scope' => 'POS']);
    }

    public function down(): void
    {
        Schema::table('cooperative_ledger_entries', function (Blueprint $table) {
            $table->dropIndex('coop_ledger_member_scope_posted_idx');
            $table->dropIndex('coop_ledger_type_posted_idx');
            $table->dropIndex('coop_ledger_scope_type_idx');
            $table->dropConstrainedForeignId('cooperative_contribution_type_id');
            $table->dropColumn(['ledger_scope', 'category_snapshot']);
        });
    }
};
