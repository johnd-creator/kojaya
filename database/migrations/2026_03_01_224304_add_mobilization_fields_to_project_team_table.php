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
        Schema::table('project_team', function (Blueprint $table) {
            $table->enum('status', ['RECRUITMENT', 'SCREENING', 'MCU', 'ONBOARDING', 'PLACED'])->default('RECRUITMENT')->after('notes');
            $table->boolean('has_ppe')->default(false)->after('status');
            $table->boolean('has_uniform')->default(false)->after('has_ppe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_team', function (Blueprint $table) {
            $table->dropColumn(['status', 'has_ppe', 'has_uniform']);
        });
    }
};
