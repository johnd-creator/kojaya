<?php

namespace Database\Seeders;

use App\Models\CooperativeMember;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AnggotaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Koperasi Jaya Bersama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'is_active' => true,
            ]
        );

        foreach ($this->rows() as $row) {
            $namaAnggota = $row['nama_anggota'];
            $noRekening = strtoupper((string) ($row['no_rekening'] ?? ''));

            CooperativeMember::query()->updateOrCreate(
                ['no_anggota' => $row['no_anggota']],
                [
                    ...$row,
                    'organization_id' => $organization->id,
                    'member_no' => $row['no_anggota'],
                    'name' => rtrim(rtrim($namaAnggota, '*')),
                    'phone' => $row['no_telp'],
                    'joined_at' => $row['tanggal_aktif'],
                    'jenis_anggota' => str_ends_with($namaAnggota, '*') ? 'ALB' : $row['jenis_anggota'],
                    'status' => $row['status'] === 'AKTIF' ? 'ACTIVE' : 'INACTIVE',
                    'no_rekening' => $row['autodebet'] === 'MANUAL' || $noRekening === 'MANUAL' ? null : $row['no_rekening'],
                ]
            );
        }
    }

    private function rows(): array
    {
        return [
            ['no_anggota' => '001', 'tanggal_aktif' => '2015-01-01', 'nama_anggota' => 'Ahmad Hidayat', 'status' => 'AKTIF', 'npwp' => '12.345.678.9-012.000', 'no_telp' => '081234560001', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'L', 'kategori' => 'IP', 'autodebet' => 'BNI', 'no_rekening' => '880100001'],
            ['no_anggota' => '002', 'tanggal_aktif' => '2015-01-01', 'nama_anggota' => 'Siti Aminah', 'status' => 'AKTIF', 'npwp' => null, 'no_telp' => '081234560002', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'P', 'kategori' => 'IP', 'autodebet' => 'BRI', 'no_rekening' => '330200002'],
            ['no_anggota' => '003', 'tanggal_aktif' => '2015-02-01', 'nama_anggota' => 'Budi Santoso*', 'status' => 'AKTIF', 'npwp' => '22.333.444.5-666.000', 'no_telp' => '081234560003', 'jenis_anggota' => 'ALB', 'jenis_kelamin' => 'L', 'kategori' => 'CDB', 'autodebet' => 'MANUAL', 'no_rekening' => null],
            ['no_anggota' => '004', 'tanggal_aktif' => '2015-02-15', 'nama_anggota' => 'Dewi Lestari', 'status' => 'NON-AKTIF', 'npwp' => null, 'no_telp' => '081234560004', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'P', 'kategori' => 'KOP', 'autodebet' => 'MANUAL', 'no_rekening' => 'MANUAL'],
            ['no_anggota' => '005', 'tanggal_aktif' => '2015-03-01', 'nama_anggota' => 'Rudi Hartono', 'status' => 'AKTIF', 'npwp' => '33.444.555.6-777.000', 'no_telp' => null, 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'L', 'kategori' => 'IP', 'autodebet' => 'BNI', 'no_rekening' => '880100005'],
            ['no_anggota' => '006', 'tanggal_aktif' => '2015-04-01', 'nama_anggota' => 'Maya Putri*', 'status' => 'AKTIF', 'npwp' => null, 'no_telp' => '081234560006', 'jenis_anggota' => 'ALB', 'jenis_kelamin' => 'P', 'kategori' => 'CDB', 'autodebet' => 'BRI', 'no_rekening' => '330200006'],
            ['no_anggota' => '007', 'tanggal_aktif' => '2015-05-01', 'nama_anggota' => 'Agus Salim', 'status' => 'AKTIF', 'npwp' => '44.555.666.7-888.000', 'no_telp' => '081234560007', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'L', 'kategori' => 'KOP', 'autodebet' => 'MANUAL', 'no_rekening' => null],
            ['no_anggota' => '008', 'tanggal_aktif' => '2015-06-01', 'nama_anggota' => 'Nina Kartika', 'status' => 'NON-AKTIF', 'npwp' => null, 'no_telp' => '081234560008', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'P', 'kategori' => 'IP', 'autodebet' => 'BNI', 'no_rekening' => '880100008'],
            ['no_anggota' => '009', 'tanggal_aktif' => '2015-07-01', 'nama_anggota' => 'Fajar Nugroho', 'status' => 'AKTIF', 'npwp' => '55.666.777.8-999.000', 'no_telp' => '081234560009', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'L', 'kategori' => 'CDB', 'autodebet' => 'BRI', 'no_rekening' => '330200009'],
            ['no_anggota' => '010', 'tanggal_aktif' => '2015-08-01', 'nama_anggota' => 'Ratna Sari*', 'status' => 'AKTIF', 'npwp' => null, 'no_telp' => '081234560010', 'jenis_anggota' => 'ALB', 'jenis_kelamin' => 'P', 'kategori' => 'KOP', 'autodebet' => 'MANUAL', 'no_rekening' => null],
        ];
    }
}
