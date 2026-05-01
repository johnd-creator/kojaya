<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('work_shift_id')->nullable()->after('notes')->constrained('work_shifts')->nullOnDelete();
            $table->time('scheduled_end_time')->nullable()->after('work_shift_id');
            $table->boolean('is_overtime')->default(false)->after('scheduled_end_time');
            $table->decimal('overtime_hours', 4, 2)->default(0)->after('is_overtime');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['work_shift_id']);
            $table->dropColumn(['work_shift_id', 'scheduled_end_time', 'is_overtime', 'overtime_hours']);
        });
    }
};
