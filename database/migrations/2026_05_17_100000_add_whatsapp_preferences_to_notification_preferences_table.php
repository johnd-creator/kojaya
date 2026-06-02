<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->boolean('whatsapp_enabled')->default(false)->after('push_enabled');
            $table->string('whatsapp_phone', 40)->nullable()->after('whatsapp_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->dropColumn(['whatsapp_enabled', 'whatsapp_phone']);
        });
    }
};
