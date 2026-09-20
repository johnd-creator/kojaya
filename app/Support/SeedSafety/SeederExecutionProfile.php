<?php

declare(strict_types=1);

namespace App\Support\SeedSafety;

/**
 * Execution profiles for database seeders (SEED-08).
 *
 * Classifies seeders according to their environment safety characteristics:
 * - ProductionSafe: Idempotent reference data safe to run in any valid environment.
 * - LocalTestFixture: Deterministic demo/dev personas and test fixtures (local, testing, playwright only).
 * - TestOnlyFixture: Negative, edge-case, and UI audit fixtures (testing and playwright only; strictly blocked in local).
 */
enum SeederExecutionProfile: string
{
    case ProductionSafe = 'PRODUCTION_SAFE';
    case LocalTestFixture = 'LOCAL_TEST_FIXTURE';
    case TestOnlyFixture = 'TEST_ONLY_FIXTURE';
}
