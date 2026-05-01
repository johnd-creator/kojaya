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
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('type', 30); // PKWT, PKWTT
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Nullable for PKWTT
            $table->string('status', 30)->default('ACTIVE'); // ACTIVE, EXPIRED, TERMINATED
            $table->timestamps();

            // Indexing Strategy for Contract Tracking & Expiry Reminders
            $table->index('end_date');
            $table->index(['employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};
