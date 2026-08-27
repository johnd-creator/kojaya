<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pos_products', 'organization_id')) {
            return;
        }

        Schema::table('pos_products', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->after('id');
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->nullOnDelete();
            $table->index(['organization_id', 'is_active', 'stock']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pos_products', 'organization_id')) {
            return;
        }

        Schema::table('pos_products', function (Blueprint $table): void {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id', 'is_active', 'stock']);
            $table->dropColumn('organization_id');
        });
    }
};
