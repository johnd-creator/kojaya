<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->unique('user_id', 'cooperative_members_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->dropUnique('cooperative_members_user_id_unique');
        });
    }
};
