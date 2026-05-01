<?php

namespace Database\Seeders;

use App\Models\SalaryComponentType;
use Illuminate\Database\Seeder;

class SalaryComponentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['code' => 'P1', 'name' => 'Pendapatan 1', 'is_taxable' => true, 'sort_order' => 1],
            ['code' => 'P2', 'name' => 'Pendapatan 2', 'is_taxable' => true, 'sort_order' => 2],
            ['code' => 'TGT', 'name' => 'Tunjangan Jabatan', 'is_taxable' => true, 'sort_order' => 3],
            ['code' => 'TPL', 'name' => 'Tunjangan Penempatan Lokasi', 'is_taxable' => false, 'sort_order' => 4],
            ['code' => 'TP', 'name' => 'Tunjangan Produktivitas', 'is_taxable' => true, 'sort_order' => 5],
        ];

        foreach ($components as $component) {
            SalaryComponentType::firstOrCreate(['code' => $component['code']], array_merge($component, ['is_active' => true]));
        }
    }
}
