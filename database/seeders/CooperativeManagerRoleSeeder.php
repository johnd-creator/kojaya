<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CooperativeManagerRoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionEnum::values() as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission]);
        }

        $role = Role::query()
            ->firstOrCreate(['name' => 'Manajer Koperasi'])
            ->syncPermissions($this->permissions());

        $organization = Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Koperasi Jaya Bersama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'is_active' => true,
            ],
        );

        $user = User::query()->updateOrCreate(
            ['email' => 'manajer.kop@koj.id'],
            [
                'name' => 'Manajer Koperasi',
                'password' => 'password',
                'organization_id' => $organization->id,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([$role->name]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * @return list<string>
     */
    private function permissions(): array
    {
        return [
            'view_cooperative_member',
            'manage_cooperative_dues',
            'manage_cooperative_payment',
            'view_cooperative_loan',
            'manage_cooperative_loan',
            'review_cooperative_loan',
            'access_cooperative_pos',
            'view_cooperative_report',
            'manage_cooperative_points',
            'manage_cooperative_rewards',
            'manage_cooperative_redemption',
            'manage_cooperative_shu',
            'manage_cooperative_loan_types',
            'manage_pos_categories',
            'manage_pos_products',
            'view_pos_reports',
            'manage_pos_shu',
            'view_cooperative_ledger',
            'manage_cooperative_ledger',
            'manage_cooperative_opening_balance',
            'approve_pos_void',
        ];
    }
}
