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
        Schema::table('cooperative_payments', function (Blueprint $table) {
            $table->string('receipt_no')->nullable()->after('reference_no');
            $table->timestamp('receipt_issued_at')->nullable()->after('receipt_no');
            $table->timestamp('reconciled_at')->nullable()->after('approved_by');
            $table->foreignId('reconciled_by')->nullable()->after('reconciled_at')->constrained('users')->nullOnDelete();
            $table->string('reconciliation_reference')->nullable()->after('reconciled_by');
            $table->string('gateway_provider', 40)->nullable()->after('payment_method');
            $table->string('gateway_reference')->nullable()->after('gateway_provider');
            $table->string('gateway_status', 40)->nullable()->after('gateway_reference');
            $table->json('gateway_payload')->nullable()->after('gateway_status');

            $table->index(['gateway_provider', 'gateway_reference']);
            $table->index(['reconciled_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cooperative_payments', function (Blueprint $table) {
            $table->dropIndex(['gateway_provider', 'gateway_reference']);
            $table->dropIndex(['reconciled_at', 'status']);
            $table->dropForeign(['reconciled_by']);
            $table->dropColumn([
                'receipt_no',
                'receipt_issued_at',
                'reconciled_at',
                'reconciled_by',
                'reconciliation_reference',
                'gateway_provider',
                'gateway_reference',
                'gateway_status',
                'gateway_payload',
            ]);
        });
    }
};
