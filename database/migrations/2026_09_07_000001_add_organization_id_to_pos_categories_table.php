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
        if (! Schema::hasColumn('pos_categories', 'organization_id')) {
            Schema::table('pos_categories', function (Blueprint $table): void {
                $table->uuid('organization_id')->nullable()->after('id');
                $table->foreign('organization_id')
                    ->references('id')
                    ->on('organizations')
                    ->nullOnDelete();

                $table->unsignedBigInteger('duplicated_from_id')->nullable()->after('organization_id');
                $table->foreign('duplicated_from_id')
                    ->references('id')
                    ->on('pos_categories')
                    ->nullOnDelete();

                $table->index(['organization_id', 'is_active']);

                $table->dropUnique(['slug']);
                $table->unique(['organization_id', 'slug'], 'pos_categories_org_slug_unique');
            });
        }

        $this->backfillOrganizationIds();
    }

    public function backfillOrganizationIds(): array
    {
        $hasProductTable = Schema::hasTable('pos_products') && Schema::hasColumn('pos_products', 'organization_id');
        if (! $hasProductTable) {
            return [
                'resolved' => 0,
                'duplicates_created' => 0,
                'products_remapped' => 0,
                'orphans' => DB::table('pos_categories')->whereNull('organization_id')->count(),
            ];
        }

        $categories = DB::table('pos_categories')->whereNull('organization_id')->get();
        $resolvedCount = 0;
        $duplicatesCreated = 0;
        $productsRemapped = 0;
        $orphansCount = 0;

        foreach ($categories as $category) {
            $productOrgs = DB::table('pos_products')
                ->where('pos_category_id', $category->id)
                ->whereNotNull('organization_id')
                ->select('organization_id')
                ->distinct()
                ->pluck('organization_id')
                ->sort()
                ->values();

            if ($productOrgs->isEmpty()) {
                // Case 0: Orphan category - zero related products with non-null organization_id.
                // Leave organization_id as NULL to avoid fabricating arbitrary ownership.
                $orphansCount++;

                continue;
            }

            if ($productOrgs->count() === 1) {
                // Case 1: Exactly one organization owns all related products.
                $singleOrgId = $productOrgs->first();
                DB::table('pos_categories')
                    ->where('id', $category->id)
                    ->update(['organization_id' => $singleOrgId]);
                $resolvedCount++;

                continue;
            }

            // Case 2: Multi-tenant reference - products belong to multiple distinct organizations.
            // Strategy A: Deterministic category duplication per organization.
            // First organization in deterministic sort retains the original category record.
            $firstOrgId = $productOrgs->first();
            DB::table('pos_categories')
                ->where('id', $category->id)
                ->update(['organization_id' => $firstOrgId]);
            $resolvedCount++;

            // Subsequent organizations get a duplicate category record with remapped products.
            foreach ($productOrgs->slice(1) as $subsequentOrgId) {
                $newCategoryId = DB::table('pos_categories')->insertGetId([
                    'organization_id' => $subsequentOrgId,
                    'duplicated_from_id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'is_active' => $category->is_active,
                    'created_at' => $category->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                $remapped = DB::table('pos_products')
                    ->where('pos_category_id', $category->id)
                    ->where('organization_id', $subsequentOrgId)
                    ->update(['pos_category_id' => $newCategoryId]);

                $duplicatesCreated++;
                $productsRemapped += $remapped;
            }
        }

        Log::info('SEC-P1-08 POS category organization backfill completed', [
            'resolved' => $resolvedCount,
            'duplicates_created' => $duplicatesCreated,
            'products_remapped' => $productsRemapped,
            'orphans' => $orphansCount,
        ]);

        return [
            'resolved' => $resolvedCount,
            'duplicates_created' => $duplicatesCreated,
            'products_remapped' => $productsRemapped,
            'orphans' => $orphansCount,
        ];
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_categories', 'organization_id')) {
            $duplicates = DB::table('pos_categories')
                ->whereNotNull('duplicated_from_id')
                ->get(['id', 'duplicated_from_id']);

            foreach ($duplicates as $duplicate) {
                DB::table('pos_products')
                    ->where('pos_category_id', $duplicate->id)
                    ->update(['pos_category_id' => $duplicate->duplicated_from_id]);
            }

            DB::table('pos_categories')->whereNotNull('duplicated_from_id')->delete();

            $duplicateSlugs = DB::table('pos_categories')
                ->select('slug')
                ->groupBy('slug')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();

            if ($duplicateSlugs > 0) {
                throw new \LogicException("Cannot rollback migration: found {$duplicateSlugs} duplicate slug records across organizations that would violate global unique constraint.");
            }

            Schema::table('pos_categories', function (Blueprint $table): void {
                $table->dropUnique('pos_categories_org_slug_unique');
                $table->unique(['slug']);
                $table->dropForeign(['duplicated_from_id']);
                $table->dropColumn('duplicated_from_id');
                $table->dropForeign(['organization_id']);
                $table->dropIndex(['organization_id', 'is_active']);
                $table->dropColumn('organization_id');
            });
        }
    }
};
