<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->unsignedInteger('no_urut')->nullable()->after('user_id');
            $table->string('no_anggota', 10)->nullable()->unique()->after('no_urut');
            $table->date('tanggal_aktif')->nullable()->after('no_anggota');
            $table->string('nama_anggota', 100)->nullable()->after('tanggal_aktif');
            $table->string('npwp', 30)->nullable()->after('status');
            $table->string('no_telp', 20)->nullable()->after('npwp');
            $table->string('jenis_anggota', 3)->default('AB')->after('npwp');
            $table->string('jenis_kelamin', 1)->nullable()->after('jenis_anggota');
            $table->string('kategori', 3)->nullable()->after('jenis_kelamin');
            $table->string('autodebet', 10)->default('MANUAL')->after('kategori');
            $table->string('no_rekening', 30)->nullable()->after('autodebet');
            $table->softDeletes();

            $table->index(['status', 'jenis_anggota', 'kategori']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table) {
            $table->dropIndex(['status', 'jenis_anggota', 'kategori']);
            $table->dropUnique(['no_anggota']);
            $table->dropColumn([
                'no_urut',
                'no_anggota',
                'tanggal_aktif',
                'nama_anggota',
                'npwp',
                'no_telp',
                'jenis_anggota',
                'jenis_kelamin',
                'kategori',
                'autodebet',
                'no_rekening',
                'deleted_at',
            ]);
        });
    }
};
