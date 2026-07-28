<?php

namespace Tests\Feature\Cooperative;

use App\Enums\CooperativeShuPeriodStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\CooperativeShuPeriod;
use App\Models\Organization;
use App\Models\PointTransaction;
use App\Models\PosCategory;
use App\Models\PosMemberPoint;
use App\Models\PosProduct;
use App\Models\PosStockMovement;
use App\Models\User;
use App\Services\Cooperative\AnnualShuDistributionService;
use App\Services\Cooperative\DuesGenerationService;
use App\Services\Cooperative\PointService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CooperativeFeatureTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_member_creation_always_uses_cooperative_head_office(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $branch = Organization::query()->create([
            'id' => fake()->uuid(),
            'code' => 'KOP-999',
            'name' => 'Cabang Test',
            'level' => 'L1',
            'type' => 'BRANCH',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cooperative.members.store'), [
            'name' => 'Anggota Test',
            'nama_anggota' => 'Anggota Test',
            'email' => 'anggota@test.local',
            'phone' => '08123',
            'tanggal_aktif' => '2026-05-01',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
        ])->assertRedirect(route('cooperative.members.index'));

        $member = CooperativeMember::query()->where('email', 'anggota@test.local')->firstOrFail();

        $this->assertSame('KOP-001', $member->organization->code);
        $this->assertNotSame($branch->id, $member->organization_id);
    }

    public function test_member_creation_starts_pending_and_generates_opening_balance(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $pokok = CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cooperative.members.store'), [
            'name' => 'Anggota Login',
            'nama_anggota' => 'Anggota Login',
            'email' => 'anggota-login@test.local',
            'phone' => '08123',
            'tanggal_aktif' => '2026-05-01',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
        ])->assertRedirect(route('cooperative.members.index'));

        $member = CooperativeMember::query()->where('email', 'anggota-login@test.local')->firstOrFail();

        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->validation_status);
        $this->assertNull($member->user_id);
        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $pokok->id,
            'period' => '2026-05',
            'amount' => 200000,
            'status' => 'UNPAID',
        ]);
    }

    public function test_member_creation_with_opening_balance_redirects_to_wizard_for_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $response = $this->actingAs($user)->post(route('cooperative.members.store'), [
            'name' => 'Anggota Migrasi',
            'nama_anggota' => 'Anggota Migrasi',
            'email' => 'anggota-migrasi@test.local',
            'phone' => '08123',
            'tanggal_aktif' => '2020-01-01',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
            'opening_saving_balance' => 125000,
        ]);

        $member = CooperativeMember::query()->where('email', 'anggota-migrasi@test.local')->firstOrFail();

        // Admin dengan permission wizard akan dialihkan ke wizard,
        // bukan langsung menulis ledger legacy.
        $response->assertRedirect(route('cooperative.members.opening-balance.show', $member));
        $this->assertNull($member->activeOpeningBalanceBatch());
        $this->assertSame(
            0,
            $member->ledgerEntries()->where('entry_type', 'OPENING_BALANCE')->count(),
            'Admin dengan manage_cooperative_opening_balance harusnya tidak menulis ledger langsung.'
        );
    }

    public function test_member_creation_without_wizard_permission_does_not_write_legacy_opening_balance(): void
    {
        $this->seed(RolePermissionSeeder::class);
        // Role ad-hoc boleh create anggota, tetapi saldo awal hanya boleh
        // ditulis melalui wizard.
        $organization = app(\App\Services\Cooperative\CooperativeHeadOfficeResolver::class)->resolve();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $legacyRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Legacy Opening Balance Creator']);
        $legacyRole->syncPermissions([
            'view_cooperative_member',
            'manage_cooperative_member',
        ]);
        $user->assignRole($legacyRole);

        $this->actingAs($user)->post(route('cooperative.members.store'), [
            'name' => 'Anggota Tanpa Wizard',
            'nama_anggota' => 'Anggota Tanpa Wizard',
            'email' => 'anggota-no-wizard@test.local',
            'phone' => '08123',
            'tanggal_aktif' => '2020-01-01',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
            'opening_saving_balance' => 75000,
        ])->assertRedirect();

        $member = CooperativeMember::query()->where('email', 'anggota-no-wizard@test.local')->firstOrFail();

        $this->assertDatabaseMissing('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'OPENING_BALANCE',
        ]);
    }

    public function test_member_creation_does_not_link_existing_user_without_dedicated_action(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = app(\App\Services\Cooperative\CooperativeHeadOfficeResolver::class)->resolve();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('System Admin');
        $existingUser = User::factory()->create([
            'email' => 'existing-member@test.local',
            'password' => Hash::make('old-password'),
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($admin)->post(route('cooperative.members.store'), [
            'name' => 'Existing Member',
            'nama_anggota' => 'Existing Member',
            'email' => 'existing-member@test.local',
            'user_id' => $existingUser->id,
            'tanggal_aktif' => '2026-05-01',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
        ])->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('cooperative_members', ['email' => 'existing-member@test.local']);
        $this->assertTrue(Hash::check('old-password', $existingUser->password));
    }

    public function test_user_management_loads_and_searches_cooperative_member_links(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        $memberUser = User::factory()->create(['email' => 'member-user@test.local']);
        $memberUser->assignRole('Anggota');
        $member = $this->member([
            'user_id' => $memberUser->id,
            'member_no' => 'KOP-SEARCH-001',
            'name' => 'Member Searchable',
            'email' => 'member-user@test.local',
        ]);

        $this->actingAs($admin)
            ->get(route('users.index', ['search' => 'KOP-SEARCH-001']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Index')
                ->where('users.data.0.cooperative_member.id', $member->id)
                ->where('users.data.0.cooperative_member.member_no', 'KOP-SEARCH-001')
            );
    }

    public function test_member_update_with_opening_balance_does_not_write_legacy_entries(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        // Buat role ad-hoc boleh update anggota, tetapi tidak boleh menulis
        // saldo awal lewat jalur legacy.
        $legacyRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Legacy Opening Balance Tester']);
        $legacyRole->syncPermissions([
            'view_cooperative_member',
            'manage_cooperative_member',
        ]);
        $admin->assignRole($legacyRole);
        $member = $this->member([
            'name' => 'Opening Balance',
            'email' => 'opening@test.local',
        ]);
        $admin->forceFill(['organization_id' => $member->organization_id])->save();

        $this->actingAs($admin)->put(route('cooperative.members.update', $member), [
            'name' => 'Opening Balance',
            'nama_anggota' => 'Opening Balance',
            'email' => 'opening@test.local',
            'phone' => '08123',
            'no_anggota' => $member->no_anggota,
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
            'opening_saving_balance' => 150000,
        ])->assertRedirect();

        $this->actingAs($admin)->put(route('cooperative.members.update', $member), [
            'name' => 'Opening Balance',
            'nama_anggota' => 'Opening Balance',
            'email' => 'opening@test.local',
            'phone' => '08123',
            'no_anggota' => $member->no_anggota,
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
            'opening_saving_balance' => 200000,
        ])->assertRedirect();

        $this->assertSame(0, $member->ledgerEntries()->where('entry_type', 'OPENING_BALANCE')->count());
        $this->assertDatabaseMissing('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'OPENING_BALANCE',
        ]);
    }

    public function test_member_can_be_activated_and_resigned(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member([
            'status' => CooperativeMember::VALIDATION_INACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_INACTIVE,
            'joined_at' => null,
        ]);

        $this->actingAs($user)->post(route('cooperative.members.activate', $member))->assertRedirect();
        $this->assertSame('ACTIVE', $member->refresh()->status);

        $this->actingAs($user)->post(route('cooperative.members.resign', $member))->assertRedirect();
        $this->assertSame('RESIGNED', $member->refresh()->status);
        $this->assertNotNull($member->resigned_at);
    }

    public function test_active_member_can_be_deactivated_without_resignation_guards(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member([
            'status' => CooperativeMember::VALIDATION_ACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 100000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);

        $this->actingAs($user)
            ->post(route('cooperative.members.deactivate', $member))
            ->assertRedirect();

        $this->assertSame('INACTIVE', $member->refresh()->status);
        $this->assertNull($member->resigned_at);
    }

    public function test_dues_generation_is_idempotent(): void
    {
        $member = $this->member(['status' => 'ACTIVE']);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $service = app(DuesGenerationService::class);

        $this->assertSame(1, $service->generateForPeriod('2026-05'));
        $this->assertSame(0, $service->generateForPeriod('2026-05'));
        $this->assertDatabaseCount('cooperative_dues_invoices', 1);
        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'period' => '2026-05',
            'status' => 'UNPAID',
        ]);
    }

    public function test_dues_generation_skips_periods_before_member_join_month(): void
    {
        $member = $this->member([
            'status' => 'ACTIVE',
            'tanggal_aktif' => '2026-06-15',
            'joined_at' => '2026-06-15',
        ]);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $service = app(DuesGenerationService::class);

        $this->assertSame(0, $service->generateForPeriod('2026-05'));
        $this->assertSame(1, $service->generateForPeriod('2026-06'));

        $this->assertDatabaseMissing('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'period' => '2026-05',
        ]);
        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'period' => '2026-06',
            'status' => 'UNPAID',
        ]);
    }

    public function test_dues_generation_prunes_unpaid_invoices_before_member_join_month(): void
    {
        $member = $this->member([
            'status' => 'ACTIVE',
            'tanggal_aktif' => '2026-06-01',
            'joined_at' => '2026-06-01',
        ]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $invalidInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => '2026-05-10',
            'status' => 'UNPAID',
        ]);

        $this->assertSame(0, app(DuesGenerationService::class)->generateForPeriod('2026-05'));

        $this->assertSoftDeleted('cooperative_dues_invoices', [
            'id' => $invalidInvoice->id,
        ]);
    }

    public function test_dues_page_lists_invoices_after_explicit_generation(): void
    {
        Carbon::setTestNow('2026-05-15 09:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $user->update(['organization_id' => $member->organization_id]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cooperative.dues.generate'), [
            'period' => '2026-05',
        ])->assertRedirect();

        $this->actingAs($user)
            ->get(route('cooperative.dues.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Dues/Index')
                ->where('filters.period', '2026-05')
                ->where('filters.status', '')
                ->where('monthlyDuesInfo.title', 'Simpanan Wajib Mei 2026')
                ->where('monthlyDuesInfo.amount', 50000)
                ->where('monthlyDuesInfo.due_date', '2026-05-10')
                ->has('invoices.data', 1)
            );

        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'status' => 'UNPAID',
        ]);
    }

    public function test_dues_page_does_not_backbill_members_before_join_month(): void
    {
        Carbon::setTestNow('2026-06-13 09:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $member = $this->member([
            'status' => 'ACTIVE',
            'tanggal_aktif' => '2026-06-01',
            'joined_at' => '2026-06-01',
        ]);
        $user->update(['organization_id' => $member->organization_id]);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cooperative.dues.generate'), [
            'period' => '2026-05',
        ])->assertRedirect();

        $this->actingAs($user)
            ->get(route('cooperative.dues.index', ['period' => '2026-05']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Dues/Index')
                ->where('filters.period', '2026-05')
                ->has('invoices.data', 0)
            );

        $this->assertDatabaseMissing('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'period' => '2026-05',
        ]);
    }

    public function test_dues_page_shows_monthly_wajib_info_for_selected_period(): void
    {
        Carbon::setTestNow('2026-06-13 09:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $user->update(['organization_id' => $member->organization_id]);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cooperative.dues.generate'), [
            'period' => '2026-06',
        ])->assertRedirect();

        $this->actingAs($user)
            ->get(route('cooperative.dues.index', ['period' => '2026-06']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Dues/Index')
                ->where('monthlyDuesInfo.title', 'Simpanan Wajib Juni 2026')
                ->where('monthlyDuesInfo.period_label', 'Juni 2026')
                ->where('monthlyDuesInfo.next_period_label', 'Juli 2026')
                ->where('monthlyDuesInfo.amount', 100000)
                ->where('monthlyDuesInfo.total_invoices', 1)
            );
    }

    public function test_dues_page_defaults_to_all_statuses_for_the_period(): void
    {
        Carbon::setTestNow('2026-05-15 09:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $paidMember = $this->member(['status' => 'ACTIVE']);
        $unpaidMember = $this->member(['status' => 'ACTIVE']);
        $partialMember = $this->member(['status' => 'ACTIVE']);
        $user->update(['organization_id' => $paidMember->organization_id]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $paidMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 50000,
            'status' => 'PAID',
        ]);
        $unpaidInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $unpaidMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
        $partialInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $partialMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 25000,
            'status' => 'PARTIAL',
        ]);

        $this->actingAs($user)
            ->get(route('cooperative.dues.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Dues/Index')
                ->where('filters.status', '')
                ->has('invoices.data', 3)
                ->where('invoices.data.0.id', $partialInvoice->id)
                ->where('invoices.data.1.id', $unpaidInvoice->id)
                ->where('invoices.data.2.status', 'PAID')
            );
    }

    public function test_dues_page_can_show_open_invoices_across_all_periods(): void
    {
        Carbon::setTestNow('2026-05-15 09:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $user->update(['organization_id' => $member->organization_id]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $oldUnpaid = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-03',
            'amount' => 100000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
        $currentPartial = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 100000,
            'paid_amount' => 50000,
            'status' => 'PARTIAL',
        ]);
        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-04',
            'amount' => 100000,
            'paid_amount' => 100000,
            'status' => 'PAID',
        ]);

        $this->actingAs($user)
            ->get(route('cooperative.dues.index', [
                'period' => '2026-05',
                'period_scope' => 'all',
                'status' => 'OPEN',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Dues/Index')
                ->where('filters.period', '2026-05')
                ->where('filters.period_scope', 'all')
                ->where('filters.status', 'OPEN')
                ->where('stats.total_invoices', 2)
                ->where('stats.total_outstanding', 150000)
                ->has('invoices.data', 2)
                ->where('invoices.data.0.id', $currentPartial->id)
                ->where('invoices.data.1.id', $oldUnpaid->id)
            );
    }

    public function test_dues_page_hides_invoices_for_soft_deleted_members(): void
    {
        Carbon::setTestNow('2026-05-15 09:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $activeMember = $this->member(['status' => 'ACTIVE']);
        $deletedMember = $this->member(['status' => 'ACTIVE']);
        $user->update(['organization_id' => $activeMember->organization_id]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $visibleInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $activeMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $deletedMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);

        $deletedMember->delete();

        $this->actingAs($user)
            ->get(route('cooperative.dues.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Dues/Index')
                ->has('invoices.data', 1)
                ->where('invoices.data.0.id', $visibleInvoice->id)
            );
    }

    public function test_dues_page_filters_members_by_partial_name_or_member_number(): void
    {
        Carbon::setTestNow('2026-05-15 09:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $matchingMember = $this->member([
            'status' => 'ACTIVE',
            'member_no' => 'KOP-2026-7788',
            'no_anggota' => '7788',
            'name' => 'Budi Santoso',
            'nama_anggota' => 'Budi Santoso',
        ]);
        $otherMember = $this->member([
            'status' => 'ACTIVE',
            'member_no' => 'KOP-2026-9900',
            'no_anggota' => '9900',
            'name' => 'Siti Aminah',
            'nama_anggota' => 'Siti Aminah',
        ]);
        $user->update(['organization_id' => $matchingMember->organization_id]);

        CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cooperative.dues.generate'), [
            'period' => '2026-05',
        ])->assertRedirect();

        $this->actingAs($user)
            ->get(route('cooperative.dues.index', ['member_search' => 'sAnT']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Dues/Index')
                ->where('filters.member_search', 'sAnT')
                ->has('invoices.data', 1)
                ->where('invoices.data.0.cooperative_member_id', $matchingMember->id)
            );

        $this->actingAs($user)
            ->get(route('cooperative.dues.index', ['member_search' => '990']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Dues/Index')
                ->where('filters.member_search', '990')
                ->has('invoices.data', 1)
                ->where('invoices.data.0.cooperative_member_id', $otherMember->id)
            );
    }

    public function test_ledger_page_filters_members_by_partial_name_or_member_number_case_insensitively(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = app(\App\Services\Cooperative\CooperativeHeadOfficeResolver::class)->resolve();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        $matchingMember = $this->member([
            'status' => 'ACTIVE',
            'member_no' => 'KOP-2026-7788',
            'no_anggota' => '7788',
            'name' => 'Budi Santoso',
            'nama_anggota' => 'Budi Santoso',
        ]);
        $otherMember = $this->member([
            'status' => 'ACTIVE',
            'member_no' => 'KOP-2026-9900',
            'no_anggota' => '9900',
            'name' => 'Siti Aminah',
            'nama_anggota' => 'Siti Aminah',
        ]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $matchingMember->id,
            'organization_id' => $organization->id,
            'cooperative_contribution_type_id' => $type->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'WAJIB',
            'debit' => 0,
            'credit' => 50000,
            'posted_at' => '2026-05-10',
        ]);
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $otherMember->id,
            'organization_id' => $organization->id,
            'cooperative_contribution_type_id' => $type->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'WAJIB',
            'debit' => 0,
            'credit' => 50000,
            'posted_at' => '2026-05-11',
        ]);

        $this->actingAs($user)
            ->get(route('cooperative.ledger.index', ['member_search' => 'sAnT']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Ledger/Index')
                ->where('filters.member_search', 'sAnT')
                ->has('entries.data', 1)
                ->where('entries.data.0.cooperative_member_id', $matchingMember->id)
            );

        $this->actingAs($user)
            ->get(route('cooperative.ledger.index', ['member_search' => '990']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Ledger/Index')
                ->where('filters.member_search', '990')
                ->has('entries.data', 1)
                ->where('entries.data.0.cooperative_member_id', $otherMember->id)
            );
    }

    public function test_system_admin_can_cancel_payment_from_ledger(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 50000,
            'status' => 'PAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $type->id,
            'user_id' => $user->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
            'status' => 'APPROVED',
            'receipt_no' => 'RC-202605-000009',
            'receipt_issued_at' => now(),
        ]);
        $entry = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_payment_id' => $payment->id,
            'cooperative_contribution_type_id' => $type->id,
            'source_type' => CooperativePayment::class,
            'source_id' => $payment->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'WAJIB',
            'debit' => 0,
            'credit' => 50000,
            'period' => '2026-05',
            'description' => 'Pembayaran simpanan wajib',
            'posted_at' => '2026-05-10',
        ]);
        CooperativeReceipt::query()->create([
            'receipt_no' => 'RC-202605-000009',
            'cooperative_payment_id' => $payment->id,
            'cooperative_member_id' => $member->id,
            'pdf_path' => 'cooperative/receipts/RC-202605-000009.pdf',
            'issued_at' => now(),
            'issued_by' => $user->id,
        ]);
        Storage::disk('local')->put('cooperative/receipts/RC-202605-000009.pdf', 'receipt');

        $adminKoperasi = User::factory()->create();
        $adminKoperasi->assignRole('Admin Koperasi');

        $this->actingAs($adminKoperasi)
            ->post(route('cooperative.ledger.cancel-payment', $entry), [
                'reason' => 'Dicoba oleh admin koperasi',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('cooperative.ledger.cancel-payment', $entry), [
                'reason' => 'Salah input anggota',
            ])
            ->assertRedirect();

        $this->assertSame('VOID', $payment->refresh()->status);
        $this->assertSame('UNPAID', $invoice->refresh()->status);
        $this->assertSame('0.00', $invoice->paid_amount);
        $this->assertDatabaseMissing('cooperative_ledger_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('cooperative_receipts', ['cooperative_payment_id' => $payment->id]);
        $this->assertFalse(Storage::disk('local')->exists('cooperative/receipts/RC-202605-000009.pdf'));
    }

    public function test_system_admin_can_revise_payment_from_ledger(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'SUKARELA',
            'name' => 'Simpanan Sukarela',
            'category' => 'SUKARELA',
            'default_amount' => 0,
            'frequency' => 'ADHOC',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 100000,
            'paid_amount' => 50000,
            'status' => 'PARTIAL',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $type->id,
            'user_id' => $user->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
            'status' => 'APPROVED',
        ]);
        $entry = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_payment_id' => $payment->id,
            'cooperative_contribution_type_id' => $type->id,
            'source_type' => CooperativePayment::class,
            'source_id' => $payment->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'SUKARELA',
            'debit' => 0,
            'credit' => 50000,
            'period' => '2026-05',
            'description' => 'Setoran awal',
            'posted_at' => '2026-05-10',
        ]);

        $this->actingAs($user)
            ->post(route('cooperative.ledger.revise-payment', $entry), [
                'amount' => 75000,
                'payment_method' => 'TRANSFER',
                'paid_at' => '2026-05-11',
                'notes' => 'Setoran sukarela dikoreksi',
                'reason' => 'Nominal transfer sebenarnya 75.000',
            ])
            ->assertRedirect();

        $this->assertSame('75000.00', $payment->refresh()->amount);
        $this->assertSame('TRANSFER', $payment->payment_method);
        $this->assertSame('2026-05-11', $payment->paid_at->toDateString());
        $this->assertSame('75000.00', $entry->refresh()->credit);
        $this->assertSame('Setoran sukarela dikoreksi', $entry->description);
        $this->assertSame('75000.00', $invoice->refresh()->paid_amount);
        $this->assertSame('PARTIAL', $invoice->status);
        $this->assertDatabaseHas('cooperative_receipts', ['cooperative_payment_id' => $payment->id]);
    }

    public function test_admin_koperasi_cannot_cancel_or_revise_payment_from_ledger(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $adminKoperasi = User::factory()->create();
        $adminKoperasi->assignRole('Admin Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $adminKoperasi->forceFill(['organization_id' => $member->organization_id])->save();
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 50000,
            'status' => 'PAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $type->id,
            'user_id' => $adminKoperasi->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
            'status' => 'APPROVED',
        ]);
        $entry = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_payment_id' => $payment->id,
            'cooperative_contribution_type_id' => $type->id,
            'source_type' => CooperativePayment::class,
            'source_id' => $payment->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'WAJIB',
            'debit' => 0,
            'credit' => 50000,
            'period' => '2026-05',
            'description' => 'Pembayaran simpanan wajib',
            'posted_at' => '2026-05-10',
        ]);

        $response = $this->actingAs($adminKoperasi)
            ->get(route('cooperative.ledger.index'));
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Cooperative/Ledger/Index')
                ->where('canManageLedger', false),
        );

        $this->actingAs($adminKoperasi)
            ->post(route('cooperative.ledger.cancel-payment', $entry), [
                'reason' => 'Admin koperasi tidak boleh cancel',
            ])
            ->assertForbidden();

        $this->actingAs($adminKoperasi)
            ->post(route('cooperative.ledger.revise-payment', $entry), [
                'amount' => 60000,
                'payment_method' => 'CASH',
                'paid_at' => '2026-05-10',
                'notes' => 'Test',
                'reason' => 'Admin koperasi tidak boleh revisi',
            ])
            ->assertForbidden();

        $this->assertSame('APPROVED', $payment->refresh()->status);
        $this->assertSame(50000.0, (float) $entry->refresh()->credit);
    }

    public function test_savings_settings_page_is_displayed_with_required_amounts(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('settings.savings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Savings')
                ->where('settings.wajib.default_amount', '100000.00')
                ->where('settings.pokok.default_amount', '200000.00')
                ->etc());
    }

    public function test_payment_store_supports_member_search_flow_with_type_and_proof_upload(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $user->forceFill(['organization_id' => $member->organization_id])->save();
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-06',
            'amount' => 100000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);

        $this->actingAs($user)->post(route('cooperative.payments.store'), [
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-06-10',
            'notes' => 'Setoran simpanan wajib bulan Juni',
            'proof' => UploadedFile::fake()->image('bukti-transfer.jpg'),
        ])->assertRedirect();

        $payment = CooperativePayment::query()->latest('id')->firstOrFail();

        $this->assertSame($invoice->id, $payment->cooperative_dues_invoice_id);
        $this->assertSame($type->id, $payment->cooperative_contribution_type_id);
        $this->assertSame('APPROVED', $payment->status);
        $this->assertSame('PAID', $invoice->refresh()->status);
        $this->assertSame('100000.00', $invoice->paid_amount);
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
        $this->assertNotNull($payment->proof_path);
        $this->assertTrue(Storage::disk('public')->exists($payment->proof_path));
    }

    public function test_payment_store_rejects_non_standard_amount_for_wajib_and_pokok(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $this->actingAs($user)->from(route('cooperative.payments.index'))
            ->post(route('cooperative.payments.store'), [
                'cooperative_member_id' => $member->id,
                'cooperative_contribution_type_id' => $type->id,
                'amount' => 90000,
                'payment_method' => 'CASH',
                'paid_at' => '2026-06-10',
                'notes' => 'Setoran manual',
            ])
            ->assertRedirect(route('cooperative.payments.index'))
            ->assertSessionHasErrors(['amount']);
    }

    public function test_approved_payment_updates_invoice_and_ledger(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);

        $this->actingAs($user)->post(route('cooperative.payments.store'), [
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-01',
            'status' => 'APPROVED',
        ])->assertRedirect();

        $this->assertSame('PAID', $invoice->refresh()->status);
        $this->assertSame('50000.00', $invoice->paid_amount);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'SAVING_PAYMENT',
            'cooperative_contribution_type_id' => $type->id,
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'WAJIB',
            'credit' => 50000,
        ]);
    }

    public function test_approving_same_cooperative_payment_twice_does_not_duplicate_invoice_or_ledger(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $approver = User::factory()->create();
        $approver->assignRole('Admin Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $approver->forceFill(['organization_id' => $member->organization_id])->save();
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-01',
            'status' => 'PENDING',
        ]);

        $this->actingAs($approver)->post(route('cooperative.payments.approve', $payment))->assertRedirect();
        $this->actingAs($approver)->post(route('cooperative.payments.approve', $payment))->assertRedirect();

        $this->assertSame('PAID', $invoice->refresh()->status);
        $this->assertSame('50000.00', $invoice->paid_amount);
        $this->assertSame('APPROVED', $payment->refresh()->status);
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_only_admin_koperasi_can_approve_pending_cooperative_payment(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $creator = User::factory()->create();
        $creator->assignRole('System Admin');
        $systemAdmin = User::factory()->create();
        $systemAdmin->assignRole('System Admin');
        $adminKoperasi = User::factory()->create();
        $adminKoperasi->assignRole('Admin Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $adminKoperasi->forceFill(['organization_id' => $member->organization_id])->save();
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $creator->id,
            'amount' => 50000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-05-01',
            'status' => 'PENDING',
        ]);

        $this->actingAs($systemAdmin)
            ->post(route('cooperative.payments.approve', $payment))
            ->assertForbidden();

        $this->assertSame('PENDING', $payment->refresh()->status);

        $this->actingAs($adminKoperasi)
            ->post(route('cooperative.payments.approve', $payment))
            ->assertRedirect();

        $this->assertSame('APPROVED', $payment->refresh()->status);
        $this->assertSame($adminKoperasi->id, $payment->approved_by);
    }

    public function test_dues_batch_mark_paid_creates_approved_payments_and_ledger_entries(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $firstMember = $this->member(['status' => 'ACTIVE']);
        $secondMember = $this->member(['status' => 'ACTIVE']);
        $user->update(['organization_id' => $firstMember->organization_id]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $firstInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $firstMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
        $secondInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $secondMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 75000,
            'paid_amount' => 25000,
            'status' => 'PARTIAL',
        ]);

        $this->actingAs($user)->post(route('cooperative.dues.mark-paid'), [
            'invoice_ids' => [$firstInvoice->id, $secondInvoice->id],
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
            'reference_no' => 'BATCH-001',
        ])->assertRedirect();

        $this->assertSame('PAID', $firstInvoice->refresh()->status);
        $this->assertSame('50000.00', $firstInvoice->paid_amount);
        $this->assertSame('PAID', $secondInvoice->refresh()->status);
        $this->assertSame('75000.00', $secondInvoice->paid_amount);
        $this->assertDatabaseHas('cooperative_payments', [
            'cooperative_dues_invoice_id' => $firstInvoice->id,
            'amount' => 50000,
            'status' => 'APPROVED',
        ]);
        $firstPayment = CooperativePayment::query()
            ->where('cooperative_dues_invoice_id', $firstInvoice->id)
            ->firstOrFail();
        $this->assertDatabaseHas('cooperative_payments', [
            'cooperative_dues_invoice_id' => $secondInvoice->id,
            'amount' => 50000,
            'status' => 'APPROVED',
        ]);
        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => CooperativePayment::class,
            'subject_id' => (string) $firstPayment->id,
            'to_status' => 'APPROVED',
        ]);
        $this->assertSame(2, $firstMember->ledgerEntries()->where('entry_type', 'SAVING_PAYMENT')->count() + $secondMember->ledgerEntries()->where('entry_type', 'SAVING_PAYMENT')->count());
    }

    public function test_dues_mark_paid_can_collect_partial_amount(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $user->update(['organization_id' => $member->organization_id]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 100000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);

        $this->actingAs($user)->post(route('cooperative.dues.mark-paid'), [
            'invoice_ids' => [$invoice->id],
            'amount' => 40000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
            'reference_no' => 'COLLECT-001',
        ])->assertRedirect();

        $this->assertSame('PARTIAL', $invoice->refresh()->status);
        $this->assertSame('40000.00', $invoice->paid_amount);
        $this->assertDatabaseHas('cooperative_payments', [
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 40000,
            'status' => 'APPROVED',
        ]);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'credit' => 40000,
        ]);
    }

    public function test_savings_settings_update_changes_future_dues_amounts(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $member = $this->member([
            'status' => 'ACTIVE',
            'joined_at' => '2026-06-01',
        ]);
        $wajib = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $pokok = CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('settings.savings.update'), [
                'wajib_default_amount' => 125000,
                'pokok_default_amount' => 250000,
            ])
            ->assertRedirect();

        $this->assertSame('125000.00', $wajib->refresh()->default_amount);
        $this->assertSame('250000.00', $pokok->refresh()->default_amount);

        $this->actingAs($user)
            ->post(route('cooperative.dues.generate'), [
                'period' => '2026-06',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $wajib->id,
            'period' => '2026-06',
            'amount' => 125000,
        ]);
        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $pokok->id,
            'period' => '2026-06',
            'amount' => 250000,
        ]);
    }

    public function test_manual_payment_page_excludes_wajib_contribution_type(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = app(\App\Services\Cooperative\CooperativeHeadOfficeResolver::class)->resolve();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');

        CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        CooperativeContributionType::query()->create([
            'code' => 'SUKARELA',
            'name' => 'Simpanan Sukarela',
            'category' => 'SUKARELA',
            'default_amount' => 0,
            'frequency' => 'FLEXIBLE',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('cooperative.payments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Payments/Index')
                ->where('canApprovePayments', true)
                ->has('contributionTypes', 2)
                ->where('contributionTypes.0.code', 'POKOK')
                ->where('contributionTypes.1.code', 'SUKARELA')
            );
    }

    public function test_system_admin_can_view_payment_page_but_cannot_approve_from_ui_capability(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $this->actingAs($user)
            ->get(route('cooperative.payments.index', ['status' => 'PENDING']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Payments/Index')
                ->where('canApprovePayments', false)
            );
    }

    public function test_system_admin_can_reset_paid_dues_invoice_to_unpaid(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 50000,
            'status' => 'PAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
            'status' => 'APPROVED',
            'receipt_no' => 'RC-202605-000001',
            'receipt_issued_at' => now(),
        ]);
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_payment_id' => $payment->id,
            'cooperative_contribution_type_id' => $type->id,
            'source_type' => CooperativePayment::class,
            'source_id' => $payment->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'WAJIB',
            'debit' => 0,
            'credit' => 50000,
            'period' => '2026-05',
            'description' => 'Pembayaran iuran/simpanan koperasi',
            'posted_at' => '2026-05-10',
        ]);
        CooperativeReceipt::query()->create([
            'receipt_no' => 'RC-202605-000001',
            'cooperative_payment_id' => $payment->id,
            'cooperative_member_id' => $member->id,
            'pdf_path' => 'cooperative/receipts/RC-202605-000001.pdf',
            'issued_at' => now(),
            'issued_by' => $user->id,
        ]);
        Storage::disk('local')->put('cooperative/receipts/RC-202605-000001.pdf', 'receipt');

        $this->actingAs($user)
            ->post(route('cooperative.dues.mark-unpaid', $invoice))
            ->assertRedirect();

        $this->assertSame('UNPAID', $invoice->refresh()->status);
        $this->assertSame('0.00', $invoice->paid_amount);
        $this->assertSame('VOID', $payment->refresh()->status);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
        $this->assertDatabaseMissing('cooperative_receipts', [
            'cooperative_payment_id' => $payment->id,
        ]);
        $this->assertFalse(Storage::disk('local')->exists('cooperative/receipts/RC-202605-000001.pdf'));
    }

    public function test_dues_batch_payment_api_processes_full_settlement(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);

        Sanctum::actingAs($user, ['cooperative:write']);

        $this->postJson('/api/v1/dues/payments/batch', [
            'invoice_ids' => [$invoice->id],
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-05-10',
            'reference_no' => 'API-BATCH-001',
        ])->assertCreated()
            ->assertJsonPath('data.processed_count', 1)
            ->assertJsonPath('data.total_amount', 50000);

        $this->assertSame('PAID', $invoice->refresh()->status);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'ledger_scope' => 'SAVINGS',
            'category_snapshot' => 'WAJIB',
        ]);
    }

    public function test_dues_mark_paid_skips_already_paid_invoices_without_duplicate_payment(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 50000,
            'status' => 'PAID',
        ]);

        $this->actingAs($user)->post(route('cooperative.dues.mark-paid'), [
            'invoice_ids' => [$invoice->id],
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
        ])->assertRedirect();

        $this->assertSame(0, $invoice->payments()->count());
        $this->assertSame('PAID', $invoice->refresh()->status);
        $this->assertSame('50000.00', $invoice->paid_amount);
    }

    public function test_pos_transaction_reduces_stock_and_requires_active_member_for_credit(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');
        $member = $this->member(['status' => 'PENDING', 'credit_limit' => 100000]);
        $product = $this->product(['stock' => 5, 'sale_price' => 10000]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'payment_method' => 'MEMBER_CREDIT',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('cooperative_member_id');

        $member->update(['status' => 'ACTIVE']);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'payment_method' => 'MEMBER_CREDIT',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertSame(3, $product->refresh()->stock);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'POS_MEMBER_CREDIT',
            'ledger_scope' => 'POS',
            'debit' => 20000,
        ]);
    }

    public function test_pos_member_transaction_snapshots_profit_and_posts_points(): void
    {
        Carbon::setTestNow('2026-05-15 10:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $product = $this->product(['stock' => 5, 'cost_price' => 6000, 'sale_price' => 10000]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POINT-001',
            'payment_method' => 'CASH',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_transaction_items', [
            'pos_product_id' => $product->id,
            'cost_price' => 6000,
            'unit_profit' => 4000,
            'line_profit' => 8000,
        ]);
        $this->assertDatabaseHas('pos_transactions', [
            'client_reference' => 'POINT-001',
            'gross_profit' => 8000,
        ]);
        $this->assertDatabaseHas('pos_member_points', [
            'cooperative_member_id' => $member->id,
            'year' => 2026,
            'profit_amount' => 8000,
            'points' => 8,
        ]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POINT-002',
            'payment_method' => 'CASH',
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $this->assertSame(1, PosMemberPoint::query()->count());
    }

    public function test_pos_profit_snapshot_and_points_do_not_change_after_product_cost_changes(): void
    {
        Carbon::setTestNow('2026-05-15 10:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $product = $this->product(['stock' => 5, 'cost_price' => 6000, 'sale_price' => 10000]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SNAPSHOT-001',
            'payment_method' => 'TRANSFER',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $product->update(['cost_price' => 9000, 'sale_price' => 12000]);

        $this->assertDatabaseHas('pos_transaction_items', [
            'pos_product_id' => $product->id,
            'cost_price' => 6000,
            'unit_price' => 10000,
            'unit_profit' => 4000,
            'line_profit' => 4000,
        ]);
        $this->assertDatabaseHas('pos_member_points', [
            'cooperative_member_id' => $member->id,
            'profit_amount' => 4000,
            'points' => 4,
        ]);
    }

    public function test_pos_rewards_use_profit_rate_and_do_not_promote_member_to_platinum_from_single_purchase(): void
    {
        Carbon::setTestNow('2026-06-14 10:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $product = $this->product([
            'stock' => 5,
            'cost_price' => 220000,
            'sale_price' => 350000,
        ]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POINT-350K-001',
            'payment_method' => 'CASH',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_member_points', [
            'cooperative_member_id' => $member->id,
            'profit_amount' => 130000,
            'points' => 130,
        ]);
        $this->assertDatabaseHas('point_transactions', [
            'cooperative_member_id' => $member->id,
            'transaction_type' => 'EARNED',
            'points' => 130,
            'balance_after' => 130,
        ]);

        $summary = app(PointService::class)->balanceSummary($member->refresh());

        $this->assertSame(130, $summary['total_points']);
        $this->assertSame('BRONZE', $summary['member_tier']);
        $this->assertSame('SILVER', $summary['next_tier']);
    }

    public function test_recalculate_pos_points_command_repairs_existing_excessive_points(): void
    {
        Carbon::setTestNow('2026-06-14 10:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $product = $this->product([
            'stock' => 5,
            'cost_price' => 220000,
            'sale_price' => 350000,
        ]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POINT-REPAIR-001',
            'payment_method' => 'CASH',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $point = PosMemberPoint::query()->where('cooperative_member_id', $member->id)->firstOrFail();
        $point->forceFill(['points' => 130000])->save();

        PointTransaction::query()
            ->where('cooperative_member_id', $member->id)
            ->where('source_type', PosMemberPoint::class)
            ->where('source_id', (string) $point->id)
            ->firstOrFail()
            ->forceFill([
                'points' => 130000,
                'balance_before' => 0,
                'balance_after' => 130000,
            ])
            ->save();

        $this->artisan('cooperative:recalculate-pos-points')
            ->expectsOutput('Recalculated 1 POS point rows.')
            ->expectsOutput('Rebuilt point balances for 1 members.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('pos_member_points', [
            'id' => $point->id,
            'points' => 130,
        ]);
        $this->assertDatabaseHas('point_transactions', [
            'cooperative_member_id' => $member->id,
            'source_type' => PosMemberPoint::class,
            'source_id' => (string) $point->id,
            'points' => 130,
            'balance_after' => 130,
        ]);

        $summary = app(PointService::class)->balanceSummary($member->refresh());

        $this->assertSame(130, $summary['total_points']);
        $this->assertSame('BRONZE', $summary['member_tier']);
    }

    public function test_annual_shu_score_rewards_long_membership_and_complete_mandatory_dues(): void
    {
        $longMember = $this->member([
            'member_no' => 'KOP-2026-00001',
            'status' => 'ACTIVE',
            'joined_at' => '2026-01-01',
        ]);
        $newMember = $this->member([
            'member_no' => 'KOP-2026-00002',
            'status' => 'ACTIVE',
            'joined_at' => '2026-07-01',
        ]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        foreach (range(1, 12) as $month) {
            CooperativeDuesInvoice::query()->create([
                'cooperative_member_id' => $longMember->id,
                'cooperative_contribution_type_id' => $type->id,
                'period' => '2026-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                'amount' => 50000,
                'paid_amount' => 50000,
                'status' => 'PAID',
            ]);
        }

        foreach ([7, 8, 9] as $month) {
            CooperativeDuesInvoice::query()->create([
                'cooperative_member_id' => $newMember->id,
                'cooperative_contribution_type_id' => $type->id,
                'period' => '2026-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                'amount' => 50000,
                'paid_amount' => 50000,
                'status' => 'PAID',
            ]);
        }

        $preview = app(AnnualShuDistributionService::class)->preview(2026, 150000);
        $allocations = collect($preview['allocations'])->keyBy(fn (array $allocation) => $allocation['member']->id);

        $this->assertGreaterThan($allocations[$newMember->id]['shu_score'], $allocations[$longMember->id]['shu_score']);
        $this->assertGreaterThan($allocations[$newMember->id]['cooperative_shu_amount'], $allocations[$longMember->id]['cooperative_shu_amount']);
    }

    public function test_annual_pos_shu_is_allocated_by_member_profit_points_and_close_is_locked(): void
    {
        Carbon::setTestNow('2026-05-15 10:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');
        $firstMember = $this->member(['member_no' => 'KOP-2026-00003', 'status' => 'ACTIVE']);
        $secondMember = $this->member(['member_no' => 'KOP-2026-00004', 'status' => 'ACTIVE']);
        $firstProduct = $this->product(['stock' => 5, 'cost_price' => 5000, 'sale_price' => 10000]);
        $secondProduct = $this->product(['stock' => 5, 'cost_price' => 5000, 'sale_price' => 20000]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POS-SHU-001',
            'payment_method' => 'CASH',
            'cooperative_member_id' => $firstMember->id,
            'items' => [
                ['pos_product_id' => $firstProduct->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POS-SHU-002',
            'payment_method' => 'CASH',
            'cooperative_member_id' => $secondMember->id,
            'items' => [
                ['pos_product_id' => $secondProduct->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $service = app(AnnualShuDistributionService::class);
        $preview = $service->preview(2026);
        $allocations = collect($preview['allocations'])->keyBy(fn (array $allocation) => $allocation['member']->id);

        $this->assertSame(40, $preview['total_pos_points']);
        $this->assertSame(40000.0, $preview['pos_profit_pool']);
        $this->assertSame(10000.0, $allocations[$firstMember->id]['pos_shu_amount']);
        $this->assertSame(30000.0, $allocations[$secondMember->id]['pos_shu_amount']);

        $period = $service->close(2026, 100000, null, $user);

        $this->assertSame(CooperativeShuPeriodStatus::Closed, $period->status);
        $this->assertDatabaseHas('cooperative_shu_periods', [
            'year' => 2026,
            'status' => 'CLOSED',
            'total_pos_points' => 40,
        ]);
        $this->assertSame(2, CooperativeShuPeriod::query()->where('year', 2026)->firstOrFail()->allocations()->count());
        $this->expectException(ValidationException::class);

        $service->close(2026, 100000, null, $user);
    }

    public function test_pos_category_and_product_can_be_managed(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $this->actingAs($user)->post(route('cooperative.pos-categories.store'), [
            'name' => 'Minuman',
            'slug' => 'minuman',
            'is_active' => true,
        ])->assertRedirect();

        $category = PosCategory::query()->where('slug', 'minuman')->firstOrFail();

        $this->actingAs($user)->post(route('cooperative.pos-products.store'), [
            'pos_category_id' => $category->id,
            'sku' => 'DRINK-001',
            'barcode' => '899000000001',
            'name' => 'Air Mineral',
            'cost_price' => 2500,
            'sale_price' => 4000,
            'stock' => 20,
            'minimum_stock' => 5,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_products', [
            'sku' => 'DRINK-001',
            'name' => 'Air Mineral',
            'stock' => 20,
        ]);

        $product = PosProduct::query()->where('sku', 'DRINK-001')->firstOrFail();

        $this->actingAs($user)->put(route('cooperative.pos-products.update', $product), [
            'pos_category_id' => $category->id,
            'sku' => 'DRINK-001',
            'barcode' => '899000000001',
            'name' => 'Air Mineral Botol',
            'cost_price' => 2600,
            'sale_price' => 4500,
            'minimum_stock' => 8,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_products', [
            'id' => $product->id,
            'name' => 'Air Mineral Botol',
            'minimum_stock' => 8,
        ]);
    }

    public function test_pos_stock_adjustment_records_movement_and_rejects_negative_stock(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $product = $this->product(['stock' => 5]);

        $this->actingAs($user)->post(route('cooperative.pos-products.adjust-stock', $product), [
            'movement_type' => 'ADJUSTMENT_IN',
            'quantity' => 7,
            'notes' => 'Restock',
        ])->assertRedirect();

        $this->assertSame(12, $product->refresh()->stock);
        $this->assertDatabaseHas('pos_stock_movements', [
            'pos_product_id' => $product->id,
            'movement_type' => 'ADJUSTMENT_IN',
            'quantity' => 7,
            'stock_before' => 5,
            'stock_after' => 12,
        ]);

        $this->actingAs($user)->post(route('cooperative.pos-products.adjust-stock', $product), [
            'movement_type' => 'ADJUSTMENT_OUT',
            'quantity' => 20,
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(12, $product->refresh()->stock);
        $this->assertSame(1, PosStockMovement::query()->where('pos_product_id', $product->id)->count());
    }

    public function test_api_requires_sanctum_and_cooperative_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->getJson('/api/v1/members')->assertUnauthorized();

        $plainUser = User::factory()->create();
        Sanctum::actingAs($plainUser, ['cooperative:read']);
        $this->getJson('/api/v1/members')->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        Sanctum::actingAs($admin, ['cooperative:read']);
        $this->getJson('/api/v1/members')->assertOk();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(array $attributes = []): CooperativeMember
    {
        $organization = Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => fake()->uuid(),
                'name' => 'Koperasi Utama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'is_active' => true,
            ],
        );

        return CooperativeMember::query()->create([
            'organization_id' => $organization->id,
            'member_no' => 'KOP-2026-'.fake()->unique()->numerify('#####'),
            'name' => fake()->name(),
            'status' => 'ACTIVE',
            'joined_at' => '2026-05-01',
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(array $attributes = []): PosProduct
    {
        $category = PosCategory::query()->create([
            'name' => 'Sembako',
            'slug' => 'sembako-'.fake()->unique()->numberBetween(1, 9999),
            'is_active' => true,
        ]);

        return PosProduct::query()->create([
            'pos_category_id' => $category->id,
            'sku' => 'SKU-'.fake()->unique()->numberBetween(1, 9999),
            'name' => fake()->word(),
            'sale_price' => 10000,
            'stock' => 10,
            'minimum_stock' => 1,
            'is_active' => true,
            ...$attributes,
        ]);
    }
}
