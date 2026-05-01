<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Cuti Tahunan',
                'default_days_allowance' => 12,
                'requires_attachment' => false,
                'is_paid' => true,
            ],
            [
                'name' => 'Sakit',
                'default_days_allowance' => 0,
                'requires_attachment' => true,
                'is_paid' => true,
            ],
            [
                'name' => 'Cuti Melahirkan',
                'default_days_allowance' => 90, // 3 months
                'requires_attachment' => true,
                'is_paid' => true,
            ],
            [
                'name' => 'Cuti Menikah',
                'default_days_allowance' => 3,
                'requires_attachment' => true,
                'is_paid' => true,
            ],
            [
                'name' => 'Cuti Alasan Penting',
                'default_days_allowance' => 3,
                'requires_attachment' => false,
                'is_paid' => true,
            ],
            [
                'name' => 'Unpaid Leave (Izin Di Luar Tanggungan)',
                'default_days_allowance' => 0,
                'requires_attachment' => false,
                'is_paid' => false,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
