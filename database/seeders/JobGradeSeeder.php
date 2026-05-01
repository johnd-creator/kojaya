<?php

namespace Database\Seeders;

use App\Models\JobGrade;
use Illuminate\Database\Seeder;

class JobGradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            ['code' => 'PELAKSANA', 'name' => 'Pelaksana', 'level' => 1],
            ['code' => 'PELAKSANA_SENIOR', 'name' => 'Pelaksana Senior', 'level' => 2],
            ['code' => 'PENYELIA_DASAR', 'name' => 'Penyelia Dasar', 'level' => 3],
            ['code' => 'PENYELIA_ATAS', 'name' => 'Penyelia Atas', 'level' => 4],
            ['code' => 'MANAJER', 'name' => 'Manajer', 'level' => 5],
            ['code' => 'DIREKSI', 'name' => 'Direksi', 'level' => 6],
        ];

        foreach ($grades as $grade) {
            JobGrade::firstOrCreate(['code' => $grade['code']], $grade);
        }
    }
}
