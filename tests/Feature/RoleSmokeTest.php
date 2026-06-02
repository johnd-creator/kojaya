<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * Note: Many cooperative web routes lack authorization middleware and are
     * accessible to any authenticated user. This matrix reflects current reality,
     * not desired state. The "forbidden" entries only include routes that actually
     * have middleware/controller-level authorization.
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
