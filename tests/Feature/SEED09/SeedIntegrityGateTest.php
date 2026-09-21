<?php

declare(strict_types=1);

namespace Tests\Feature\SEED09;

use App\Enums\Cooperative\MemberLifecycleExperience;
use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Enums\MemberStoreAccountStatus;
use App\Enums\MemberStoreLedgerEffect;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\PosPayment;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\SeedSafety\SeederEnvironmentGuard;
use App\Support\SeedSafety\SeederExecutionProfile;
use App\Support\SeedSafety\SeederSafetyRegistry;
use Database\Seeders\CooperativeEdgeCaseFixtureSeeder;
use Database\Seeders\CooperativeFinancialFixtureSeeder;
use Database\Seeders\CooperativePersonaSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use LogicException;
use Tests\TestCase;

/**
 * SEED-09: Seed Integrity & Readiness Gate.
 *
 * Authoritative cross-component integration gate proving that everything delivered
 * by SEED-01 through SEED-08 is coherent, financially reconciled, deterministic,
 * secure, and safe enough to start Phase 4 functional testing.
 */
class SeedIntegrityGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);
    }

    protected function tearDown(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'testing']);

        parent::tearDown();
    }

    private function ensureCanonicalBaseline(): void
    {
        $exitCode = Artisan::call('cooperative:reset-test-data');
        $this->assertSame(0, $exitCode, 'Canonical cooperative test data reset must succeed.');
    }

    // =========================================================================
    // 1. IDENTITY GATE (Scenarios A, B, F, G, H, Z)
    // =========================================================================

    /**
     * Scenario A: Canonical Identity Count.
     *
     * Exactly 12 seed.*@kojaya.test users and 7 DEV-KOP-* members.
     */
    public function test_identity_scenario_a_canonical_identity_count(): void
    {
        $this->ensureCanonicalBaseline();

        $seedUsers = User::query()
            ->where('email', 'like', 'seed.%@kojaya.test')
            ->orderBy('email')
            ->pluck('email')
            ->all();

        $expectedUsers = [
            'seed.admin.kop@kojaya.test',
            'seed.kasir@kojaya.test',
            'seed.manajer@kojaya.test',
            'seed.member.active@kojaya.test',
            'seed.member.google@kojaya.test',
            'seed.member.no-google@kojaya.test',
            'seed.member.rejected@kojaya.test',
            'seed.member.review@kojaya.test',
            'seed.member.revision@kojaya.test',
            'seed.member.waiting@kojaya.test',
            'seed.pengurus@kojaya.test',
            'seed.system.admin@kojaya.test',
        ];

        $this->assertSame($expectedUsers, $seedUsers, 'Canonical seed users count and emails must match exactly 12.');

        $devMembers = CooperativeMember::query()
            ->where('member_no', 'like', 'DEV-KOP-%')
            ->orderBy('member_no')
            ->pluck('member_no')
            ->all();

        $expectedMembers = [
            'DEV-KOP-006',
            'DEV-KOP-007',
            'DEV-KOP-008',
            'DEV-KOP-009',
            'DEV-KOP-010',
            'DEV-KOP-012',
            'DEV-KOP-013',
        ];

        $this->assertSame($expectedMembers, $devMembers, 'Canonical DEV-KOP-* members must match exactly the 7 accepted personas.');
    }

    /**
     * Scenario B: Reserved and Invalid Personas Absent.
     * Default baseline must have P11 = 0, P14 = 0, P15 = 0, and no DEV-KBU-* members.
     */
    public function test_identity_scenario_b_reserved_and_invalid_personas_absent(): void
    {
        $this->ensureCanonicalBaseline();

        $this->assertSame(
            0,
            CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count(),
            'DEV-KOP-011 (P11) must be absent from default canonical baseline.',
        );
        $this->assertSame(
            0,
            User::query()->where('email', 'seed.member.blocked@kojaya.test')->count(),
            'seed.member.blocked@kojaya.test must be absent from default canonical baseline.',
        );

        $this->assertSame(
            0,
            CooperativeMember::query()->whereIn('member_no', ['DEV-KOP-014', 'DEV-KOP-015'])->count(),
            'Reserved personas P14 and P15 must be absent from default baseline.',
        );

        $this->assertSame(
            0,
            CooperativeMember::query()->where('member_no', 'like', 'DEV-KBU-%')->count(),
            'No DEV-KBU-* CooperativeMember records may exist.',
        );
    }

    /**
     * Scenario F: Canonical User / Member Link Integrity.
     * Each member persona user_id points to expected canonical User, with matching organization.
     */
    public function test_identity_scenario_f_canonical_user_member_link_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $members = CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-%')->with('user')->get();

        $expectedEmailMap = [
            'DEV-KOP-006' => 'seed.member.waiting@kojaya.test',
            'DEV-KOP-007' => 'seed.member.review@kojaya.test',
            'DEV-KOP-008' => 'seed.member.revision@kojaya.test',
            'DEV-KOP-009' => 'seed.member.rejected@kojaya.test',
            'DEV-KOP-010' => 'seed.member.active@kojaya.test',
            'DEV-KOP-012' => 'seed.member.google@kojaya.test',
            'DEV-KOP-013' => 'seed.member.no-google@kojaya.test',
        ];

        foreach ($members as $member) {
            $this->assertNotNull($member->user, "Member {$member->member_no} must have an associated User.");
            $this->assertSame(
                $expectedEmailMap[$member->member_no],
                $member->user->email,
                "Member {$member->member_no} must link to canonical User {$expectedEmailMap[$member->member_no]}.",
            );
            $this->assertSame($kop->id, $member->organization_id, "Member {$member->member_no} must belong to KOP-001.");
            $this->assertSame($kop->id, $member->user->organization_id, "User {$member->user->email} must belong to KOP-001.");
        }
    }

    /**
     * Scenario G: Role Integrity.
     * Canonical staff personas hold exact roles; member personas hold Anggota with no privilege escalation.
     */
    public function test_identity_scenario_g_role_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $staffRoleMap = [
            'seed.system.admin@kojaya.test' => 'System Admin',
            'seed.pengurus@kojaya.test' => 'Pengurus Koperasi',
            'seed.manajer@kojaya.test' => 'Manajer Koperasi',
            'seed.admin.kop@kojaya.test' => 'Admin Koperasi',
            'seed.kasir@kojaya.test' => 'Kasir Koperasi',
        ];

        foreach ($staffRoleMap as $email => $expectedRole) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $roles = $user->getRoleNames()->all();

            $this->assertContains($expectedRole, $roles, "User {$email} must have role {$expectedRole}.");
            $this->assertNotContains('Anggota', $roles, "Staff user {$email} must not have Anggota role.");
        }

        $memberEmails = [
            'seed.member.waiting@kojaya.test',
            'seed.member.review@kojaya.test',
            'seed.member.revision@kojaya.test',
            'seed.member.rejected@kojaya.test',
            'seed.member.active@kojaya.test',
            'seed.member.google@kojaya.test',
            'seed.member.no-google@kojaya.test',
        ];

        $privilegedRoles = ['System Admin', 'Pengurus Koperasi', 'Manajer Koperasi', 'Admin Koperasi', 'Kasir Koperasi'];

        foreach ($memberEmails as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $roles = $user->getRoleNames()->all();

            $this->assertContains('Anggota', $roles, "Member user {$email} must have role Anggota.");
            foreach ($privilegedRoles as $privRole) {
                $this->assertNotContains($privRole, $roles, "Member user {$email} must not have privileged role {$privRole}.");
            }
        }
    }

    /**
     * Scenario H: Google SSO Integrity.
     * P12 has exactly 1 deterministic Google SocialAccount; P13 and others do not.
     */
    public function test_identity_scenario_h_google_sso_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $p12User = User::query()->where('email', 'seed.member.google@kojaya.test')->firstOrFail();
        $p12Member = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();

        $socialAccounts = SocialAccount::query()->where('provider', 'google')->get();
        $this->assertCount(1, $socialAccounts, 'Exactly 1 Google SocialAccount must exist in the canonical baseline.');

        $googleAccount = $socialAccounts->first();
        $this->assertSame('google-seed-sub-012', $googleAccount->provider_id);
        $this->assertSame($p12User->id, $googleAccount->user_id);
        $this->assertSame('google', $p12Member->sso_provider);

        // P13 must remain non-Google
        $p13Member = CooperativeMember::query()->where('member_no', 'DEV-KOP-013')->firstOrFail();
        $this->assertNull($p13Member->sso_provider);
        $p13User = User::query()->where('email', 'seed.member.no-google@kojaya.test')->firstOrFail();
        $this->assertSame(0, SocialAccount::query()->where('user_id', $p13User->id)->count());
    }

    /**
     * Scenario Z: Natural-Key Uniqueness.
     * Canonical natural keys must be distinct with zero collision.
     */
    public function test_identity_scenario_z_natural_key_uniqueness(): void
    {
        $this->ensureCanonicalBaseline();

        // Emails
        $emails = User::query()->where('email', 'like', 'seed.%@kojaya.test')->pluck('email')->all();
        $this->assertCount(count(array_unique($emails)), $emails, 'Canonical seed emails must be unique.');

        // Member numbers
        $memberNos = CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-%')->pluck('member_no')->all();
        $this->assertCount(count(array_unique($memberNos)), $memberNos, 'Canonical member_no must be unique.');

        $noAnggotas = CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-%')->pluck('no_anggota')->all();
        $this->assertCount(count(array_unique($noAnggotas)), $noAnggotas, 'Canonical no_anggota must be unique.');

        // Google provider identity
        $googleSubs = SocialAccount::query()->where('provider_id', 'like', 'google-seed-%')->pluck('provider_id')->all();
        $this->assertCount(count(array_unique($googleSubs)), $googleSubs, 'Canonical Google provider IDs must be unique.');

        // Loan reference numbers
        $loanRefs = Loan::query()->where('reference_no', 'like', 'SEED-LOAN-%')->pluck('reference_no')->all();
        $this->assertCount(count(array_unique($loanRefs)), $loanRefs, 'Canonical loan reference numbers must be unique.');

        // Payment reference numbers
        $payRefs = CooperativePayment::query()->where('reference_no', 'like', 'SEED-PAY-%')->pluck('reference_no')->all();
        $this->assertCount(count(array_unique($payRefs)), $payRefs, 'Canonical payment reference numbers must be unique.');

        // Receipt numbers
        $rcNos = CooperativeReceipt::query()->where('receipt_no', 'like', 'SEED-RC-%')->pluck('receipt_no')->all();
        $this->assertCount(count(array_unique($rcNos)), $rcNos, 'Canonical receipt numbers must be unique.');

        // POS transactions
        $posTxNos = PosTransaction::query()->where('transaction_no', 'like', 'SEED-POS-TX-%')->pluck('transaction_no')->all();
        $this->assertCount(count(array_unique($posTxNos)), $posTxNos, 'Canonical POS transaction numbers must be unique.');

        $posClientRefs = PosTransaction::query()->where('client_reference', 'like', 'SEED-POS-REF-%')->pluck('client_reference')->all();
        $this->assertCount(count(array_unique($posClientRefs)), $posClientRefs, 'Canonical POS client references must be unique.');

        // Product SKU and Barcode
        $skus = PosProduct::query()->where('sku', 'like', 'SEED-POS-%')->pluck('sku')->all();
        $this->assertCount(count(array_unique($skus)), $skus, 'Canonical product SKUs must be unique.');

        $barcodes = PosProduct::query()->where('sku', 'like', 'SEED-POS-%')->pluck('barcode')->all();
        $this->assertCount(count(array_unique($barcodes)), $barcodes, 'Canonical product barcodes must be unique.');
    }

    // =========================================================================
    // 2. LIFECYCLE GATE (Scenarios D, E)
    // =========================================================================

    /**
     * Scenario D: Lifecycle Matrix.
     * Authoritative application mapping via MemberLifecycleExperience::fromMember().
     */
    public function test_lifecycle_scenario_d_authoritative_lifecycle_matrix(): void
    {
        $this->ensureCanonicalBaseline();

        $expectedMatrix = [
            'DEV-KOP-006' => MemberLifecycleExperience::WaitingVerification,
            'DEV-KOP-007' => MemberLifecycleExperience::UnderReview,
            'DEV-KOP-008' => MemberLifecycleExperience::RevisionRequired,
            'DEV-KOP-009' => MemberLifecycleExperience::Rejected,
            'DEV-KOP-010' => MemberLifecycleExperience::Active,
            'DEV-KOP-012' => MemberLifecycleExperience::Active,
            'DEV-KOP-013' => MemberLifecycleExperience::Active,
        ];

        foreach ($expectedMatrix as $memberNo => $expectedExperience) {
            $member = CooperativeMember::query()->where('member_no', $memberNo)->firstOrFail();
            $actualExperience = MemberLifecycleExperience::fromMember($member);

            $this->assertSame(
                $expectedExperience,
                $actualExperience,
                "Member {$memberNo} must resolve to lifecycle {$expectedExperience->value}, got {$actualExperience->value}.",
            );
        }
    }

    /**
     * Scenario E: Lifecycle Metadata Coherence.
     * Verify required lifecycle verification timestamps, notes, and actor metadata.
     */
    public function test_lifecycle_scenario_e_lifecycle_metadata_coherence(): void
    {
        $this->ensureCanonicalBaseline();

        $adminKop = User::query()->where('email', 'seed.admin.kop@kojaya.test')->firstOrFail();
        $pengurus = User::query()->where('email', 'seed.pengurus@kojaya.test')->firstOrFail();

        // P06: WAITING_VERIFICATION
        $p06 = CooperativeMember::query()->where('member_no', 'DEV-KOP-006')->firstOrFail();
        $this->assertNull($p06->tanggal_aktif);
        $this->assertNull($p06->admin_validated_at);
        $this->assertNull($p06->admin_validated_by);
        $this->assertNull($p06->validated_at);
        $this->assertNull($p06->validated_by);

        // P07: UNDER_REVIEW (Admin verified, waiting for Pengurus)
        $p07 = CooperativeMember::query()->where('member_no', 'DEV-KOP-007')->firstOrFail();
        $this->assertNull($p07->tanggal_aktif);
        $this->assertNotNull($p07->admin_validated_at);
        $this->assertSame($adminKop->id, $p07->admin_validated_by);
        $this->assertNotNull($p07->admin_validation_notes);
        $this->assertNull($p07->validated_at);
        $this->assertNull($p07->validated_by);

        // P08: REVISION_REQUIRED (Admin requested revision)
        $p08 = CooperativeMember::query()->where('member_no', 'DEV-KOP-008')->firstOrFail();
        $this->assertNull($p08->tanggal_aktif);
        $this->assertNotNull($p08->admin_validated_at);
        $this->assertSame($adminKop->id, $p08->admin_validated_by);
        $this->assertNotNull($p08->validated_at);
        $this->assertSame($adminKop->id, $p08->validated_by);
        $this->assertNotNull($p08->validation_notes);

        // P09: REJECTED (Pengurus final rejection)
        $p09 = CooperativeMember::query()->where('member_no', 'DEV-KOP-009')->firstOrFail();
        $this->assertNull($p09->tanggal_aktif);
        $this->assertNotNull($p09->admin_validated_at);
        $this->assertSame($adminKop->id, $p09->admin_validated_by);
        $this->assertNotNull($p09->validated_at);
        $this->assertSame($pengurus->id, $p09->validated_by);
        $this->assertNotSame($p09->admin_validated_by, $p09->validated_by);
        $this->assertNotNull($p09->validation_notes);

        // P10, P12, P13: ACTIVE
        foreach (['DEV-KOP-010', 'DEV-KOP-012', 'DEV-KOP-013'] as $activeNo) {
            $m = CooperativeMember::query()->where('member_no', $activeNo)->firstOrFail();
            $this->assertNotNull($m->tanggal_aktif, "Active member {$activeNo} must have tanggal_aktif set.");
            $this->assertNotNull($m->admin_validated_at, "Active member {$activeNo} must have admin_validated_at set.");
            $this->assertSame($adminKop->id, $m->admin_validated_by);
            $this->assertNotNull($m->validated_at, "Active member {$activeNo} must have validated_at set.");
            $this->assertSame($pengurus->id, $m->validated_by);
            $this->assertSame('ACTIVE', $m->status);
            $this->assertSame('ACTIVE', $m->validation_status);
        }
    }

    // =========================================================================
    // 3. ORGANIZATION GATE (Scenarios C, AC)
    // =========================================================================

    /**
     * Scenario C: Organization Topology.
     * KOP-001 owns all members; KBU-001 and ISO-999 own zero members.
     */
    public function test_organization_scenario_c_topology_and_member_ownership(): void
    {
        $this->ensureCanonicalBaseline();

        $kop = Organization::query()->where('code', 'KOP-001')->first();
        $this->assertNotNull($kop, 'KOP-001 must exist.');
        $this->assertSame('L0', $kop->level);
        $this->assertSame('HEAD_OFFICE', $kop->type);

        $kbu = Organization::query()->where('code', 'KBU-001')->first();
        $this->assertNotNull($kbu, 'KBU-001 must exist.');
        $this->assertSame($kop->id, $kbu->parent_id);
        $this->assertSame('L1', $kbu->level);
        $this->assertSame('BRANCH', $kbu->type);

        $iso = Organization::query()->where('code', 'ISO-999')->first();
        $this->assertNotNull($iso, 'ISO-999 must exist.');
        $this->assertNull($iso->parent_id);

        $this->assertSame(
            7,
            CooperativeMember::query()->where('organization_id', $kop->id)->count(),
            'KOP-001 must own all 7 canonical cooperative members.',
        );
        $this->assertSame(
            0,
            CooperativeMember::query()->where('organization_id', $kbu->id)->count(),
            'KBU-001 must own exactly 0 CooperativeMember records.',
        );
        $this->assertSame(
            0,
            CooperativeMember::query()->where('organization_id', $iso->id)->count(),
            'ISO-999 baseline must own exactly 0 CooperativeMember records.',
        );
    }

    /**
     * Scenario AC: Organization Financial Isolation.
     * All cooperative finance strictly belongs to KOP-001; KBU-001 and ISO-999 have 0.
     */
    public function test_organization_scenario_ac_financial_isolation(): void
    {
        $this->ensureCanonicalBaseline();

        $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $kbu = Organization::query()->where('code', 'KBU-001')->firstOrFail();
        $iso = Organization::query()->where('code', 'ISO-999')->firstOrFail();

        // KOP-001 has financial fixtures
        $this->assertGreaterThan(0, MemberStoreAccount::query()->where('organization_id', $kop->id)->count());
        $this->assertGreaterThan(0, Loan::query()->where('organization_id', $kop->id)->count());
        $this->assertGreaterThan(0, PosTransaction::query()->where('organization_id', $kop->id)->count());
        $this->assertGreaterThan(0, CooperativeLedgerEntry::query()->where('organization_id', $kop->id)->count());

        // KBU-001 must have 0 cooperative finance
        $this->assertSame(0, CooperativeDuesInvoice::query()->whereHas('member', fn ($q) => $q->where('organization_id', $kbu->id))->count());
        $this->assertSame(0, CooperativePayment::query()->whereHas('member', fn ($q) => $q->where('organization_id', $kbu->id))->count());
        $this->assertSame(0, CooperativeReceipt::query()->whereHas('member', fn ($q) => $q->where('organization_id', $kbu->id))->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('organization_id', $kbu->id)->count());
        $this->assertSame(0, MemberStoreAccount::query()->where('organization_id', $kbu->id)->count());
        $this->assertSame(0, MemberStoreLedgerEntry::query()->where('organization_id', $kbu->id)->count());
        $this->assertSame(0, Loan::query()->where('organization_id', $kbu->id)->count());
        $this->assertSame(0, PosTransaction::query()->where('organization_id', $kbu->id)->count());

        // ISO-999 must have 0 cooperative finance
        $this->assertSame(0, CooperativeDuesInvoice::query()->whereHas('member', fn ($q) => $q->where('organization_id', $iso->id))->count());
        $this->assertSame(0, CooperativePayment::query()->whereHas('member', fn ($q) => $q->where('organization_id', $iso->id))->count());
        $this->assertSame(0, CooperativeReceipt::query()->whereHas('member', fn ($q) => $q->where('organization_id', $iso->id))->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('organization_id', $iso->id)->count());
        $this->assertSame(0, MemberStoreAccount::query()->where('organization_id', $iso->id)->count());
        $this->assertSame(0, MemberStoreLedgerEntry::query()->where('organization_id', $iso->id)->count());
        $this->assertSame(0, Loan::query()->where('organization_id', $iso->id)->count());
        $this->assertSame(0, PosTransaction::query()->where('organization_id', $iso->id)->count());
    }

    // =========================================================================
    // 4. FINANCIAL GATE (Scenarios I, J, K, L, M, N, AA, AB)
    // =========================================================================

    /**
     * Scenario I: Financial Isolation for Non-Active Members.
     * P06, P07, P08, P09 must have strictly zero financial fixtures.
     */
    public function test_financial_scenario_i_isolation_for_non_active_members(): void
    {
        $this->ensureCanonicalBaseline();

        $nonActiveNos = ['DEV-KOP-006', 'DEV-KOP-007', 'DEV-KOP-008', 'DEV-KOP-009'];
        $nonActiveIds = CooperativeMember::query()->whereIn('member_no', $nonActiveNos)->pluck('id')->all();

        $this->assertCount(4, $nonActiveIds);

        foreach ($nonActiveIds as $memberId) {
            $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $memberId)->count(), 'Non-active member must have 0 dues invoices.');
            $this->assertSame(0, CooperativePayment::query()->where('cooperative_member_id', $memberId)->count(), 'Non-active member must have 0 payments.');
            $this->assertSame(0, CooperativeReceipt::query()->where('cooperative_member_id', $memberId)->count(), 'Non-active member must have 0 receipts.');
            $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_member_id', $memberId)->count(), 'Non-active member must have 0 cooperative ledger entries.');
            $this->assertSame(0, MemberStoreAccount::query()->where('cooperative_member_id', $memberId)->count(), 'Non-active member must have 0 store accounts.');
            $this->assertSame(0, Loan::query()->where('cooperative_member_id', $memberId)->count(), 'Non-active member must have 0 loans.');
            $this->assertSame(0, PosTransaction::query()->where('cooperative_member_id', $memberId)->count(), 'Non-active member must have 0 POS transactions.');
        }
    }

    /**
     * Scenario J: P13 Empty Financial Shape.
     * P13 is ACTIVE but has strictly zero financial records.
     */
    public function test_financial_scenario_j_p13_empty_financial_shape(): void
    {
        $this->ensureCanonicalBaseline();

        $p13 = CooperativeMember::query()->where('member_no', 'DEV-KOP-013')->firstOrFail();

        $this->assertSame(MemberLifecycleExperience::Active, MemberLifecycleExperience::fromMember($p13));

        $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $p13->id)->count(), 'P13 must have 0 dues invoices.');
        $this->assertSame(0, CooperativePayment::query()->where('cooperative_member_id', $p13->id)->count(), 'P13 must have 0 payments.');
        $this->assertSame(0, CooperativeReceipt::query()->where('cooperative_member_id', $p13->id)->count(), 'P13 must have 0 receipts.');
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_member_id', $p13->id)->count(), 'P13 must have 0 ledger entries.');
        $this->assertSame(0, MemberStoreAccount::query()->where('cooperative_member_id', $p13->id)->count(), 'P13 must have 0 store accounts.');
        $this->assertSame(0, Loan::query()->where('cooperative_member_id', $p13->id)->count(), 'P13 must have 0 loans.');
        $this->assertSame(0, PosTransaction::query()->where('cooperative_member_id', $p13->id)->count(), 'P13 must have 0 POS transactions.');
    }

    /**
     * Scenario K: P10 Contribution Integrity.
     * P10 has PAID POKOK and WAJIB with reconciled amounts, payments, receipts, and ledger.
     */
    public function test_financial_scenario_k_p10_contribution_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $pokok = CooperativeContributionType::query()->where('code', 'POKOK')->firstOrFail();
        $wajib = CooperativeContributionType::query()->where('code', 'WAJIB')->firstOrFail();

        // POKOK
        $invPokok = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('cooperative_contribution_type_id', $pokok->id)
            ->where('period', '2026-01')
            ->firstOrFail();

        $this->assertSame('PAID', $invPokok->status);
        $this->assertEquals($invPokok->amount, $invPokok->paid_amount);

        $payPokok = CooperativePayment::query()->where('cooperative_dues_invoice_id', $invPokok->id)->firstOrFail();
        $this->assertSame('APPROVED', $payPokok->status);
        $this->assertEquals($invPokok->amount, $payPokok->amount);
        $this->assertSame('SEED-RC-010-001', $payPokok->receipt_no);

        $rcPokok = CooperativeReceipt::query()->where('receipt_no', 'SEED-RC-010-001')->firstOrFail();
        $this->assertSame($payPokok->id, $rcPokok->cooperative_payment_id);
        $this->assertSame($p10->id, $rcPokok->cooperative_member_id);

        $ledgerPokok = CooperativeLedgerEntry::query()
            ->where('cooperative_payment_id', $payPokok->id)
            ->where('ledger_scope', 'SAVINGS')
            ->firstOrFail();
        $this->assertEquals($payPokok->amount, (float) $ledgerPokok->credit);
        $this->assertEquals(0, (float) $ledgerPokok->debit);

        // WAJIB
        $invWajib = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('cooperative_contribution_type_id', $wajib->id)
            ->where('period', '2026-01')
            ->firstOrFail();

        $this->assertSame('PAID', $invWajib->status);
        $this->assertEquals($invWajib->amount, $invWajib->paid_amount);

        $payWajib = CooperativePayment::query()->where('cooperative_dues_invoice_id', $invWajib->id)->firstOrFail();
        $this->assertSame('APPROVED', $payWajib->status);
        $this->assertEquals($invWajib->amount, $payWajib->amount);
        $this->assertSame('SEED-RC-010-002', $payWajib->receipt_no);

        $rcWajib = CooperativeReceipt::query()->where('receipt_no', 'SEED-RC-010-002')->firstOrFail();
        $this->assertSame($payWajib->id, $rcWajib->cooperative_payment_id);
        $this->assertSame($p10->id, $rcWajib->cooperative_member_id);

        $ledgerWajib = CooperativeLedgerEntry::query()
            ->where('cooperative_payment_id', $payWajib->id)
            ->where('ledger_scope', 'SAVINGS')
            ->firstOrFail();
        $this->assertEquals($payWajib->amount, (float) $ledgerWajib->credit);
        $this->assertEquals(0, (float) $ledgerWajib->debit);
    }

    /**
     * Scenario L: P12 Unpaid Contribution Integrity.
     * P12 has UNPAID WAJIB 2026-06 with paid_amount 0 and zero settling payments.
     */
    public function test_financial_scenario_l_p12_unpaid_contribution_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $wajib = CooperativeContributionType::query()->where('code', 'WAJIB')->firstOrFail();

        $inv = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $p12->id)
            ->where('cooperative_contribution_type_id', $wajib->id)
            ->where('period', '2026-06')
            ->firstOrFail();

        $this->assertSame('UNPAID', $inv->status);
        $this->assertEquals(100000.0, (float) $inv->amount);
        $this->assertEquals(0.0, (float) $inv->paid_amount);
        $this->assertSame('2026-06-10', $inv->due_date?->toDateString());

        $this->assertSame(
            0,
            CooperativePayment::query()->where('cooperative_dues_invoice_id', $inv->id)->count(),
            'P12 unpaid invoice must have 0 payments.',
        );
    }

    /**
     * Scenario M: Receipt / Payment Reconciliation.
     * Every canonical SEED payment claiming a receipt reconciles exactly.
     */
    public function test_financial_scenario_m_receipt_payment_reconciliation(): void
    {
        $this->ensureCanonicalBaseline();

        $paymentsWithReceipts = CooperativePayment::query()
            ->whereNotNull('receipt_no')
            ->where('reference_no', 'like', 'SEED-PAY-%')
            ->get();

        $this->assertNotEmpty($paymentsWithReceipts, 'Canonical payments with receipts must exist.');

        foreach ($paymentsWithReceipts as $payment) {
            $receipt = CooperativeReceipt::query()->where('receipt_no', $payment->receipt_no)->first();
            $this->assertNotNull($receipt, "Receipt {$payment->receipt_no} must exist for payment {$payment->reference_no}.");
            $this->assertSame($payment->id, $receipt->cooperative_payment_id, 'Receipt payment link must match.');
            $this->assertSame($payment->cooperative_member_id, $receipt->cooperative_member_id, 'Receipt member link must match.');
            $this->assertSame('APPROVED', $payment->status, 'Payment claiming a receipt must be APPROVED.');
        }
    }

    /**
     * Scenario N: Savings Ledger Reconciliation.
     * Approved payment totals reconcile with savings ledger credits.
     */
    public function test_financial_scenario_n_savings_ledger_reconciliation(): void
    {
        $this->ensureCanonicalBaseline();

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();

        $totalApprovedPayments = (float) CooperativePayment::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('status', 'APPROVED')
            ->sum('amount');

        $totalSavingsCredits = (float) CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $p10->id)
            ->where('ledger_scope', 'SAVINGS')
            ->sum('credit');

        $this->assertGreaterThan(0, $totalApprovedPayments);
        $this->assertEquals(
            $totalApprovedPayments,
            $totalSavingsCredits,
            'Total approved savings payments must reconcile with savings ledger credits.',
        );

        // Every payment has a corresponding ledger entry
        $payments = CooperativePayment::query()->where('cooperative_member_id', $p10->id)->get();
        foreach ($payments as $pay) {
            $entry = CooperativeLedgerEntry::query()
                ->where('cooperative_payment_id', $pay->id)
                ->where('ledger_scope', 'SAVINGS')
                ->first();
            $this->assertNotNull($entry, "Payment {$pay->reference_no} must have a corresponding savings ledger entry.");
            $this->assertEquals((float) $pay->amount, (float) $entry->credit);
            $this->assertEquals(0.0, (float) $entry->debit);
        }
    }

    /**
     * Scenario AA: No Orphan Financial Rows.
     * All child records must reference existing valid parents.
     */
    public function test_financial_scenario_aa_no_orphan_financial_rows(): void
    {
        $this->ensureCanonicalBaseline();

        // Invoices -> Member
        $orphanInvoices = CooperativeDuesInvoice::query()
            ->where('period', 'like', '2026-%')
            ->whereDoesntHave('member')
            ->count();
        $this->assertSame(0, $orphanInvoices, 'No orphan dues invoices permitted.');

        // Payments -> Invoice & Member
        $orphanPayments = CooperativePayment::query()
            ->where('reference_no', 'like', 'SEED-PAY-%')
            ->where(fn ($q) => $q->whereDoesntHave('member')->orWhereDoesntHave('invoice'))
            ->count();
        $this->assertSame(0, $orphanPayments, 'No orphan payments permitted.');

        // Receipts -> Payment & Member
        $orphanReceipts = CooperativeReceipt::query()
            ->where('receipt_no', 'like', 'SEED-RC-%')
            ->where(fn ($q) => $q->whereDoesntHave('member')->orWhereDoesntHave('payment'))
            ->count();
        $this->assertSame(0, $orphanReceipts, 'No orphan receipts permitted.');

        // Cooperative Ledger -> Member
        $orphanLedger = CooperativeLedgerEntry::query()
            ->where('description', 'like', '%P1%')
            ->whereDoesntHave('member')
            ->count();
        $this->assertSame(0, $orphanLedger, 'No orphan cooperative ledger entries permitted.');

        // Store Ledger -> Account
        $orphanStoreLedger = MemberStoreLedgerEntry::query()
            ->where('idempotency_key', 'like', 'seed-store-ledger:%')
            ->whereDoesntHave('account')
            ->count();
        $this->assertSame(0, $orphanStoreLedger, 'No orphan store ledger entries permitted.');

        // Loan Installments -> Loan
        $orphanInstallments = LoanInstallment::query()
            ->whereHas('loan', fn ($q) => $q->where('reference_no', 'like', 'SEED-LOAN-%'))
            ->whereDoesntHave('loan')
            ->count();
        $this->assertSame(0, $orphanInstallments, 'No orphan loan installments permitted.');

        // Loan Payments -> Loan & Installment
        $orphanLoanPayments = LoanPayment::query()
            ->where('reference_no', 'like', 'SEED-LOAN-PAY-%')
            ->where(fn ($q) => $q->whereDoesntHave('loan')->orWhereDoesntHave('installment'))
            ->count();
        $this->assertSame(0, $orphanLoanPayments, 'No orphan loan payments permitted.');

        // POS Items -> Transaction & Product
        $orphanPosItems = PosTransactionItem::query()
            ->whereHas('transaction', fn ($q) => $q->where('transaction_no', 'like', 'SEED-POS-TX-%'))
            ->where(fn ($q) => $q->whereDoesntHave('transaction')->orWhereDoesntHave('product'))
            ->count();
        $this->assertSame(0, $orphanPosItems, 'No orphan POS transaction items permitted.');

        // POS Payments -> Transaction
        $orphanPosPayments = PosPayment::query()
            ->where('reference_no', 'like', 'SEED-POS-PAY-%')
            ->whereDoesntHave('transaction')
            ->count();
        $this->assertSame(0, $orphanPosPayments, 'No orphan POS payments permitted.');
    }

    /**
     * Scenario AB: No Cross-Member Financial Leakage.
     * Records of P10 must never reference P12, and vice versa.
     */
    public function test_financial_scenario_ab_no_cross_member_financial_leakage(): void
    {
        $this->ensureCanonicalBaseline();

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();

        // P10 check: no relationship points to P12
        $p10Invoices = CooperativeDuesInvoice::query()->where('cooperative_member_id', $p10->id)->get();
        foreach ($p10Invoices as $inv) {
            $this->assertSame($p10->id, $inv->cooperative_member_id);
            $this->assertNotSame($p12->id, $inv->cooperative_member_id);
        }

        $p10Payments = CooperativePayment::query()->with('invoice')->where('cooperative_member_id', $p10->id)->get();
        foreach ($p10Payments as $pay) {
            $this->assertSame($p10->id, $pay->cooperative_member_id);
            $this->assertNotSame($p12->id, $pay->cooperative_member_id);
            $this->assertSame($p10->id, $pay->invoice->cooperative_member_id);
        }

        $p10Loans = Loan::query()->where('cooperative_member_id', $p10->id)->get();
        foreach ($p10Loans as $loan) {
            $this->assertSame($p10->id, $loan->cooperative_member_id);
            $this->assertNotSame($p12->id, $loan->cooperative_member_id);
            $this->assertSame($p10->user_id, $loan->user_id);
        }

        $p10Pos = PosTransaction::query()->where('cooperative_member_id', $p10->id)->get();
        foreach ($p10Pos as $tx) {
            $this->assertSame($p10->id, $tx->cooperative_member_id);
            $this->assertNotSame($p12->id, $tx->cooperative_member_id);
        }

        // P12 check: no relationship points to P10
        $p12Loans = Loan::query()->where('cooperative_member_id', $p12->id)->get();
        foreach ($p12Loans as $loan) {
            $this->assertSame($p12->id, $loan->cooperative_member_id);
            $this->assertNotSame($p10->id, $loan->cooperative_member_id);
            $this->assertSame($p12->user_id, $loan->user_id);
        }

        $p12Pos = PosTransaction::query()->where('cooperative_member_id', $p12->id)->get();
        foreach ($p12Pos as $tx) {
            $this->assertSame($p12->id, $tx->cooperative_member_id);
            $this->assertNotSame($p10->id, $tx->cooperative_member_id);
        }
    }

    // =========================================================================
    // 5. STORE CREDIT GATE (Scenarios O, P, Q)
    // =========================================================================

    /**
     * Scenario O: P10 Store Account Integrity.
     * Balance +150,000, limit 500,000, availableCredit 650,000.
     */
    public function test_store_credit_scenario_o_p10_store_account_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $account = MemberStoreAccount::query()->where('cooperative_member_id', $p10->id)->firstOrFail();

        $this->assertSame(150000, $account->balance);
        $this->assertSame(500000, $account->credit_limit);
        $this->assertSame(MemberStoreAccountStatus::Active, $account->status);
        $this->assertSame(650000, $account->availableCredit(), 'P10 available credit must be balance + limit = 650000.');
    }

    /**
     * Scenario P: P12 Store Boundary Integrity.
     * Balance -450,000, limit 500,000, availableCredit 50,000, balance >= -limit.
     */
    public function test_store_credit_scenario_p_p12_store_boundary_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $account = MemberStoreAccount::query()->where('cooperative_member_id', $p12->id)->firstOrFail();

        $this->assertSame(-450000, $account->balance);
        $this->assertSame(500000, $account->credit_limit);
        $this->assertSame(50000, $account->availableCredit(), 'P12 available credit must be balance + limit = 50000.');
        $this->assertTrue(
            $account->balance >= -$account->credit_limit,
            'P12 balance must not exceed the negative credit limit.',
        );
    }

    /**
     * Scenario Q: Store Ledger Reconciliation.
     * Cached MemberStoreAccount balances must reconcile exactly against MemberStoreLedgerEntry.
     */
    public function test_store_credit_scenario_q_store_ledger_reconciliation(): void
    {
        $this->ensureCanonicalBaseline();

        foreach (['DEV-KOP-010', 'DEV-KOP-012'] as $memberNo) {
            $member = CooperativeMember::query()->where('member_no', $memberNo)->firstOrFail();
            $account = MemberStoreAccount::query()->where('cooperative_member_id', $member->id)->firstOrFail();

            $entries = MemberStoreLedgerEntry::query()
                ->where('account_id', $account->id)
                ->orderBy('id')
                ->get();

            $this->assertNotEmpty($entries, "Store ledger entries must exist for member {$memberNo}.");

            $runningBalance = 0;
            foreach ($entries as $entry) {
                $expectedAfter = $entry->effect === MemberStoreLedgerEffect::Credit
                    ? $entry->balance_before + $entry->amount
                    : $entry->balance_before - $entry->amount;

                $this->assertSame(
                    $expectedAfter,
                    $entry->balance_after,
                    "Entry {$entry->id} balance_after must equal balance_before adjusted by effect and amount.",
                );

                $runningBalance = $entry->effect === MemberStoreLedgerEffect::Credit
                    ? $runningBalance + $entry->amount
                    : $runningBalance - $entry->amount;
            }

            $this->assertSame(
                $account->balance,
                $runningBalance,
                "Cached balance for {$memberNo} must reconcile with ledger sum.",
            );

            $lastEntry = $entries->last();
            $this->assertSame(
                $account->balance,
                $lastEntry->balance_after,
                "Latest ledger entry balance_after must equal cached account balance for {$memberNo}.",
            );
        }
    }

    // =========================================================================
    // 6. POS GATE (Scenarios R, S, T)
    // =========================================================================

    /**
     * Scenario R: POS Integrity P10.
     * Routine cash purchase: subtotal 110,000, CASH payment 110,000, COMPLETED status.
     */
    public function test_pos_scenario_r_p10_routine_cash_pos_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $tx = PosTransaction::query()
            ->where('transaction_no', 'SEED-POS-TX-010-001')
            ->with(['items', 'payments'])
            ->firstOrFail();

        $this->assertSame($p10->id, $tx->cooperative_member_id);
        $this->assertSame('COMPLETED', $tx->status);
        $this->assertEquals(110000.0, (float) $tx->subtotal);
        $this->assertEquals(110000.0, (float) $tx->total_amount);
        $this->assertEquals(110000.0, (float) $tx->cash_received);
        $this->assertEquals(0.0, (float) $tx->cash_change);

        // Item lines reconciliation
        $this->assertEquals(110000.0, (float) $tx->items->sum('line_total'));
        $this->assertCount(2, $tx->items);

        // Payments reconciliation
        $this->assertCount(1, $tx->payments);
        $payment = $tx->payments->first();
        $this->assertSame('CASH', $payment->payment_method);
        $this->assertEquals(110000.0, (float) $payment->amount);
    }

    /**
     * Scenario S: POS Integrity P12.
     * Member store credit POS: total 450,000, MEMBER_STORE_ACCOUNT payment, debited from ledger.
     */
    public function test_pos_scenario_s_p12_member_store_credit_pos_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $tx = PosTransaction::query()
            ->where('transaction_no', 'SEED-POS-TX-012-001')
            ->with(['items', 'payments'])
            ->firstOrFail();

        $this->assertSame($p12->id, $tx->cooperative_member_id);
        $this->assertSame('COMPLETED', $tx->status);
        $this->assertEquals(450000.0, (float) $tx->total_amount);

        // Payment check
        $payment = $tx->payments->where('payment_method', 'MEMBER_STORE_ACCOUNT')->first();
        $this->assertNotNull($payment, 'P12 POS must have MEMBER_STORE_ACCOUNT payment.');
        $this->assertEquals(450000.0, (float) $payment->amount);

        // Store ledger mutation check
        $storeAccount = MemberStoreAccount::query()->where('cooperative_member_id', $p12->id)->firstOrFail();
        $ledgerEntry = MemberStoreLedgerEntry::query()
            ->where('account_id', $storeAccount->id)
            ->where('reference_type', 'pos_transaction')
            ->where('reference_id', (string) $tx->id)
            ->first();

        $this->assertNotNull($ledgerEntry, 'POS transaction must create a store ledger entry.');
        $this->assertSame(450000, $ledgerEntry->amount);
        $this->assertSame(MemberStoreLedgerEffect::Debit, $ledgerEntry->effect);
        $this->assertSame(-450000, $ledgerEntry->balance_after);
        $this->assertSame(-450000, $storeAccount->balance);
    }

    /**
     * Scenario T: POS Product Stock Integrity.
     * Ending stock reconciles: opening stock - canonical sold quantities.
     */
    public function test_pos_scenario_t_pos_product_stock_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $expectedStock = [
            'SEED-POS-001' => ['final' => 93, 'sold' => 7, 'opening' => 100],
            'SEED-POS-002' => ['final' => 99, 'sold' => 1, 'opening' => 100],
            'SEED-POS-003' => ['final' => 100, 'sold' => 0, 'opening' => 100],
            'SEED-POS-004' => ['final' => 80, 'sold' => 0, 'opening' => 80],
        ];

        foreach ($expectedStock as $sku => $data) {
            $product = PosProduct::query()->where('sku', $sku)->firstOrFail();
            $this->assertSame(
                $data['final'],
                $product->stock,
                "Product {$sku} stock must equal expected {$data['final']}.",
            );

            $soldQuantity = (int) PosTransactionItem::query()
                ->where('pos_product_id', $product->id)
                ->sum('quantity');

            $this->assertSame(
                $data['sold'],
                $soldQuantity,
                "Product {$sku} sold quantity in transactions must equal {$data['sold']}.",
            );

            $this->assertSame(
                $data['opening'],
                $product->stock + $soldQuantity,
                "Product {$sku} opening stock ({$data['opening']}) must equal final stock plus sold quantity.",
            );
        }
    }

    // =========================================================================
    // 7. LOANS GATE (Scenarios U, V, W, X, Y)
    // =========================================================================

    /**
     * Scenario U: P10 Paid-Off Loan Integrity.
     * Status PAID_OFF, outstanding 0, 6 installments paid, payments sum 3,225,000.
     */
    public function test_loan_scenario_u_p10_paid_off_loan_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $p10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $loan = Loan::query()
            ->where('reference_no', 'SEED-LOAN-CLOSED-010-001')
            ->with(['installments', 'payments'])
            ->firstOrFail();

        $this->assertSame($p10->id, $loan->cooperative_member_id);
        $this->assertSame(LoanStatus::PaidOff, $loan->status);
        $this->assertEquals(0.0, (float) $loan->outstanding_amount);
        $this->assertEquals(3225000.0, (float) $loan->total_amount);

        // 6 Paid installments
        $this->assertCount(6, $loan->installments);
        foreach ($loan->installments as $inst) {
            $this->assertSame(InstallmentStatus::Paid, $inst->status);
            $this->assertEquals((float) $inst->amount_due, (float) $inst->amount_paid);
            $this->assertEquals(537500.0, (float) $inst->amount_paid);
        }

        // Installment sum == total amount
        $this->assertEquals(3225000.0, (float) $loan->installments->sum('amount_due'));

        // Payments sum == total amount
        $this->assertCount(6, $loan->payments);
        $this->assertEquals(3225000.0, (float) $loan->payments->sum('amount'));
    }

    /**
     * Scenario V: P10 Maker-Checker Integrity.
     * Manager reviewer = P03, Final approver = P02, reviewer != approver, chronological order.
     */
    public function test_loan_scenario_v_p10_maker_checker_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $manajer = User::query()->where('email', 'seed.manajer@kojaya.test')->firstOrFail();
        $pengurus = User::query()->where('email', 'seed.pengurus@kojaya.test')->firstOrFail();

        $loan = Loan::query()->where('reference_no', 'SEED-LOAN-CLOSED-010-001')->firstOrFail();

        $this->assertSame($manajer->id, $loan->manager_reviewed_by);
        $this->assertSame($pengurus->id, $loan->approved_by);
        $this->assertSame($pengurus->id, $loan->disbursed_by);

        $this->assertNotSame($loan->manager_reviewed_by, $loan->approved_by, 'Manager reviewer and final approver must be distinct.');

        $this->assertTrue(
            $loan->approved_at->gt($loan->manager_reviewed_at),
            'Approval timestamp must be strictly after manager review timestamp.',
        );
        $this->assertTrue(
            $loan->disbursed_at->gte($loan->approved_at),
            'Disbursement timestamp must be on or after approval timestamp.',
        );
    }

    /**
     * Scenario W: P12 Active Loan Integrity.
     * Status ACTIVE, outstanding 3,225,000, 6 installments pending, 0 payments.
     */
    public function test_loan_scenario_w_p12_active_loan_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $p12 = CooperativeMember::query()->where('member_no', 'DEV-KOP-012')->firstOrFail();
        $loan = Loan::query()
            ->where('reference_no', 'SEED-LOAN-ACTIVE-012-001')
            ->with(['installments', 'payments'])
            ->firstOrFail();

        $this->assertSame($p12->id, $loan->cooperative_member_id);
        $this->assertSame(LoanStatus::Active, $loan->status);
        $this->assertEquals(3225000.0, (float) $loan->outstanding_amount);
        $this->assertEquals(3225000.0, (float) $loan->total_amount);

        // 6 Pending installments
        $this->assertCount(6, $loan->installments);
        foreach ($loan->installments as $inst) {
            $this->assertSame(InstallmentStatus::Pending, $inst->status);
            $this->assertEquals(0.0, (float) $inst->amount_paid);
            $this->assertEquals(537500.0, (float) $inst->amount_due);
        }

        $this->assertEquals(3225000.0, (float) $loan->installments->sum('amount_due'));
        $this->assertCount(0, $loan->payments, 'Active loan must have 0 loan payments in baseline.');
    }

    /**
     * Scenario X: P12 Loan Maker-Checker.
     * Reviewer = P03, Approver = P02, chronological order valid.
     */
    public function test_loan_scenario_x_p12_maker_checker_integrity(): void
    {
        $this->ensureCanonicalBaseline();

        $manajer = User::query()->where('email', 'seed.manajer@kojaya.test')->firstOrFail();
        $pengurus = User::query()->where('email', 'seed.pengurus@kojaya.test')->firstOrFail();

        $loan = Loan::query()->where('reference_no', 'SEED-LOAN-ACTIVE-012-001')->firstOrFail();

        $this->assertSame($manajer->id, $loan->manager_reviewed_by);
        $this->assertSame($pengurus->id, $loan->approved_by);
        $this->assertSame($pengurus->id, $loan->disbursed_by);

        $this->assertNotSame($loan->manager_reviewed_by, $loan->approved_by);
        $this->assertTrue($loan->approved_at->gt($loan->manager_reviewed_at));
        $this->assertTrue($loan->disbursed_at->gte($loan->approved_at));
    }

    /**
     * Scenario Y: Baseline Has No Bad Loan States.
     * Defaulted and Written-off loans must be 0 in normal baseline.
     */
    public function test_loan_scenario_y_baseline_has_no_bad_loan_states(): void
    {
        $this->ensureCanonicalBaseline();

        $this->assertSame(
            0,
            Loan::query()->where('status', LoanStatus::Defaulted)->count(),
            'Baseline must contain 0 DEFAULTED loans.',
        );

        $this->assertSame(
            0,
            Loan::query()->where('status', LoanStatus::WrittenOff)->count(),
            'Baseline must contain 0 WRITTEN_OFF loans.',
        );
    }

    // =========================================================================
    // 8. EDGE ISOLATION GATE (Scenarios AD, AE)
    // =========================================================================

    /**
     * Scenario AD: Baseline Edge Isolation.
     * Edge fixtures (P11, corrupt store accounts, defaulted loans) must not leak into baseline.
     */
    public function test_edge_scenario_ad_baseline_edge_isolation(): void
    {
        $this->ensureCanonicalBaseline();

        // P11 absent
        $this->assertSame(0, CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count());
        $this->assertSame(0, User::query()->where('email', 'seed.member.blocked@kojaya.test')->count());

        // Over-limit store accounts absent
        $overLimitAccounts = MemberStoreAccount::query()
            ->whereRaw('balance < -credit_limit')
            ->count();
        $this->assertSame(0, $overLimitAccounts, 'No over-limit corrupt store account may exist in baseline.');

        // Defaulted loans absent
        $this->assertSame(0, Loan::query()->whereIn('status', [LoanStatus::Defaulted, LoanStatus::WrittenOff])->count());
    }

    /**
     * Scenario AE: Edge Dataset Readiness and Reversible Separation.
     * Baseline -> with-edge-cases -> baseline transition is clean and deterministic.
     */
    public function test_edge_scenario_ae_edge_dataset_readiness_and_reversible_separation(): void
    {
        // 1. Initial canonical baseline
        $this->ensureCanonicalBaseline();
        $this->assertSame(12, User::query()->where('email', 'like', 'seed.%@kojaya.test')->count());
        $this->assertSame(7, CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-%')->count());
        $this->assertSame(0, CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count());

        // 2. Reset with edge cases
        $edgeExit = Artisan::call('cooperative:reset-test-data', ['--with-edge-cases' => true]);
        $this->assertSame(0, $edgeExit);

        // Baseline remains valid + P11 exactly once
        $this->assertSame(13, User::query()->where('email', 'like', 'seed.%@kojaya.test')->count());
        $this->assertSame(8, CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-%')->count());

        $p11 = CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->firstOrFail();
        $this->assertSame(MemberLifecycleExperience::BlockedUnknown, MemberLifecycleExperience::fromMember($p11));

        // P11 has zero finance
        $this->assertSame(0, MemberStoreAccount::query()->where('cooperative_member_id', $p11->id)->count());
        $this->assertSame(0, Loan::query()->where('cooperative_member_id', $p11->id)->count());
        $this->assertSame(0, CooperativeDuesInvoice::query()->where('cooperative_member_id', $p11->id)->count());

        // 3. Normal reset removes P11 again
        $this->ensureCanonicalBaseline();

        $this->assertSame(12, User::query()->where('email', 'like', 'seed.%@kojaya.test')->count());
        $this->assertSame(7, CooperativeMember::query()->where('member_no', 'like', 'DEV-KOP-%')->count());
        $this->assertSame(0, CooperativeMember::query()->where('member_no', 'DEV-KOP-011')->count());
        $this->assertSame(0, User::query()->where('email', 'seed.member.blocked@kojaya.test')->count());
    }

    // =========================================================================
    // 9. DETERMINISM GATE (Scenarios AF, AG)
    // =========================================================================

    /**
     * Scenario AF: Reset Determinism.
     * Reset #1 snapshot A == Reset #2 snapshot B across all canonical entities.
     */
    public function test_determinism_scenario_af_reset_determinism(): void
    {
        $this->ensureCanonicalBaseline();
        $snapshotA = $this->captureNormalizedSnapshot();

        $this->ensureCanonicalBaseline();
        $snapshotB = $this->captureNormalizedSnapshot();

        $this->assertEqualsCanonicalizing(
            $snapshotA,
            $snapshotB,
            'Consecutive canonical resets must yield identical normalized business state.',
        );
    }

    /**
     * Scenario AG: Dirty State Recovery.
     * Mutating representative values followed by reset restores canonical fixtures.
     */
    public function test_determinism_scenario_ag_dirty_state_recovery(): void
    {
        $this->ensureCanonicalBaseline();

        // Mutate P07 lifecycle metadata
        $p07 = CooperativeMember::query()->where('member_no', 'DEV-KOP-007')->firstOrFail();
        $p07->update(['validation_status' => 'CORRUPT_STATUS', 'validation_notes' => 'Corrupt dirty note']);

        // Mutate P12 store balance
        $p12Store = MemberStoreAccount::query()->whereHas('member', fn ($q) => $q->where('member_no', 'DEV-KOP-012'))->firstOrFail();
        $p12Store->update(['balance' => -9999999]);

        // Mutate a financial record
        $p10Inv = CooperativeDuesInvoice::query()->whereHas('member', fn ($q) => $q->where('member_no', 'DEV-KOP-010'))->firstOrFail();
        $p10Inv->update(['amount' => 888888, 'status' => 'CORRUPTED']);

        // Run reset
        $this->ensureCanonicalBaseline();

        // Verify repaired
        $p07Restored = CooperativeMember::query()->where('member_no', 'DEV-KOP-007')->firstOrFail();
        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $p07Restored->validation_status);

        $p12StoreRestored = MemberStoreAccount::query()->whereHas('member', fn ($q) => $q->where('member_no', 'DEV-KOP-012'))->firstOrFail();
        $this->assertSame(-450000, $p12StoreRestored->balance);

        $p10InvRestored = CooperativeDuesInvoice::query()
            ->whereHas('member', fn ($q) => $q->where('member_no', 'DEV-KOP-010'))
            ->where('period', $p10Inv->period)
            ->firstOrFail();
        $this->assertSame('PAID', $p10InvRestored->status);
        $this->assertNotSame(888888.0, (float) $p10InvRestored->amount);
    }

    // =========================================================================
    // 10. SAFETY GATE (Scenarios AH, AI, AJ, AK, AL, AM, AN, AO)
    // =========================================================================

    /**
     * Scenario AH: Manual Data Preservation.
     * Non-seed manual cooperative records must survive canonical reset untouched.
     */
    public function test_safety_scenario_ah_manual_data_preservation(): void
    {
        $this->ensureCanonicalBaseline();

        $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();

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

        $this->ensureCanonicalBaseline();

        $this->assertDatabaseHas('users', ['id' => $manualUser->id, 'email' => 'manual.member@example.test']);
        $this->assertDatabaseHas('cooperative_members', ['id' => $manualMember->id, 'member_no' => 'MANUAL-KOP-001']);
    }

    /**
     * Scenario AI: ERP Isolation.
     * Unrelated ERP sentinel data must survive reset without spillover.
     */
    public function test_safety_scenario_ai_erp_isolation(): void
    {
        $this->ensureCanonicalBaseline();

        $employee = Employee::factory()->create([
            'employee_code' => 'EMP-SENTINEL-001',
            'first_name' => 'Sentinel',
            'last_name' => 'ERP',
        ]);

        $this->ensureCanonicalBaseline();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'employee_code' => 'EMP-SENTINEL-001']);
    }

    /**
     * Scenario AJ: Operator Reference Preservation.
     * Custom operator-configured reference values must survive reset.
     */
    public function test_safety_scenario_aj_operator_reference_preservation(): void
    {
        $this->ensureCanonicalBaseline();

        $wajib = CooperativeContributionType::query()->where('code', 'WAJIB')->firstOrFail();
        $wajib->update(['default_amount' => 123456]);

        $prodLoanType = LoanType::query()->where('code', 'productive')->firstOrFail();
        $prodLoanType->update(['interest_rate' => 3.50]);

        $this->ensureCanonicalBaseline();

        $wajibFresh = CooperativeContributionType::query()->where('code', 'WAJIB')->firstOrFail();
        $this->assertEquals(123456.0, (float) $wajibFresh->default_amount);

        $loanTypeFresh = LoanType::query()->where('code', 'productive')->firstOrFail();
        $this->assertEquals(3.50, (float) $loanTypeFresh->interest_rate);
    }

    /**
     * Scenario AK: Seeder Registry Integrity.
     * Complete 1:1 classification coverage of all seeders via SeederSafetyRegistry.
     */
    public function test_safety_scenario_ak_seeder_registry_integrity(): void
    {
        // Must execute cleanly with 0 exceptions
        SeederSafetyRegistry::assertCompleteCoverage();
        $this->assertTrue(true, 'SeederSafetyRegistry coverage is complete.');
    }

    /**
     * Scenario AL: Environment Policy Integrity.
     * Canonical profiles map strictly to intended allowed environments.
     */
    public function test_safety_scenario_al_environment_policy_integrity(): void
    {
        $prodSafe = SeederEnvironmentGuard::allowedEnvironmentsFor(SeederExecutionProfile::ProductionSafe);
        $this->assertSame(
            ['production', 'staging', 'qa', 'development', 'local', 'testing', 'playwright'],
            $prodSafe,
        );

        $localTest = SeederEnvironmentGuard::allowedEnvironmentsFor(SeederExecutionProfile::LocalTestFixture);
        $this->assertSame(['local', 'testing', 'playwright'], $localTest);

        $testOnly = SeederEnvironmentGuard::allowedEnvironmentsFor(SeederExecutionProfile::TestOnlyFixture);
        $this->assertSame(['testing', 'playwright'], $testOnly);
    }

    /**
     * Scenario AM: Production Baseline Safety.
     * Running DatabaseSeeder in production creates only safe references and ZERO fixture rows.
     */
    public function test_safety_scenario_am_production_baseline_safety(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        (new DatabaseSeeder)->run();

        // Safe references exist
        $this->assertDatabaseHas('roles', ['name' => 'System Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Pengurus Koperasi']);
        $this->assertDatabaseHas('organizations', ['code' => 'KOP-001']);
        $this->assertDatabaseHas('cooperative_contribution_types', ['code' => 'POKOK']);
        $this->assertDatabaseHas('cooperative_contribution_types', ['code' => 'WAJIB']);

        // ZERO fixture records
        $this->assertSame(0, CooperativeMember::query()->count(), 'No members in production.');
        $this->assertSame(0, User::query()->where('email', 'like', 'seed.%')->count(), 'No seed users in production.');
        $this->assertSame(0, SocialAccount::query()->count(), 'No social accounts in production.');
        $this->assertSame(0, PosTransaction::query()->count(), 'No POS transactions in production.');
        $this->assertSame(0, PosProduct::query()->count(), 'No POS products in production.');
        $this->assertSame(0, Loan::query()->count(), 'No loans in production.');
        $this->assertSame(0, CooperativeDuesInvoice::query()->count(), 'No dues invoices in production.');
        $this->assertSame(0, CooperativePayment::query()->count(), 'No payments in production.');
        $this->assertSame(0, CooperativeReceipt::query()->count(), 'No receipts in production.');
        $this->assertSame(0, MemberStoreAccount::query()->count(), 'No store accounts in production.');
    }

    /**
     * Scenario AN: Environment Mismatch Gate.
     * Mismatched runtime != config fails closed before any mutation.
     */
    public function test_safety_scenario_an_environment_mismatch_gate(): void
    {
        $this->app['env'] = 'testing';
        config(['app.env' => 'production']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Seeder environment mismatch detected');

        SeederEnvironmentGuard::assertEnvironmentConsistency();
    }

    /**
     * Scenario AO: Direct Fixture Production Denial.
     * Direct invocation of non-production seeders under production fails closed.
     */
    public function test_safety_scenario_ao_direct_fixture_production_denial(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $disallowed = [
            CooperativePersonaSeeder::class,
            CooperativeFinancialFixtureSeeder::class,
            CooperativeEdgeCaseFixtureSeeder::class,
        ];

        foreach ($disallowed as $seederClass) {
            $thrown = false;
            try {
                (new $seederClass)->run();
            } catch (LogicException $e) {
                $thrown = true;
                $this->assertStringContainsString('is only available in', $e->getMessage());
            }

            $this->assertTrue($thrown, "Expected {$seederClass} to fail closed under production.");
        }
    }

    // =========================================================================
    // HELPER: Deterministic Normalized Snapshot Capture
    // =========================================================================

    /**
     * Capture a normalized business snapshot strictly using natural keys and business attributes.
     *
     * @return array<string, mixed>
     */
    private function captureNormalizedSnapshot(): array
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
                    'tanggal_aktif', 'sso_provider',
                ])
                ->toArray(),
            'social_accounts' => SocialAccount::query()
                ->where('provider_id', 'like', 'google-seed-%')
                ->orderBy('provider_id')
                ->get(['provider', 'provider_id', 'provider_email'])
                ->toArray(),
            'dues_invoices' => CooperativeDuesInvoice::query()
                ->whereHas('member', fn ($q) => $q->where('member_no', 'like', 'DEV-KOP-%'))
                ->orderBy('period')
                ->get(['period', 'amount', 'paid_amount', 'status', 'due_date'])
                ->toArray(),
            'payments' => CooperativePayment::query()
                ->where('reference_no', 'like', 'SEED-PAY-%')
                ->orderBy('reference_no')
                ->get(['reference_no', 'amount', 'payment_method', 'status', 'receipt_no'])
                ->toArray(),
            'receipts' => CooperativeReceipt::query()
                ->where('receipt_no', 'like', 'SEED-RC-%')
                ->orderBy('receipt_no')
                ->get(['receipt_no', 'pdf_path'])
                ->toArray(),
            'cooperative_ledger' => CooperativeLedgerEntry::query()
                ->whereHas('member', fn ($q) => $q->where('member_no', 'like', 'DEV-KOP-%'))
                ->orderBy('period')
                ->orderBy('description')
                ->get(['entry_type', 'ledger_scope', 'debit', 'credit', 'period', 'description'])
                ->toArray(),
            'store_accounts' => MemberStoreAccount::query()
                ->whereHas('member', fn ($q) => $q->where('member_no', 'like', 'DEV-KOP-%'))
                ->get(['balance', 'credit_limit', 'status'])
                ->toArray(),
            'store_ledger' => MemberStoreLedgerEntry::query()
                ->where('idempotency_key', 'like', 'seed-store-ledger:%')
                ->orderBy('idempotency_key')
                ->get(['entry_type', 'amount', 'effect', 'balance_before', 'balance_after', 'idempotency_key'])
                ->toArray(),
            'loans' => Loan::query()
                ->where('reference_no', 'like', 'SEED-LOAN-%')
                ->orderBy('reference_no')
                ->get(['reference_no', 'principal_amount', 'term_months', 'status', 'total_amount', 'outstanding_amount'])
                ->toArray(),
            'loan_installments' => LoanInstallment::query()
                ->whereHas('loan', fn ($q) => $q->where('reference_no', 'like', 'SEED-LOAN-%'))
                ->orderBy('installment_no')
                ->get(['installment_no', 'principal_amount', 'interest_amount', 'amount_due', 'amount_paid', 'status'])
                ->toArray(),
            'loan_payments' => LoanPayment::query()
                ->where('reference_no', 'like', 'SEED-LOAN-PAY-%')
                ->orderBy('reference_no')
                ->get(['reference_no', 'amount', 'principal_amount', 'interest_amount', 'status'])
                ->toArray(),
            'pos_transactions' => PosTransaction::query()
                ->where('transaction_no', 'like', 'SEED-POS-TX-%')
                ->orderBy('transaction_no')
                ->get(['transaction_no', 'client_reference', 'subtotal', 'total_amount', 'status'])
                ->toArray(),
            'pos_items' => PosTransactionItem::query()
                ->whereHas('transaction', fn ($q) => $q->where('transaction_no', 'like', 'SEED-POS-TX-%'))
                ->orderBy('unit_price')
                ->get(['quantity', 'unit_price', 'line_total'])
                ->toArray(),
            'pos_payments' => PosPayment::query()
                ->where('reference_no', 'like', 'SEED-POS-PAY-%')
                ->orderBy('reference_no')
                ->get(['reference_no', 'payment_method', 'amount'])
                ->toArray(),
            'pos_products' => PosProduct::query()
                ->where('sku', 'like', 'SEED-POS-%')
                ->orderBy('sku')
                ->get(['sku', 'stock', 'sale_price', 'is_active'])
                ->toArray(),
        ];
    }
}
