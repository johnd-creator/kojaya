<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pos_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 60)->unique();
            $table->string('barcode', 80)->nullable()->unique();
            $table->string('name');
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2);
            $table->integer('stock')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'stock']);
        });

        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no', 50)->unique();
            $table->string('client_reference', 80)->nullable()->unique();
            $table->foreignId('cooperative_member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('status', 30)->default('COMPLETED');
            $table->timestamp('sold_at');
            $table->timestamps();

            $table->index(['sold_at', 'status']);
        });

        Schema::create('pos_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_product_id')->constrained()->restrictOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });

        Schema::create('pos_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_transaction_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method', 30);
            $table->decimal('amount', 15, 2);
            $table->string('reference_no')->nullable();
            $table->timestamps();
        });

        Schema::create('pos_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_product_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('source');
            $table->string('movement_type', 30);
            $table->integer('quantity');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_stock_movements');
        Schema::dropIfExists('pos_payments');
        Schema::dropIfExists('pos_transaction_items');
        Schema::dropIfExists('pos_transactions');
        Schema::dropIfExists('pos_products');
        Schema::dropIfExists('pos_categories');
    }
};
