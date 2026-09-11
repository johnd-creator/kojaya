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

            // If the roles table is completely empty (e.g. unseeded database or isolated test suites
            // testing role creation from scratch), there are no existing production roles to cut over.
            if (Role::count() === 0) {
                return;
            }

            // Invalidate permission cache first
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // 1. Ensure required attendance permissions exist deterministically
            $viewAll = Permission::firstOrCreate([
                'name' => PermissionEnum::ATTENDANCE_VIEW_ALL->value,
                'guard_name' => $guard,
            ]);

            $viewUnit = Permission::firstOrCreate([
                'name' => PermissionEnum::ATTENDANCE_VIEW_UNIT->value,
                'guard_name' => $guard,
            ]);

            $approveAttendance = Permission::firstOrCreate([
                'name' => PermissionEnum::ATTENDANCE_APPROVE->value,
                'guard_name' => $guard,
            ]);

            // 2. Ensure canonical roles exist
            $hrPusat = Role::firstOrCreate(['name' => 'HR Pusat', 'guard_name' => $guard]);
            $hrUnit = Role::firstOrCreate(['name' => 'HR Unit', 'guard_name' => $guard]);
            $adminUnit = Role::firstOrCreate(['name' => 'Admin Unit', 'guard_name' => $guard]);
            $employee = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => $guard]);

            // 3. Mutate ONLY the specific attendance permission edges:
            // HR Pusat: grant view_attendance_all and approve_attendance
            if (! $hrPusat->hasPermissionTo($viewAll)) {
                $hrPusat->givePermissionTo($viewAll);
            }
            if (! $hrPusat->hasPermissionTo($approveAttendance)) {
                $hrPusat->givePermissionTo($approveAttendance);
            }

            // HR Unit: grant view_attendance_unit and approve_attendance
            if (! $hrUnit->hasPermissionTo($viewUnit)) {
                $hrUnit->givePermissionTo($viewUnit);
            }
            if (! $hrUnit->hasPermissionTo($approveAttendance)) {
                $hrUnit->givePermissionTo($approveAttendance);
            }

            // Admin Unit: ensure view_attendance_unit is present, ensure approve_attendance is NOT present
            if (! $adminUnit->hasPermissionTo($viewUnit)) {
                $adminUnit->givePermissionTo($viewUnit);
            }
            if ($adminUnit->hasPermissionTo($approveAttendance)) {
                $adminUnit->revokePermissionTo($approveAttendance);
            }

            // Employee: revoke view_attendance_unit to prevent unauthorized unit-wide attendance admin access
            if ($employee->hasPermissionTo($viewUnit)) {
                $employee->revokePermissionTo($viewUnit);
            }
            if ($employee->hasPermissionTo($viewAll)) {
                $employee->revokePermissionTo($viewAll);
            }
            if ($employee->hasPermissionTo($approveAttendance)) {
                $employee->revokePermissionTo($approveAttendance);
            }

            // Invalidate cache after changes
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Security Note: Rollback must NOT restore 'view_attendance_unit' to the
     * 'Employee' role, as doing so would reintroduce vulnerability SEC-P1-11-A.
     * Rollback only safely revokes newly granted permissions from HR roles.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            if (Role::count() === 0) {
                return;
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $guard = config('auth.defaults.guard', 'web');

            $viewAll = Permission::where(['name' => PermissionEnum::ATTENDANCE_VIEW_ALL->value, 'guard_name' => $guard])->first();
            $viewUnit = Permission::where(['name' => PermissionEnum::ATTENDANCE_VIEW_UNIT->value, 'guard_name' => $guard])->first();
            $approve = Permission::where(['name' => PermissionEnum::ATTENDANCE_APPROVE->value, 'guard_name' => $guard])->first();

            $hrPusat = Role::where(['name' => 'HR Pusat', 'guard_name' => $guard])->first();
            if ($hrPusat) {
                if ($viewAll && $hrPusat->hasPermissionTo($viewAll)) {
                    $hrPusat->revokePermissionTo($viewAll);
                }
                if ($approve && $hrPusat->hasPermissionTo($approve)) {
                    $hrPusat->revokePermissionTo($approve);
                }
            }

            $hrUnit = Role::where(['name' => 'HR Unit', 'guard_name' => $guard])->first();
            if ($hrUnit) {
                if ($viewUnit && $hrUnit->hasPermissionTo($viewUnit)) {
                    $hrUnit->revokePermissionTo($viewUnit);
                }
                if ($approve && $hrUnit->hasPermissionTo($approve)) {
                    $hrUnit->revokePermissionTo($approve);
                }
            }

            // Intentionally do NOT re-grant view_attendance_unit to Employee to avoid restoring insecure state.

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
};
