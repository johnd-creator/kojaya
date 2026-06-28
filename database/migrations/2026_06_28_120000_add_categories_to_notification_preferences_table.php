<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->json('categories')->nullable()->after('channels');
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->dropColumn('categories');
        });
    }
};
