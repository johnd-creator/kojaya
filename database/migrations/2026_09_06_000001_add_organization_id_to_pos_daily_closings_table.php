<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pos_daily_closings', 'organization_id')) {
            Schema::table('pos_daily_closings', function (Blueprint $table): void {
                $table->uuid('organization_id')->nullable()->after('id');
                $table->foreign('organization_id')
                    ->references('id')
                    ->on('organizations')
                    ->nullOnDelete();

                $table->timestamp('closed_at')->nullable()->change();
                $table->boolean('is_locked')->default(false)->change();

                $table->dropUnique(['closing_date']);
                $table->unique(['organization_id', 'closing_date'], 'pos_daily_closings_org_date_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_daily_closings', 'organization_id')) {
            $duplicateCount = DB::table('pos_daily_closings')
                ->whereNotNull('closing_date')
                ->select('closing_date')
                ->groupBy('closing_date')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();

            if ($duplicateCount > 0) {
                throw new \RuntimeException("Cannot rollback migration: found {$duplicateCount} duplicate closing_date records across organizations that would violate global unique constraint.");
            }

            Schema::table('pos_daily_closings', function (Blueprint $table): void {
                $table->dropUnique('pos_daily_closings_org_date_unique');
                $table->unique(['closing_date']);
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            });
        }
    }
};
