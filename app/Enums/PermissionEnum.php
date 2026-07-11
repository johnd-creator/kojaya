<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // Organizations
    case ORG_VIEW_ALL = 'view_organization_all';
    case ORG_VIEW_UNIT = 'view_organization_unit';
    case ORG_CREATE = 'create_organization';
    case ORG_EDIT = 'edit_organization';
    case ORG_DELETE = 'delete_organization';

    // Users & Roles
    case USER_VIEW_ALL = 'view_user_all';
    case USER_VIEW_UNIT = 'view_user_unit';
    case USER_CREATE = 'create_user';
    case USER_EDIT = 'edit_user';
    case USER_DELETE = 'delete_user';

    // Employees
    case EMPLOYEE_VIEW_ALL = 'view_employee_all';
    case EMPLOYEE_VIEW_UNIT = 'view_employee_unit';
    case EMPLOYEE_CREATE = 'create_employee';
    case EMPLOYEE_EDIT = 'edit_employee';
    case EMPLOYEE_DELETE = 'delete_employee';

    // Attendances
    case ATTENDANCE_VIEW_ALL = 'view_attendance_all';
    case ATTENDANCE_VIEW_UNIT = 'view_attendance_unit';
    case ATTENDANCE_APPROVE = 'approve_attendance';

    // Payroll
    case PAYROLL_VIEW_ALL = 'view_payroll_all';
    case PAYROLL_VIEW_UNIT = 'view_payroll_unit';
    case PAYROLL_PROCESS = 'process_payroll';
    case PAYROLL_APPROVE = 'approve_payroll';

    // Procurement
    case PR_VIEW_ALL = 'view_pr_all';
    case PR_CREATE = 'create_pr';
    case PR_APPROVE = 'approve_pr';
    case PO_VIEW_ALL = 'view_po_all';
    case PO_CREATE = 'create_po';
    case GRN_VIEW_ALL = 'view_grn_all';
    case GRN_RECEIVE = 'receive_grn';

    // Cooperative
    case COOPERATIVE_MEMBER_VIEW = 'view_cooperative_member';
    case COOPERATIVE_MEMBER_MANAGE = 'manage_cooperative_member';
    case COOPERATIVE_MEMBER_PII_VIEW = 'view_cooperative_member_pii';
    case COOPERATIVE_DUES_MANAGE = 'manage_cooperative_dues';
    case COOPERATIVE_PAYMENT_MANAGE = 'manage_cooperative_payment';
    case COOPERATIVE_LOAN_VIEW = 'view_cooperative_loan';
    case COOPERATIVE_LOAN_MANAGE = 'manage_cooperative_loan';
    case COOPERATIVE_LOAN_REVIEW = 'review_cooperative_loan';
    case COOPERATIVE_LOAN_APPROVE = 'approve_cooperative_loan';
    case COOPERATIVE_POS_ACCESS = 'access_cooperative_pos';
    case COOPERATIVE_REPORT_VIEW = 'view_cooperative_report';

    // --- NEW: Finance & Accounting ---
    case INVOICE_VIEW_ALL = 'view_invoice_all';
    case BUDGET_VIEW_ALL = 'view_budget_all';
    case BUDGET_MANAGE = 'manage_budget';
    case PETTY_CASH_MANAGE = 'manage_petty_cash';
    case BANK_BATCH_MANAGE = 'manage_bank_batch';
    case BANK_RECONCILIATION_MANAGE = 'manage_bank_reconciliation';
    case COA_VIEW = 'view_chart_of_accounts';
    case COA_MANAGE = 'manage_chart_of_accounts';
    case JOURNAL_MANAGE = 'manage_journal_entries';
    case TRIAL_BALANCE_VIEW = 'view_trial_balance';
    case BALANCE_SHEET_VIEW = 'view_balance_sheet';
    case INCOME_STATEMENT_VIEW = 'view_income_statement';
    case EFAKTUR_MANAGE = 'manage_efaktur';
    case REIMBURSEMENT_MANAGE = 'manage_reimbursement';
    case REIMBURSEMENT_APPROVE = 'approve_reimbursement';

    // --- NEW: Projects ---
    case PROJECT_VIEW_ALL = 'view_project_all';
    case PROJECT_VIEW_UNIT = 'view_project_unit';
    case PROJECT_MANAGE = 'manage_project';
    case PROJECT_TEAM_MANAGE = 'manage_project_team';

    // --- NEW: Asset & Maintenance ---
    case ASSET_VIEW_ALL = 'view_asset_all';
    case ASSET_VIEW_UNIT = 'view_asset_unit';
    case ASSET_MANAGE = 'manage_asset';
    case WORK_ORDER_VIEW_ALL = 'view_work_order_all';
    case WORK_ORDER_VIEW_UNIT = 'view_work_order_unit';
    case WORK_ORDER_MANAGE = 'manage_work_order';

    // --- NEW: HR Master Data ---
    case DEPARTMENTS_MANAGE = 'manage_departments';
    case POSITIONS_MANAGE = 'manage_positions';
    case JOB_GRADES_MANAGE = 'manage_job_grades';
    case WORK_SHIFTS_MANAGE = 'manage_work_shifts';
    case SALARY_STRUCTURES_MANAGE = 'manage_salary_structures';
    case SHIFT_ROSTERS_MANAGE = 'manage_shift_rosters';

    // --- NEW: Employee Advanced ---
    case EMPLOYEE_TRANSFER_MANAGE = 'manage_employee_transfer';
    case EMPLOYEE_TRANSFER_APPROVE = 'approve_employee_transfer';
    case EMPLOYEE_CONTRACT_MANAGE = 'manage_employee_contract';
    case EMPLOYEE_FAMILY_MANAGE = 'manage_employee_family';

    // --- NEW: Leave & Overtime ---
    case LEAVE_VIEW_ALL = 'view_leave_all';
    case LEAVE_VIEW_UNIT = 'view_leave_unit';
    case LEAVE_APPROVE = 'approve_leave';
    case OVERTIME_VIEW_ALL = 'view_overtime_all';
    case OVERTIME_VIEW_UNIT = 'view_overtime_unit';
    case OVERTIME_APPROVE = 'approve_overtime';

    // --- NEW: ESS Portal ---
    case ESS_PORTAL_ACCESS = 'access_ess_portal';
    case OWN_PAYSLIP_VIEW = 'view_own_payslip';

    // --- Member Portal (self-service, ownership enforced by middleware/policy) ---
    case MEMBER_PORTAL_ACCESS = 'member_portal_access';

    // --- NEW: Cooperative Extended ---
    case COOPERATIVE_POINTS_MANAGE = 'manage_cooperative_points';
    case COOPERATIVE_REWARDS_MANAGE = 'manage_cooperative_rewards';
    case COOPERATIVE_REDEMPTION_MANAGE = 'manage_cooperative_redemption';
    case COOPERATIVE_SHU_MANAGE = 'manage_cooperative_shu';
    case COOPERATIVE_LOAN_TYPES_MANAGE = 'manage_cooperative_loan_types';
    case POS_CATEGORIES_MANAGE = 'manage_pos_categories';
    case POS_PRODUCTS_MANAGE = 'manage_pos_products';
    case POS_REPORTS_VIEW = 'view_pos_reports';
    case POS_SHU_MANAGE = 'manage_pos_shu';
    case POS_VOID_APPROVE = 'approve_pos_void';
    case COOPERATIVE_LEDGER_VIEW = 'view_cooperative_ledger';
    case COOPERATIVE_LEDGER_MANAGE = 'manage_cooperative_ledger';
    case COOPERATIVE_VIEW_ALL = 'view_cooperative_all';
    case COOPERATIVE_SETTINGS_MANAGE = 'manage_cooperative_settings';
    case COOPERATIVE_MEMBER_VALIDATE = 'validate_cooperative_member';
    case COOPERATIVE_MEMBER_VERIFY = 'verify_cooperative_member';
    case COOPERATIVE_MEMBER_APPROVE = 'approve_cooperative_member';
    case COOPERATIVE_MEMBER_EXPORT = 'export_cooperative_member';
    case COOPERATIVE_RESIGNATION_REVIEW = 'review_cooperative_resignation';
    case COOPERATIVE_OPENING_BALANCE_MANAGE = 'manage_cooperative_opening_balance';
    case COOPERATIVE_OPENING_BALANCE_APPROVE = 'approve_cooperative_opening_balance';
    case COOPERATIVE_OPENING_BALANCE_VOID = 'void_cooperative_opening_balance';

    // --- NEW: System / Admin ---
    case CLIENTS_MANAGE = 'manage_clients';
    case VENDORS_MANAGE = 'manage_vendors';
    case AUDIT_LOGS_VIEW = 'view_audit_logs';
    case AUDIT_LOGS_EXPORT = 'export_audit_logs';
    case ORGANIZATIONS_MANAGE = 'manage_organizations';
    case ROLES_MANAGE = 'manage_roles';
    case USERS_MANAGE = 'manage_users';
    case REPORTS_VIEW = 'view_reports';

    // --- NEW: Storage ---
    case SPARE_PARTS_MANAGE = 'manage_spare_parts';
    case WAREHOUSES_MANAGE = 'manage_warehouses';
    case STOCK_VIEW = 'view_stock';

    /**
     * Get all permissions as an array of strings.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
