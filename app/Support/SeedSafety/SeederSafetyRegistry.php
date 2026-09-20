<?php

declare(strict_types=1);

namespace App\Support\SeedSafety;

use Database\Seeders\AnggotaSeeder;
use Database\Seeders\CooperativeEdgeCaseFixtureSeeder;
use Database\Seeders\CooperativeFinancialFixtureSeeder;
use Database\Seeders\CooperativeFixtureReferenceSeeder;
use Database\Seeders\CooperativeManagerRoleSeeder;
use Database\Seeders\CooperativeMemberLifecycleSeeder;
use Database\Seeders\CooperativePersonaSeeder;
use Database\Seeders\CooperativeReferenceSeeder;
use Database\Seeders\CooperativeSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\InvoiceSeeder;
use Database\Seeders\JobGradeSeeder;
use Database\Seeders\LeaveTypeSeeder;
use Database\Seeders\LoanTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SalaryComponentTypeSeeder;
use Database\Seeders\TaxRuleSeeder;
use Database\Seeders\UiAuditSeeder;
use Database\Seeders\WorkShiftSeeder;
use InvalidArgumentException;
use LogicException;

/**
 * Authoritative 1:1 classification registry of all database seeders (SEED-08).
 *
 * Every seeder in database/seeders (except DatabaseSeeder) must be classified here.
 */
final class SeederSafetyRegistry
{
    /**
     * Map of seeder classes to execution profiles.
     *
     * @var array<class-string, SeederExecutionProfile>
     */
    private const REGISTRY = [
        // Production-Safe Reference Seeders (8)
        TaxRuleSeeder::class => SeederExecutionProfile::ProductionSafe,
        RolePermissionSeeder::class => SeederExecutionProfile::ProductionSafe,
        LoanTypeSeeder::class => SeederExecutionProfile::ProductionSafe,
        JobGradeSeeder::class => SeederExecutionProfile::ProductionSafe,
        LeaveTypeSeeder::class => SeederExecutionProfile::ProductionSafe,
        SalaryComponentTypeSeeder::class => SeederExecutionProfile::ProductionSafe,
        WorkShiftSeeder::class => SeederExecutionProfile::ProductionSafe,
        CooperativeReferenceSeeder::class => SeederExecutionProfile::ProductionSafe,

        // Local Development & Test Fixtures (9)
        CooperativeFixtureReferenceSeeder::class => SeederExecutionProfile::LocalTestFixture,
        CooperativePersonaSeeder::class => SeederExecutionProfile::LocalTestFixture,
        CooperativeMemberLifecycleSeeder::class => SeederExecutionProfile::LocalTestFixture,
        CooperativeFinancialFixtureSeeder::class => SeederExecutionProfile::LocalTestFixture,
        CooperativeSeeder::class => SeederExecutionProfile::LocalTestFixture,
        AnggotaSeeder::class => SeederExecutionProfile::LocalTestFixture,
        DemoDataSeeder::class => SeederExecutionProfile::LocalTestFixture,
        InvoiceSeeder::class => SeederExecutionProfile::LocalTestFixture,
        CooperativeManagerRoleSeeder::class => SeederExecutionProfile::LocalTestFixture,

        // Test-Only Invalid Fixtures (2)
        UiAuditSeeder::class => SeederExecutionProfile::TestOnlyFixture,
        CooperativeEdgeCaseFixtureSeeder::class => SeederExecutionProfile::TestOnlyFixture,
    ];

    /**
     * Get all registered seeders with their execution profiles.
     *
     * @return array<class-string, SeederExecutionProfile>
     */
    public static function all(): array
    {
        return self::REGISTRY;
    }

    /**
     * Resolve the execution profile for a given seeder class.
     *
     * @param  class-string|string  $seederClass
     *
     * @throws InvalidArgumentException
     */
    public static function profileFor(string $seederClass): SeederExecutionProfile
    {
        $normalized = ltrim($seederClass, '\\');
        foreach (self::REGISTRY as $class => $profile) {
            if (ltrim($class, '\\') === $normalized) {
                return $profile;
            }
        }

        throw new InvalidArgumentException("Seeder class [{$seederClass}] is not registered in SeederSafetyRegistry.");
    }

    /**
     * Check whether a given seeder class is registered.
     */
    public static function isClassified(string $seederClass): bool
    {
        $normalized = ltrim($seederClass, '\\');
        foreach (array_keys(self::REGISTRY) as $class) {
            if (ltrim($class, '\\') === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all seeder class names matching a given execution profile.
     *
     * @return list<class-string>
     */
    public static function byProfile(SeederExecutionProfile $profile): array
    {
        $matches = [];
        foreach (self::REGISTRY as $class => $p) {
            if ($p === $profile) {
                $matches[] = $class;
            }
        }

        return $matches;
    }

    /**
     * Assert that every *Seeder.php file in database/seeders (except DatabaseSeeder.php)
     * is explicitly registered in this registry.
     *
     * @throws LogicException
     */
    public static function assertCompleteCoverage(?string $seedersPath = null): void
    {
        $seedersDir = $seedersPath ?? database_path('seeders');
        if (! is_dir($seedersDir)) {
            throw new LogicException("Seeders directory not found at [{$seedersDir}].");
        }

        $files = scandir($seedersDir);
        if ($files === false) {
            throw new LogicException("Unable to scan seeders directory at [{$seedersDir}].");
        }

        $seederFiles = array_values(array_filter(
            $files,
            fn (string $file): bool => str_ends_with($file, 'Seeder.php') && $file !== 'DatabaseSeeder.php',
        ));

        $registeredClasses = array_keys(self::REGISTRY);
        $registeredBasenames = array_map(fn (string $class): string => class_basename($class).'.php', $registeredClasses);

        $unregistered = array_diff($seederFiles, $registeredBasenames);
        if (! empty($unregistered)) {
            throw new LogicException('Unregistered seeders detected in database/seeders: '.implode(', ', $unregistered));
        }

        $extraRegistered = array_diff($registeredBasenames, $seederFiles);
        if (! empty($extraRegistered)) {
            throw new LogicException('Registry contains seeders that do not exist on disk: '.implode(', ', $extraRegistered));
        }
    }
}
