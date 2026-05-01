<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('job_grade_id')->constrained('job_grades')->restrictOnDelete();
            $table->timestamps();

            $table->index(['department_id', 'job_grade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
