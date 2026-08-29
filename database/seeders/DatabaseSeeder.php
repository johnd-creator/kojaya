<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Default execution contains ONLY production-safe, deterministic reference data.
     * Demo fixture seeders are strictly restricted to local development environments.
     */
    public function run(): void
    {
        $this->call([
            TaxRuleSeeder::class,
            RolePermissionSeeder::class,
            LoanTypeSeeder::class,
            JobGradeSeeder::class,
            LeaveTypeSeeder::class,
            SalaryComponentTypeSeeder::class,
            WorkShiftSeeder::class,
            CooperativeReferenceSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([
                CooperativeSeeder::class,
                AnggotaSeeder::class,
                DemoDataSeeder::class,
            ]);
        }
    }
}
