<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receive_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('unit_id')->nullable();
            $table->uuid('purchase_order_id');
            $table->string('grn_no')->nullable();
            $table->string('status')->default('DRAFT');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['purchase_order_id']);

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('organizations')->onDelete('set null');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receive_notes');
    }
};
