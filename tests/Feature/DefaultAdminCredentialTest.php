<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\CooperativeReferenceSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_admin_create_command_creates_new_user(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CooperativeReferenceSeeder::class);

        $this->artisan('admin:create', [
            '--email' => 'newadmin@example.com',
            '--name' => 'New Admin',
            '--password' => 'SecurePass123!',
            '--role' => 'System Admin',
        ])->assertSuccessful();

        $user = User::where('email', 'newadmin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('System Admin'));
        $this->assertTrue(Hash::check('SecurePass123!', $user->password));
        $this->assertNotNull($user->organization_id);
    }

    public function test_admin_create_command_generates_random_password_when_omitted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->artisan('admin:create', [
            '--email' => 'randomadmin@example.com',
            '--name' => 'Random Admin',
        ])->assertSuccessful();

        $user = User::where('email', 'randomadmin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertFalse(
            Hash::check('password', $user->password),
            'Generated password should not be the literal string "password".',
        );
    }

    public function test_admin_create_refuses_to_overwrite_existing_user_by_default(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing Operator',
            'password' => Hash::make('ExistingSecret123!'),
        ]);
        $existingUser->assignRole('Anggota');

        $this->artisan('admin:create', [
            '--email' => 'existing@example.com',
            '--name' => 'Attempted Overwrite',
            '--password' => 'NewSecretPassword!',
        ])->assertFailed();

        $existingUser->refresh();
        $this->assertSame('Existing Operator', $existingUser->name);
        $this->assertTrue(Hash::check('ExistingSecret123!', $existingUser->password));
        $this->assertFalse($existingUser->hasRole('System Admin'));
        $this->assertTrue($existingUser->hasRole('Anggota'));
    }

    public function test_admin_create_with_update_existing_preserves_password_when_password_omitted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing Operator',
            'password' => Hash::make('ExistingSecret123!'),
        ]);

        $this->artisan('admin:create', [
            '--email' => 'existing@example.com',
            '--role' => 'Pengurus Koperasi',
            '--update-existing' => true,
        ])->assertSuccessful();

        $existingUser->refresh();
        $this->assertTrue(
            Hash::check('ExistingSecret123!', $existingUser->password),
            'Existing user password must be preserved when --password is not explicitly provided with --update-existing.',
        );
        $this->assertTrue($existingUser->hasRole('Pengurus Koperasi'));
    }

    public function test_admin_create_with_update_existing_updates_password_when_explicitly_provided(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing Operator',
            'password' => Hash::make('ExistingSecret123!'),
        ]);

        $this->artisan('admin:create', [
            '--email' => 'existing@example.com',
            '--password' => 'NewExplicitSecret123!',
            '--update-existing' => true,
        ])->assertSuccessful();

        $existingUser->refresh();
        $this->assertTrue(
            Hash::check('NewExplicitSecret123!', $existingUser->password),
            'Explicitly provided password must update the account when --update-existing is specified.',
        );
    }

    public function test_admin_create_fails_and_does_not_create_user_when_password_is_empty(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->artisan('admin:create', [
            '--email' => 'empty@example.com',
            '--name' => 'Empty Password Admin',
            '--password' => '',
        ])->assertFailed();

        $this->assertNull(User::where('email', 'empty@example.com')->first());
    }

    public function test_admin_create_fails_and_does_not_create_user_when_password_is_weak(): void
    {
        $this->seed(RolePermissionSeeder::class);

        // Test with too short password
        $this->artisan('admin:create', [
            '--email' => 'weak1@example.com',
            '--name' => 'Weak Admin 1',
            '--password' => 'short',
        ])->assertFailed();

        $this->assertNull(User::where('email', 'weak1@example.com')->first());

        // Test with password missing mixed case, numbers, or symbols
        $this->artisan('admin:create', [
            '--email' => 'weak2@example.com',
            '--name' => 'Weak Admin 2',
            '--password' => 'onlylowercaseletters12345',
        ])->assertFailed();

        $this->assertNull(User::where('email', 'weak2@example.com')->first());
    }

    public function test_admin_create_with_update_existing_fails_and_preserves_state_when_password_is_weak(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $existingUser = User::factory()->create([
            'email' => 'existing.member@example.com',
            'name' => 'Original Member Name',
            'password' => Hash::make('OriginalSecureSecret123!'),
            'organization_id' => null,
        ]);
        $existingUser->assignRole('Anggota');

        $this->artisan('admin:create', [
            '--email' => 'existing.member@example.com',
            '--name' => 'Attempted Modified Name',
            '--password' => 'weakpass',
            '--role' => 'System Admin',
            '--update-existing' => true,
        ])->assertFailed();

        $existingUser->refresh();
        $this->assertSame('Original Member Name', $existingUser->name);
        $this->assertTrue(Hash::check('OriginalSecureSecret123!', $existingUser->password));
        $this->assertTrue($existingUser->hasRole('Anggota'));
        $this->assertFalse($existingUser->hasRole('System Admin'));
        $this->assertNull($existingUser->organization_id);
    }

    public function test_admin_create_preserves_existing_null_organization_on_update(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CooperativeReferenceSeeder::class);

        $existingUser = User::factory()->create([
            'email' => 'nullorg.admin@example.com',
            'organization_id' => null,
            'password' => Hash::make('OriginalSecret123!'),
        ]);

        $this->artisan('admin:create', [
            '--email' => 'nullorg.admin@example.com',
            '--role' => 'Pengurus Koperasi',
            '--update-existing' => true,
        ])->assertSuccessful();

        $existingUser->refresh();
        $this->assertNull(
            $existingUser->organization_id,
            'Updating an existing user with NULL organization_id must leave organization_id as NULL without assigning KOP-001.',
        );
        $this->assertTrue($existingUser->hasRole('Pengurus Koperasi'));
    }

    public function test_admin_create_preserves_existing_organization_on_update(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CooperativeReferenceSeeder::class);

        $customBranch = Organization::factory()->create(['code' => 'CUSTOM-BRANCH-001']);
        $existingUser = User::factory()->create([
            'email' => 'branch.admin@example.com',
            'organization_id' => $customBranch->id,
            'password' => Hash::make('Secret123!'),
        ]);

        $this->artisan('admin:create', [
            '--email' => 'branch.admin@example.com',
            '--role' => 'Admin Koperasi',
            '--update-existing' => true,
        ])->assertSuccessful();

        $existingUser->refresh();
        $this->assertSame(
            $customBranch->id,
            $existingUser->organization_id,
            'Updating an existing user must preserve their existing organization assignment.',
        );
    }

    public function test_admin_create_fails_for_unauthorized_or_nonexistent_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        // Disallowed non-admin role
        $this->artisan('admin:create', [
            '--email' => 'operator@example.com',
            '--role' => 'Employee',
        ])->assertFailed();

        // Nonexistent role
        $this->artisan('admin:create', [
            '--email' => 'hacker@example.com',
            '--role' => 'Superuser',
        ])->assertFailed();

        $this->assertNull(User::where('email', 'operator@example.com')->first());
        $this->assertNull(User::where('email', 'hacker@example.com')->first());
    }
}
