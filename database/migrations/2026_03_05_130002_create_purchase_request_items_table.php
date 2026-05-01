<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_request_id');
            $table->string('description');
            $table->string('gl_account');
            $table->decimal('qty', 18, 2);
            $table->decimal('price', 18, 2);
            $table->decimal('amount', 18, 2);
            $table->timestamps();

            $table->index(['purchase_request_id']);
            $table->index(['gl_account']);

            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
