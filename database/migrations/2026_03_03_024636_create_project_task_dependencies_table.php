<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task_dependencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('task_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->foreignUuid('predecessor_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->string('type')->default('FS');
            $table->integer('lag_days')->default(0);
            $table->timestamps();

            $table->unique(['task_id', 'predecessor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_dependencies');
    }
};
