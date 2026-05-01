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
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('parent_task_id')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])->default('PENDING');
            $table->integer('progress_percentage')->default(0);
            $table->integer('estimated_hours')->default(0);
            $table->integer('actual_hours')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('employees')->onDelete('set null');
            $table->index(['project_id', 'status']);
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->foreign('parent_task_id')->references('id')->on('project_tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
