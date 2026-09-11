<?php

use App\Enums\PermissionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $guard = config('auth.defaults.guard', 'web');

            // Invalidate permission cache first
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // 1. Ensure view_bank_batch_all permission exists deterministically
            $viewAll = Permission::firstOrCreate([
                'name' => PermissionEnum::BANK_BATCH_VIEW_ALL->value,
                'guard_name' => $guard,
            ]);

            // If the roles table is completely empty (e.g. unseeded database or isolated test suites
            // testing role creation from scratch), there are no existing production roles to cut over.
            if (Role::count() === 0) {
                app(PermissionRegistrar::class)->forgetCachedPermissions();

                return;
            }

            // 2. Ensure canonical roles exist
            $financePusat = Role::firstOrCreate(['name' => 'Finance Pusat', 'guard_name' => $guard]);
            $financeUnit = Role::firstOrCreate(['name' => 'Finance Unit', 'guard_name' => $guard]);
            $systemAdmin = Role::firstOrCreate(['name' => 'System Admin', 'guard_name' => $guard]);
            $adminPusat = Role::firstOrCreate(['name' => 'Admin Pusat', 'guard_name' => $guard]);

            // 3. Finance Pusat: grant view_bank_batch_all
            if (! $financePusat->hasPermissionTo($viewAll)) {
                $financePusat->givePermissionTo($viewAll);
            }

            // 4. System Admin: grant view_bank_batch_all
            if (! $systemAdmin->hasPermissionTo($viewAll)) {
                $systemAdmin->givePermissionTo($viewAll);
            }

            // 5. Admin Pusat: grant view_bank_batch_all
            if (! $adminPusat->hasPermissionTo($viewAll)) {
                $adminPusat->givePermissionTo($viewAll);
            }

            // 6. Finance Unit: explicitly revoke if present to guarantee tenant isolation
            if ($financeUnit->hasPermissionTo($viewAll)) {
                $financeUnit->revokePermissionTo($viewAll);
            }

            // Invalidate cache after changes
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Security Note: Rollback must NOT grant 'view_bank_batch_all' to 'Finance Unit'.
     * Rollback safely removes the permission assignments from global roles and deletes the permission record.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            if (Role::count() === 0) {
                return;
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $guard = config('auth.defaults.guard', 'web');

            $viewAll = Permission::where([
                'name' => PermissionEnum::BANK_BATCH_VIEW_ALL->value,
                'guard_name' => $guard,
            ])->first();

            if ($viewAll) {
                foreach (['Finance Pusat', 'System Admin', 'Admin Pusat'] as $roleName) {
                    $role = Role::where(['name' => $roleName, 'guard_name' => $guard])->first();
                    if ($role && $role->hasPermissionTo($viewAll)) {
                        $role->revokePermissionTo($viewAll);
                    }
                }

                // Security Note: Never grant view_bank_batch_all to Finance Unit during down().
                $viewAll->delete();
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
};
