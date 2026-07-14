<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->text('identity_number_enc')->nullable()->after('identity_number');
            $table->char('identity_number_bidx', 64)->nullable()->after('identity_number_enc');
            $table->text('npwp_enc')->nullable()->after('npwp');
            $table->char('npwp_bidx', 64)->nullable()->after('npwp_enc');
            $table->text('no_rekening_enc')->nullable()->after('no_rekening');
            $table->char('no_rekening_bidx', 64)->nullable()->after('no_rekening_enc');

            $table->index('identity_number_bidx', 'cooperative_members_identity_bidx_index');
            $table->index('npwp_bidx', 'cooperative_members_npwp_bidx_index');
            $table->index('no_rekening_bidx', 'cooperative_members_account_bidx_index');
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'PII encrypted columns are irreversible after rollout. Restore a backup or use a dedicated rollback procedure.',
        );
    }
};
