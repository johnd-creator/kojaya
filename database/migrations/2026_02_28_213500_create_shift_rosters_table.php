<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_rosters', function (Blueprint $table) {
            $table->id();
            $table->date('date');                                        // e.g. 2026-03-01
            $table->char('shift_group', 1);                             // A, B, C, D
            $table->foreignId('work_shift_id')->nullable()
                ->constrained('work_shifts')->nullOnDelete();          // null = OFF day
            $table->boolean('is_off_day')->default(false);
            $table->text('notes')->nullable();                           // override reason
            $table->timestamps();

            $table->unique(['date', 'shift_group']);                     // one entry per day per group
            $table->index(['shift_group', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_rosters');
    }
};
