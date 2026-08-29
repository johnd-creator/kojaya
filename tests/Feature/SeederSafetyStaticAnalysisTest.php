<?php

namespace Tests\Feature;

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
        'CooperativeSeeder.php',
        'AnggotaSeeder.php',
        'DemoDataSeeder.php',
        'InvoiceSeeder.php',
        'CooperativeManagerRoleSeeder.php',
        'UiAuditSeeder.php',
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

    public function test_non_reference_seeders_contain_strict_environment_guards(): void
    {
        $seederDir = database_path('seeders');

        foreach ($this->guardedNonReferenceSeeders as $fileName) {
            $filePath = $seederDir.'/'.$fileName;
            $this->assertFileExists($filePath);
            $content = file_get_contents($filePath);
            $this->assertIsString($content);

            $hasEnvGuard = str_contains($content, 'LogicException') &&
                (str_contains($content, "config('app.env')") || str_contains($content, 'app()->environment('));

            $this->assertTrue(
                $hasEnvGuard,
                "Seeder '{$fileName}' must have an explicit environment guard throwing a LogicException.",
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

        $expectedSeeders = array_merge($this->referenceSeeders, $this->guardedNonReferenceSeeders);
        sort($foundSeeders);
        sort($expectedSeeders);

        $this->assertSame(
            $expectedSeeders,
            $foundSeeders,
            'All seeders in database/seeders must be explicitly classified as reference or guarded demo/test seeders.',
        );
    }
}
