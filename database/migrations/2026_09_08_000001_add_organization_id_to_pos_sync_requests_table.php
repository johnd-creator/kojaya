<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pos_sync_requests', 'organization_id')) {
            Schema::table('pos_sync_requests', function (Blueprint $table): void {
                $table->uuid('organization_id')->nullable()->after('user_id');
                $table->foreign('organization_id')
                    ->references('id')
                    ->on('organizations')
                    ->nullOnDelete();

                $table->index(['organization_id', 'status']);
            });
        }
    }

    public function backfillOrganizationIds(): int
    {
        // SEC-P1-03 R2: Current user.organization_id is not historical proof of ownership.
        // Legacy sync requests with unknown historical tenant must NOT be backfilled.
        // Legacy NULL stays NULL and fails closed during processing.
        return 0;
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_sync_requests', 'organization_id')) {
            Schema::table('pos_sync_requests', function (Blueprint $table): void {
                $table->dropIndex(['organization_id', 'status']);
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            });
        }
    }
};
