<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_payment_charge_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_payment_intent_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt');
            $table->string('idempotency_key', 120);
            $table->string('provider_order_id', 120)->nullable();
            $table->string('state', 20)->default('PREPARING');
            $table->string('provider_reference', 120)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['member_payment_intent_id', 'attempt'], 'charge_attempts_intent_attempt_unique');
            $table->unique('idempotency_key', 'charge_attempts_idempotency_unique');
        });

        Schema::table('member_payment_charge_attempts', function (Blueprint $table): void {
            $table->index(['state', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_payment_charge_attempts');
    }
};
