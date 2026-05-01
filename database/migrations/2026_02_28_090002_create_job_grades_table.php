<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_grades', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // e.g. PELAKSANA
            $table->string('name');
            $table->unsignedInteger('level'); // 1=Pelaksana ... 6=Direksi
            $table->timestamps();

            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_grades');
    }
};
