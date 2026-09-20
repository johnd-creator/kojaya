<?php

declare(strict_types=1);

namespace App\Support\SeedSafety;

use LogicException;

/**
 * Centralized, fail-closed runtime environment guard for all seeder operations (SEED-08).
 *
 * Enforces:
 * 1. Environment consistency: runtime app()->environment() MUST strictly match configured config('app.env').
 * 2. Fail-closed profile execution matrix:
 *    - PRODUCTION_SAFE: Allowed in production, staging, qa, development, local, testing, playwright.
 *    - LOCAL_TEST_FIXTURE: Allowed ONLY in local, testing, playwright (strictly blocked in production/staging/qa/development).
 *    - TEST_ONLY_FIXTURE: Allowed ONLY in testing, playwright (strictly blocked in local and all higher tiers).
 * 3. Exact lowercase string matching (zero tolerance for casing quirks or arbitrary unknown environments).
 */
final class SeederEnvironmentGuard
{
    /**
     * Assert that runtime environment matches configured environment.
     *
     * If there is any discrepancy, execution is immediately denied with zero mutations.
     *
     * @throws LogicException
     */
    public static function assertEnvironmentConsistency(): string
    {
        $configured = (string) config('app.env');
        $runtime = (string) app()->environment();

        if ($configured === '' || $runtime === '') {
            throw new LogicException("Empty environment detected: runtime='{$runtime}' configured='{$configured}'. Execution denied.");
        }

        if ($configured !== $runtime) {
            throw new LogicException("Seeder environment mismatch detected: runtime={$runtime} configured={$configured}. Execution denied.");
        }

        return $configured;
    }

    /**
     * Get the authoritative list of allowed environments for a given execution profile.
     *
     * @return list<string>
     */
    public static function allowedEnvironmentsFor(SeederExecutionProfile $profile): array
    {
        return match ($profile) {
            SeederExecutionProfile::ProductionSafe => [
                'production',
                'staging',
                'qa',
                'development',
                'local',
                'testing',
                'playwright',
            ],
            SeederExecutionProfile::LocalTestFixture => [
                'local',
                'testing',
                'playwright',
            ],
            SeederExecutionProfile::TestOnlyFixture => [
                'testing',
                'playwright',
            ],
        };
    }

    /**
     * Check whether an execution profile is permitted in the given (or current) environment.
     *
     * If $env is omitted, environment consistency between runtime and config is asserted first.
     */
    public static function isAllowed(SeederExecutionProfile $profile, ?string $env = null): bool
    {
        $targetEnv = $env ?? self::assertEnvironmentConsistency();

        return in_array($targetEnv, self::allowedEnvironmentsFor($profile), true);
    }

    /**
     * Assert that a seeder class is authorized to execute in the current environment.
     *
     * Throws a descriptive LogicException if the environment is disallowed.
     *
     * @param  class-string|string  $seederClass
     *
     * @throws LogicException
     */
    public static function assertAllowed(string $seederClass, ?SeederExecutionProfile $profile = null): void
    {
        $env = self::assertEnvironmentConsistency();
        $profile ??= SeederSafetyRegistry::profileFor($seederClass);

        if (! self::isAllowed($profile, $env)) {
            $shortName = class_basename($seederClass);
            $allowed = self::allowedEnvironmentsFor($profile);

            $phrase = match ($profile) {
                SeederExecutionProfile::LocalTestFixture => 'local, testing, or playwright',
                SeederExecutionProfile::TestOnlyFixture => 'testing or playwright',
                SeederExecutionProfile::ProductionSafe => implode(', ', $allowed),
            };

            throw new LogicException("{$shortName} is only available in {$phrase} environments. Current environment [{$env}] is rejected for safety.");
        }
    }
}
