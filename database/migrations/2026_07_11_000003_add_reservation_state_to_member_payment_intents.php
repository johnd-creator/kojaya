<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_payment_intents', function (Blueprint $table): void {
            $table->string('reservation_status', 20)->nullable()->after('metadata');
            $table->index(['reservation_status', 'expires_at'], 'member_payment_intents_reservation_expiry_index');
        });
    }

    public function down(): void
    {
        Schema::table('member_payment_intents', function (Blueprint $table): void {
            $table->dropIndex('member_payment_intents_reservation_expiry_index');
            $table->dropColumn('reservation_status');
        });
    }
};
