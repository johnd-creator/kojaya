<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // e.g. Shift Pagi
            $table->string('type', 20);                         // SHIFT | NON_SHIFT
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_flexible')->default(false);     // true = Non-Shift
            $table->unsignedInteger('flexible_minutes')->default(60); // toleransi keterlambatan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_shifts');
    }
};
