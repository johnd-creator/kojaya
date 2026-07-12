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
        Schema::table('payment_reconciliation_incidents', function (Blueprint $table): void {
            $table->string('deduplication_key', 64)->nullable()->after('gateway_reference');
            $table->unique('deduplication_key', 'incidents_dedup_unique');

            // Change cascadeOnDelete to nullOnDelete so financial evidence
            // is preserved even if the intent is deleted.
            $table->dropForeign(['member_payment_intent_id']);
            $table->foreign('member_payment_intent_id')
                ->references('id')
                ->on('member_payment_intents')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_reconciliation_incidents', function (Blueprint $table): void {
            $table->dropForeign(['member_payment_intent_id']);
            $table->foreign('member_payment_intent_id')
                ->references('id')
                ->on('member_payment_intents')
                ->cascadeOnDelete();

            $table->dropUnique('incidents_dedup_unique');
            $table->dropColumn('deduplication_key');
        });
    }
};
