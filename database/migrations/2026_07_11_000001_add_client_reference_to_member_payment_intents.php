<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_payment_intents', function (Blueprint $table): void {
            $table->string('client_reference', 80)->nullable()->after('payable_id');
            $table->unique(['cooperative_member_id', 'payable_type', 'client_reference'], 'member_payment_intents_member_type_client_unique');
            $table->index(['gateway_status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('member_payment_intents', function (Blueprint $table): void {
            $table->dropIndex(['gateway_status', 'expires_at']);
            $table->dropUnique('member_payment_intents_member_type_client_unique');
            $table->dropColumn('client_reference');
        });
    }
};
