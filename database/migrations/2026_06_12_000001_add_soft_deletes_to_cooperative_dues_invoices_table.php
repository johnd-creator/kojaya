<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_dues_invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('cooperative_dues_invoices', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cooperative_dues_invoices', function (Blueprint $table): void {
            if (Schema::hasColumn('cooperative_dues_invoices', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
