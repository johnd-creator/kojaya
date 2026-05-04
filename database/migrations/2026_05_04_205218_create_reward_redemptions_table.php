<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reward_id')->constrained('rewards')->cascadeOnDelete();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('point_transaction_id')->nullable()->constrained('point_transactions')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('points_used');
            $table->text('delivery_address')->nullable();
            $table->string('status', 30)->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamp('redeemed_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['cooperative_member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
    }
};
