<?php

namespace Database\Seeders;

use App\Models\WorkShift;
use Illuminate\Database\Seeder;

class WorkShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Shift Pagi',
                'type' => 'SHIFT',
                'start_time' => '07:00:00',
                'end_time' => '15:00:00',
                'is_flexible' => false,
                'flexible_minutes' => 0,
            ],
            [
                'name' => 'Shift Siang',
                'type' => 'SHIFT',
                'start_time' => '15:00:00',
                'end_time' => '23:00:00',
                'is_flexible' => false,
                'flexible_minutes' => 0,
            ],
            [
                'name' => 'Shift Malam',
                'type' => 'SHIFT',
                'start_time' => '23:00:00',
                'end_time' => '07:00:00',
                'is_flexible' => false,
                'flexible_minutes' => 0,
            ],
            [
                'name' => 'Non-Shift',
                'type' => 'NON_SHIFT',
                'start_time' => '07:00:00',
                'end_time' => '16:00:00',
                'is_flexible' => true,
                'flexible_minutes' => 60,
            ],
        ];

        foreach ($shifts as $shift) {
            WorkShift::firstOrCreate(['name' => $shift['name']], $shift);
        }
    }
}
