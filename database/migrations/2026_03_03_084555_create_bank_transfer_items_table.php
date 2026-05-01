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
        Schema::create('bank_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('batch_id');
            $table->string('beneficiary_name');
            $table->string('beneficiary_account');
            $table->decimal('amount', 18, 2);
            $table->string('currency')->default('IDR');
            $table->string('reference')->nullable();
            $table->uuid('invoice_id')->nullable();
            $table->string('status')->default('PENDING');
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('bank_transfer_batches')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transfer_items');
    }
};
