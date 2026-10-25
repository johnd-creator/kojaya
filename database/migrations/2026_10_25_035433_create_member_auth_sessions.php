<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_auth_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_name', 255);
            $table->string('device_id', 100)->nullable();
            $table->boolean('remembered');
            $table->char('security_digest', 64);
            $table->timestamp('idle_expires_at');
            $table->timestamp('absolute_expires_at');
            $table->timestamp('last_renewed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 32)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'revoked_at']);
        });
        Schema::create('member_session_renewals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_auth_session_id')->constrained()->cascadeOnDelete();
            $table->char('secret_hash', 64);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->foreignUuid('member_session_id')->nullable()->constrained('member_auth_sessions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Rollback is for drained disposable/approved environments, never active remembered sessions.
        // Never turn a family-bound credential into an unbound legacy credential on rollback.
        \Illuminate\Support\Facades\DB::table('personal_access_tokens')->whereNotNull('member_session_id')->delete();
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('member_session_id');
        });
        Schema::dropIfExists('member_session_renewals');
        Schema::dropIfExists('member_auth_sessions');
    }
};
