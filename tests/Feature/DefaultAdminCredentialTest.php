<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultAdminCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_does_not_create_admin_user_in_any_environment(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertNull(
            User::where('email', 'admin@erp.com')->first(),
            'RolePermissionSeeder must not create privileged users with default passwords in testing.',
        );

        $this->app['env'] = 'production';
        (new RolePermissionSeeder)->run();

        $this->assertNull(
            User::where('email', 'admin@erp.com')->first(),
            'RolePermissionSeeder must not create privileged users with default passwords in production.',
        );
    }

    public function test_seeder_creates_roles_and_permissions_in_all_environments(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'System Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Anggota']);
        $this->assertDatabaseHas('roles', ['name' => 'Pengurus Koperasi']);
        $this->assertDatabaseHas('roles', ['name' => 'Manajer Koperasi']);
        $this->assertDatabaseHas('roles', ['name' => 'Admin Koperasi']);
        $this->assertDatabaseHas('roles', ['name' => 'Kasir Koperasi']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_cooperative_member']);
        $this->assertDatabaseHas('permissions', ['name' => 'export_cooperative_member']);
        $this->assertDatabaseHas('permissions', ['name' => 'review_cooperative_resignation']);
    }

    public function test_admin_create_command_creates_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->artisan('admin:create', [
            '--email' => 'newadmin@example.com',
            '--name' => 'New Admin',
            '--password' => 'SecurePass123!',
        ])->assertSuccessful();

        $user = User::where('email', 'newadmin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('System Admin'));
    }

    public function test_admin_create_command_generates_random_password(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->artisan('admin:create', [
            '--email' => 'randomadmin@example.com',
            '--name' => 'Random Admin',
        ])->assertSuccessful();

        $user = User::where('email', 'randomadmin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertFalse(
            \Illuminate\Support\Facades\Hash::check('password', $user->password),
            'Generated password should not be the literal string "password".',
        );
    }
}
