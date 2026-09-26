<?php

namespace Database\Seeders;

use App\Services\Cooperative\CooperativeHeadOfficeResolver;
use App\Support\SeedSafety\SeederEnvironmentGuard;
use App\Support\SeedSafety\SeederExecutionProfile;
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
        SeederEnvironmentGuard::assertEnvironmentConsistency();
        app(CooperativeHeadOfficeResolver::class)->assertBootstrapIdentityConfigured();

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

        if (app()->environment('local') && SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture)) {
            $this->call([
                CooperativeFixtureReferenceSeeder::class,
                CooperativePersonaSeeder::class,
                CooperativeMemberLifecycleSeeder::class,
                CooperativeSeeder::class,
                AnggotaSeeder::class,
                CooperativeFinancialFixtureSeeder::class,
                DemoDataSeeder::class,
            ]);
        }
    }
}
