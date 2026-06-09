<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->date('tanggal_lahir')->nullable()->after('jenis_kelamin');
            $table->string('tempat_lahir', 120)->nullable()->after('tanggal_lahir');
            $table->string('pekerjaan', 120)->nullable()->after('tempat_lahir');
            $table->string('perusahaan', 160)->nullable()->after('pekerjaan');
            $table->string('nama_bank', 60)->nullable()->after('no_rekening');
            $table->string('nama_pemilik_rekening', 160)->nullable()->after('nama_bank');
            $table->timestamp('onboarding_submitted_at')->nullable()->after('profile_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_lahir',
                'tempat_lahir',
                'pekerjaan',
                'perusahaan',
                'nama_bank',
                'nama_pemilik_rekening',
                'onboarding_submitted_at',
            ]);
        });
    }
};
