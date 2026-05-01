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
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('phtkp_status', ['TK/0', 'K/0', 'TK/1', 'K/1', 'TK/2', 'K/2', 'TK/3', 'K/3'])->default('TK/0')->after('shift_group');
            $table->string('npwp_number', 20)->nullable()->after('phtkp_status');
            $table->boolean('is_npwp_available')->default(false)->after('npwp_number');
            $table->unsignedInteger('number_of_dependents')->default(0)->after('is_npwp_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['phtkp_status', 'npwp_number', 'is_npwp_available', 'number_of_dependents']);
        });
    }
};
