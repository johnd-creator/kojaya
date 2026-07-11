<?php

use App\Models\CooperativeMember;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $invalid = DB::table('cooperative_members')
            ->whereNull('validation_status')
            ->orWhereNotIn('status', ['PENDING', 'ACTIVE', 'INACTIVE', 'RESIGNED'])
            ->orWhereNotIn('validation_status', [
                CooperativeMember::VALIDATION_PENDING,
                CooperativeMember::VALIDATION_PENDING_REVIEW,
                CooperativeMember::VALIDATION_ACTIVE,
                CooperativeMember::VALIDATION_INACTIVE,
                CooperativeMember::VALIDATION_REJECTED,
                CooperativeMember::VALIDATION_REVISION,
                CooperativeMember::VALIDATION_RESIGNED,
            ])
            ->count();

        if ($invalid > 0) {
            throw new RuntimeException(sprintf(
                '%d cooperative member lifecycle rows are unresolved. Run members:audit-status-consistency, review manual rows, then run the deterministic backfill before retrying this migration.',
                $invalid,
            ));
        }

        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->string('validation_status', 32)->nullable(false)->default(CooperativeMember::VALIDATION_PENDING)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->string('validation_status', 32)->nullable()->default(CooperativeMember::VALIDATION_PENDING)->change();
        });
    }
};
