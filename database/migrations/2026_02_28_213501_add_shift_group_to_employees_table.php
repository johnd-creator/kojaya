<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->char('shift_group', 1)->nullable()->after('work_shift_id')
                ->comment('Rotating shift group: A, B, C, or D. Null for non-operational staff.');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('shift_group');
        });
    }
};
