<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import {
  BarChart3,
  BookOpen,
  FileSearch,
  LayoutGrid,
  BriefcaseBusiness,
  UserPlus,
  Database,
  FileText,
  Wrench,
  Warehouse,
  ShoppingCart,
  Users,
  Wallet,
  WalletCards,
  Store,
  Boxes,
  CreditCard,
  Gift,
  ReceiptText,
  ClipboardCheck,
  UserRound,
} from "lucide-vue-next";
import { computed } from "vue";
import { index as assetsIndex } from "@/actions/App/Http/Controllers/AssetController";
import {
  index as attendancesIndex,
  selfService as attendancesSelfService,
} from "@/actions/App/Http/Controllers/AttendanceController";
import { index as clientsIndex } from "@/actions/App/Http/Controllers/ClientController";
import { index as departmentsIndex } from "@/actions/App/Http/Controllers/DepartmentController";
import { index as employeesIndex } from "@/actions/App/Http/Controllers/EmployeeController";
import { index as invoicesIndex } from "@/actions/App/Http/Controllers/InvoiceController";
import { index as jobGradesIndex } from "@/actions/App/Http/Controllers/JobGradeController";
import { index as orgsIndex } from "@/actions/App/Http/Controllers/OrganizationController";
import { index as payrollsIndex } from "@/actions/App/Http/Controllers/PayrollController";
// import { index as pettyCashIndex } from '@/actions/App/Http/Controllers/PettyCashAccountController'; // TODO: Controller exists but no routes yet
import { index as positionsIndex } from "@/actions/App/Http/Controllers/PositionController";
import { index as projectsIndex } from "@/actions/App/Http/Controllers/ProjectController";
import { index as reimbursementsIndex } from "@/actions/App/Http/Controllers/ReimbursementController";
import { index as rolesIndex } from "@/actions/App/Http/Controllers/RoleController";
import { index as salaryStructuresIndex } from "@/actions/App/Http/Controllers/SalaryStructureController";
import { index as sparePartsIndex } from "@/actions/App/Http/Controllers/SparePartController";
import { index as usersIndex } from "@/actions/App/Http/Controllers/UserController";
import { index as warehousesIndex } from "@/actions/App/Http/Controllers/WarehouseController";
import { index as workOrdersIndex } from "@/actions/App/Http/Controllers/WorkOrderController";
// import { index as workShiftsIndex } from '@/actions/App/Http/Controllers/WorkShiftController'; // TODO: Controller exists but no routes yet
import NavFooter from "@/components/NavFooter.vue";
import NavMain from "@/components/NavMain.vue";
import NavUser from "@/components/NavUser.vue";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar";
import { dashboard } from "@/routes";
import { index as cooperativeDuesIndex } from "@/routes/cooperative/dues";
import { index as cooperativeLedgerIndex } from "@/routes/cooperative/ledger";
import { index as cooperativeLoanTypesIndex } from "@/routes/cooperative/loan-types";
import {
  calculator as cooperativeLoansCalculator,
  index as cooperativeLoansIndex,
} from "@/routes/cooperative/loans";
import { index as cooperativeMembersIndex } from "@/routes/cooperative/members";
import { index as cooperativeMembersResignationsIndex } from "@/routes/cooperative/members/resignations";
import {
  dashboard as operatorDashboard,
  closing as operatorClosing,
} from "@/routes/cooperative/operator";
import { index as cooperativePaymentsIndex } from "@/routes/cooperative/payments";
import { index as cooperativePointsIndex } from "@/routes/cooperative/points";
import { index as cooperativePosIndex } from "@/routes/cooperative/pos";
import { index as cooperativePosCoffeeOrdersIndex } from "@/routes/cooperative/pos/coffee-orders";
import { index as cooperativePosReportsIndex } from "@/routes/cooperative/pos/reports";
import { index as cooperativePosShuIndex } from "@/routes/cooperative/pos/shu";
import { index as cooperativePosTransactionsIndex } from "@/routes/cooperative/pos/transactions";
import { index as cooperativePosCategoriesIndex } from "@/routes/cooperative/pos-categories";
import { index as cooperativePosProductsIndex } from "@/routes/cooperative/pos-products";
import { index as cooperativeReportsIndex } from "@/routes/cooperative/reports";
import { index as cooperativeRedemptionsIndex } from "@/routes/cooperative/redemptions";
import { index as cooperativeRewardsIndex } from "@/routes/cooperative/rewards";
import { index as cooperativeSavingsWithdrawalsIndex } from "@/routes/cooperative/savings/withdrawals";
import { index as cooperativeShuIndex } from "@/routes/cooperative/shu";
import {
  index as cooperativeStoreCreditIndex,
  report as cooperativeStoreCreditReport,
} from "@/routes/cooperative/store-credit";
import { index as cooperativeStoreCreditTransfersIndex } from "@/routes/cooperative/store-credit/transfers";
import { storeAccount as memberStoreAccount } from "@/routes/member";
import {
  isAdminNavigationExperience,
  isPlatformExperience,
  resolveEffectiveExperience,
  roleExperienceNavigationLabel,
} from "@/lib/role-experience";
import type { NavItem } from "@/types";
import AppLogo from "./AppLogo.vue";

