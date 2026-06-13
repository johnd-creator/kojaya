<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sync_requests', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 80);
            $table->string('device_id', 80)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pos_cashier_shift_id')->nullable()->constrained('pos_cashier_shifts')->nullOnDelete();
            $table->string('endpoint', 120);
            $table->string('method', 10);
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->string('idempotency_key', 120)->unique();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->string('status', 30)->default('PENDING');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['device_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sync_requests');
    }
};
