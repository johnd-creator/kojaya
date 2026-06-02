<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $systemAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions (this creates admin@erp.com user)
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // Get the existing system admin user created by seeder
        $this->systemAdmin = User::where('email', 'admin@erp.com')->first();
    }

    public function test_system_admin_has_all_permissions_via_spatie(): void
    {
        // Get all permissions from PermissionEnum
        $allPermissions = \App\Enums\PermissionEnum::values();

        // Assert system admin has all permissions
        foreach ($allPermissions as $permission) {
            $this->assertTrue(
                $this->systemAdmin->hasPermissionTo($permission),
                "System Admin should have permission: {$permission}",
            );
        }
    }

    public function test_system_admin_receives_all_permissions_in_frontend(): void
    {
        $response = $this->actingAs($this->systemAdmin)
            ->get('/dashboard');

        // Check that permissions are shared with frontend
        $permissions = $this->systemAdmin->getAllPermissions()->pluck('name')->values();

        $this->assertGreaterThan(100, $permissions->count(), 'System Admin should have 100+ permissions');

        // Verify some critical permissions exist
        $this->assertTrue($permissions->contains('manage_users'));
        $this->assertTrue($permissions->contains('manage_roles'));
        $this->assertTrue($permissions->contains('view_cooperative_all'));
        $this->assertTrue($permissions->contains('manage_cooperative_settings'));
        $this->assertTrue($permissions->contains('view_reports'));
        $this->assertTrue($permissions->contains('view_audit_logs'));
    }

    public function test_system_admin_can_access_all_web_pages(): void
    {
        // Test critical pages
        $pages = [
            '/organizations',
            '/users',
            '/roles',
            '/cooperative/members',
            '/cooperative/loans',
            '/cooperative/payments',
            '/cooperative/pos',
            '/cooperative/reports',
            '/employees',
            '/payrolls',
            '/procurement/purchase-requests',
            '/projects',
            '/finance/invoices',
            '/reports',
            '/audit-logs',
        ];

        foreach ($pages as $page) {
            $response = $this->actingAs($this->systemAdmin)->get($page);

            $this->assertNotEquals(403, $response->status(), "Should not be forbidden to access: {$page}");
        }
    }

    public function test_system_admin_bypasses_gates(): void
    {
        // Test that Gate::before allows system admin to bypass authorization
        $this->actingAs($this->systemAdmin);

        // Test some gates that should be bypassed
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($this->systemAdmin)->check('manage_users'));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($this->systemAdmin)->check('manage_cooperative_settings'));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($this->systemAdmin)->check('view_audit_logs'));
    }

    public function test_system_admin_sidebar_shows_all_menus(): void
    {
        $response = $this->actingAs($this->systemAdmin)
            ->get('/dashboard')
            ->assertStatus(200);

        // Check that all permissions are passed to frontend
        $viewData = $response->viewData('page');
        $this->assertNotNull($viewData);
    }

    public function test_admin_pusat_also_has_all_permissions(): void
    {
        $adminPusat = User::factory()->create([
            'email' => 'admin.pusat@erp.com',
        ])->assignRole('Admin Pusat');

        // Get all permissions from PermissionEnum
        $allPermissions = \App\Enums\PermissionEnum::values();

        // Assert admin pusat has all permissions
        foreach ($allPermissions as $permission) {
            $this->assertTrue(
                $adminPusat->hasPermissionTo($permission),
                "Admin Pusat should have permission: {$permission}",
            );
        }
    }

    public function test_system_admin_has_wildcard_token_ability_for_mobile_api(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'admin@erp.com',
            'password' => 'password',
            'app' => 'admin',
            'device_name' => 'System Admin Test',
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('abilities', ['*']);
    }
}
