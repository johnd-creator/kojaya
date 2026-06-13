<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sync_requests', function (Blueprint $table) {
            $table->string('payload_hash', 64)->nullable()->after('payload');
            $table->index(['user_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::table('pos_sync_requests', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'device_id']);
            $table->dropColumn('payload_hash');
        });
    }
};
