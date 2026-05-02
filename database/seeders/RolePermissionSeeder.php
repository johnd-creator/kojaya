<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles based on PRD
        $roles = [
            'System Admin',
            'Admin Pusat',
            'Admin Unit',
            'HR Pusat',
            'HR Unit',
            'Finance Pusat',
            'Finance Unit',
            'Project Manager',
            'Site Manager',
            'Karyawan',
            'Pengurus Koperasi',
            'Kasir Koperasi',
            'Anggota',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Create Permissions from Enum
        $permissions = \App\Enums\PermissionEnum::values();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign ALL permissions to System Admin role
        $systemAdmin = Role::where('name', 'System Admin')->first();
        if ($systemAdmin) {
            $systemAdmin->syncPermissions($permissions);
        }

        Role::where('name', 'Pengurus Koperasi')->first()?->syncPermissions([
            'view_cooperative_member',
            'manage_cooperative_member',
            'manage_cooperative_dues',
            'manage_cooperative_payment',
            'access_cooperative_pos',
            'view_cooperative_report',
        ]);

        Role::where('name', 'Kasir Koperasi')->first()?->syncPermissions([
            'view_cooperative_member',
            'manage_cooperative_payment',
            'access_cooperative_pos',
        ]);

        Role::where('name', 'Anggota')->first()?->syncPermissions([
            'view_cooperative_member',
        ]);

        // Ensure initially there's a head office (Pusat) Organization
        $pusat = Organization::updateOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => Organization::query()->where('code', 'KOP-001')->value('id') ?? Str::uuid(),
                'name' => 'Koperasi Jaya Bersama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'parent_id' => null,
                'address' => 'Jalan Jaya Bersama No. 1, Jakarta',
                'phone' => '021-12345678',
                'email' => 'info@koperasijayabersama.id',
                'is_active' => true,
            ]
        );

        // Create Super Admin User
        $user = User::updateOrCreate(
            ['email' => 'admin@erp.com'],
            [
                'name' => 'System Admin ERP',
                'password' => Hash::make('password'),
                'organization_id' => $pusat->id,
            ]
        );

        $user->assignRole('System Admin');
    }
}
