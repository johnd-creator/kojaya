<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->string('token_app', 32)->nullable()->after('abilities');
            $table->string('token_version', 32)->nullable()->after('token_app');
            $table->string('device_id', 120)->nullable()->after('token_version');
            $table->timestamp('issued_at')->nullable()->after('device_id');
            $table->index(['tokenable_type', 'tokenable_id', 'token_app'], 'pat_tokenable_app_idx');
            $table->index('token_version', 'pat_token_version_idx');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropIndex('pat_tokenable_app_idx');
            $table->dropIndex('pat_token_version_idx');
            $table->dropColumn(['token_app', 'token_version', 'device_id', 'issued_at']);
        });
    }
};
