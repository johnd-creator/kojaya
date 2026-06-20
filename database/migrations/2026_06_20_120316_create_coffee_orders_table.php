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
        Schema::create('coffee_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_transaction_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->string('status', 30)->default('RECEIVED');
            $table->json('customization')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('brewing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'received_at']);
            $table->index(['cooperative_member_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffee_orders');
    }
};
