<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receive_note_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('goods_receive_note_id');
            $table->uuid('purchase_order_item_id');
            $table->decimal('received_qty', 18, 2);
            $table->string('condition')->nullable();
            $table->timestamps();

            $table->index(['goods_receive_note_id']);
            $table->index(['purchase_order_item_id']);

            $table->foreign('goods_receive_note_id')->references('id')->on('goods_receive_notes')->onDelete('cascade');
            $table->foreign('purchase_order_item_id')->references('id')->on('purchase_order_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receive_note_items');
    }
};
