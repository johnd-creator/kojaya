<?php

declare(strict_types=1);

namespace Tests\Feature\SEED06;

use App\Enums\ApiErrorCode;
use App\Enums\Cooperative\MemberLifecycleExperience;
use App\Enums\LoanStatus;
use App\Enums\MemberStoreAccountStatus;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\PosTransaction;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\Sso\MemberGoogleSsoMatchingService;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\Cooperative\MemberImportValidator;
use App\Services\Cooperative\MemberStoreCheckoutService;
use App\Services\Security\PiiCryptoService;
use Database\Seeders\CooperativeEdgeCaseFixtureSeeder;
use Database\Seeders\CooperativeFinancialFixtureSeeder;
use Database\Seeders\CooperativeMemberLifecycleSeeder;
use Database\Seeders\CooperativePersonaSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

/**
 * SEED-06: Negative & Edge-Case Dataset Feature Test Suite.
 *
 * Covers Scenarios A through W for deliberately invalid, boundary, conflict,
 * and fail-closed security scenarios.
 */
class CooperativeEdgeCaseFixtureSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario A: Strict Fail-Closed Environment Guard.
     * Rejects local, development, qa, staging, and production with LogicException.
     * Guarantees zero mutation on rejection. Accepts testing and playwright.
     */
    public function test_scenario_a_environment_guard(): void
    {
        $rejectedEnvironments = ['local', 'development', 'qa', 'staging', 'production'];

        foreach ($rejectedEnvironments as $env) {
            config(['app.env' => $env]);

            $thrown = false;
            try {
                (new CooperativeEdgeCaseFixtureSeeder)->run();
            } catch (LogicException $e) {
                $thrown = true;
                $this->assertStringContainsString('CooperativeEdgeCaseFixtureSeeder is only available in testing or playwright environments', $e->getMessage());
            }

            $this->assertTrue($thrown, "Expected LogicException in environment [{$env}].");
            $this->assertSame(0, User::query()->where('email', 'seed.member.blocked@kojaya.test')->count());
            $this->assertSame(0, CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count());
        }

        // Accepts testing
        config(['app.env' => 'testing']);
        (new CooperativeEdgeCaseFixtureSeeder)->run();
        $this->assertSame(1, User::query()->where('email', 'seed.member.blocked@kojaya.test')->count());

        // Accepts playwright
        config(['app.env' => 'playwright']);
        (new CooperativeEdgeCaseFixtureSeeder)->run();
        $this->assertSame(1, User::query()->where('email', 'seed.member.blocked@kojaya.test')->count());

        // Reset
        config(['app.env' => 'testing']);
    }

    /**
     * Scenario B: Direct P11 Bootstrap under testing.
     */
    public function test_scenario_b_direct_p11_bootstrap(): void
    {
        $this->seed(CooperativeEdgeCaseFixtureSeeder::class);

        $user = User::query()->where('email', 'seed.member.blocked@kojaya.test')->firstOrFail();
        $member = CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->firstOrFail();

        $this->assertSame('Seed Member Blocked', $user->name);
        $this->assertTrue($user->hasRole('Anggota'));
        $this->assertSame('KOP-001', $user->organization?->code);

        $this->assertSame($user->id, $member->user_id);
        $this->assertSame('DEV-KOP-011', $member->no_anggota);
        $this->assertSame('seed.member.blocked@kojaya.test', $member->email);
        $this->assertSame('KOP-001', $member->organization?->code);
    }

    /**
     * Scenario C: P11 Lifecycle Experience resolves to BLOCKED_UNKNOWN.
     */
    public function test_scenario_c_p11_lifecycle_experience(): void
    {
        $this->seed(CooperativeEdgeCaseFixtureSeeder::class);

        $member = CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->firstOrFail();

        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $member->status);
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $member->validation_status);

        $experience = MemberLifecycleExperience::fromMember($member);
        $this->assertSame(MemberLifecycleExperience::BlockedUnknown, $experience);
        $this->assertSame('blocked', $experience->reviewState());
        $this->assertTrue($experience->isBlocked());
        $this->assertFalse($experience->isActive());
    }

    /**
     * Scenario D: P11 Web Fail-Closed.
     */
    public function test_scenario_d_p11_web_fail_closed(): void
    {
        $this->seed(CooperativeEdgeCaseFixtureSeeder::class);

        $user = User::query()->where('email', 'seed.member.blocked@kojaya.test')->firstOrFail();

        // 1. /dashboard aborts 403
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(403);

        // 2. /member/onboarding aborts 403
        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertStatus(403);
    }

    /**
     * Scenario E: P11 API Fail-Closed.
     */
    public function test_scenario_e_p11_api_fail_closed(): void
    {
        $this->seed(CooperativeEdgeCaseFixtureSeeder::class);

        $user = User::query()->where('email', 'seed.member.blocked@kojaya.test')->firstOrFail();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'seed.member.blocked@kojaya.test',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'data' => [
                    'lifecycle_experience' => MemberLifecycleExperience::BlockedUnknown->value,
                ],
            ]);

        // Zero Sanctum tokens issued
        $this->assertSame(0, $user->tokens()->count());
    }

    /**
     * Scenario F: P11 Idempotence.
     */
    public function test_scenario_f_p11_idempotence(): void
    {
        $this->seed(CooperativeEdgeCaseFixtureSeeder::class);
        $this->seed(CooperativeEdgeCaseFixtureSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'seed.member.blocked@kojaya.test')->count());
        $this->assertSame(1, CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count());
    }

    /**
     * Scenario G: P11 Zero Financial Records.
     */
    public function test_scenario_g_p11_zero_finance(): void
    {
        $this->seed(CooperativeEdgeCaseFixtureSeeder::class);

        $member = CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->firstOrFail();

        $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $member->id)->count());
        $this->assertSame(0, CooperativePayment::query()->where('cooperative_member_id', $member->id)->count());
        $this->assertSame(0, CooperativeReceipt::query()->where('cooperative_member_id', $member->id)->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_member_id', $member->id)->count());
        $this->assertSame(0, MemberStoreAccount::query()->where('cooperative_member_id', $member->id)->count());
        $this->assertSame(0, Loan::query()->where('cooperative_member_id', $member->id)->count());
        $this->assertSame(0, LoanPayment::query()->where('cooperative_member_id', $member->id)->count());
        $this->assertSame(0, PosTransaction::query()->where('cooperative_member_id', $member->id)->count());
    }

    /**
     * Scenario H: Default DatabaseSeeder Excludes Edge Data.
     */
    public function test_scenario_h_default_database_seeder_excludes_edge_data(): void
    {
        $this->app['env'] = 'local';
        config(['app.env' => 'local']);

        try {
            $this->seed(DatabaseSeeder::class);

            // P11 absent
            $this->assertSame(0, User::query()->where('email', 'seed.member.blocked@kojaya.test')->count());
            $this->assertSame(0, CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count());

            // Zero BLOCKED_UNKNOWN members
            $allMembers = CooperativeMember::all();
            foreach ($allMembers as $m) {
                $this->assertNotSame(
                    MemberLifecycleExperience::BlockedUnknown,
                    MemberLifecycleExperience::fromMember($m),
                    "Member [{$m->member_no}] unexpectedly resolved to BLOCKED_UNKNOWN in default local seed."
                );
            }

            // Zero DEFAULTED or WRITTEN_OFF loans
            $this->assertSame(0, Loan::query()->whereIn('status', [LoanStatus::Defaulted, LoanStatus::WrittenOff])->count());

            // Zero over-limit store accounts
            $overLimitCount = MemberStoreAccount::query()
                ->whereRaw('balance < -credit_limit')
                ->count();
            $this->assertSame(0, $overLimitCount);
        } finally {
            $this->app['env'] = 'testing';
            config(['app.env' => 'testing']);
        }
    }

    /**
     * Scenario I: Duplicate Email Batch Detection.
     */
    public function test_scenario_i_duplicate_email_batch(): void
    {
        $validator = new MemberImportValidator(app(PiiCryptoService::class));
        $csvPath = base_path('tests/Fixtures/SEED-06/member-import-duplicate-batch.csv');

        $result = $validator->validateFile($csvPath, ['import_date' => '2026-06-01']);

        $this->assertFalse($result->valid);

        $duplicateEmailErrors = array_filter(
            $result->errors,
            fn ($err) => $err->code === MemberImportValidator::CODE_DUPLICATE_EMAIL_BATCH
        );

        $this->assertNotEmpty($duplicateEmailErrors);
        $this->assertSame(0, CooperativeMember::query()->where('email', 'budi.duplicate@example.test')->count());
    }

    /**
     * Scenario J: Duplicate NIK Batch Detection.
     */
    public function test_scenario_j_duplicate_nik_batch(): void
    {
        $validator = new MemberImportValidator(app(PiiCryptoService::class));
        $csvPath = base_path('tests/Fixtures/SEED-06/member-import-duplicate-batch.csv');

        $result = $validator->validateFile($csvPath, ['import_date' => '2026-06-01']);

        $this->assertFalse($result->valid);

        $duplicateNikErrors = array_filter(
            $result->errors,
            fn ($err) => $err->code === MemberImportValidator::CODE_DUPLICATE_IDENTITY_NUMBER_BATCH
        );

        $this->assertNotEmpty($duplicateNikErrors);
        $this->assertSame(0, CooperativeMember::query()->where('email', 'dewi.l@example.test')->count());
    }

    /**
     * Scenario K: Duplicate Member Number Batch Detection.
     */
    public function test_scenario_k_duplicate_member_number_batch(): void
    {
        $validator = new MemberImportValidator(app(PiiCryptoService::class));
        $csvPath = base_path('tests/Fixtures/SEED-06/member-import-duplicate-batch.csv');

        $result = $validator->validateFile($csvPath, ['import_date' => '2026-06-01']);

        $this->assertFalse($result->valid);

        $duplicateMemberNoErrors = array_filter(
            $result->errors,
            fn ($err) => $err->code === MemberImportValidator::CODE_DUPLICATE_MEMBER_NUMBER_BATCH
        );

        $this->assertNotEmpty($duplicateMemberNoErrors);
        $this->assertSame(0, CooperativeMember::query()->where('email', 'fajar.n@example.test')->count());
    }

    /**
     * Scenario L: Existing Database Conflicts.
     */
    public function test_scenario_l_existing_database_conflicts(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $validator = new MemberImportValidator(app(PiiCryptoService::class));
        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();

        // Row conflicting with canonical P10 email, NIK, and member_no
        $conflictRows = [
            [
                'member_number' => 'DEV-KOP-010',
                'full_name' => 'Conflict Attempter',
                'email' => 'seed.member.active@kojaya.test',
                'phone_number' => '081299999999',
                'identity_number' => '3174990000000010',
                'gender' => 'L',
                'company_code' => 'IP',
                'employee_number' => null,
                'address' => 'Jl. Konflik No 1',
                'membership_type' => 'AB',
                'join_date' => '2026-06-01',
                'notes' => 'Attempting DB collision',
            ],
        ];

        $result = $validator->validateRows(
            $conflictRows,
            MemberImportValidator::CANONICAL_HEADERS,
            ['import_date' => '2026-06-01']
        );

        $this->assertFalse($result->valid);

        $errorCodes = array_map(fn ($err) => $err->code, $result->errors);
        $this->assertContains(MemberImportValidator::CODE_EMAIL_ALREADY_EXISTS, $errorCodes);
        $this->assertContains(MemberImportValidator::CODE_IDENTITY_NUMBER_ALREADY_EXISTS, $errorCodes);
        $this->assertContains(MemberImportValidator::CODE_MEMBER_NUMBER_ALREADY_EXISTS, $errorCodes);

        // Existing P10 record remains completely untouched
        $p10Fresh = $p10->fresh();
        $this->assertSame('Seed Member Active', $p10Fresh->name);
        $this->assertSame('seed.member.active@kojaya.test', $p10Fresh->email);
    }

    /**
     * Scenario M: Malformed CSV Detection.
     */
    public function test_scenario_m_malformed_csv(): void
    {
        $validator = new MemberImportValidator(app(PiiCryptoService::class));
        $csvPath = base_path('tests/Fixtures/SEED-06/member-import-malformed.csv');

        $result = $validator->validateFile($csvPath, ['import_date' => '2026-06-01']);

        $this->assertFalse($result->valid);

        $errorCodes = array_map(fn ($err) => $err->code, $result->errors);
        $this->assertContains(MemberImportValidator::CODE_MISSING_REQUIRED_FIELD, $errorCodes);
        $this->assertContains(MemberImportValidator::CODE_INVALID_CONTROLLED_VALUE, $errorCodes);

        // Zero rows persisted
        $this->assertSame(0, CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-20%')->count());
    }

    /**
     * Scenario N: Invalid Header Detection.
     */
    public function test_scenario_n_invalid_header(): void
    {
        $validator = new MemberImportValidator(app(PiiCryptoService::class));
        $csvPath = base_path('tests/Fixtures/SEED-06/member-import-invalid-header.csv');

        $result = $validator->validateFile($csvPath);

        $this->assertFalse($result->valid);
        $this->assertFalse($result->headerValid);

        $errorCodes = array_map(fn ($err) => $err->code, $result->errors);
        $this->assertContains(MemberImportValidator::CODE_INVALID_HEADER, $errorCodes);
    }

    /**
     * Scenario O: PII Redaction in Validation Result.
     */
    public function test_scenario_o_pii_redaction(): void
    {
        $validator = new MemberImportValidator(app(PiiCryptoService::class));
        $csvPath = base_path('tests/Fixtures/SEED-06/member-import-duplicate-batch.csv');

        $result = $validator->validateFile($csvPath, ['import_date' => '2026-06-01']);

        $this->assertNotEmpty($result->rows);
        foreach ($result->rows as $row) {
            $this->assertSame('[REDACTED]', $row->rawData['identity_number']);
            $this->assertStringNotContainsString('317101230190000', json_encode($row->rawData));
        }
    }

    /**
     * Scenario P: Google SSO Conflict Fails Closed.
     */
    public function test_scenario_p_google_sso_conflict_fails_closed(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $p12 = User::query()->where('email', 'seed.member.google@kojaya.test')->firstOrFail();
        $p12Social = SocialAccount::query()->where('user_id', $p12->id)->firstOrFail();

        $matchingService = app(MemberGoogleSsoMatchingService::class);

        // Another user tries to claim P12's google provider_id
        $conflictingGoogleUser = $this->createFakeSocialiteUser(
            id: (string) $p12Social->provider_id,
            email: 'attacker@example.test',
            verified: true,
        );

        $result = $matchingService->resolve($conflictingGoogleUser);

        // Resolves to existing owner P12, not the attacker
        $this->assertSame($p12->id, $result->user?->id);

        // Verify P12 SocialAccount remains completely unchanged
        $p12SocialFresh = $p12Social->fresh();
        $this->assertSame($p12->id, $p12SocialFresh->user_id);
        $this->assertSame('google-seed-sub-012', $p12SocialFresh->provider_id);

        // If attacker tries to match using P12's email with a DIFFERENT provider_id
        $attackerNewId = $this->createFakeSocialiteUser(
            id: 'unauthorized-google-sub-999',
            email: 'seed.member.google@kojaya.test',
            verified: true,
        );

        $resultConflict = $matchingService->resolve($attackerNewId);
        $this->assertFalse($resultConflict->success);
        $this->assertSame(MemberGoogleSsoMatchingService::CODE_MEMBER_USER_CONFLICT, $resultConflict->reason);

        // Zero new social accounts created
        $this->assertSame(0, SocialAccount::query()->where('provider_id', 'unauthorized-google-sub-999')->count());
    }

    /**
     * Scenario Q: Non-Active Member (P06) Active-Feature Denial.
     */
    public function test_scenario_q_non_active_member_active_feature_denial(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $p06User = User::query()->where('email', 'seed.member.waiting@kojaya.test')->firstOrFail();
        $token = $p06User->createToken('test-token', ['member:read', 'member:write'])->plainTextToken;

        // Calling active-only route
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/member/dues/invoices');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => ApiErrorCode::MemberNotActive->value,
            ]);

        // Zero financial rows created
        $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $p06User->cooperativeMember->id)->count());
    }

    /**
     * Scenario R: Store Credit Over-Limit Attempt Rejection & Atomicity.
     */
    public function test_scenario_r_store_credit_over_limit_attempt(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $p12Member = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $kasirUser = User::query()->where('email', 'seed.kasir@kojaya.test')->firstOrFail();
        $checkoutService = app(MemberStoreCheckoutService::class);

        $account = MemberStoreAccount::query()->where('cooperative_member_id', $p12Member->id)->firstOrFail();
        $this->assertEquals(-450000, (float) $account->balance);
        $this->assertEquals(500000, (float) $account->credit_limit);

        // Attempt purchase of 60,000 (projected -510,000 > limit 500,000)
        $thrown = false;
        try {
            $checkoutService->preparePurchase(
                member: $p12Member,
                amount: 60000,
                cashier: $kasirUser,
                delegateCode: null,
                purchaserName: 'Seed Member Google',
            );
        } catch (ValidationException $e) {
            $thrown = true;
            $this->assertArrayHasKey('account', $e->errors());
            $this->assertStringContainsString('melebihi limit kredit', $e->errors()['account'][0]);
        }

        $this->assertTrue($thrown, 'Expected ValidationException for over-limit purchase attempt.');

        // Balance remains exactly -450000
        $accountFresh = $account->fresh();
        $this->assertEquals(-450000, (float) $accountFresh->balance);

        // Zero new ledger entries or transactions created
        $this->assertSame(1, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count());
    }

    /**
     * Scenario S: Suspended Store Account Denial.
     */
    public function test_scenario_s_suspended_store_account(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $headOffice = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $p10Member = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $kasirUser = User::query()->where('email', 'seed.kasir@kojaya.test')->firstOrFail();

        // Create an isolated suspended store account
        $suspendedAccount = MemberStoreAccount::query()->where('cooperative_member_id', $p10Member->id)->firstOrFail();
        $suspendedAccount->status = MemberStoreAccountStatus::Suspended->value;
        $suspendedAccount->save();

        $checkoutService = app(MemberStoreCheckoutService::class);

        $thrown = false;
        try {
            $checkoutService->preparePurchase(
                member: $p10Member,
                amount: 10000,
                cashier: $kasirUser,
                delegateCode: null,
                purchaserName: 'Seed Member Active',
            );
        } catch (ValidationException $e) {
            $thrown = true;
            $this->assertArrayHasKey('account', $e->errors());
            $this->assertStringContainsString('ditangguhkan', $e->errors()['account'][0]);
        }

        $this->assertTrue($thrown, 'Expected ValidationException for suspended store account purchase.');
    }

    /**
     * Scenario T: Defaulted Loan Factory Capability.
     */
    public function test_scenario_t_defaulted_loan_factory(): void
    {
        $headOffice = Organization::factory()->create(['code' => 'TEST-ORG-001']);
        $loan = Loan::factory()->defaulted()->create(['organization_id' => $headOffice->id]);

        $this->assertSame(LoanStatus::Defaulted, $loan->status);
        $this->assertGreaterThan(0, (float) $loan->outstanding_amount);
    }

    /**
     * Scenario U: Cross-Organization Authorization Denial.
     */
    public function test_scenario_u_cross_organization_authorization(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $kopOffice = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $isoOffice = Organization::query()->where('code', 'ISO-999')->firstOrFail();

        // KOP actor without global permission
        $kasirUser = User::query()->where('email', 'seed.kasir@kojaya.test')->firstOrFail();

        // Isolated resource under ISO-999
        $isoMember = CooperativeMember::factory()->create([
            'organization_id' => $isoOffice->id,
            'member_no' => 'ISO-MEMBER-001',
        ]);

        $scopeService = app(OrganizationScopeService::class);

        // 1. assertVisible throws AuthorizationException
        $thrownAuth = false;
        try {
            $scopeService->assertVisible($kasirUser, $isoMember);
        } catch (AuthorizationException $e) {
            $thrownAuth = true;
            $this->assertStringContainsString('The resource is outside the user organization', $e->getMessage());
        }
        $this->assertTrue($thrownAuth, 'Expected AuthorizationException when accessing ISO resource with KOP actor.');

        // 2. resolveVisible throws ModelNotFoundException (404)
        $this->expectException(ModelNotFoundException::class);
        $scopeService->resolveVisible(CooperativeMember::class, $kasirUser, $isoMember->id);
    }

    /**
     * Scenario V: Baseline Still Clean After Edge Tests.
     */
    public function test_scenario_v_baseline_still_clean(): void
    {
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        // Exactly 12 valid personas
        $this->assertSame(12, User::query()->where('email', 'like', 'seed.%@kojaya.test')->count());
        $this->assertSame(0, User::query()->where('email', 'seed.member.blocked@kojaya.test')->count());

        // P14/P15 absent
        $this->assertSame(0, User::query()->whereIn('email', ['seed.employee.p14@kojaya.test', 'seed.employee.p15@kojaya.test'])->count());

        // KBU members = 0
        $kbu = Organization::query()->where('code', 'KBU-001')->firstOrFail();
        $this->assertSame(0, CooperativeMember::query()->where('organization_id', $kbu->id)->count());

        // ISO baseline members = 0
        $iso = Organization::query()->where('code', 'ISO-999')->firstOrFail();
        $this->assertSame(0, CooperativeMember::query()->where('organization_id', $iso->id)->count());

        // 0 defaulted loans in baseline
        $this->assertSame(0, Loan::query()->where('status', LoanStatus::Defaulted)->count());
        $this->assertSame(0, Loan::query()->where('status', LoanStatus::WrittenOff)->count());

        // P10 finance intact
        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $this->assertSame(2, CooperativeDuesInvoice::query()->where('cooperative_member_id', $p10->id)->count());

        // P12 finance intact
        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $this->assertSame(1, Loan::query()->where('cooperative_member_id', $p12->id)->where('status', LoanStatus::Active)->count());

        // P13 empty
        $p13 = CooperativeMember::query()->where('member_no', 'DEV-KOP-013')->firstOrFail();
        $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $p13->id)->count());

        // P06-P09 zero finance
        $nonActiveIds = CooperativeMember::query()->whereIn('member_no', ['DEV-KOP-006', 'DEV-KOP-007', 'DEV-KOP-008', 'DEV-KOP-009'])->pluck('id')->all();
        $this->assertSame(0, CooperativeDuesInvoice::query()->whereIn('cooperative_member_id', $nonActiveIds)->count());
    }

    /**
     * Scenario W: Edge Isolation Matrix.
     */
    public function test_scenario_w_edge_isolation_matrix(): void
    {
        // 1. Baseline dataset
        $this->seed(CooperativeFinancialFixtureSeeder::class);

        $this->assertSame(0, CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count(), 'Baseline must not contain P11');
        $this->assertSame(0, Loan::query()->where('status', LoanStatus::Defaulted)->count(), 'Baseline must not contain DEFAULTED loans');
        $this->assertSame(0, MemberStoreAccount::query()->whereRaw('balance < -credit_limit')->count(), 'Baseline must not contain over-limit store accounts');

        // 2. Edge capability under isolated test
        $this->seed(CooperativeEdgeCaseFixtureSeeder::class);
        $this->assertSame(1, CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count(), 'Edge seeder must contain P11');

        $defaultedLoan = Loan::factory()->defaulted()->create();
        $this->assertSame(LoanStatus::Defaulted, $defaultedLoan->status);
    }

    private function createFakeSocialiteUser(string $id, string $email, bool $verified = true, ?string $name = null): \Laravel\Socialite\Two\User
    {
        $user = new \Laravel\Socialite\Two\User;
        $user->id = $id;
        $user->nickname = null;
        $user->name = $name ?? ('Tester '.$id);
        $user->email = $email;
        $user->avatar = null;
        $user->user = [
            'sub' => $id,
            'email' => $email,
            'email_verified' => $verified,
            'name' => $name ?? ('Tester '.$id),
        ];
        $user->token = 'fake-token-test-'.md5($id);
        $user->refreshToken = null;
        $user->expiresIn = 3600;
        $user->attributes['token_type'] = 'Bearer';

        return $user;
    }
}
