<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reconciliation_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_payment_intent_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('gateway_reference', 120)->nullable();
            $table->string('incident_type', 60);
            $table->string('status', 20)->default('OPEN');
            $table->string('provider_status', 30)->nullable();
            $table->string('provider_reference', 120)->nullable();
            $table->string('expected_amount_minor', 20)->nullable();
            $table->string('actual_amount_minor', 20)->nullable();
            $table->json('webhook_payload')->nullable();
            $table->json('resolution')->nullable();
            $table->timestamp('incident_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'incident_type']);
            $table->index('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_incidents');
    }
};
