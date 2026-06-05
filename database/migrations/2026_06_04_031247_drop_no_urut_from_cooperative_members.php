<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->dropColumn('no_urut');
        });
    }

    public function down(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->unsignedInteger('no_urut')->nullable()->after('user_id');
        });
    }
};
