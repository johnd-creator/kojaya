<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Phase4ControllerAuthorizationTest extends TestCase
{
    use DatabaseMigrations;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function protectedRouteProvider(): array
    {
        return [
            'finance chart of accounts' => ['/finance/chart-of-accounts'],
            'finance bank batches' => ['/finance/bank-batches'],
            'petty cash' => ['/petty-cash'],
            'hr departments' => ['/departments'],
            'user management' => ['/users'],
            'roles' => ['/roles'],
            'audit logs' => ['/audit-logs'],
            'reports' => ['/reports'],
            'vendors' => ['/procurement/vendors'],
        ];
    }

    #[DataProvider('protectedRouteProvider')]
    public function test_user_without_phase_four_permissions_is_forbidden(string $uri): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get($uri)
            ->assertForbidden();
    }

    public function test_finance_role_can_access_finance_controllers(): void
    {
        $user = $this->user('Finance Pusat');

        $this->actingAs($user)->get('/finance/chart-of-accounts')->assertOk();
        $this->actingAs($user)->get('/finance/bank-batches')->assertOk();
        $this->actingAs($user)->get('/finance/trial-balance')->assertOk();
        $this->actingAs($user)->get('/petty-cash')->assertOk();
    }

    public function test_hr_role_can_access_hr_master_data_controllers(): void
    {
        $user = $this->user('HR Pusat');

        $this->actingAs($user)->get('/departments')->assertOk();
        $this->actingAs($user)->get('/positions')->assertOk();
        $this->actingAs($user)->get('/job-grades')->assertOk();
        $this->actingAs($user)->get('/salary-structures')->assertOk();
    }

    public function test_admin_role_can_access_system_admin_controllers(): void
    {
        $user = $this->user('Admin Pusat');

        $this->actingAs($user)->get('/users')->assertOk();
        $this->actingAs($user)->get('/roles')->assertOk();
        $this->actingAs($user)->get('/organizations')->assertOk();
        $this->actingAs($user)->get('/reports')->assertOk();
        $this->actingAs($user)->get('/audit-logs')->assertOk();
    }

    public function test_project_manager_technician_token_includes_review_ability(): void
    {
        $user = $this->user('Project Manager', [
            'email' => 'project.manager@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'project.manager@example.com',
            'password' => 'password',
            'app' => 'technician',
            'device_name' => 'test device',
        ]);

        $response->assertOk()
            ->assertJsonPath('abilities.0', 'profile:read')
            ->assertJsonPath('abilities.1', 'work-orders:read')
            ->assertJsonPath('abilities.2', 'work-orders:write')
            ->assertJsonPath('abilities.3', 'work-orders:review');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
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
}
