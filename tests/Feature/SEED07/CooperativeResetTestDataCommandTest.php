<?php

declare(strict_types=1);

namespace Tests\Feature\SEED07;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\MemberStoreAccount;
use App\Models\Organization;
use App\Models\PosTransaction;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Cooperative\CooperativeTestDataResetService;
use Database\Seeders\CooperativeEdgeCaseFixtureSeeder;
use Database\Seeders\CooperativeFinancialFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Tests\TestCase;

class CooperativeResetTestDataCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario A: Command Registered.
     */
    public function test_scenario_a_command_is_registered_and_available(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('cooperative:reset-test-data', $commands);
    }

    /**
     * Scenario B: Environment Guard (rejected in production, staging, qa, dev).
     */
    public function test_scenario_b_environment_guard_rejects_unsafe_environments(): void
    {
        foreach (['production', 'staging', 'qa', 'development'] as $env) {
            $this->app['env'] = $env;
            config(['app.env' => $env]);

            $exitCode = Artisan::call('cooperative:reset-test-data');
            $output = Artisan::output();

            $this->assertSame(SymfonyCommand::FAILURE, $exitCode, "Expected failure in environment {$env}");
            $this->assertStringContainsString('SEED-07 reset tooling is unavailable in this environment', $output);
            $this->assertSame(0, User::query()->count());
            $this->assertSame(0, CooperativeMember::query()->count());
        }
    }

    /**
     * Scenario C: Local Baseline Reset Allowed.
     */
    public function test_scenario_c_local_baseline_reset_is_allowed(): void
    {
        $this->app['env'] = 'local';
        config(['app.env' => 'local']);

        $exitCode = Artisan::call('cooperative:reset-test-data');
        $output = Artisan::output();

        $this->assertSame(SymfonyCommand::SUCCESS, $exitCode);
        $this->assertStringContainsString('SEED-07 reset complete', $output);
        $this->assertSame(12, User::query()->where('email', 'like', 'seed.%@kojaya.test')->count());
        $this->assertSame(7, CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-%')->count());
    }

    /**
     * Scenario D: --with-edge-cases Local Rejected.
     */
    public function test_scenario_d_with_edge_cases_rejected_in_local_environment(): void
    {
        $this->app['env'] = 'local';
        config(['app.env' => 'local']);

        // Create a sentinel user before attempting illegal command
        $sentinel = User::factory()->create([
            'email' => 'sentinel.qa@example.test',
            'name' => 'Sentinel User',
        ]);

        $exitCode = Artisan::call('cooperative:reset-test-data', ['--with-edge-cases' => true]);
        $output = Artisan::output();

        $this->assertSame(SymfonyCommand::FAILURE, $exitCode);
        $this->assertStringContainsString('The --with-edge-cases option is only allowed in testing and playwright environments', $output);

        // Sentinel must remain completely untouched
        $this->assertDatabaseHas('users', ['id' => $sentinel->id, 'email' => 'sentinel.qa@example.test']);
        $this->assertSame(0, CooperativeMember::query()->count());
    }

    /**
     * Scenario E: Dry Run makes zero database changes.
     */
    public function test_scenario_e_dry_run_makes_zero_database_modifications(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $initialUserCount = User::query()->count();
        $initialMemberCount = CooperativeMember::query()->count();
        $initialPaymentCount = CooperativePayment::query()->count();

        $exitCode = Artisan::call('cooperative:reset-test-data', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertSame(SymfonyCommand::SUCCESS, $exitCode);
        $this->assertStringContainsString('SEED-07 — Deterministic Reset / Reseed Tooling (DRY RUN)', $output);
        $this->assertStringContainsString('No changes were made', $output);

        $this->assertSame($initialUserCount, User::query()->count());
        $this->assertSame($initialMemberCount, CooperativeMember::query()->count());
        $this->assertSame($initialPaymentCount, CooperativePayment::query()->count());
    }

    /**
     * Scenario F: Canonical Baseline after normal reset.
     */
    public function test_scenario_f_canonical_baseline_established(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        $exitCode = Artisan::call('cooperative:reset-test-data');
        $this->assertSame(SymfonyCommand::SUCCESS, $exitCode);

        // 12 canonical users
        $canonicalUsers = User::query()->where('email', 'like', 'seed.%@kojaya.test')->get();
        $this->assertCount(12, $canonicalUsers);

        // 7 canonical members
        $canonicalMembers = CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-%')->get();
        $this->assertCount(7, $canonicalMembers);

        // P11, P14, P15 must NOT exist
        $this->assertSame(0, User::query()->where('email', 'seed.member.blocked@kojaya.test')->count());
        $this->assertSame(0, CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count());
        $this->assertSame(0, CooperativeMember::query()->whereIn('member_no', ['DEV-KOP-014', 'DEV-KOP-015'])->count());

        // Validate exact lifecycle matrix
        $p06 = CooperativeMember::query()->where('member_no', 'DEV-KOP-006')->firstOrFail();
        $this->assertSame('PENDING', $p06->status);
        $this->assertSame('PENDING', $p06->validation_status);

        $p07 = CooperativeMember::query()->where('member_no', 'DEV-KOP-007')->firstOrFail();
        $this->assertSame('PENDING', $p07->status);
        $this->assertSame('PENDING_VALIDATION', $p07->validation_status);

        $p08 = CooperativeMember::query()->where('member_no', 'DEV-KOP-008')->firstOrFail();
        $this->assertSame('INACTIVE', $p08->status);
        $this->assertSame('REVISION', $p08->validation_status);

        $p09 = CooperativeMember::query()->where('member_no', 'DEV-KOP-009')->firstOrFail();
        $this->assertSame('INACTIVE', $p09->status);
        $this->assertSame('REJECTED', $p09->validation_status);

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $this->assertSame('ACTIVE', $p10->status);
        $this->assertSame('ACTIVE', $p10->validation_status);

        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $this->assertSame('ACTIVE', $p12->status);
        $this->assertSame('ACTIVE', $p12->validation_status);

        $p13 = CooperativeMember::query()->where('member_no', 'DEV-KOP-013')->firstOrFail();
        $this->assertSame('ACTIVE', $p13->status);
        $this->assertSame('ACTIVE', $p13->validation_status);
    }

    /**
     * Scenario G: Financial Baseline verification.
     */
    public function test_scenario_g_financial_baseline_verification(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        Artisan::call('cooperative:reset-test-data');

        // P10: normal/completed financial state
        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $p10Store = MemberStoreAccount::query()->where('cooperative_member_id', $p10->id)->firstOrFail();
        $this->assertSame(150000, $p10Store->balance);
        $this->assertSame(500000, $p10Store->credit_limit);

        $p10Loan = Loan::query()->where('cooperative_member_id', $p10->id)->firstOrFail();
        $this->assertSame('SEED-LOAN-CLOSED-010-001', $p10Loan->reference_no);
        $this->assertSame('PAID_OFF', $p10Loan->status->value);

        $p10Invoices = CooperativeDuesInvoice::query()->where('cooperative_member_id', $p10->id)->get();
        $this->assertCount(2, $p10Invoices);
        $this->assertTrue($p10Invoices->every(fn ($inv) => $inv->status === 'PAID'));

        // P12: ongoing/boundary financial state
        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $p12Store = MemberStoreAccount::query()->where('cooperative_member_id', $p12->id)->firstOrFail();
        $this->assertSame(-450000, $p12Store->balance);
        $this->assertSame(500000, $p12Store->credit_limit);
        $this->assertSame(50000, $p12Store->availableCredit());

        $p12Loan = Loan::query()->where('cooperative_member_id', $p12->id)->firstOrFail();
        $this->assertSame('SEED-LOAN-ACTIVE-012-001', $p12Loan->reference_no);
        $this->assertSame('ACTIVE', $p12Loan->status->value);

        $p12Invoice = CooperativeDuesInvoice::query()->where('cooperative_member_id', $p12->id)->firstOrFail();
        $this->assertSame('UNPAID', $p12Invoice->status);

        // P13: empty financial state
        $p13 = CooperativeMember::query()->where('member_no', 'DEV-KOP-013')->firstOrFail();
        $this->assertSame(0, MemberStoreAccount::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, Loan::query()->where('cooperative_member_id', $p13->id)->count());
        $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $p13->id)->count());

        // P06-P09: strictly zero financial records
        $nonActiveIds = CooperativeMember::query()->whereIn('member_no', ['DEV-KOP-006', 'DEV-KOP-007', 'DEV-KOP-008', 'DEV-KOP-009'])->pluck('id');
        $this->assertSame(0, MemberStoreAccount::query()->whereIn('cooperative_member_id', $nonActiveIds)->count());
        $this->assertSame(0, Loan::query()->whereIn('cooperative_member_id', $nonActiveIds)->count());
        $this->assertSame(0, CooperativeDuesInvoice::query()->whereIn('cooperative_member_id', $nonActiveIds)->count());
        $this->assertSame(0, CooperativePayment::query()->whereIn('cooperative_member_id', $nonActiveIds)->count());
    }

    /**
     * Scenario H: Edge Option (--with-edge-cases under testing).
     */
    public function test_scenario_h_edge_option_seeds_p11_under_testing(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        $exitCode = Artisan::call('cooperative:reset-test-data', ['--with-edge-cases' => true]);
        $output = Artisan::output();

        $this->assertSame(SymfonyCommand::SUCCESS, $exitCode);
        $this->assertStringContainsString('P11 BLOCKED_UNKNOWN', $output);

        // Canonical users = 13 (12 baseline + P11)
        $this->assertSame(13, User::query()->where('email', 'like', 'seed.%@kojaya.test')->count());
        $this->assertSame(8, CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-%')->count());

        $p11 = CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->firstOrFail();
        $this->assertSame('INACTIVE', $p11->status);
        $this->assertSame('INACTIVE', $p11->validation_status);

        // P11 finance = 0
        $this->assertSame(0, MemberStoreAccount::query()->where('cooperative_member_id', $p11->id)->count());
        $this->assertSame(0, Loan::query()->where('cooperative_member_id', $p11->id)->count());
        $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $p11->id)->count());
    }

    /**
     * Scenario I: P11 Removed by Default Reset.
     */
    public function test_scenario_i_default_reset_removes_p11_edge_data(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        // First run edge seeder
        $this->seed(CooperativeEdgeCaseFixtureSeeder::class);
        $this->assertSame(1, CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count());

        // Run default reset (without --with-edge-cases)
        $exitCode = Artisan::call('cooperative:reset-test-data');
        $this->assertSame(SymfonyCommand::SUCCESS, $exitCode);

        // P11 must be completely removed
        $this->assertSame(0, User::query()->where('email', 'seed.member.blocked@kojaya.test')->count());
        $this->assertSame(0, CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count());

        // Canonical baseline preserved
        $this->assertSame(12, User::query()->where('email', 'like', 'seed.%@kojaya.test')->count());
        $this->assertSame(7, CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-%')->count());
    }

    /**
     * Scenario J: Idempotence (Snapshot 1 == Snapshot 2).
     */
    public function test_scenario_j_idempotence_produces_identical_business_state(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        Artisan::call('cooperative:reset-test-data');
        $snapshot1 = $this->getCanonicalSnapshot();

        Artisan::call('cooperative:reset-test-data');
        $snapshot2 = $this->getCanonicalSnapshot();

        $this->assertSame($snapshot1, $snapshot2, 'Subsequent resets must yield identical canonical business snapshots.');
    }

    /**
     * Scenario K: Canonical Dirty Repair.
     */
    public function test_scenario_k_dirty_canonical_data_is_repaired(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        Artisan::call('cooperative:reset-test-data');

        // Mutate P07 lifecycle metadata
        $p07 = CooperativeMember::query()->where('member_no', 'DEV-KOP-007')->firstOrFail();
        $p07->update(['validation_status' => 'INVALID_STATUS', 'validation_notes' => 'Corrupt note']);

        // Mutate P12 store balance
        $p12Store = MemberStoreAccount::query()->whereHas('member', fn ($q) => $q->where('member_no', 'DEV-KOP-012'))->firstOrFail();
        $p12Store->update(['balance' => -9999999]);

        // Run reset
        Artisan::call('cooperative:reset-test-data');

        // Verify repaired
        $p07Fresh = CooperativeMember::query()->where('member_no', 'DEV-KOP-007')->firstOrFail();
        $this->assertSame('PENDING_VALIDATION', $p07Fresh->validation_status);

        $p12StoreFresh = MemberStoreAccount::query()->whereHas('member', fn ($q) => $q->where('member_no', 'DEV-KOP-012'))->firstOrFail();
        $this->assertSame(-450000, $p12StoreFresh->balance);
    }

    /**
     * Scenario L: Soft-Delete Recovery.
     */
    public function test_scenario_l_soft_deleted_canonical_fixture_recovered(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        Artisan::call('cooperative:reset-test-data');

        // Soft delete P10
        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $p10->delete();
        $this->assertSoftDeleted('cooperative_members', ['id' => $p10->id]);

        // Run reset
        Artisan::call('cooperative:reset-test-data');

        // One restored canonical member, zero duplicates
        $p10Count = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->count();
        $this->assertSame(1, $p10Count);
        $this->assertDatabaseHas('cooperative_members', ['member_no' => 'DEV-KOP-010', 'deleted_at' => null]);
    }

    /**
     * Scenario M: Seed User Runtime Token Cleanup.
     */
    public function test_scenario_m_seed_user_runtime_tokens_cleaned_while_non_seed_preserved(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        Artisan::call('cooperative:reset-test-data');

        $p10User = User::query()->where('email', 'seed.member.active@kojaya.test')->firstOrFail();
        $p10User->createToken('test-token-p10');

        $manualUser = User::factory()->create(['email' => 'manual.tester@example.test']);
        $manualUser->createToken('test-token-manual');

        $this->assertSame(1, PersonalAccessToken::query()->where('tokenable_id', $p10User->id)->count());
        $this->assertSame(1, PersonalAccessToken::query()->where('tokenable_id', $manualUser->id)->count());

        // Run reset
        Artisan::call('cooperative:reset-test-data');

        $reseededP10User = User::query()->where('email', 'seed.member.active@kojaya.test')->firstOrFail();
        $this->assertSame(0, PersonalAccessToken::query()->where('tokenable_id', $reseededP10User->id)->count());
        $this->assertSame(1, PersonalAccessToken::query()->where('tokenable_id', $manualUser->id)->count());
    }

    /**
     * Scenario N: Manual Cooperative Data Preserved.
     */
    public function test_scenario_n_manual_cooperative_data_is_preserved(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        $kop = Organization::factory()->create(['code' => 'KOP-001', 'name' => 'Koperasi']);

        $manualUser = User::factory()->create([
            'email' => 'manual.member@example.test',
            'name' => 'Manual Member User',
            'organization_id' => $kop->id,
        ]);

        $manualMember = CooperativeMember::factory()->create([
            'organization_id' => $kop->id,
            'user_id' => $manualUser->id,
            'member_no' => 'MANUAL-KOP-001',
            'no_anggota' => 'MANUAL-KOP-001',
            'name' => 'Manual QA Member',
            'email' => 'manual.member@example.test',
        ]);

        Artisan::call('cooperative:reset-test-data');

        $this->assertDatabaseHas('users', ['id' => $manualUser->id, 'email' => 'manual.member@example.test']);
        $this->assertDatabaseHas('cooperative_members', ['id' => $manualMember->id, 'member_no' => 'MANUAL-KOP-001']);
    }

    /**
     * Scenario O: Unrelated ERP Data Preserved.
     */
    public function test_scenario_o_unrelated_erp_data_is_preserved(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        $employee = Employee::factory()->create([
            'employee_code' => 'EMP-SENTINEL-001',
            'first_name' => 'Sentinel',
            'last_name' => 'ERP',
        ]);

        Artisan::call('cooperative:reset-test-data');

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'employee_code' => 'EMP-SENTINEL-001']);
    }

    /**
     * Scenario P: Reference Values Preserved (custom operator configuration).
     */
    public function test_scenario_p_operator_modified_reference_values_preserved(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        // Seed references first
        Artisan::call('cooperative:reset-test-data');

        // Operator customizes WAJIB contribution amount
        $wajib = CooperativeContributionType::query()->where('code', 'WAJIB')->firstOrFail();
        $wajib->update(['default_amount' => 123456]);

        // Operator customizes Emergency loan interest rate
        $emergency = LoanType::query()->where('code', 'emergency')->firstOrFail();
        $emergency->update(['interest_rate' => 3.75]);

        // Run reset
        Artisan::call('cooperative:reset-test-data');

        $wajibFresh = CooperativeContributionType::query()->where('code', 'WAJIB')->firstOrFail();
        $this->assertSame(123456.0, (float) $wajibFresh->default_amount);

        $emergencyFresh = LoanType::query()->where('code', 'emergency')->firstOrFail();
        $this->assertSame(3.75, (float) $emergencyFresh->interest_rate);
    }

    /**
     * Scenario Q: Organization Integrity.
     */
    public function test_scenario_q_organization_topology_and_isolation_intact(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        Artisan::call('cooperative:reset-test-data');

        $kop = Organization::query()->where('code', 'KOP-001')->first();
        $this->assertNotNull($kop);

        $kbu = Organization::query()->where('code', 'KBU-001')->first();
        $this->assertNotNull($kbu);
        $this->assertSame(0, CooperativeMember::query()->where('organization_id', $kbu->id)->count());

        $iso = Organization::query()->where('code', 'ISO-999')->first();
        $this->assertNotNull($iso);
        $this->assertSame(0, CooperativeMember::query()->where('organization_id', $iso->id)->count());
    }

    /**
     * Scenario R: Legacy Demo Cleanup.
     */
    public function test_scenario_r_legacy_cooperative_demo_fixtures_cleaned(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        $kop = Organization::factory()->create(['code' => 'KOP-001']);

        // Create legacy demo members
        CooperativeMember::factory()->create([
            'organization_id' => $kop->id,
            'member_no' => 'DEMO-KOP-001',
            'no_anggota' => 'DEMO-KOP-001',
            'email' => 'demo.anggota1@koperasijayabersama.id',
        ]);
        CooperativeMember::factory()->create([
            'organization_id' => $kop->id,
            'member_no' => 'DEMO-ANG-001',
            'no_anggota' => 'DEMO-ANG-001',
            'email' => 'demo.anggota2@koperasijayabersama.id',
        ]);

        $this->assertSame(2, CooperativeMember::query()->where(function ($q) {
            $q->where('member_no', 'like', 'DEMO-KOP-%')->orWhere('member_no', 'like', 'DEMO-ANG-%');
        })->count());

        // Run reset
        Artisan::call('cooperative:reset-test-data');

        // All legacy demo cooperative fixtures removed
        $this->assertSame(0, CooperativeMember::query()->where(function ($q) {
            $q->where('member_no', 'like', 'DEMO-KOP-%')->orWhere('member_no', 'like', 'DEMO-ANG-%');
        })->count());
    }

    /**
     * Scenario S: UI Audit Isolation (AUD-* and ui.*@kojaya.test remain untouched).
     */
    public function test_scenario_s_ui_audit_fixtures_remain_isolated_and_untouched(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        $kop = Organization::factory()->create(['code' => 'KOP-001']);

        $uiUser = User::factory()->create([
            'email' => 'ui.system@kojaya.test',
            'name' => 'UI Audit User',
            'organization_id' => $kop->id,
        ]);

        $uiMember = CooperativeMember::factory()->create([
            'organization_id' => $kop->id,
            'member_no' => 'AUD-MEM-001',
            'no_anggota' => 'AUD-MEM-001',
            'name' => 'UI Audit Member',
            'user_id' => $uiUser->id,
        ]);

        Artisan::call('cooperative:reset-test-data');

        $this->assertDatabaseHas('users', ['id' => $uiUser->id, 'email' => 'ui.system@kojaya.test']);
        $this->assertDatabaseHas('cooperative_members', ['id' => $uiMember->id, 'member_no' => 'AUD-MEM-001']);
    }

    /**
     * Scenario T: Atomicity (Rollback on reseed failure).
     */
    public function test_scenario_t_atomicity_rolls_back_if_reseed_throws(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        Artisan::call('cooperative:reset-test-data');

        $preCountUsers = User::query()->count();
        $preCountMembers = CooperativeMember::query()->count();

        // Bind a failing service subclass that throws during reseed
        $failingService = new class extends CooperativeTestDataResetService
        {
            protected function executeReseed(bool $withEdgeCases): void
            {
                throw new RuntimeException('Simulated reseed failure for atomicity verification');
            }
        };

        $this->app->instance(CooperativeTestDataResetService::class, $failingService);

        $exitCode = Artisan::call('cooperative:reset-test-data');
        $this->assertSame(SymfonyCommand::FAILURE, $exitCode);

        // State must remain intact due to transaction rollback
        $this->assertSame($preCountUsers, User::query()->count());
        $this->assertSame($preCountMembers, CooperativeMember::query()->count());
    }

    /**
     * Scenario U: No Broad Deletes (Static analysis of service and command).
     */
    public function test_scenario_u_no_broad_or_unscoped_deletions(): void
    {
        $serviceFile = app_path('Services/Cooperative/CooperativeTestDataResetService.php');
        $commandFile = app_path('Console/Commands/CooperativeResetTestData.php');

        $this->assertFileExists($serviceFile);
        $this->assertFileExists($commandFile);

        $serviceContent = file_get_contents($serviceFile);
        $commandContent = file_get_contents($commandFile);
        $combined = $serviceContent."\n".$commandContent;

        $forbiddenPatterns = [
            'migrate:fresh',
            'db:wipe',
            'truncate(',
            'TRUNCATE',
            'Schema::drop',
            '->truncate()',
        ];

        foreach ($forbiddenPatterns as $pattern) {
            $this->assertStringNotContainsString(
                $pattern,
                $combined,
                "Reset tooling must never contain forbidden broad delete pattern: {$pattern}",
            );
        }
    }

    /**
     * Scenario V: Production-Safe Seeder Compatibility.
     */
    public function test_scenario_v_production_safe_seeder_compatibility(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        // Run reset
        $exitCode = Artisan::call('cooperative:reset-test-data');
        $this->assertSame(SymfonyCommand::SUCCESS, $exitCode);

        // Verify that default seeders can still run safely
        $this->assertDatabaseHas('roles', ['name' => 'Pengurus Koperasi']);
        $this->assertDatabaseHas('cooperative_contribution_types', ['code' => 'POKOK']);
        $this->assertDatabaseHas('loan_types', ['code' => 'emergency']);
    }

    /**
     * Scenario W: Complete Deterministic Normalized Snapshot.
     */
    public function test_scenario_w_complete_deterministic_snapshot_across_resets(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        Artisan::call('cooperative:reset-test-data');
        $snapshotA = $this->getCanonicalSnapshot();

        Artisan::call('cooperative:reset-test-data');
        $snapshotB = $this->getCanonicalSnapshot();

        $this->assertEqualsCanonicalizing($snapshotA, $snapshotB);
    }

    /**
     * Helper to retrieve a normalized snapshot of canonical cooperative business records.
     *
     * @return array<string, mixed>
     */
    private function getCanonicalSnapshot(): array
    {
        return [
            'users' => User::query()
                ->where('email', 'like', 'seed.%@kojaya.test')
                ->orderBy('email')
                ->get(['name', 'email'])
                ->toArray(),
            'members' => CooperativeMember::query()
                ->where('member_no', 'like', 'DEV-KOP-%')
                ->orderBy('member_no')
                ->get([
                    'member_no', 'no_anggota', 'name', 'email', 'status', 'validation_status',
                    'tanggal_aktif',
                ])
                ->toArray(),
            'social_accounts' => SocialAccount::query()
                ->where('provider_id', 'like', 'google-seed-%')
                ->orderBy('provider_id')
                ->get(['provider_name', 'provider_id'])
                ->toArray(),
            'dues_invoices' => CooperativeDuesInvoice::query()
                ->whereHas('member', fn ($q) => $q->where('member_no', 'like', 'DEV-KOP-%'))
                ->orderBy('period')
                ->get(['period', 'amount', 'paid_amount', 'status', 'due_date'])
                ->toArray(),
            'payments' => CooperativePayment::query()
                ->where('reference_no', 'like', 'SEED-PAY-%')
                ->orderBy('reference_no')
                ->get(['reference_no', 'amount', 'payment_method', 'status', 'paid_at'])
                ->toArray(),
            'receipts' => CooperativeReceipt::query()
                ->where('receipt_no', 'like', 'SEED-RC-%')
                ->orderBy('receipt_no')
                ->get(['receipt_no', 'pdf_path'])
                ->toArray(),
            'store_accounts' => MemberStoreAccount::query()
                ->whereHas('member', fn ($q) => $q->where('member_no', 'like', 'DEV-KOP-%'))
                ->get(['balance', 'credit_limit', 'status'])
                ->toArray(),
            'loans' => Loan::query()
                ->where('reference_no', 'like', 'SEED-LOAN-%')
                ->orderBy('reference_no')
                ->get(['reference_no', 'principal_amount', 'term_months', 'status', 'total_amount', 'outstanding_amount'])
                ->toArray(),
            'pos_transactions' => PosTransaction::query()
                ->where('transaction_no', 'like', 'SEED-POS-TX-%')
                ->orderBy('transaction_no')
                ->get(['transaction_no', 'client_reference', 'subtotal', 'total_amount', 'status'])
                ->toArray(),
        ];
    }
}
