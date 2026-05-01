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
        Schema::dropIfExists('maintenance_schedules');

        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->string('type'); // TIME_BASED, METER_BASED, EVENT_BASED
            $table->string('frequency'); // DAILY, WEEKLY, MONTHLY, QUARTERLY, YEARLY, EVERY_X_HOURS, EVERY_X_KM
            $table->integer('interval_value')->default(1); // e.g., every 3 months, every 500 hours
            $table->uuid('maintenance_checklist_id')->nullable();
            $table->date('next_due_date')->nullable();
            $table->decimal('last_meter_reading', 10, 2)->nullable();
            $table->decimal('target_meter_reading', 10, 2)->nullable();
            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH', 'EMERGENCY'])->default('MEDIUM');
            $table->foreignId('assigned_to')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_completed_at')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('maintenance_checklist_id')->references('id')->on('maintenance_checklists')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
