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
        Schema::create('work_order_sync_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_order_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('idempotency_key', 120);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('work_order_id')->references('id')->on('work_orders')->cascadeOnDelete();
            $table->unique(['work_order_id', 'user_id', 'idempotency_key'], 'wo_sync_unique_idempotency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_sync_requests');
    }
};
