<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->string('identity_number_key_version', 16)->nullable()->after('identity_number_enc');
            $table->string('identity_number_bidx_version', 16)->nullable()->after('identity_number_bidx');
            $table->timestamp('identity_number_migrated_at')->nullable()->after('identity_number_bidx_version');
            $table->string('npwp_key_version', 16)->nullable()->after('npwp_enc');
            $table->string('npwp_bidx_version', 16)->nullable()->after('npwp_bidx');
            $table->timestamp('npwp_migrated_at')->nullable()->after('npwp_bidx_version');
            $table->string('no_rekening_key_version', 16)->nullable()->after('no_rekening_enc');
            $table->string('no_rekening_bidx_version', 16)->nullable()->after('no_rekening_bidx');
            $table->timestamp('no_rekening_migrated_at')->nullable()->after('no_rekening_bidx_version');
        });
    }

    public function down(): void
    {
        if (! config('security.pii_allow_schema_rollback', false)) {
            throw new RuntimeException(
                'PII metadata rollback is blocked unless explicitly enabled for a disposable test database.',
            );
        }

        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->dropColumn([
                'identity_number_key_version',
                'identity_number_bidx_version',
                'identity_number_migrated_at',
                'npwp_key_version',
                'npwp_bidx_version',
                'npwp_migrated_at',
                'no_rekening_key_version',
                'no_rekening_bidx_version',
                'no_rekening_migrated_at',
            ]);
        });
    }
};
