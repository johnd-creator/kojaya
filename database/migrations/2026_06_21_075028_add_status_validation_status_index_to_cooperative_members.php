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
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->index(['status', 'validation_status'], 'coop_members_status_validation_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->dropIndex('coop_members_status_validation_status_idx');
        });
    }
};
