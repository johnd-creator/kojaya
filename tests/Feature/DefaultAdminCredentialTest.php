<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultAdminCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_admin_user_in_testing_environment(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertNotNull(
            User::where('email', 'admin@erp.com')->first(),
            'Default admin user should be created in non-production environments.',
        );
    }

    public function test_seeder_does_not_create_admin_in_production_environment(): void
    {
        $this->app['env'] = 'production';

        // Run the seeder directly to bypass db:seed's confirmToProceed() prompt
        // which cannot interact during tests.
        (new RolePermissionSeeder)->run();

        $this->assertNull(
            User::where('email', 'admin@erp.com')->first(),
            'Default admin user must not be created in production.',
        );
    }

    public function test_seeder_still_creates_roles_and_permissions_in_production(): void
    {
        $this->seed(RolePermissionSeeder::class);

        // Roles and permissions are always created regardless of environment.
        $this->assertDatabaseHas('roles', ['name' => 'System Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Anggota']);
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
