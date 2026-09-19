<?php

namespace Tests\Feature;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeMember;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use Database\Seeders\AnggotaSeeder;
use Database\Seeders\CooperativeFixtureReferenceSeeder;
use Database\Seeders\CooperativeSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CooperativeReferenceSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario A: Fresh reference creation from empty database produces expected canonical defaults.
     */
    public function test_fresh_production_safe_reference_seeding_creates_canonical_defaults(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        (new DatabaseSeeder)->run();

        // 1. KOP-001 organization anchor
        $kop = Organization::query()->where('code', 'KOP-001')->first();
        $this->assertNotNull($kop);
        $this->assertSame('Koperasi Jaya Bersama', $kop->name);
        $this->assertSame('L0', $kop->level);
        $this->assertSame('HEAD_OFFICE', $kop->type);
        $this->assertNull($kop->parent_id);
        $this->assertTrue((bool) $kop->is_active);

        // 2. Contribution types
        $expectedContributionTypes = [
            'POKOK' => ['name' => 'Simpanan Pokok', 'default_amount' => 200000.0, 'frequency' => 'ONCE'],
            'WAJIB' => ['name' => 'Simpanan Wajib', 'default_amount' => 100000.0, 'frequency' => 'MONTHLY'],
            'SUKARELA' => ['name' => 'Simpanan Sukarela', 'default_amount' => 0.0, 'frequency' => 'ADHOC'],
            'KHUSUS' => ['name' => 'Simpanan Khusus', 'default_amount' => 0.0, 'frequency' => 'ADHOC'],
        ];

        foreach ($expectedContributionTypes as $code => $expected) {
            $type = CooperativeContributionType::query()->where('code', $code)->first();
            $this->assertNotNull($type, "Contribution type {$code} must exist.");
            $this->assertSame($expected['name'], $type->name);
            $this->assertSame($expected['default_amount'], (float) $type->default_amount);
            $this->assertSame($expected['frequency'], $type->frequency);
            $this->assertTrue((bool) $type->is_active);
        }

        // 3. POS categories
        $expectedPosCategories = ['sembako', 'minuman', 'atk', 'espresso', 'signature', 'non-coffee'];
        foreach ($expectedPosCategories as $slug) {
            $this->assertDatabaseHas('pos_categories', ['slug' => $slug, 'is_active' => true]);
        }

        // 4. Loan types
        $expectedLoanTypes = ['emergency', 'productive', 'consumer'];
        foreach ($expectedLoanTypes as $code) {
            $this->assertDatabaseHas('loan_types', ['code' => $code, 'is_active' => true]);
        }

        // 5. Canonical roles
        $expectedRoles = [
            'System Admin',
            'Pengurus Koperasi',
            'Manajer Koperasi',
            'Admin Koperasi',
            'Kasir Koperasi',
            'Anggota',
        ];
        foreach ($expectedRoles as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }
    }

    /**
     * Scenario B: Rerunning reference seeding is completely idempotent.
     */
    public function test_production_safe_reference_seeding_is_idempotent(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        (new DatabaseSeeder)->run();

        $baseline = [
            'organizations' => Organization::query()->count(),
            'contribution_types' => CooperativeContributionType::query()->count(),
            'pos_categories' => PosCategory::query()->count(),
            'loan_types' => LoanType::query()->count(),
            'roles' => Role::query()->count(),
            'permissions' => Permission::query()->count(),
        ];

        (new DatabaseSeeder)->run();

        $afterSecondRun = [
            'organizations' => Organization::query()->count(),
            'contribution_types' => CooperativeContributionType::query()->count(),
            'pos_categories' => PosCategory::query()->count(),
            'loan_types' => LoanType::query()->count(),
            'roles' => Role::query()->count(),
            'permissions' => Permission::query()->count(),
        ];

        $this->assertSame($baseline, $afterSecondRun, 'Seeding reference data twice must yield identical record counts.');
        $this->assertSame(1, Organization::query()->where('code', 'KOP-001')->count());
        $this->assertSame(1, CooperativeContributionType::query()->where('code', 'POKOK')->count());
        $this->assertSame(1, PosCategory::query()->where('slug', 'sembako')->count());
        $this->assertSame(1, LoanType::query()->where('code', 'emergency')->count());
    }

    /**
     * Scenario C: Existing operator configurations are strictly preserved.
     */
    public function test_production_safe_reference_seeding_preserves_operator_configurations(): void
    {
        $customOrg = Organization::factory()->create([
            'code' => 'KOP-001',
            'name' => 'Koperasi Operator Khusus',
            'phone' => '021-998877',
            'email' => 'operator@koperasikhusus.id',
            'address' => 'Jl. Operator Khusus No. 10',
            'is_active' => true,
        ]);

        $customPokok = CooperativeContributionType::factory()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok Custom 350K',
            'default_amount' => 350000,
            'is_active' => false,
        ]);

        $customEmergencyLoan = LoanType::query()->create([
            'code' => 'emergency',
            'name' => 'Pinjaman Darurat Kustom Pengurus',
            'description' => 'Deskripsi kustom pengurus',
            'interest_rate' => 2.50,
            'admin_fee' => 25000,
            'late_fee_per_day' => 5000,
            'min_amount' => 800000,
            'max_amount' => 10000000,
            'min_term_months' => 2,
            'max_term_months' => 12,
            'is_active' => false,
        ]);

        $customSembako = PosCategory::query()->create([
            'slug' => 'sembako',
            'name' => 'Sembako & Kebutuhan Rumah Tangga',
            'is_active' => false,
        ]);

        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        (new DatabaseSeeder)->run();

        $customOrg->refresh();
        $this->assertSame('Koperasi Operator Khusus', $customOrg->name);
        $this->assertSame('021-998877', $customOrg->phone);
        $this->assertSame('operator@koperasikhusus.id', $customOrg->email);

        $customPokok->refresh();
        $this->assertSame('Simpanan Pokok Custom 350K', $customPokok->name);
        $this->assertSame(350000.0, (float) $customPokok->default_amount);
        $this->assertFalse((bool) $customPokok->is_active);

        $customEmergencyLoan->refresh();
        $this->assertSame('Pinjaman Darurat Kustom Pengurus', $customEmergencyLoan->name);
        $this->assertSame(2.50, (float) $customEmergencyLoan->interest_rate);
        $this->assertSame(800000.0, (float) $customEmergencyLoan->min_amount);
        $this->assertSame(10000000.0, (float) $customEmergencyLoan->max_amount);
        $this->assertFalse((bool) $customEmergencyLoan->is_active);

        $customSembako->refresh();
        $this->assertSame('Sembako & Kebutuhan Rumah Tangga', $customSembako->name);
        $this->assertFalse((bool) $customSembako->is_active);
    }

    /**
     * Scenario D1: DatabaseSeeder under production does not create test fixtures or tenants.
     */
    public function test_database_seeder_under_production_does_not_create_test_fixtures_or_tenants(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        (new DatabaseSeeder)->run();

        $this->assertDatabaseHas('organizations', ['code' => 'KOP-001']);
        $this->assertSame(0, User::query()->count(), 'Zero users must exist in production.');
        $this->assertSame(0, CooperativeMember::query()->count(), 'Zero members must exist in production.');
        $this->assertSame(0, PosTransaction::query()->count(), 'Zero POS transactions must exist in production.');
        $this->assertSame(0, PosProduct::query()->count(), 'Zero POS products must exist in production.');

        $this->assertDatabaseMissing('organizations', ['code' => 'KBU-001']);
        $this->assertDatabaseMissing('organizations', ['code' => 'ISO-999']);
    }

    /**
     * Scenario D2: DatabaseSeeder under staging does not create test fixtures or tenants.
     */
    public function test_database_seeder_under_staging_does_not_create_test_fixtures_or_tenants(): void
    {
        $this->app['env'] = 'staging';
        config(['app.env' => 'staging']);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('organizations', ['code' => 'KOP-001']);
        $this->assertSame(0, User::query()->count(), 'Zero users must exist in staging.');
        $this->assertSame(0, CooperativeMember::query()->count(), 'Zero members must exist in staging.');
        $this->assertSame(0, PosTransaction::query()->count(), 'Zero POS transactions must exist in staging.');
        $this->assertSame(0, PosProduct::query()->count(), 'Zero POS products must exist in staging.');

        $this->assertDatabaseMissing('organizations', ['code' => 'KBU-001']);
        $this->assertDatabaseMissing('organizations', ['code' => 'ISO-999']);
    }

    /**
     * Scenario E: CooperativeFixtureReferenceSeeder establishes deterministic topology in non-production.
     */
    public function test_cooperative_fixture_reference_seeder_creates_deterministic_topology_under_testing(): void
    {
        config(['app.env' => 'testing']);

        $this->seed(CooperativeFixtureReferenceSeeder::class);

        $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $this->assertSame('L0', $kop->level);
        $this->assertSame('HEAD_OFFICE', $kop->type);
        $this->assertNull($kop->parent_id);

        $kbu = Organization::query()->where('code', 'KBU-001')->firstOrFail();
        $this->assertSame('PT Koperasi Berkah Usaha', $kbu->name);
        $this->assertSame($kop->id, $kbu->parent_id);
        $this->assertSame('L1', $kbu->level);
        $this->assertSame('BRANCH', $kbu->type);

        $iso = Organization::query()->where('code', 'ISO-999')->firstOrFail();
        $this->assertSame('Koperasi Mandiri Sejahtera', $iso->name);
        $this->assertNull($iso->parent_id);
        $this->assertSame('L0', $iso->level);
        $this->assertSame('HEAD_OFFICE', $iso->type);
    }

    /**
     * Scenario F: CooperativeFixtureReferenceSeeder fails closed outside allowed environments with zero persistence.
     */
    public function test_cooperative_fixture_reference_seeder_fails_closed_outside_allowed_environments(): void
    {
        foreach (['production', 'staging', 'qa', 'development'] as $environment) {
            config(['app.env' => $environment]);
            $thrown = false;

            try {
                (new CooperativeFixtureReferenceSeeder)->run();
            } catch (LogicException $exception) {
                $thrown = true;
                $this->assertStringContainsString('is only available in local, testing, or playwright environments', $exception->getMessage());
            }

            $this->assertTrue($thrown, "CooperativeFixtureReferenceSeeder must throw LogicException in {$environment}.");
            $this->assertDatabaseMissing('organizations', ['code' => 'KBU-001']);
            $this->assertDatabaseMissing('organizations', ['code' => 'ISO-999']);
        }
    }

    /**
     * Scenario G: CooperativeFixtureReferenceSeeder is idempotent when rerun.
     */
    public function test_cooperative_fixture_reference_seeder_is_idempotent(): void
    {
        config(['app.env' => 'testing']);

        (new CooperativeFixtureReferenceSeeder)->run();

        $this->assertSame(1, Organization::query()->where('code', 'KOP-001')->count());
        $this->assertSame(1, Organization::query()->where('code', 'KBU-001')->count());
        $this->assertSame(1, Organization::query()->where('code', 'ISO-999')->count());

        (new CooperativeFixtureReferenceSeeder)->run();

        $this->assertSame(1, Organization::query()->where('code', 'KOP-001')->count());
        $this->assertSame(1, Organization::query()->where('code', 'KBU-001')->count());
        $this->assertSame(1, Organization::query()->where('code', 'ISO-999')->count());

        $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $kbu = Organization::query()->where('code', 'KBU-001')->firstOrFail();
        $iso = Organization::query()->where('code', 'ISO-999')->firstOrFail();

        $this->assertSame($kop->id, $kbu->parent_id);
        $this->assertNull($iso->parent_id);
    }

    /**
     * Scenario H: Cooperative and subsidiary semantic integrity.
     * Only the cooperative legal entity (KOP-001) may own CooperativeMember fixtures.
     * PT Subsidiary (KBU-001) and isolated organization (ISO-999) must have ZERO CooperativeMember records.
     */
    public function test_cooperative_and_subsidiary_organization_semantic_integrity(): void
    {
        config(['app.env' => 'testing']);

        $this->seed(CooperativeFixtureReferenceSeeder::class);

        $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $kbu = Organization::query()->where('code', 'KBU-001')->firstOrFail();
        $iso = Organization::query()->where('code', 'ISO-999')->firstOrFail();

        // After reference seeder, members count across all orgs is 0
        $this->assertSame(0, CooperativeMember::query()->where('organization_id', $kbu->id)->count());
        $this->assertSame(0, CooperativeMember::query()->where('organization_id', $iso->id)->count());
        $this->assertSame(0, CooperativeMember::query()->where('organization_id', $kop->id)->count());

        // Run demo seeders to populate sample cooperative data
        $this->fakeCooperativeReceiptIssuance();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(CooperativeSeeder::class);
        $this->seed(AnggotaSeeder::class);

        $totalMembers = CooperativeMember::query()->count();
        $kopMembers = CooperativeMember::query()->where('organization_id', $kop->id)->count();
        $kbuMembers = CooperativeMember::query()->where('organization_id', $kbu->id)->count();
        $isoMembers = CooperativeMember::query()->where('organization_id', $iso->id)->count();

        // 1. Members exist under cooperative legal entity (KOP-001)
        $this->assertGreaterThan(0, $kopMembers, 'KOP-001 must own cooperative members.');
        $this->assertSame($totalMembers, $kopMembers, 'All cooperative members must belong exclusively to KOP-001.');

        // 2. KBU-001 (PT Subsidiary) must have ZERO CooperativeMember records
        $this->assertSame(0, $kbuMembers, 'KBU-001 (PT Subsidiary) must contain ZERO CooperativeMember fixtures.');

        // 3. ISO-999 (Isolated Org) must have ZERO CooperativeMember records
        $this->assertSame(0, $isoMembers, 'ISO-999 (Isolated Org) must contain ZERO CooperativeMember fixtures.');

        // 4. No member number namespace may be assigned to KBU-001
        $kbuMemberNoCount = CooperativeMember::query()
            ->where('no_anggota', 'like', '%KBU%')
            ->orWhere('member_no', 'like', '%KBU%')
            ->count();
        $this->assertSame(0, $kbuMemberNoCount, 'No member-number namespace may be assigned to KBU-001.');
    }
}
