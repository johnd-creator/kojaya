<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleIntegrationTest extends TestCase
{
    use DatabaseMigrations, WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_all_prd_roles_exist_after_seeding(): void
    {
        $expectedRoles = [
            'System Admin',
            'Admin Pusat',
            'Admin Unit',
            'HR Pusat',
            'HR Unit',
            'Finance Pusat',
            'Finance Unit',
            'Project Manager',
            'Site Manager',
            'Technician',
            'Employee',
            'Pengurus Koperasi',
            'Manajer Koperasi',
            'Admin Koperasi',
            'Kasir Koperasi',
            'Anggota',
        ];

        foreach ($expectedRoles as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }

        $this->assertCount(count($expectedRoles), Role::all());
    }

    public function test_system_admin_is_created_with_pusat_organization(): void
    {
        $this->artisan('admin:create', [
            '--email' => 'admin@erp.com',
            '--name' => 'System Admin',
            '--password' => 'SecureAdminPass123!',
        ])->assertSuccessful();

        $admin = User::where('email', 'admin@erp.com')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('System Admin'));

        $this->assertNotNull($admin->organization_id);
        $this->assertEquals('KOP-001', $admin->organization->code);
    }

    public function test_creating_new_user_with_role_and_organization(): void
    {
        $pusat = Organization::where('code', 'KOP-001')->firstOrFail();
        $admin = User::factory()->create([
            'email' => 'admin@erp.com',
            'organization_id' => $pusat->id,
        ]);
        $admin->syncRoles(['System Admin']);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'John Doe',
            'email' => 'john@erp.com',
            'password' => 'password',
            'role' => 'Project Manager',
            'organization_id' => $pusat->id,
        ]);

        $response->assertRedirect();

        $newUser = User::where('email', 'john@erp.com')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole('Project Manager'));
        $this->assertEquals($pusat->id, $newUser->organization_id);
    }
}
