<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->string('validation_status', 32)->default('PENDING')->after('status');
            $table->timestamp('validated_at')->nullable()->after('validation_status');
            $table->foreignId('validated_by')->nullable()->after('validated_at')
                ->constrained('users')->nullOnDelete();
            $table->text('validation_notes')->nullable()->after('validated_by');
            $table->timestamp('profile_completed_at')->nullable()->after('validation_notes');
            $table->string('sso_provider', 32)->nullable()->after('profile_completed_at');
            $table->timestamp('last_sso_login_at')->nullable()->after('sso_provider');

            $table->index('validation_status');
        });
    }

    public function down(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->dropIndex(['validation_status']);
        });

        if (Schema::hasColumn('cooperative_members', 'validated_by')) {
            Schema::table('cooperative_members', function (Blueprint $table): void {
                $table->dropForeign(['validated_by']);
            });

            Schema::table('cooperative_members', function (Blueprint $table): void {
                $table->dropColumn('validated_by');
            });
        }

        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->dropColumn([
                'validation_status',
                'validated_at',
                'validation_notes',
                'profile_completed_at',
                'sso_provider',
                'last_sso_login_at',
            ]);
        });
    }
};
