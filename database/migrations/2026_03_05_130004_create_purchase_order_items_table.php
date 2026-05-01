<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id');
            $table->uuid('purchase_request_item_id')->nullable();
            $table->string('description');
            $table->decimal('qty', 18, 2);
            $table->decimal('price', 18, 2);
            $table->decimal('amount', 18, 2);
            $table->timestamps();

            $table->index(['purchase_order_id']);
            $table->index(['purchase_request_item_id']);

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('purchase_request_item_id')->references('id')->on('purchase_request_items')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
