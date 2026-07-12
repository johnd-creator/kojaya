<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\CooperativeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSmokeTest extends TestCase
{
    use DatabaseMigrations;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create();
    }

    private function user(?string $role = null, array $attributes = []): User
    {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'email_verified_at' => now(),
            ...$attributes,
        ]);

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    // ─── Access matrix: each role → allowed routes ───────────────

    /**
     * Provides role access matrix based on actual application behavior.
     *
     * @return array<string, array{0: string, 1: list<string>, 2: list<string>}>
     */
    public static function roleAccessMatrixProvider(): array
    {
        return [
            'Anggota' => [
                'Anggota',
                // allowed
                [
                    '/dashboard',
                    '/member',
                ],
                // forbidden (routes with explicit authorization)
                [
                    '/cooperative/operator/dashboard',
                    '/cooperative/operator/closing',
                ],
            ],
            'Kasir Koperasi' => [
                'Kasir Koperasi',
                [
                    '/dashboard',
                    '/cooperative/pos',
                    '/cooperative/operator/dashboard',
                ],
                [
                    '/cooperative/operator/closing',
                ],
            ],
            'Admin Koperasi' => [
                'Admin Koperasi',
                [
                    '/dashboard',
                    '/cooperative/members',
                    '/cooperative/dues',
                    '/cooperative/payments',
                    '/cooperative/ledger',
                    '/cooperative/loans',
                    '/cooperative/loans/calculator',
                    '/cooperative/loan-types',
                    '/cooperative/points',
                    '/cooperative/rewards',
                    '/cooperative/redemptions',
                    '/cooperative/shu',
                    '/cooperative/pos',
                    '/cooperative/pos/transactions',
                    '/cooperative/pos/reports',
                    '/cooperative/pos-products',
                    '/cooperative/pos-categories',
                ],
                [
                    '/cooperative/pos/shu',
                    '/cooperative/reports',
                    '/cooperative/operator/dashboard',
                    '/cooperative/operator/closing',
                    '/reports',
                    '/departments',
                    '/finance/chart-of-accounts',
                ],
            ],
            'Pengurus Koperasi' => [
                'Pengurus Koperasi',
                [
                    '/dashboard',
                    '/cooperative/operator/dashboard',
                    '/cooperative/operator/closing',
                    '/cooperative/members',
                    '/cooperative/loans',
                    '/cooperative/payments',
                    '/cooperative/dues',
                    '/cooperative/shu',
                    '/cooperative/pos',
                    '/cooperative/reports',
                ],
                [],
            ],
            'Manajer Koperasi' => [
                'Manajer Koperasi',
                [
                    '/dashboard',
                    '/cooperative/operator/dashboard',
                    '/cooperative/members',
                    '/cooperative/loans',
                    '/cooperative/payments',
                    '/cooperative/dues',
                    '/cooperative/shu',
                    '/cooperative/pos',
                    '/cooperative/reports',
                ],
                [
                    '/cooperative/operator/closing',
                ],
            ],
            'Employee' => [
                'Employee',
                [
                    '/dashboard',
                ],
                [
                    '/cooperative/operator/dashboard',
                    '/cooperative/operator/closing',
                ],
            ],
            'Technician' => [
                'Technician',
                [
                    '/dashboard',
                ],
                [
                    '/cooperative/operator/dashboard',
                    '/cooperative/operator/closing',
                ],
            ],
            'HR Pusat' => [
                'HR Pusat',
                [
                    '/dashboard',
                    '/departments',
                    '/positions',
                    '/job-grades',
                    '/salary-structures',
                ],
                [],
            ],
            'Finance Pusat' => [
                'Finance Pusat',
                [
                    '/dashboard',
                    '/finance/chart-of-accounts',
                    '/finance/bank-batches',
                    '/payrolls',
                ],
                [],
            ],
            'Project Manager' => [
                'Project Manager',
                [
                    '/dashboard',
                    '/procurement/purchase-requests',
                    '/procurement/purchase-orders',
                    '/procurement/grns',
                ],
                [],
            ],
            'System Admin' => [
                'System Admin',
                [
                    '/dashboard',
                    '/users',
                    '/roles',
                    '/organizations',
                    '/audit-logs',
                    '/reports',
                    '/cooperative/operator/dashboard',
                    '/cooperative/operator/closing',
                    '/finance/chart-of-accounts',
                    '/departments',
                    '/payrolls',
                    '/procurement/purchase-requests',
                ],
                [],
            ],
            'Admin Pusat' => [
                'Admin Pusat',
                [
                    '/dashboard',
                    '/users',
                    '/roles',
                    '/organizations',
                    '/audit-logs',
                    '/reports',
                    '/cooperative/operator/dashboard',
                    '/finance/chart-of-accounts',
                    '/departments',
                    '/payrolls',
                    '/procurement/purchase-requests',
                ],
                [],
            ],
        ];
    }

    #[DataProvider('roleAccessMatrixProvider')]
    public function test_role_can_access_allowed_routes(string $role, array $allowed, array $forbidden): void
    {
        $user = $this->user($role);

        foreach ($allowed as $route) {
            $status = $this->actingAs($user)
                ->get($route)
                ->getStatusCode();

            $this->assertContains($status, [200, 302], "{$role} seharusnya bisa mengakses {$route}, tapi mendapat {$status}");
        }
    }

    #[DataProvider('roleAccessMatrixProvider')]
    public function test_role_is_forbidden_from_unauthorized_routes(string $role, array $allowed, array $forbidden): void
    {
        $user = $this->user($role);

        if ($forbidden === []) {
            $this->assertNotEmpty($allowed, "{$role} should have at least one allowed route in the smoke matrix.");

            return;
        }

        foreach ($forbidden as $route) {
            $response = $this->actingAs($user)->get($route);

            // 403 Forbidden atau redirect ke dashboard (untuk middleware EnsureIsMember
            $this->assertContains(
                $response->getStatusCode(),
                [302, 403],
                "{$role} seharusnya tidak bisa mengakses {$route}, tapi mendapat ".$response->getStatusCode()
            );
        }
    }

    // ─── Permission guard on frontend sidebar ───────────────

    public function test_admin_can_access_all_sidebar_menu_links(): void
    {
        $admin = $this->user('System Admin');
        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/cooperative/operator/dashboard')->assertOk();
        $this->actingAs($admin)->get('/cooperative/members')->assertOk();
        $this->actingAs($admin)->get('/cooperative/loans')->assertOk();
    }

    public function test_admin_koperasi_has_only_operational_cooperative_sidebar_permissions(): void
    {
        $adminKoperasi = $this->user('Admin Koperasi');

        $permissions = $adminKoperasi->getAllPermissions()->pluck('name');

        $this->assertTrue($permissions->contains('manage_cooperative_member'));
        $this->assertTrue($permissions->contains('manage_cooperative_dues'));
        $this->assertTrue($permissions->contains('manage_cooperative_payment'));
        $this->assertTrue($permissions->contains('view_cooperative_ledger'));
        $this->assertTrue($permissions->contains('view_cooperative_loan'));
        $this->assertTrue($permissions->contains('manage_cooperative_loan'));
        $this->assertFalse($permissions->contains('review_cooperative_loan'));
        $this->assertFalse($permissions->contains('approve_cooperative_loan'));
        $this->assertTrue($permissions->contains('manage_cooperative_points'));
        $this->assertTrue($permissions->contains('manage_cooperative_rewards'));
        $this->assertTrue($permissions->contains('manage_cooperative_redemption'));
        $this->assertTrue($permissions->contains('manage_cooperative_shu'));
        $this->assertTrue($permissions->contains('access_cooperative_pos'));
        $this->assertTrue($permissions->contains('manage_pos_products'));
        $this->assertTrue($permissions->contains('manage_pos_categories'));

        $this->assertFalse($permissions->contains('view_cooperative_report'));
        $this->assertFalse($permissions->contains('manage_pos_shu'));
        $this->assertFalse($permissions->contains('manage_cooperative_settings'));
        $this->assertFalse($permissions->contains('view_reports'));
        $this->assertFalse($permissions->contains('manage_departments'));
        $this->assertFalse($permissions->contains('view_chart_of_accounts'));
    }

    public function test_role_permission_seeder_creates_admin_koperasi_with_required_operational_permissions(): void
    {
        $adminKoperasi = Role::query()->where('name', 'Admin Koperasi')->first();

        $this->assertNotNull($adminKoperasi);

        $permissionNames = $adminKoperasi->permissions->pluck('name');

        $this->assertCount(22, $permissionNames);

        foreach ([
            'view_cooperative_member',
            'manage_cooperative_member',
            'validate_cooperative_member',
            'verify_cooperative_member',
            'manage_cooperative_dues',
            'manage_cooperative_payment',
            'view_cooperative_loan',
            'manage_cooperative_loan',
            'access_cooperative_pos',
            'manage_cooperative_points',
            'manage_cooperative_rewards',
            'manage_cooperative_redemption',
            'manage_cooperative_shu',
            'manage_cooperative_loan_types',
            'manage_pos_categories',
            'manage_pos_products',
            'view_pos_reports',
            'view_cooperative_ledger',
            'export_cooperative_member',
            'review_cooperative_resignation',
            'approve_pos_void',
            'manage_cooperative_opening_balance',
        ] as $permission) {
            $this->assertTrue(
                $permissionNames->contains($permission),
                "Admin Koperasi missing required permission [{$permission}]."
            );
        }

        foreach ([
            'approve_cooperative_loan',
            'review_cooperative_loan',
            'view_cooperative_report',
            'manage_pos_shu',
            'view_cooperative_all',
            'manage_cooperative_settings',
        ] as $permission) {
            $this->assertFalse(
                $permissionNames->contains($permission),
                "Admin Koperasi should not receive restricted permission [{$permission}]."
            );
        }
    }

    public function test_role_permission_seeder_creates_manajer_koperasi_between_pengurus_and_admin(): void
    {
        $manager = Role::query()->where('name', 'Manajer Koperasi')->first();

        $this->assertNotNull($manager);

        $permissionNames = $manager->permissions->pluck('name');

        foreach ([
            'view_cooperative_loan',
            'manage_cooperative_loan',
            'review_cooperative_loan',
            'view_cooperative_report',
            'manage_cooperative_ledger',
            'access_cooperative_pos',
        ] as $permission) {
            $this->assertTrue(
                $permissionNames->contains($permission),
                "Manajer Koperasi missing required permission [{$permission}]."
            );
        }

        $this->assertFalse($permissionNames->contains('approve_cooperative_loan'));
        $this->assertFalse($permissionNames->contains('approve_cooperative_member'));
        $this->assertFalse($permissionNames->contains('manage_cooperative_settings'));
    }

    public function test_cooperative_seeder_can_assign_admin_koperasi_after_role_permission_seeder_runs(): void
    {
        $this->seed(CooperativeSeeder::class);

        $adminKoperasi = User::query()
            ->where('email', 'admin.kop@koj.id')
            ->firstOrFail();

        $this->assertTrue($adminKoperasi->hasRole('Admin Koperasi'));
        $this->assertTrue($adminKoperasi->can('manage_cooperative_dues'));
        $this->assertTrue($adminKoperasi->can('manage_cooperative_payment'));
        $this->assertTrue($adminKoperasi->can('view_cooperative_ledger'));
        $this->assertTrue($adminKoperasi->can('manage_cooperative_shu'));
    }

    public function test_operator_dashboard_is_protected_for_unauthorized_roles(): void
    {
        $employee = $this->user('Employee');
        $anggota = $this->user('Anggota');

        $this->actingAs($employee)
            ->get('/cooperative/operator/dashboard')
            ->assertForbidden();

        $this->actingAs($anggota)
            ->get('/cooperative/operator/dashboard')
            ->assertForbidden();
    }

    public function test_operator_closing_is_protected_for_unauthorized_roles(): void
    {
        $kasir = $this->user('Kasir Koperasi');

        $this->actingAs($kasir)
            ->get('/cooperative/operator/closing')
            ->assertForbidden();
    }

    // ─── Authorization for operator JSON endpoints ───────────────

    public function test_operator_json_endpoints_require_authorization(): void
    {
        $employee = $this->user('Employee');

        $this->actingAs($employee)
            ->get('/cooperative/operator/approval-inbox')
            ->assertForbidden();

        $this->actingAs($employee)
            ->get('/cooperative/operator/exceptions')
            ->assertForbidden();

        $this->actingAs($employee)
            ->get('/cooperative/operator/analytics')
            ->assertForbidden();
    }

    // ─── Authorization for cooperative web routes ───────────────

    /**
     * Cooperative controllers now enforce policy-level authorization consistently.
     */
    public function test_cooperative_web_routes_authorization_consistency(): void
    {
        $employee = $this->user('Employee');

        $this->actingAs($employee)
            ->get('/cooperative/members')
            ->assertForbidden();

        $this->actingAs($employee)
            ->get('/cooperative/loans')
            ->assertForbidden();

        $this->actingAs($employee)
            ->get('/cooperative/payments')
            ->assertForbidden();
    }
}