const page = usePage();
const userRoles = computed(() => page.props.auth.roles ?? []);
const userPermissions = computed(() => page.props.auth.permissions ?? []);
const effectiveExperience = computed(() =>
  resolveEffectiveExperience(
    page.props.auth.primary_role,
    userRoles.value,
    userPermissions.value,
  ),
);
const isMember = computed(() => userRoles.value.includes("Anggota"));
const hasNonMemberRole = computed(
  () => isMember.value && userRoles.value.some((r: string) => r !== "Anggota"),
);
const isMemberOnly = computed(() => isMember.value && !hasNonMemberRole.value);
const isSystemAdmin = computed(
  () => isPlatformExperience(effectiveExperience.value),
);
type MemberAccess = {
  is_active: boolean;
  is_pending_review: boolean;
  can_access_financial_features: boolean;
  can_access_onboarding: boolean;
};
const memberAccess = computed<MemberAccess | null>(() => {
  return (page.props.auth.member_access ?? null) as MemberAccess | null;
});
const canAccess = (permissions?: string | string[]): boolean => {
  // System Admin and Admin Pusat have access to everything
  if (isSystemAdmin.value) {
    return true;
  }

  if (!permissions) {
    return true;
  }

  const required = Array.isArray(permissions) ? permissions : [permissions];

  return required.some((permission) =>
    userPermissions.value.includes(permission),
  );
};
const filterNavByPermission = (items: NavItem[]): NavItem[] =>
  items
    .filter((item) => {
      if ((item as any).memberOnly && !isMember.value) return false;
      return true;
    })
    .map((item) => ({
      ...item,
      items: item.items ? filterNavByPermission(item.items) : undefined,
    }))
    .filter(
      (item) =>
        ((item as any).memberOnly || canAccess(item.permissions)) &&
        (!item.items || item.items.length > 0),
    );

