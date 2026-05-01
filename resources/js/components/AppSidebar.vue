<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart3, BookOpen, FileSearch, LayoutGrid, BriefcaseBusiness, UserPlus, Database, FileText, Wrench, Warehouse, ShoppingCart, Users, WalletCards, Store, Boxes } from 'lucide-vue-next';
import { computed } from 'vue';
import { index as assetsIndex } from '@/actions/App/Http/Controllers/AssetController';
import { index as attendancesIndex, selfService as attendancesSelfService } from '@/actions/App/Http/Controllers/AttendanceController';
import { index as clientsIndex } from '@/actions/App/Http/Controllers/ClientController';
import { index as departmentsIndex } from '@/actions/App/Http/Controllers/DepartmentController';
import { index as employeesIndex } from '@/actions/App/Http/Controllers/EmployeeController';
import { index as invoicesIndex } from '@/actions/App/Http/Controllers/InvoiceController';
import { index as jobGradesIndex } from '@/actions/App/Http/Controllers/JobGradeController';
import { index as orgsIndex } from '@/actions/App/Http/Controllers/OrganizationController';
import { index as payrollsIndex } from '@/actions/App/Http/Controllers/PayrollController';
// import { index as pettyCashIndex } from '@/actions/App/Http/Controllers/PettyCashAccountController'; // TODO: Controller exists but no routes yet
import { index as positionsIndex } from '@/actions/App/Http/Controllers/PositionController';
import { index as projectsIndex } from '@/actions/App/Http/Controllers/ProjectController';
import { index as reimbursementsIndex } from '@/actions/App/Http/Controllers/ReimbursementController';
import { index as rolesIndex } from '@/actions/App/Http/Controllers/RoleController';
import { index as salaryStructuresIndex } from '@/actions/App/Http/Controllers/SalaryStructureController';
import { index as sparePartsIndex } from '@/actions/App/Http/Controllers/SparePartController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/UserController';
import { index as warehousesIndex } from '@/actions/App/Http/Controllers/WarehouseController';
import { index as workOrdersIndex } from '@/actions/App/Http/Controllers/WorkOrderController';
// import { index as workShiftsIndex } from '@/actions/App/Http/Controllers/WorkShiftController'; // TODO: Controller exists but no routes yet
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as cooperativeDuesIndex } from '@/routes/cooperative/dues';
import { index as cooperativeLedgerIndex } from '@/routes/cooperative/ledger';
import { index as cooperativeMembersIndex } from '@/routes/cooperative/members';
import { index as cooperativePaymentsIndex } from '@/routes/cooperative/payments';
import { index as cooperativePosIndex } from '@/routes/cooperative/pos';
import { index as cooperativePosCategoriesIndex } from '@/routes/cooperative/pos-categories';
import { index as cooperativePosProductsIndex } from '@/routes/cooperative/pos-products';
import { index as cooperativePosReportsIndex } from '@/routes/cooperative/pos/reports';
import { index as cooperativePosShuIndex } from '@/routes/cooperative/pos/shu';
import { index as cooperativePosTransactionsIndex } from '@/routes/cooperative/pos/transactions';
import { index as cooperativeReportsIndex } from '@/routes/cooperative/reports';
import { index as cooperativeShuIndex } from '@/routes/cooperative/shu';
import type { NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();
const userRoles = computed(() => {
    const user = page.props.auth?.user as any;
    return (user?.roles ?? []).map((r: any) => r.name ?? r);
});
const isEmployee = computed(() => userRoles.value.includes('Employee') && !userRoles.value.some((r: string) => ['System Admin', 'Admin Pusat', 'Admin Unit', 'HR Pusat', 'HR Unit'].includes(r)));

const allNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'User Management',
        href: '#',
        icon: UserPlus,
        adminOnly: true,
        items: [
            { title: 'Organizations', href: orgsIndex().url },
            { title: 'Users', href: usersIndex().url },
            { title: 'Roles & Permissions', href: rolesIndex().url },
        ],
    },
    {
        title: 'Human Resources',
        href: '#',
        icon: BriefcaseBusiness,
        items: [
            { title: 'Attendance ESS', href: attendancesSelfService().url },
            { title: 'Leave ESS', href: '/leaves/self-service' },
            { title: 'Overtime', href: '/overtime' },
            ...(isEmployee.value ? [] : [
                { title: 'Attendance Tracker', href: attendancesIndex().url },
                { title: 'Leave Approvals', href: '/leaves' },
                { title: 'Payroll', href: payrollsIndex().url },
            ]),
        ],
    },
    {
        title: 'Procurement',
        href: '#',
        icon: ShoppingCart,
        items: [
            { title: 'Purchase Requests', href: '/procurement/purchase-requests' },
            { title: 'Purchase Orders', href: '/procurement/purchase-orders' },
            { title: 'Goods Receive', href: '/procurement/grns' },
            { title: 'Vendors', href: '/procurement/vendors' },
        ],
    },
    {
        title: 'Asset Management',
        href: '#',
        icon: Wrench,
        adminOnly: true,
        items: [
            { title: 'Assets', href: assetsIndex().url },
            { title: 'Work Orders', href: workOrdersIndex().url },
        ],
    },
    {
        title: 'Projects',
        href: '#',
        icon: BookOpen,
        items: [
            { title: 'All Projects', href: projectsIndex().url },
            { title: 'Clients', href: clientsIndex().url },
        ],
    },
    {
        title: 'Finance',
        href: '#',
        icon: FileText,
        items: [
            { title: 'Invoices', href: invoicesIndex().url },
            { title: 'RKAP', href: '/budgets' },
            { title: 'Petty Cash', href: '/petty-cash' }, // TODO: Route not defined yet
            { title: 'Reimbursements', href: reimbursementsIndex().url },
            { title: 'Bank Batches', href: '/finance/bank-batches' },
        ],
    },
    {
        title: 'Keanggotaan',
        href: '#',
        icon: Users,
        items: [
            { title: 'Anggota', href: cooperativeMembersIndex().url },
            { title: 'Verifikasi / Status', href: cooperativeMembersIndex({ query: { status: 'PENDING' } }).url },
        ],
    },
    {
        title: 'Iuran & Simpanan',
        href: '#',
        icon: WalletCards,
        items: [
            { title: 'Tagihan Iuran', href: cooperativeDuesIndex().url },
            { title: 'Pembayaran', href: cooperativePaymentsIndex().url },
            { title: 'Ledger Simpanan', href: cooperativeLedgerIndex().url },
            { title: 'SHU Koperasi', href: cooperativeShuIndex().url },
        ],
    },
    {
        title: 'POS Toko',
        href: '#',
        icon: Store,
        items: [
            { title: 'Kasir POS', href: cooperativePosIndex().url },
            { title: 'Riwayat Transaksi', href: cooperativePosTransactionsIndex().url },
            { title: 'Report Penjualan', href: cooperativePosReportsIndex().url },
            { title: 'SHU POS Tahunan', href: cooperativePosShuIndex().url },
        ],
    },
    {
        title: 'Inventory POS',
        href: '#',
        icon: Boxes,
        items: [
            { title: 'Produk', href: cooperativePosProductsIndex().url },
            { title: 'Kategori', href: cooperativePosCategoriesIndex().url },
            { title: 'Stok Minimum', href: cooperativePosProductsIndex({ query: { low_stock: 1 } }).url },
            { title: 'Stock Movement', href: cooperativePosProductsIndex().url },
        ],
    },
    {
        title: 'HR Master Data',
        href: '#',
        icon: Database,
        adminOnly: true,
        items: [
            { title: 'Employee', href: employeesIndex().url },
            { title: 'Departments', href: departmentsIndex().url },
            { title: 'Job Grades', href: jobGradesIndex().url },
            { title: 'Positions', href: positionsIndex().url },
            { title: 'Work Shifts', href: '/work-shifts' }, // TODO: Route not defined yet
            { title: 'Salary Structures', href: salaryStructuresIndex().url },
            { title: 'Shift Roster', href: '/shift-rosters' },
        ],
    },
    {
        title: 'Storage',
        href: '#',
        icon: Warehouse,
        adminOnly: true,
        items: [
            { title: 'Spare Parts', href: sparePartsIndex().url },
            { title: 'Warehouses', href: warehousesIndex().url },
        ],
    },
    {
        title: 'Reports',
        href: '/reports',
        icon: BarChart3,
        items: [
            { title: 'Reports ERP', href: '/reports' },
            { title: 'Laporan Koperasi', href: cooperativeReportsIndex().url },
        ],
    },
    {
        title: 'Audit Logs',
        href: '/audit-logs',
        icon: FileSearch,
    },
] as any[];

const mainNavItems = computed(() =>
    isEmployee.value ? allNavItems.filter((item: any) => !item.adminOnly) : allNavItems
);

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
