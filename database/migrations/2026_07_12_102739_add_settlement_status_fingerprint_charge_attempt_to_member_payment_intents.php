<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_payment_intents', function (Blueprint $table): void {
            $table->string('settlement_status', 20)->default('NOT_SETTLED')->after('reservation_status');
            $table->string('request_fingerprint', 64)->nullable()->after('client_reference');
            $table->unsignedSmallInteger('charge_attempt')->default(0)->after('gateway_payload');
            $table->index(['settlement_status']);
        });
    }

    public function down(): void
    {
        Schema::table('member_payment_intents', function (Blueprint $table): void {
            $table->dropIndex(['settlement_status']);
            $table->dropColumn(['settlement_status', 'request_fingerprint', 'charge_attempt']);
        });
    }
};
