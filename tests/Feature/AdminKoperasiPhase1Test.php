<?php

namespace Tests\Feature;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminKoperasiPhase1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public static function primaryRoleDashboardProvider(): array
    {
        return [
            'system admin wins over admin koperasi' => [['System Admin', 'Admin Koperasi'], 'platform', 'system-admin', false],
            'admin pusat wins over admin koperasi' => [['Admin Pusat', 'Admin Koperasi'], 'platform', 'admin-pusat', false],
            'pengurus wins over admin koperasi' => [['Pengurus Koperasi', 'Admin Koperasi'], 'platform', 'pengurus', false],
            'manajer wins over admin koperasi' => [['Manajer Koperasi', 'Admin Koperasi'], 'platform', 'manajer', false],
            'admin koperasi wins over kasir' => [['Admin Koperasi', 'Kasir Koperasi'], 'admin-koperasi', 'admin-koperasi', true],
            'admin koperasi receives admin payload' => [['Admin Koperasi'], 'admin-koperasi', 'admin-koperasi', true],
        ];
    }

    #[DataProvider('primaryRoleDashboardProvider')]
    public function test_shared_primary_role_and_dashboard_workspace_follow_the_same_contract(array $roles, string $workspace, string $primaryRole, bool $isAdminPayload): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($roles);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.primary_role', $primaryRole)
                ->loadDeferredProps('dashboard', function (Assert $page) use ($workspace, $isAdminPayload): void {
                    $page->where('dashboard.workspace', $workspace);

                    if ($isAdminPayload) {
                        $page->has('dashboard.work_queue')
                            ->missing('dashboard.pos');
                    } else {
                        $page->missing('dashboard.work_queue')
                            ->missing('dashboard.organization');
                    }
                }),
            );
    }

    public function test_admin_dashboard_is_scoped_and_uses_operational_payload(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->pending()->create(['organization_id' => $organization->id]);
        CooperativeMember::factory()->pending()->create([
            'organization_id' => $organization->id,
            'validation_status' => CooperativeMember::VALIDATION_REVISION,
        ]);
        CooperativeMember::factory()->pending()->create(['organization_id' => $otherOrganization->id]);
        $activeMember = CooperativeMember::factory()->active()->create(['organization_id' => $organization->id]);
        $type = CooperativeContributionType::factory()->wajib(100000)->create(['code' => 'WAJIB']);

        CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);
        CooperativePayment::query()->create([
            'cooperative_member_id' => $activeMember->id,
            'amount' => 900000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);
        $otherMember = CooperativeMember::factory()->active()->create([
            'organization_id' => $otherOrganization->id,
        ]);
        CooperativePayment::query()->create([
            'cooperative_member_id' => $otherMember->id,
            'amount' => 2000000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);
        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $activeMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 25000,
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'PARTIAL',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->loadDeferredProps('dashboard', fn (Assert $page) => $page
                    ->where('dashboard.workspace', 'admin-koperasi')
                    ->where('dashboard.organization.id', $organization->id)
                    ->where('dashboard.summary.pending_members', 1)
                    ->where('dashboard.summary.revision_members', 1)
                    ->where('dashboard.summary.pending_payments', 2)
                    ->where('dashboard.collections.pending_payment_amount', 1000000)
                    ->where('dashboard.summary.unpaid_dues_count', 1)
                    ->where('dashboard.summary.unpaid_dues_amount', 75000)
                    ->where('dashboard.summary.active_members', 1)
                    ->missing('dashboard.pos')
                    ->missing('dashboard.shu'),
                ),
            );
    }

    public function test_admin_dashboard_uses_neutral_collection_state_when_period_has_no_dues(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');
        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->loadDeferredProps('dashboard', fn (Assert $page) => $page
                    ->where('dashboard.workspace', 'admin-koperasi')
                    ->where('dashboard.collections.total_due', 0)
                    ->where('dashboard.collections.paid', 0)
                    ->where('dashboard.collections.outstanding', 0)
                    ->where('dashboard.collections.collection_rate', 0)
                )
            );
    }

    public function test_admin_dashboard_scopes_pos_and_inventory_metrics_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');
        $otherCashier = User::factory()->create(['organization_id' => $otherOrganization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
        ]);
        $otherMember = CooperativeMember::factory()->active()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        PosProduct::factory()->lowStock()->create([
            'organization_id' => $organization->id,
        ]);
        PosProduct::factory()->lowStock()->create([
            'organization_id' => $otherOrganization->id,
        ]);
        PosProduct::factory()->lowStock()->create([
            'organization_id' => null,
        ]);

        PosTransaction::query()->create([
            'transaction_no' => 'POS-ORG-A-001',
            'cooperative_member_id' => $member->id,
            'cashier_id' => $admin->id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'gross_profit' => 40000,
            'status' => 'COMPLETED',
            'sold_at' => now(),
        ]);
        PosTransaction::query()->create([
            'transaction_no' => 'POS-ORG-B-001',
            'cooperative_member_id' => $otherMember->id,
            'cashier_id' => $otherCashier->id,
            'subtotal' => 900000,
            'discount_amount' => 0,
            'total_amount' => 900000,
            'gross_profit' => 300000,
            'status' => 'COMPLETED',
            'sold_at' => now(),
        ]);
        PosTransaction::query()->create([
            'transaction_no' => 'POS-MISMATCH-001',
            'cooperative_member_id' => $otherMember->id,
            'cashier_id' => $admin->id,
            'subtotal' => 777000,
            'discount_amount' => 0,
            'total_amount' => 777000,
            'gross_profit' => 200000,
            'status' => 'COMPLETED',
            'sold_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->loadDeferredProps('dashboard', fn (Assert $page) => $page
                    ->where('dashboard.summary.today_sales', 100000)
                    ->where('dashboard.summary.today_transactions', 1)
                    ->where('dashboard.summary.low_stock_products', 1)
                    ->where('dashboard.work_queue.low_stock_products', 1)
                ),
            );
    }

    public function test_admin_navigation_restores_cooperative_sections_without_platform_modules(): void
    {
        $sidebar = file_get_contents(resource_path('js/components/AppSidebar.vue'));

        self::assertIsString($sidebar);
        $adminNavigation = substr(
            $sidebar,
            strpos($sidebar, 'const adminNavItems'),
            strpos($sidebar, 'const memberNavItems') - strpos($sidebar, 'const adminNavItems'),
        );

        foreach ([
            'Dashboard',
            'Keanggotaan',
            'Iuran & Simpanan',
            'Pinjaman',
            'Saldo Toko',
            'Poin & Reward',
            'POS Toko',
            'Inventory POS',
            'Stock Movement',
        ] as $section) {
            self::assertStringContainsString($section, $adminNavigation);
        }

        self::assertStringNotContainsString('Stock Opname', $adminNavigation);

        foreach (['User Management', 'Human Resources', 'Finance', 'Audit Logs'] as $platformModule) {
            self::assertStringNotContainsString($platformModule, $adminNavigation);
        }

        self::assertStringContainsString('Pusat Panduan', $sidebar);
    }

    public function test_admin_dashboard_get_does_not_mutate_records(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');
        CooperativeMember::factory()->active()->create(['organization_id' => $organization->id]);
        $type = CooperativeContributionType::factory()->wajib(100000)->create(['code' => 'WAJIB']);

        $beforeMembers = CooperativeMember::query()->count();
        $beforeInvoices = CooperativeDuesInvoice::query()->count();
        $beforePayments = CooperativePayment::query()->count();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $this->assertSame($beforeMembers, CooperativeMember::query()->count());
        $this->assertSame($beforeInvoices, CooperativeDuesInvoice::query()->count());
        $this->assertSame($beforePayments, CooperativePayment::query()->count());
        $this->assertDatabaseMissing('cooperative_dues_invoices', [
            'cooperative_contribution_type_id' => $type->id,
        ]);
    }

    public function test_dues_get_is_read_only_and_does_not_generate_invoices(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');
        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'joined_at' => now()->subMonth(),
            'tanggal_aktif' => now()->subMonth(),
        ]);
        $type = CooperativeContributionType::factory()->wajib(100000)->create(['code' => 'WAJIB']);

        $this->actingAs($admin)
            ->get(route('cooperative.dues.index'))
            ->assertOk();

        $this->assertDatabaseMissing('cooperative_dues_invoices', [
            'cooperative_contribution_type_id' => $type->id,
        ]);
    }

    public function test_admin_cannot_perform_member_final_approval(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->pendingReview()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($admin)
            ->post(route('cooperative.members.approve-final', $member), ['notes' => 'Tidak berwenang'])
            ->assertForbidden();
    }

    public function test_admin_cannot_view_member_from_another_organization(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $this->actingAs($admin)
            ->get(route('cooperative.members.show', $member))
            ->assertForbidden();
    }

    public function test_admin_payment_filters_match_member_period_and_method(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'name' => 'Santi Filter',
            'member_no' => 'ADM-FILTER-001',
        ]);
        $otherMember = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'name' => 'Other Member',
        ]);

        $matchingPayment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-06-10',
            'status' => 'PENDING',
        ]);
        CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-06-10',
            'status' => 'PENDING',
        ]);
        CooperativePayment::query()->create([
            'cooperative_member_id' => $otherMember->id,
            'amount' => 75000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-05-10',
            'status' => 'PENDING',
        ]);

        $this->actingAs($admin)
            ->get(route('cooperative.payments.index', [
                'search' => 'sAnTi',
                'period' => '2026-06',
                'payment_method' => 'TRANSFER',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'sAnTi')
                ->where('filters.period', '2026-06')
                ->where('filters.payment_method', 'TRANSFER')
                ->where('payments.total', 1)
                ->where('payments.data.0.id', $matchingPayment->id),
            );
    }
}
