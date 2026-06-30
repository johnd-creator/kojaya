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
        Schema::create('member_payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cooperative_member_id')->constrained('cooperative_members')->cascadeOnDelete();
            $table->string('payable_type', 40);
            $table->unsignedBigInteger('payable_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('channel', 30);
            $table->string('gateway_provider', 40)->nullable();
            $table->string('gateway_reference', 120)->nullable()->unique();
            $table->string('gateway_status', 30)->default('PENDING');
            $table->json('gateway_payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->string('settled_by_service', 80)->nullable();
            $table->timestamps();

            $table->index(['cooperative_member_id', 'gateway_status']);
            $table->index(['payable_type', 'payable_id']);
            $table->index(['created_at', 'gateway_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_payment_intents');
    }
};
