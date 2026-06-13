<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sync_requests', function (Blueprint $table) {
            $table->unique(['device_id', 'client_id'], 'pos_sync_device_client_unique');
            $table->index(['user_id', 'device_id', 'idempotency_key'], 'pos_sync_user_device_idem_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sync_requests', function (Blueprint $table) {
            $table->dropIndex('pos_sync_user_device_idem_idx');
            $table->dropUnique('pos_sync_device_client_unique');
        });
    }
};
