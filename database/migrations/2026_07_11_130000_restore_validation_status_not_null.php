<?php

use App\Models\CooperativeMember;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exact allowed status/validation_status pairs.
     *
     * Any row that does not match one of these pairs blocks the migration.
     * This includes known-but-contradictory pairs such as ACTIVE/REJECTED,
     * PENDING/ACTIVE, RESIGNED/PENDING, and INACTIVE/PENDING_VALIDATION.
     */
    private const ALLOWED_PAIRS = [
        ['PENDING', CooperativeMember::VALIDATION_PENDING],
        ['PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW],
        ['INACTIVE', CooperativeMember::VALIDATION_INACTIVE],
        ['INACTIVE', CooperativeMember::VALIDATION_REVISION],
        ['INACTIVE', CooperativeMember::VALIDATION_REJECTED],
        ['ACTIVE', CooperativeMember::VALIDATION_ACTIVE],
        ['RESIGNED', CooperativeMember::VALIDATION_RESIGNED],
    ];

    public function up(): void
    {
        $query = DB::table('cooperative_members');

        // Null validation_status
        $query->whereNull('validation_status');

        // Unknown status values
        $query->orWhereNotIn('status', ['PENDING', 'ACTIVE', 'INACTIVE', 'RESIGNED']);

        // Unknown validation_status values
        $query->orWhere(function ($query): void {
            $query->whereNotNull('validation_status')
                ->whereNotIn('validation_status', [
                    CooperativeMember::VALIDATION_PENDING,
                    CooperativeMember::VALIDATION_PENDING_REVIEW,
                    CooperativeMember::VALIDATION_ACTIVE,
                    CooperativeMember::VALIDATION_INACTIVE,
                    CooperativeMember::VALIDATION_REJECTED,
                    CooperativeMember::VALIDATION_REVISION,
                    CooperativeMember::VALIDATION_RESIGNED,
                ]);
        });

        // Known-but-contradictory pairs: any row that does NOT match an allowed pair
        $query->orWhere(function ($query): void {
            $query->whereNotNull('validation_status')->where(function ($query): void {
                foreach (self::ALLOWED_PAIRS as [$status, $validationStatus]) {
                    $query->whereNot(function ($pair) use ($status, $validationStatus): void {
                        $pair->where('status', $status)
                            ->where('validation_status', $validationStatus);
                    });
                }
            });
        });

        $invalid = $query->count();

        if ($invalid > 0) {
            throw new RuntimeException(sprintf(
                '%d cooperative member lifecycle rows have unresolved or contradictory status pairs. '
                .'Run members:audit-status-consistency, review manual rows, then run the deterministic backfill before retrying this migration. '
                .'Allowed pairs: %s',
                $invalid,
                implode(', ', array_map(
                    fn (array $pair): string => $pair[0].'/'.$pair[1],
                    self::ALLOWED_PAIRS,
                )),
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