const allNavItems: NavItem[] = [
  {
    title: "Dashboard",
    href: dashboard(),
    icon: LayoutGrid,
  },
  {
    title: "Pusat Panduan",
    href: "/documentation",
    icon: BookOpen,
  },
  {
    title: "User Management",
    href: "#",
    icon: UserPlus,
    items: [
      {
        title: "Organizations",
        href: orgsIndex().url,
        permissions: "manage_organizations",
      },
      { title: "Users", href: usersIndex().url, permissions: "manage_users" },
      {
        title: "Roles & Permissions",
        href: rolesIndex().url,
        permissions: "manage_roles",
      },
    ],
  },
  {
    title: "Keanggotaan",
    href: "#",
    icon: Users,
    items: [
      {
        title: "Anggota",
        href: cooperativeMembersIndex().url,
        permissions: ["view_cooperative_member", "view_cooperative_all"],
      },
      {
        title: "Verifikasi / Status",
        href: cooperativeMembersIndex({ query: { status: "PENDING" } }).url,
        permissions: "manage_cooperative_member",
      },
      {
        title: "Pengunduran Diri",
        href: cooperativeMembersResignationsIndex().url,
        permissions: ["view_cooperative_member", "view_cooperative_all"],
      },
    ],
  },
  {
    title: "Iuran & Simpanan",
    href: "#",
    icon: WalletCards,
    items: [
      {
        title: "Tagihan Iuran",
        href: cooperativeDuesIndex().url,
        permissions: "manage_cooperative_dues",
      },
      {
        title: "Pembayaran",
        href: cooperativePaymentsIndex().url,
        permissions: "manage_cooperative_payment",
      },
      {
        title: "Ledger Simpanan",
        href: cooperativeLedgerIndex().url,
        permissions: "view_cooperative_ledger",
      },
      {
        title: "Penarikan Simpanan",
        href: cooperativeSavingsWithdrawalsIndex().url,
        permissions: "view_cooperative_ledger",
      },
      {
        title: "SHU Koperasi",
        href: cooperativeShuIndex().url,
        permissions: "manage_cooperative_shu",
      },
    ],
  },
  {
    title: "Pinjaman",
    href: "#",
    icon: WalletCards,
    items: [
      {
        title: "Pinjaman",
        href: cooperativeLoansIndex().url,
        permissions: "view_cooperative_loan",
      },
      {
        title: "Kalkulator Pinjaman",
        href: cooperativeLoansCalculator().url,
        permissions: "view_cooperative_loan",
      },
      {
        title: "Tipe Pinjaman",
        href: cooperativeLoanTypesIndex().url,
        permissions: "manage_cooperative_loan_types",
      },
    ],
  },
  {
    title: "Saldo Toko",
    href: "#",
    icon: Wallet,
    items: [
      {
        title: "Akun Saldo Toko",
        href: cooperativeStoreCreditIndex().url,
        permissions: "view_store_credit",
      },
      {
        title: "Verifikasi Transfer",
        href: cooperativeStoreCreditTransfersIndex().url,
        permissions: "approve_store_credit_transfer",
      },
      {
        title: "Laporan Saldo Toko",
        href: cooperativeStoreCreditReport().url,
        permissions: "report_store_credit",
      },
    ],
  },
  {
    title: "Poin & Reward",
    href: "#",
    icon: Gift,
    items: [
      {
        title: "Poin Anggota",
        href: "/cooperative/points",
        permissions: "manage_cooperative_points",
      },
      {
        title: "Katalog Reward",
        href: "/cooperative/rewards",
        permissions: "manage_cooperative_rewards",
      },
      {
        title: "Penukaran",
        href: "/cooperative/redemptions",
        permissions: "manage_cooperative_redemption",
      },
    ],
  },
  {
    title: "POS Toko",
    href: "#",
    icon: Store,
    items: [
      {
        title: "Kasir POS",
        href: cooperativePosIndex().url,
        permissions: "access_cooperative_pos",
      },
      {
        title: "Riwayat Transaksi",
        href: cooperativePosTransactionsIndex().url,
        permissions: "access_cooperative_pos",
      },
      {
        title: "Pesanan Kopi",
        href: cooperativePosCoffeeOrdersIndex().url,
        permissions: "access_cooperative_pos",
      },
      {
        title: "Report Penjualan",
        href: cooperativePosReportsIndex().url,
        permissions: "view_pos_reports",
      },
      {
        title: "SHU POS Tahunan",
        href: cooperativePosShuIndex().url,
        permissions: "manage_pos_shu",
      },
    ],
  },
  {
    title: "Inventory POS",
    href: "#",
    icon: Boxes,
    items: [
      {
        title: "Produk",
        href: cooperativePosProductsIndex().url,
        permissions: "manage_pos_products",
      },
      {
        title: "Kategori",
        href: cooperativePosCategoriesIndex().url,
        permissions: "manage_pos_categories",
      },
      {
        title: "Stok Minimum",
        href: cooperativePosProductsIndex({ query: { low_stock: 1 } }).url,
        permissions: "manage_pos_products",
      },
      {
        title: "Stock Movement",
        href: cooperativePosProductsIndex().url,
        permissions: "manage_pos_products",
      },
    ],
  },
  {
    title: "Operator Koperasi",
    href: "#",
    icon: ClipboardCheck,
    permissions: ["view_cooperative_report", "manage_cooperative_settings"],
    items: [
      {
        title: "Dashboard Operator",
        href: operatorDashboard().url,
        permissions: ["view_cooperative_report", "manage_cooperative_settings"],
      },
      {
        title: "Tutup Periode",
        href: operatorClosing().url,
        permissions: "manage_cooperative_settings",
      },
    ],
  },
  {
    title: "Human Resources",
    href: "#",
    icon: BriefcaseBusiness,
    items: [
      {
        title: "Attendance ESS",
        href: attendancesSelfService().url,
        permissions: "access_ess_portal",
      },
      {
        title: "Leave ESS",
        href: "/leaves/self-service",
        permissions: "access_ess_portal",
      },
      {
        title: "Overtime",
        href: "/overtime",
        permissions: [
          "access_ess_portal",
          "view_overtime_all",
          "view_overtime_unit",
        ],
      },
      {
        title: "Attendance Tracker",
        href: attendancesIndex().url,
        permissions: ["view_attendance_all", "view_attendance_unit"],
      },
      {
        title: "Leave Approvals",
        href: "/leaves",
        permissions: ["view_leave_all", "view_leave_unit", "approve_leave"],
      },
      {
        title: "Payroll",
        href: payrollsIndex().url,
        permissions: ["view_payroll_all", "view_payroll_unit"],
      },
    ],
  },
  {
    title: "HR Master Data",
    href: "#",
    icon: Database,
    items: [
      {
        title: "Employee",
        href: employeesIndex().url,
        permissions: ["view_employee_all", "view_employee_unit"],
      },
      {
        title: "Departments",
        href: departmentsIndex().url,
        permissions: "manage_departments",
      },
      {
        title: "Job Grades",
        href: jobGradesIndex().url,
        permissions: "manage_job_grades",
      },
      {
        title: "Positions",
        href: positionsIndex().url,
        permissions: "manage_positions",
      },
      {
        title: "Work Shifts",
        href: "/work-shifts",
        permissions: "manage_work_shifts",
      },
      {
        title: "Salary Structures",
        href: salaryStructuresIndex().url,
        permissions: "manage_salary_structures",
      },
      {
        title: "Shift Roster",
        href: "/shift-rosters",
        permissions: "manage_shift_rosters",
      },
    ],
  },
  {
    title: "Procurement",
    href: "#",
    icon: ShoppingCart,
    items: [
      {
        title: "Purchase Requests",
        href: "/procurement/purchase-requests",
        permissions: "view_pr_all",
      },
      {
        title: "Purchase Orders",
        href: "/procurement/purchase-orders",
        permissions: "view_po_all",
      },
      {
        title: "Goods Receive",
        href: "/procurement/grns",
        permissions: "view_grn_all",
      },
      {
        title: "Vendors",
        href: "/procurement/vendors",
        permissions: "manage_vendors",
      },
    ],
  },
  {
    title: "Asset Management",
    href: "#",
    icon: Wrench,
    items: [
      {
        title: "Assets",
        href: assetsIndex().url,
        permissions: ["view_asset_all", "view_asset_unit", "manage_asset"],
      },
      {
        title: "Work Orders",
        href: workOrdersIndex().url,
        permissions: [
          "view_work_order_all",
          "view_work_order_unit",
          "manage_work_order",
        ],
      },
    ],
  },
  {
    title: "Projects",
    href: "#",
    icon: BookOpen,
    items: [
      {
        title: "All Projects",
        href: projectsIndex().url,
        permissions: [
          "view_project_all",
          "view_project_unit",
          "manage_project",
        ],
      },
      {
        title: "Clients",
        href: clientsIndex().url,
        permissions: "manage_clients",
      },
    ],
  },
  {
    title: "Finance",
    href: "#",
    icon: FileText,
    items: [
      {
        title: "Invoices",
        href: invoicesIndex().url,
        permissions: "view_invoice_all",
      },
      {
        title: "RKAP",
        href: "/budgets",
        permissions: ["view_budget_all", "manage_budget"],
      },
      {
        title: "Petty Cash",
        href: "/petty-cash",
        permissions: "manage_petty_cash",
      },
      {
        title: "Reimbursements",
        href: reimbursementsIndex().url,
        permissions: "manage_reimbursement",
      },
      {
        title: "Bank Batches",
        href: "/finance/bank-batches",
        permissions: "manage_bank_batch",
      },
      {
        title: "Bank Reconciliation",
        href: "/finance/bank-reconciliation",
        permissions: "manage_bank_reconciliation",
      },
      {
        title: "Chart of Accounts",
        href: "/finance/chart-of-accounts",
        permissions: ["view_chart_of_accounts", "manage_chart_of_accounts"],
      },
      {
        title: "Journal Entries",
        href: "/finance/journal-entries",
        permissions: "manage_journal_entries",
      },
      {
        title: "Trial Balance",
        href: "/finance/trial-balance",
        permissions: "view_trial_balance",
      },
      {
        title: "Balance Sheet",
        href: "/finance/balance-sheet",
        permissions: "view_balance_sheet",
      },
      {
        title: "Income Statement",
        href: "/finance/income-statement",
        permissions: "view_income_statement",
      },
      {
        title: "E-Faktur",
        href: "/finance/efaktur",
        permissions: "manage_efaktur",
      },
      {
        title: "Tutup Periode",
        href: "/finance/closing",
        permissions: "view_balance_sheet",
      },
    ],
  },
  {
    title: "Exceptions",
    href: "/exceptions",
    permissions: "view_balance_sheet",
  },
  {
    title: "Storage",
    href: "#",
    icon: Warehouse,
    items: [
      {
        title: "Spare Parts",
        href: sparePartsIndex().url,
        permissions: "manage_spare_parts",
      },
      {
        title: "Warehouses",
        href: warehousesIndex().url,
        permissions: "manage_warehouses",
      },
    ],
  },
  {
    title: "Reports",
    href: "/reports",
    icon: BarChart3,
    items: [
      { title: "Reports ERP", href: "/reports", permissions: "view_reports" },
      {
        title: "Laporan Koperasi",
        href: cooperativeReportsIndex().url,
        permissions: "view_cooperative_report",
      },
    ],
  },
  {
    title: "Audit Logs",
    href: "/audit-logs",
    icon: FileSearch,
    permissions: "view_audit_logs",
  },
  {
    title: "Kojayaku",
    href: "#",
    icon: LayoutGrid,
    memberOnly: true,
    items: [
      { title: "Dashboard", href: "/member" },
      { title: "Simpanan", href: "/member/savings" },
      { title: "Saldo Toko", href: memberStoreAccount().url },
      { title: "Pinjaman", href: "/member/loans" },
      { title: "Poin Saya", href: "/member/points" },
      { title: "Rewards", href: "/member/rewards" },
      { title: "Aktivitas Keuangan", href: "/member/transactions" },
      { title: "Profil", href: "/member/profile" },
    ],
  },
] as any[];

