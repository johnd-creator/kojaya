<?php

declare(strict_types=1);

namespace Tests\Feature\SEED08;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Support\SeedSafety\SeederEnvironmentGuard;
use App\Support\SeedSafety\SeederExecutionProfile;
use App\Support\SeedSafety\SeederSafetyRegistry;
use Database\Seeders\CooperativeEdgeCaseFixtureSeeder;
use Database\Seeders\CooperativeFinancialFixtureSeeder;
use Database\Seeders\CooperativeFixtureReferenceSeeder;
use Database\Seeders\CooperativePersonaSeeder;
use Database\Seeders\TaxRuleSeeder;
use Database\Seeders\UiAuditSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

/**
 * Verification test suite for SEED-08: Environment Safety Guard.
 *
 * Covers Scenarios A through W:
 * - Runtime vs Config consistency (Scenarios A - E)
 * - PRODUCTION_SAFE profile matrix (Scenarios F - M)
 * - LOCAL_TEST_FIXTURE profile matrix (Scenarios N - T)
 * - TEST_ONLY_FIXTURE profile matrix (Scenarios U - W)
 * - Additional registry completeness & bypass prevention guarantees
 */
class SeederEnvironmentGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        parent::tearDown();
    }

    /**
     * Scenario A: Environment consistency passes when runtime matches configured.
     */
    public function test_scenario_a_environment_consistency_passes_when_runtime_matches_configured(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        $env = SeederEnvironmentGuard::assertEnvironmentConsistency();
        $this->assertSame('testing', $env);

        $this->app['env'] = 'local';
        config(['app.env' => 'local']);

        $env = SeederEnvironmentGuard::assertEnvironmentConsistency();
        $this->assertSame('local', $env);
    }

    /**
     * Scenario B: Environment consistency rejects runtime / configured mismatch under staging.
     */
    public function test_scenario_b_environment_consistency_rejects_staging_mismatch(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'staging']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Seeder environment mismatch detected: runtime=testing configured=staging. Execution denied.');

        SeederEnvironmentGuard::assertEnvironmentConsistency();
    }

    /**
     * Scenario C: Environment consistency rejects runtime / configured mismatch under production.
     */
    public function test_scenario_c_environment_consistency_rejects_production_mismatch(): void
    {
        $this->app['env'] = 'local';
        config(['app.env' => 'production']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Seeder environment mismatch detected: runtime=local configured=production. Execution denied.');

        SeederEnvironmentGuard::assertEnvironmentConsistency();
    }

    /**
     * Scenario D: Environment consistency rejects empty runtime or configured environment strings.
     */
    public function test_scenario_d_environment_consistency_rejects_empty_environments(): void
    {
        $this->app['env'] = '';
        config(['app.env' => '']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Empty environment detected: runtime='' configured=''. Execution denied.");

        SeederEnvironmentGuard::assertEnvironmentConsistency();
    }

    /**
     * Scenario E: Environment guard enforces strict exact lowercase matching.
     */
    public function test_scenario_e_environment_guard_enforces_strict_exact_lowercase_matching(): void
    {
        $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture, 'LOCAL'));
        $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture, 'Local'));
        $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::ProductionSafe, 'PRODUCTION'));
        $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::TestOnlyFixture, 'TESTING'));

        $this->app['env'] = 'LOCAL';
        config(['app.env' => 'LOCAL']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('CooperativePersonaSeeder is only available in local, testing, or playwright environments. Current environment [LOCAL] is rejected for safety.');

        SeederEnvironmentGuard::assertAllowed(CooperativePersonaSeeder::class);
    }

    /**
     * Scenario F: ProductionSafe profile is allowed in production.
     */
    public function test_scenario_f_production_safe_profile_allowed_in_production(): void
    {
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::ProductionSafe, 'production'));

        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        // Must not throw
        SeederEnvironmentGuard::assertAllowed(TaxRuleSeeder::class);
    }

    /**
     * Scenario G: ProductionSafe profile is allowed in staging.
     */
    public function test_scenario_g_production_safe_profile_allowed_in_staging(): void
    {
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::ProductionSafe, 'staging'));

        $this->app['env'] = 'staging';
        config(['app.env' => 'staging']);

        SeederEnvironmentGuard::assertAllowed(TaxRuleSeeder::class);
    }

    /**
     * Scenario H: ProductionSafe profile is allowed in qa.
     */
    public function test_scenario_h_production_safe_profile_allowed_in_qa(): void
    {
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::ProductionSafe, 'qa'));

        $this->app['env'] = 'qa';
        config(['app.env' => 'qa']);

        SeederEnvironmentGuard::assertAllowed(TaxRuleSeeder::class);
    }

    /**
     * Scenario I: ProductionSafe profile is allowed in development.
     */
    public function test_scenario_i_production_safe_profile_allowed_in_development(): void
    {
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::ProductionSafe, 'development'));

        $this->app['env'] = 'development';
        config(['app.env' => 'development']);

        SeederEnvironmentGuard::assertAllowed(TaxRuleSeeder::class);
    }

    /**
     * Scenario J: ProductionSafe profile is allowed in local.
     */
    public function test_scenario_j_production_safe_profile_allowed_in_local(): void
    {
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::ProductionSafe, 'local'));

        $this->app['env'] = 'local';
        config(['app.env' => 'local']);

        SeederEnvironmentGuard::assertAllowed(TaxRuleSeeder::class);
    }

    /**
     * Scenario K: ProductionSafe profile is allowed in testing.
     */
    public function test_scenario_k_production_safe_profile_allowed_in_testing(): void
    {
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::ProductionSafe, 'testing'));

        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        SeederEnvironmentGuard::assertAllowed(TaxRuleSeeder::class);
    }

    /**
     * Scenario L: ProductionSafe profile is allowed in playwright.
     */
    public function test_scenario_l_production_safe_profile_allowed_in_playwright(): void
    {
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::ProductionSafe, 'playwright'));

        $this->app['env'] = 'playwright';
        config(['app.env' => 'playwright']);

        SeederEnvironmentGuard::assertAllowed(TaxRuleSeeder::class);
    }

    /**
     * Scenario M: ProductionSafe profile is denied in unknown or arbitrary environments.
     */
    public function test_scenario_m_production_safe_profile_denied_in_unknown_environments(): void
    {
        $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::ProductionSafe, 'preview'));
        $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::ProductionSafe, 'sandbox'));
        $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::ProductionSafe, 'custom'));

        $this->app['env'] = 'sandbox';
        config(['app.env' => 'sandbox']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('TaxRuleSeeder is only available in production, staging, qa, development, local, testing, playwright environments. Current environment [sandbox] is rejected for safety.');

        SeederEnvironmentGuard::assertAllowed(TaxRuleSeeder::class);
    }

    /**
     * Scenario N: LocalTestFixture profile is allowed in local.
     */
    public function test_scenario_n_local_test_fixture_profile_allowed_in_local(): void
    {
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture, 'local'));

        $this->app['env'] = 'local';
        config(['app.env' => 'local']);

        SeederEnvironmentGuard::assertAllowed(CooperativePersonaSeeder::class);
    }

    /**
     * Scenario O: LocalTestFixture profile is allowed in testing.
     */
    public function test_scenario_o_local_test_fixture_profile_allowed_in_testing(): void
    {
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture, 'testing'));

        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        SeederEnvironmentGuard::assertAllowed(CooperativePersonaSeeder::class);
    }

    /**
     * Scenario P: LocalTestFixture profile is allowed in playwright.
     */
    public function test_scenario_p_local_test_fixture_profile_allowed_in_playwright(): void
    {
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture, 'playwright'));

        $this->app['env'] = 'playwright';
        config(['app.env' => 'playwright']);

        SeederEnvironmentGuard::assertAllowed(CooperativePersonaSeeder::class);
    }

    /**
     * Scenario Q: LocalTestFixture profile is strictly denied in production with zero database mutations.
     */
    public function test_scenario_q_local_test_fixture_profile_denied_in_production(): void
    {
        $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture, 'production'));

        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $thrown = false;
        try {
            (new CooperativePersonaSeeder)->run();
        } catch (LogicException $e) {
            $thrown = true;
            $this->assertStringContainsString('CooperativePersonaSeeder is only available in local, testing, or playwright environments', $e->getMessage());
        }

        $this->assertTrue($thrown, 'Expected CooperativePersonaSeeder to throw LogicException in production.');
        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, CooperativeMember::query()->count());
    }

    /**
     * Scenario R: LocalTestFixture profile is strictly denied in staging with zero database mutations.
     */
    public function test_scenario_r_local_test_fixture_profile_denied_in_staging(): void
    {
        $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture, 'staging'));

        $this->app['env'] = 'staging';
        config(['app.env' => 'staging']);

        $thrown = false;
        try {
            (new CooperativeFinancialFixtureSeeder)->run();
        } catch (LogicException $e) {
            $thrown = true;
            $this->assertStringContainsString('CooperativeFinancialFixtureSeeder is only available in local, testing, or playwright environments', $e->getMessage());
        }

        $this->assertTrue($thrown, 'Expected CooperativeFinancialFixtureSeeder to throw LogicException in staging.');
        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, CooperativeMember::query()->count());
    }

    /**
     * Scenario S: LocalTestFixture profile is strictly denied in qa and development.
     */
    public function test_scenario_s_local_test_fixture_profile_denied_in_qa_and_development(): void
    {
        foreach (['qa', 'development'] as $env) {
            $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture, $env));

            $this->app['env'] = $env;
            config(['app.env' => $env]);

            $thrown = false;
            try {
                (new CooperativeFixtureReferenceSeeder)->run();
            } catch (LogicException $e) {
                $thrown = true;
                $this->assertStringContainsString('CooperativeFixtureReferenceSeeder is only available in local, testing, or playwright environments', $e->getMessage());
            }

            $this->assertTrue($thrown, "Expected CooperativeFixtureReferenceSeeder to throw LogicException in {$env}.");
        }
    }

    /**
     * Scenario T: LocalTestFixture profile is strictly denied in unknown environments.
     */
    public function test_scenario_t_local_test_fixture_profile_denied_in_unknown_environments(): void
    {
        foreach (['sandbox', 'preview', 'ci-preview'] as $env) {
            $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture, $env));

            $this->app['env'] = $env;
            config(['app.env' => $env]);

            $thrown = false;
            try {
                SeederEnvironmentGuard::assertAllowed(CooperativePersonaSeeder::class);
            } catch (LogicException $e) {
                $thrown = true;
                $this->assertStringContainsString('CooperativePersonaSeeder is only available in local, testing, or playwright environments', $e->getMessage());
            }

            $this->assertTrue($thrown, "Expected rejection in unknown env {$env}.");
        }
    }

    /**
     * Scenario U: TestOnlyFixture profile is allowed in testing and playwright.
     */
    public function test_scenario_u_test_only_fixture_profile_allowed_in_testing_and_playwright(): void
    {
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::TestOnlyFixture, 'testing'));
        $this->assertTrue(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::TestOnlyFixture, 'playwright'));

        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        SeederEnvironmentGuard::assertAllowed(CooperativeEdgeCaseFixtureSeeder::class);
        SeederEnvironmentGuard::assertAllowed(UiAuditSeeder::class);

        $this->app['env'] = 'playwright';
        config(['app.env' => 'playwright']);

        SeederEnvironmentGuard::assertAllowed(CooperativeEdgeCaseFixtureSeeder::class);
        SeederEnvironmentGuard::assertAllowed(UiAuditSeeder::class);
    }

    /**
     * Scenario V: TestOnlyFixture profile is strictly denied in local environment.
     */
    public function test_scenario_v_test_only_fixture_profile_denied_in_local_environment(): void
    {
        $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::TestOnlyFixture, 'local'));

        $this->app['env'] = 'local';
        config(['app.env' => 'local']);

        $thrownEdge = false;
        try {
            (new CooperativeEdgeCaseFixtureSeeder)->run();
        } catch (LogicException $e) {
            $thrownEdge = true;
            $this->assertStringContainsString('CooperativeEdgeCaseFixtureSeeder is only available in testing or playwright environments', $e->getMessage());
        }
        $this->assertTrue($thrownEdge, 'Expected CooperativeEdgeCaseFixtureSeeder to throw in local.');

        $thrownUi = false;
        try {
            (new UiAuditSeeder)->run();
        } catch (LogicException $e) {
            $thrownUi = true;
            $this->assertStringContainsString('UiAuditSeeder is only available in testing or playwright environments', $e->getMessage());
        }
        $this->assertTrue($thrownUi, 'Expected UiAuditSeeder to throw in local.');
    }

    /**
     * Scenario W: TestOnlyFixture profile is strictly denied in production, staging, qa, development.
     */
    public function test_scenario_w_test_only_fixture_profile_denied_in_production_staging_qa_dev(): void
    {
        foreach (['production', 'staging', 'qa', 'development', 'unknown-env'] as $env) {
            $this->assertFalse(SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::TestOnlyFixture, $env));

            $this->app['env'] = $env;
            config(['app.env' => $env]);

            $thrown = false;
            try {
                SeederEnvironmentGuard::assertAllowed(CooperativeEdgeCaseFixtureSeeder::class);
            } catch (LogicException $e) {
                $thrown = true;
                $this->assertStringContainsString('CooperativeEdgeCaseFixtureSeeder is only available in testing or playwright environments', $e->getMessage());
            }

            $this->assertTrue($thrown, "Expected TestOnlyFixture rejection in {$env}.");
        }
    }

    /**
     * Extra Guarantee 1: Artisan --force flag cannot bypass SeederEnvironmentGuard.
     */
    public function test_artisan_db_seed_force_flag_cannot_bypass_environment_guard(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $thrown = false;
        try {
            Artisan::call('db:seed', [
                '--class' => CooperativePersonaSeeder::class,
                '--force' => true,
            ]);
        } catch (LogicException $e) {
            $thrown = true;
            $this->assertStringContainsString('CooperativePersonaSeeder is only available in local, testing, or playwright environments', $e->getMessage());
        }

        $this->assertTrue($thrown, 'Artisan db:seed --force must not bypass SeederEnvironmentGuard.');
        $this->assertSame(0, User::query()->count());
    }

    /**
     * Extra Guarantee 2: Unregistered seeder throws InvalidArgumentException.
     */
    public function test_unregistered_seeder_throws_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Seeder class [NonExistentSeeder] is not registered in SeederSafetyRegistry.');

        SeederSafetyRegistry::profileFor('NonExistentSeeder');
    }

    /**
     * Extra Guarantee 3: SeederSafetyRegistry has complete 1:1 coverage.
     */
    public function test_seeder_safety_registry_assert_complete_coverage_succeeds(): void
    {
        SeederSafetyRegistry::assertCompleteCoverage();

        $all = SeederSafetyRegistry::all();
        $this->assertCount(19, $all);

        $this->assertCount(8, SeederSafetyRegistry::byProfile(SeederExecutionProfile::ProductionSafe));
        $this->assertCount(9, SeederSafetyRegistry::byProfile(SeederExecutionProfile::LocalTestFixture));
        $this->assertCount(2, SeederSafetyRegistry::byProfile(SeederExecutionProfile::TestOnlyFixture));
    }
}
