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
    case COOPERATIVE_DUES_MANAGE = 'manage_cooperative_dues';
    case COOPERATIVE_PAYMENT_MANAGE = 'manage_cooperative_payment';
    case COOPERATIVE_POS_ACCESS = 'access_cooperative_pos';
    case COOPERATIVE_REPORT_VIEW = 'view_cooperative_report';

    // Get all permissions as an array of strings
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
