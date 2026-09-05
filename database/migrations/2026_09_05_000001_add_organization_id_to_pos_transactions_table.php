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
        if (! Schema::hasColumn('pos_transactions', 'organization_id')) {
            Schema::table('pos_transactions', function (Blueprint $table): void {
                $table->uuid('organization_id')->nullable()->after('id');
                $table->foreign('organization_id')
                    ->references('id')
                    ->on('organizations')
                    ->nullOnDelete();
                $table->index(['organization_id', 'sold_at']);

                // Relax global client_reference uniqueness to tenant-scoped uniqueness
                $table->dropUnique(['client_reference']);
                $table->unique(['organization_id', 'client_reference']);
            });
        }

        $this->backfillOrganizationIds();
    }

    public function backfillOrganizationIds(): int
    {
        // Backfill existing transactions deterministically:
        // Preferred evidence: all transaction item products resolve to exactly one identical non-null organization.
        // Optional consistency evidence: member organization, when present, agrees.
        // If ambiguous or unresolved, leave organization_id NULL (fail closed).
        $hasProductOrg = Schema::hasColumn('pos_products', 'organization_id');

        $transactions = DB::table('pos_transactions')->whereNull('organization_id')->get(['id', 'cooperative_member_id']);
        $resolvedCount = 0;
        $unresolvedCount = 0;

        foreach ($transactions as $trx) {
            $targetOrgId = null;

            if ($hasProductOrg) {
                $productOrgs = DB::table('pos_transaction_items')
                    ->join('pos_products', 'pos_products.id', '=', 'pos_transaction_items.pos_product_id')
                    ->where('pos_transaction_items.pos_transaction_id', $trx->id)
                    ->select('pos_products.organization_id')
                    ->get();

                if ($productOrgs->isNotEmpty() && $productOrgs->every(fn ($p) => ! empty($p->organization_id))) {
                    $uniqueProductOrgs = $productOrgs->pluck('organization_id')->unique()->values();
                    if ($uniqueProductOrgs->count() === 1) {
                        $candidateOrgId = $uniqueProductOrgs->first();

                        if ($trx->cooperative_member_id !== null) {
                            $memberOrg = DB::table('cooperative_members')
                                ->where('id', $trx->cooperative_member_id)
                                ->value('organization_id');

                            if ($memberOrg !== null && (string) $memberOrg === (string) $candidateOrgId) {
                                $targetOrgId = $candidateOrgId;
                            }
                        } else {
                            $targetOrgId = $candidateOrgId;
                        }
                    }
                }
            }

            if ($targetOrgId !== null) {
                DB::table('pos_transactions')->where('id', $trx->id)->update(['organization_id' => $targetOrgId]);
                $resolvedCount++;
            } else {
                $unresolvedCount++;
            }
        }

        Log::info('SEC-P1-03 POS transaction organization backfill completed', [
            'resolved' => $resolvedCount,
            'unresolved' => $unresolvedCount,
        ]);

        return $resolvedCount;
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_transactions', 'organization_id')) {
            $duplicateCount = DB::table('pos_transactions')
                ->whereNotNull('client_reference')
                ->select('client_reference')
                ->groupBy('client_reference')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();

            if ($duplicateCount > 0) {
                throw new \LogicException("Cannot rollback migration: found {$duplicateCount} duplicate client_reference records across organizations that would violate global unique constraint.");
            }

            Schema::table('pos_transactions', function (Blueprint $table): void {
                $table->dropUnique(['organization_id', 'client_reference']);
                $table->unique(['client_reference']);
                $table->dropForeign(['organization_id']);
                $table->dropIndex(['organization_id', 'sold_at']);
                $table->dropColumn('organization_id');
            });
        }
    }
};
