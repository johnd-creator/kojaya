<?php

namespace Tests\Feature;

use App\Support\SeedSafety\SeederExecutionProfile;
use App\Support\SeedSafety\SeederSafetyRegistry;
use Tests\TestCase;

class SeederSafetyStaticAnalysisTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $referenceSeeders = [
        'TaxRuleSeeder.php',
        'RolePermissionSeeder.php',
        'LoanTypeSeeder.php',
        'JobGradeSeeder.php',
        'LeaveTypeSeeder.php',
        'SalaryComponentTypeSeeder.php',
        'WorkShiftSeeder.php',
        'CooperativeReferenceSeeder.php',
    ];

    /**
     * @var list<string>
     */
    private array $guardedNonReferenceSeeders = [
        'CooperativeFixtureReferenceSeeder.php',
        'CooperativePersonaSeeder.php',
        'CooperativeMemberLifecycleSeeder.php',
        'CooperativeFinancialFixtureSeeder.php',
        'CooperativeSeeder.php',
        'AnggotaSeeder.php',
        'DemoDataSeeder.php',
        'InvoiceSeeder.php',
        'CooperativeManagerRoleSeeder.php',
        'UiAuditSeeder.php',
    ];

    /**
     * @var list<string>
     */
    private array $testOnlyInvalidFixtureSeeders = [
        'CooperativeEdgeCaseFixtureSeeder.php',
    ];

    public function test_reference_seeders_contain_no_destructive_operations(): void
    {
        $seederDir = database_path('seeders');
        $dangerousTokens = [
            'truncate(',
            'forceDelete(',
            '->delete(',
            'DB::delete(',
            'DB::statement(',
            'DB::unprepared(',
            'DROP TABLE',
            'TRUNCATE TABLE',
        ];

        foreach ($this->referenceSeeders as $fileName) {
            $filePath = $seederDir.'/'.$fileName;
            $this->assertFileExists($filePath);
            $content = file_get_contents($filePath);
            $this->assertIsString($content);

            foreach ($dangerousTokens as $token) {
                $this->assertStringNotContainsString(
                    $token,
                    $content,
                    "Reference seeder '{$fileName}' must not contain destructive token '{$token}'.",
                );
            }
        }
    }

    public function test_reference_seeders_do_not_create_users_or_passwords(): void
    {
        $seederDir = database_path('seeders');
        $credentialTokens = [
            'User::create',
            'User::updateOrCreate',
            'User::firstOrCreate',
            'Hash::make',
        ];

        foreach ($this->referenceSeeders as $fileName) {
            $filePath = $seederDir.'/'.$fileName;
            $this->assertFileExists($filePath);
            $content = file_get_contents($filePath);
            $this->assertIsString($content);

            foreach ($credentialTokens as $token) {
                $this->assertStringNotContainsString(
                    $token,
                    $content,
                    "Reference seeder '{$fileName}' must never create users or credentials with token '{$token}'.",
                );
            }
        }
    }

    public function test_operational_reference_seeders_use_first_or_create_for_business_parameters(): void
    {
        $seederDir = database_path('seeders');
        $operationalSeeders = [
            'CooperativeReferenceSeeder.php',
            'LoanTypeSeeder.php',
            'JobGradeSeeder.php',
            'LeaveTypeSeeder.php',
            'SalaryComponentTypeSeeder.php',
            'WorkShiftSeeder.php',
        ];

        foreach ($operationalSeeders as $fileName) {
            $filePath = $seederDir.'/'.$fileName;
            $this->assertFileExists($filePath);
            $content = file_get_contents($filePath);
            $this->assertIsString($content);

            $this->assertStringNotContainsString(
                'updateOrCreate(',
                $content,
                "Operational reference seeder '{$fileName}' must not use updateOrCreate to prevent overwriting operator configurations.",
            );
        }
    }

    public function test_non_reference_seeders_contain_centralized_environment_guards(): void
    {
        $seederDir = database_path('seeders');

        foreach ($this->guardedNonReferenceSeeders as $fileName) {
            $filePath = $seederDir.'/'.$fileName;
            $this->assertFileExists($filePath);
            $content = file_get_contents($filePath);
            $this->assertIsString($content);

            $hasEnvGuard = str_contains($content, 'SeederEnvironmentGuard::assertAllowed(');

            $this->assertTrue(
                $hasEnvGuard,
                "Seeder '{$fileName}' must call SeederEnvironmentGuard::assertAllowed(static::class).",
            );
        }
    }

    public function test_test_only_invalid_fixture_seeders_contain_strict_test_only_guard(): void
    {
        $seederDir = database_path('seeders');

        foreach ($this->testOnlyInvalidFixtureSeeders as $fileName) {
            $filePath = $seederDir.'/'.$fileName;
            $this->assertFileExists($filePath);
            $content = file_get_contents($filePath);
            $this->assertIsString($content);

            $hasEnvGuard = str_contains($content, 'SeederEnvironmentGuard::assertAllowed(');

            $this->assertTrue(
                $hasEnvGuard,
                "Test-only invalid fixture seeder '{$fileName}' must call SeederEnvironmentGuard::assertAllowed(static::class).",
            );

            $className = 'Database\\Seeders\\'.pathinfo($fileName, PATHINFO_FILENAME);
            $profile = SeederSafetyRegistry::profileFor($className);
            $this->assertSame(
                SeederExecutionProfile::TestOnlyFixture,
                $profile,
                "Seeder '{$fileName}' must have profile TestOnlyFixture.",
            );
        }
    }

    public function test_all_seeder_files_are_classified_and_accounted_for(): void
    {
        $seederDir = database_path('seeders');
        $files = scandir($seederDir);
        $this->assertIsArray($files);

        $foundSeeders = array_values(array_filter(
            $files,
            fn (string $file): bool => str_ends_with($file, 'Seeder.php') && $file !== 'DatabaseSeeder.php',
        ));

        $expectedSeeders = array_merge(
            $this->referenceSeeders,
            $this->guardedNonReferenceSeeders,
            $this->testOnlyInvalidFixtureSeeders,
        );
        sort($foundSeeders);
        sort($expectedSeeders);

        $this->assertSame(
            $expectedSeeders,
            $foundSeeders,
            'All seeders in database/seeders must be explicitly classified as reference or guarded demo/test seeders.',
        );

        // Also assert complete coverage via SeederSafetyRegistry
        SeederSafetyRegistry::assertCompleteCoverage();
    }

    public function test_cooperative_test_data_reset_service_does_not_maintain_duplicate_environment_whitelist(): void
    {
        $servicePath = app_path('Services/Cooperative/CooperativeTestDataResetService.php');
        $this->assertFileExists($servicePath);

        $content = file_get_contents($servicePath);
        $this->assertIsString($content);

        $this->assertStringNotContainsString(
            'ALLOWED_ENVIRONMENTS',
            $content,
            'CooperativeTestDataResetService must not define legacy ALLOWED_ENVIRONMENTS constant.',
        );

        $this->assertStringNotContainsString(
            'EDGE_ALLOWED_ENVIRONMENTS',
            $content,
            'CooperativeTestDataResetService must not define legacy EDGE_ALLOWED_ENVIRONMENTS constant.',
        );

        $this->assertStringContainsString(
            'SeederEnvironmentGuard::assertEnvironmentConsistency',
            $content,
            'CooperativeTestDataResetService must consume central guard assertEnvironmentConsistency().',
        );

        $this->assertStringContainsString(
            'SeederEnvironmentGuard::isAllowed',
            $content,
            'CooperativeTestDataResetService must consume central guard isAllowed().',
        );

        $this->assertStringContainsString(
            'SeederEnvironmentGuard::allowedEnvironmentsFor',
            $content,
            'CooperativeTestDataResetService must consume central guard allowedEnvironmentsFor().',
        );
    }
}
