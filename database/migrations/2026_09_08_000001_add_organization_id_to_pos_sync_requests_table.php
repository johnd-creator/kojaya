<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $this->backfillOrganizationIds();
    }

    public function backfillOrganizationIds(): int
    {
        // Deterministic backfill: only when sync user belongs to an ordinary organization
        // (has non-null organization_id and does not have view_cooperative_all permission).
        // If user is null, missing, has null org, or is global, leave organization_id NULL (fail closed).
        // Historical requests without provable organization must fail closed when processed.
        $requests = DB::table('pos_sync_requests')
            ->whereNull('organization_id')
            ->whereNotNull('user_id')
            ->get(['id', 'user_id']);

        $resolvedCount = 0;
        foreach ($requests as $req) {
            $user = DB::table('users')->where('id', $req->user_id)->first(['id', 'organization_id']);
            if ($user && ! empty($user->organization_id)) {
                $hasGlobal = DB::table('model_has_permissions')
                    ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
                    ->where('model_has_permissions.model_type', 'App\\Models\\User')
                    ->where('model_has_permissions.model_id', $user->id)
                    ->where('permissions.name', 'view_cooperative_all')
                    ->exists();

                $hasGlobalRole = DB::table('model_has_roles')
                    ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
                    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('model_has_roles.model_id', $user->id)
                    ->where('permissions.name', 'view_cooperative_all')
                    ->exists();

                if (! $hasGlobal && ! $hasGlobalRole) {
                    DB::table('pos_sync_requests')
                        ->where('id', $req->id)
                        ->update(['organization_id' => $user->organization_id]);
                    $resolvedCount++;
                }
            }
        }

        Log::info('SEC-P1-03 POS sync request organization backfill completed', [
            'resolved' => $resolvedCount,
        ]);

        return $resolvedCount;
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
