<?php

namespace Tests\Feature\Cooperative;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_anggota_does_not_have_admin_permissions(): void
    {
        $anggota = Role::where('name', 'Anggota')->first();
        $permissionNames = $anggota->permissions->pluck('name');

        foreach ([
            'view_cooperative_member',
            'manage_cooperative_member',
            'view_cooperative_loan',
            'manage_cooperative_loan',
            'review_cooperative_loan',
            'approve_cooperative_loan',
            'manage_cooperative_dues',
            'manage_cooperative_payment',
            'export_cooperative_member',
            'review_cooperative_resignation',
            'access_cooperative_pos',
            'view_cooperative_ledger',
            'manage_cooperative_ledger',
            'view_cooperative_all',
        ] as $permission) {
            $this->assertFalse(
                $permissionNames->contains($permission),
                "Anggota must not have admin permission [{$permission}].",
            );
        }
    }

    public function test_anggota_has_member_portal_access(): void
    {
        $anggota = Role::where('name', 'Anggota')->first();

        $this->assertTrue(
            $anggota->permissions->pluck('name')->contains('member_portal_access'),
            'Anggota must have member_portal_access permission.',
        );
    }

    public function test_kasir_does_not_have_member_management_or_loan_approval(): void
    {
        $kasir = Role::where('name', 'Kasir Koperasi')->first();
        $permissionNames = $kasir->permissions->pluck('name');

        foreach ([
            'manage_cooperative_member',
            'approve_cooperative_member',
            'approve_cooperative_loan',
            'review_cooperative_loan',
            'export_cooperative_member',
            'review_cooperative_resignation',
            'manage_cooperative_shu',
            'manage_cooperative_settings',
        ] as $permission) {
            $this->assertFalse(
                $permissionNames->contains($permission),
                "Kasir Koperasi must not have permission [{$permission}].",
            );
        }
    }

    public function test_admin_koperasi_does_not_have_final_approval(): void
    {
        $admin = Role::where('name', 'Admin Koperasi')->first();
        $permissionNames = $admin->permissions->pluck('name');

        $this->assertFalse(
            $permissionNames->contains('approve_cooperative_loan'),
            'Admin Koperasi must not have final loan approval.',
        );
        $this->assertFalse(
            $permissionNames->contains('review_cooperative_loan'),
            'Admin Koperasi must not have loan review permission.',
        );
    }

    public function test_all_enum_permissions_exist_in_database_after_seeding(): void
    {
        $enumValues = \App\Enums\PermissionEnum::values();
        $dbPermissions = \Spatie\Permission\Models\Permission::pluck('name')->toArray();

        foreach ($enumValues as $permission) {
            $this->assertContains(
                $permission,
                $dbPermissions,
                "Permission [{$permission}] from PermissionEnum must exist in database after seeding.",
            );
        }
    }

    public function test_no_permission_string_in_seeder_is_missing_from_enum(): void
    {
        $enumValues = \App\Enums\PermissionEnum::values();
        $dbPermissions = \Spatie\Permission\Models\Permission::pluck('name')->toArray();

        foreach ($dbPermissions as $permission) {
            $this->assertContains(
                $permission,
                $enumValues,
                "Permission [{$permission}] in database must exist in PermissionEnum.",
            );
        }
    }

    public function test_permission_cache_is_reset_after_seeding(): void
    {
        // After seeding, the permission cache should be clean.
        // This test verifies by checking that a freshly created user with Anggota role
        // can immediately use the permission.
        $user = \App\Models\User::factory()->create();
        $user->assignRole('Anggota');

        $this->assertTrue($user->can('member_portal_access'));
        $this->assertFalse($user->can('view_cooperative_member'));
    }

    public function test_pengurus_has_export_and_resignation_review(): void
    {
        $pengurus = Role::where('name', 'Pengurus Koperasi')->first();
        $permissionNames = $pengurus->permissions->pluck('name');

        $this->assertTrue($permissionNames->contains('export_cooperative_member'));
        $this->assertTrue($permissionNames->contains('review_cooperative_resignation'));
    }

    public function test_manajer_has_resignation_review(): void
    {
        $manajer = Role::where('name', 'Manajer Koperasi')->first();
        $permissionNames = $manajer->permissions->pluck('name');

        $this->assertTrue($permissionNames->contains('review_cooperative_resignation'));
    }
}
