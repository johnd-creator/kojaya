<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, BriefcaseBusiness, UserPlus, Database, FileText, Wrench, Warehouse } from 'lucide-vue-next';
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
