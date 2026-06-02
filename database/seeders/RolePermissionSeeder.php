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
            'Technician',
            'Employee',
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

        // System Admin & Admin Pusat: ALL permissions
        Role::where('name', 'System Admin')->first()?->syncPermissions($permissions);
        Role::where('name', 'Admin Pusat')->first()?->syncPermissions($permissions);

        // Admin Unit
        Role::where('name', 'Admin Unit')->first()?->syncPermissions([
            'view_employee_unit',
            'view_attendance_unit',
            'view_pr_all',
            'view_po_all',
            'view_grn_all',
            'view_project_unit',
            'view_asset_unit',
            'view_work_order_unit',
            'view_leave_unit',
            'view_overtime_unit',
            'manage_petty_cash',
            'manage_reimbursement',
        ]);

        // HR Pusat
        Role::where('name', 'HR Pusat')->first()?->syncPermissions([
            'view_employee_all',
            'create_employee',
            'edit_employee',
            'view_payroll_all',
            'process_payroll',
            'manage_departments',
            'manage_positions',
            'manage_job_grades',
            'manage_work_shifts',
            'manage_salary_structures',
            'manage_shift_rosters',
            'view_leave_all',
            'approve_leave',
            'view_overtime_all',
            'approve_overtime',
            'access_ess_portal',
            'manage_employee_contract',
            'manage_employee_family',
            'manage_employee_transfer',
            'approve_employee_transfer',
        ]);

        // HR Unit
        Role::where('name', 'HR Unit')->first()?->syncPermissions([
            'view_employee_unit',
            'create_employee',
            'edit_employee',
            'view_payroll_unit',
            'process_payroll',
            'view_leave_unit',
            'approve_leave',
            'view_overtime_unit',
            'approve_overtime',
            'access_ess_portal',
            'manage_employee_transfer',
        ]);

        // Finance Pusat
        Role::where('name', 'Finance Pusat')->first()?->syncPermissions([
            'view_payroll_all',
            'process_payroll',
            'approve_payroll',
            'view_pr_all',
            'view_po_all',
            'view_grn_all',
            'view_invoice_all',
            'view_budget_all',
            'manage_budget',
            'manage_petty_cash',
            'manage_bank_batch',
            'manage_bank_reconciliation',
            'view_chart_of_accounts',
            'manage_chart_of_accounts',
            'manage_journal_entries',
            'view_trial_balance',
            'view_balance_sheet',
            'view_income_statement',
            'manage_efaktur',
            'manage_reimbursement',
            'approve_reimbursement',
        ]);

        // Finance Unit
        Role::where('name', 'Finance Unit')->first()?->syncPermissions([
            'view_payroll_unit',
            'process_payroll',
            'view_pr_all',
            'view_po_all',
            'view_grn_all',
            'manage_petty_cash',
            'manage_bank_batch',
            'manage_bank_reconciliation',
            'view_chart_of_accounts',
            'view_trial_balance',
            'view_balance_sheet',
            'view_income_statement',
            'manage_reimbursement',
            'approve_reimbursement',
        ]);

        // Project Manager
        Role::where('name', 'Project Manager')->first()?->syncPermissions([
            'view_pr_all',
            'create_pr',
            'approve_pr',
            'view_po_all',
            'create_po',
            'view_grn_all',
            'receive_grn',
            'view_project_all',
            'manage_project',
            'manage_project_team',
            'view_asset_all',
            'manage_asset',
            'view_work_order_all',
            'manage_work_order',
            'manage_clients',
            'manage_vendors',
            'manage_spare_parts',
            'manage_warehouses',
            'view_stock',
        ]);

        // Site Manager
        Role::where('name', 'Site Manager')->first()?->syncPermissions([
            'view_project_unit',
            'view_asset_unit',
            'manage_asset',
            'view_work_order_unit',
        ]);

        // Technician
        Role::where('name', 'Technician')->first()?->syncPermissions([
            'view_asset_unit',
            'view_work_order_unit',
        ]);

        // Employee (regular)
        Role::where('name', 'Employee')->first()?->syncPermissions([
            'view_employee_unit',
            'access_ess_portal',
            'view_attendance_unit',
            'view_payroll_unit',
            'view_own_payslip',
        ]);

        // Pengurus Koperasi
        Role::where('name', 'Pengurus Koperasi')->first()?->syncPermissions([
            'view_cooperative_member',
            'manage_cooperative_member',
            'manage_cooperative_dues',
            'manage_cooperative_payment',
            'view_cooperative_loan',
            'manage_cooperative_loan',
            'approve_cooperative_loan',
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
            'view_cooperative_all',
            'manage_cooperative_settings',
        ]);

        // Kasir Koperasi
        Role::where('name', 'Kasir Koperasi')->first()?->syncPermissions([
            'view_cooperative_member',
            'manage_cooperative_payment',
            'view_cooperative_loan',
            'access_cooperative_pos',
            'view_cooperative_report',
            'view_pos_reports',
        ]);

        // Anggota
        Role::where('name', 'Anggota')->first()?->syncPermissions([
            'view_cooperative_member',
            'view_cooperative_loan',
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
