<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->uuid('correlation_id')->nullable()->after('id')->index();
            $table->foreignUuid('organization_id')->nullable()->after('user_id')->constrained('organizations')->nullOnDelete();
            $table->json('actor_roles')->nullable()->after('organization_id');
            $table->text('reason')->nullable()->after('new_values');
            $table->timestamp('occurred_at')->nullable()->after('user_agent')->index();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['correlation_id']);
            $table->dropIndex(['occurred_at']);
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['correlation_id', 'actor_roles', 'reason', 'occurred_at']);
        });
    }
};
