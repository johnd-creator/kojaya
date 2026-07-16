<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('source', 30)->default('system')->after('actor_roles')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('audit_logs', 'source')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
