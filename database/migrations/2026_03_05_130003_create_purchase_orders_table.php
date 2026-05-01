<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('unit_id')->nullable();
            $table->uuid('purchase_request_id')->nullable();
            $table->uuid('vendor_id')->nullable();
            $table->string('po_no')->nullable();
            $table->string('status')->default('ISSUED');
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['purchase_request_id']);
            $table->index(['vendor_id']);

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('organizations')->onDelete('set null');
            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
