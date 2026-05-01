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
        Schema::create('overtime_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->uuid('overtime_request_id');
            $table->decimal('hours', 5, 2);
            $table->decimal('hourly_rate', 15, 2);
            $table->decimal('multiplier', 4, 2);
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->foreign('overtime_request_id')->references('id')->on('overtime_requests')->onDelete('restrict');
            $table->unique(['payroll_id', 'overtime_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_payments');
    }
};