const footerNavItems: NavItem[] = [];
const adminNavItems = computed<NavItem[]>(() =>
  filterNavByPermission([
    {
      title: "Ringkasan",
      href: "#",
      icon: LayoutGrid,
      items: [{ title: "Dashboard", href: dashboard(), icon: LayoutGrid }],
    },
    {
      title: "Anggota",
      href: "#",
      icon: Users,
      items: [
        {
          title: "Data Anggota",
          href: cooperativeMembersIndex().url,
          permissions: ["view_cooperative_member", "view_cooperative_all"],
        },
        {
          title: "Validasi Anggota",
          href: cooperativeMembersIndex({
            query: { validation_status: "PENDING" },
          }).url,
          permissions: ["validate_cooperative_member"],
        },
        {
          title: "Pengunduran Diri",
          href: cooperativeMembersResignationsIndex({
            query: { status: "PENDING" },
          }).url,
          permissions: ["review_cooperative_resignation"],
        },
      ],
    },
    {
      title: "Keuangan Anggota",
      href: "#",
      icon: WalletCards,
      items: [
        {
          title: "Pembayaran",
          href: cooperativePaymentsIndex({ query: { status: "PENDING" } }).url,
          permissions: ["manage_cooperative_payment"],
        },
        {
          title: "Iuran dan Tagihan",
          href: cooperativeDuesIndex({
            query: { period_scope: "all", status: "OPEN" },
          }).url,
          permissions: ["manage_cooperative_dues"],
        },
        {
          title: "Ledger Simpanan",
          href: cooperativeLedgerIndex().url,
          permissions: ["view_cooperative_ledger"],
        },
        {
          title: "Penarikan Simpanan",
          href: cooperativeSavingsWithdrawalsIndex().url,
          permissions: ["view_cooperative_ledger"],
        },
      ],
    },
    {
      title: "Pinjaman",
      href: "#",
      icon: CreditCard,
      items: [
        {
          title: "Data Pinjaman",
          href: cooperativeLoansIndex().url,
          permissions: ["view_cooperative_loan"],
        },
        {
          title: "Jenis Pinjaman",
          href: cooperativeLoanTypesIndex().url,
          permissions: ["manage_cooperative_loan_types"],
        },
      ],
    },
    {
      title: "Benefit Anggota",
      href: "#",
      icon: Gift,
      items: [
        {
          title: "Poin",
          href: cooperativePointsIndex().url,
          permissions: ["manage_cooperative_points"],
        },
        {
          title: "Reward",
          href: cooperativeRewardsIndex().url,
          permissions: ["manage_cooperative_rewards"],
        },
        {
          title: "Penukaran Reward",
          href: cooperativeRedemptionsIndex().url,
          permissions: ["manage_cooperative_redemption"],
        },
        {
          title: "SHU",
          href: cooperativeShuIndex().url,
          permissions: ["manage_cooperative_shu"],
        },
      ],
    },
    {
      title: "Operasional Toko",
      href: "#",
      icon: Store,
      items: [
        {
          title: "POS",
          href: cooperativePosIndex().url,
          permissions: ["access_cooperative_pos"],
        },
        {
          title: "Produk dan Stok",
          href: cooperativePosProductsIndex().url,
          permissions: ["manage_pos_products"],
        },
        {
          title: "Laporan POS",
          href: cooperativePosReportsIndex().url,
          permissions: ["view_pos_reports"],
        },
        {
          title: "Saldo Toko",
          href: cooperativeStoreCreditIndex().url,
          permissions: ["view_store_credit"],
        },
        {
          title: "Laporan Saldo Toko",
          href: cooperativeStoreCreditReport().url,
          permissions: ["report_store_credit"],
        },
      ],
    },
    {
      title: "Laporan",
      href: "#",
      icon: BarChart3,
      items: [
        {
          title: "Laporan Koperasi",
          href: cooperativeReportsIndex().url,
          permissions: ["view_cooperative_report"],
        },
      ],
    },
  ]),
);
const memberNavItems = computed<NavItem[]>(() => {
  if (!memberAccess.value?.can_access_financial_features) {
    return [
      {
        title: "Beranda",
        href: "/member",
        icon: LayoutGrid,
      },
      ...(memberAccess.value?.can_access_onboarding
        ? [
            {
              title: memberAccess.value.is_pending_review
                ? "Status Pengajuan"
                : "Onboarding",
              href: "/member/onboarding",
              icon: ClipboardCheck,
            } as NavItem,
          ]
        : []),
      {
        title: "Profil",
        href: "/member/profile",
        icon: UserRound,
      },
    ];
  }

  return [
    {
      title: "Beranda",
      href: "/member",
      icon: LayoutGrid,
    },
    {
      title: "Simpanan",
      href: "/member/savings",
      icon: WalletCards,
    },
    {
      title: "Saldo Toko",
      href: memberStoreAccount().url,
      icon: WalletCards,
    },
    {
      title: "Pinjaman",
      href: "/member/loans",
      icon: CreditCard,
    },
    {
      title: "Poin & Reward",
      href: "#",
      icon: Gift,
      items: [
        { title: "Poin Saya", href: "/member/points" },
        { title: "Rewards", href: "/member/rewards" },
      ],
    },
    {
      title: "Aktivitas Keuangan",
      href: "/member/transactions",
      icon: ReceiptText,
    },
    {
      title: "Profil",
      href: "/member/profile",
      icon: UserRound,
    },
  ];
});
const mainNavItems = computed(() => {
  if (isMemberOnly.value) {
    return memberNavItems.value;
  }

  if (isAdminNavigationExperience(effectiveExperience.value)) {
    return adminNavItems.value;
  }

  return filterNavByPermission(allNavItems);
});
const logoHref = computed(() => (isMemberOnly.value ? "/member" : dashboard()));
const navigationLabel = computed(() => {
  if (isMemberOnly.value) {
    return "Kojayaku";
  }

  return roleExperienceNavigationLabel(effectiveExperience.value);
});
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="logoHref" prefetch>
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" :label="navigationLabel" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
