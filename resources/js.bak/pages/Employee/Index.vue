<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Users, Search, Plus, Building, UserCheck } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { create as employeeCreate, edit as employeeEdit } from '@/actions/App/Http/Controllers/EmployeeController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    employees: any;
    organizations: any[];
    filters: Record<string, string>;
    stats: {
        total_active: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'HR & Employee', href: '#' },
    { title: 'Employee Master', href: '/employees' },
];

const search = ref(props.filters.search || '');
const selectedOrg = ref(props.filters.organization_id || '');
const selectedStatus = ref(props.filters.status || '');

let searchTimeout: ReturnType<typeof setTimeout>;

const applyFilters = () => {
    router.get('/employees', {
        search: search.value,
        organization_id: selectedOrg.value,
        status: selectedStatus.value,
    }, { preserveState: true, replace: true });
};

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

watch([selectedOrg, selectedStatus], () => {
    applyFilters();
});
</script>

<template>
    <Head title="Employee Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Employee Master</h1>
                    <p class="text-zinc-500 mt-1">Manage employee profiles, contracts, and placements.</p>
                </div>
                <div class="flex gap-3">
                    <Button as-child variant="default">
                        <Link :href="employeeCreate().url">
                            <Plus class="h-4 w-4 mr-2" />
                            Add Employee
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Stats & Filters -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Stats Box -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Active Employees</p>
                        <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">{{ stats.total_active }}</h2>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <UserCheck class="h-6 w-6" />
                    </div>
                </div>

                <!-- Filters -->
                <div class="lg:col-span-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 h-full items-end">
                        <div>
                            <label class="text-xs font-medium text-zinc-500 mb-1 block">Search Employee</label>
                            <div class="relative">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-zinc-500" />
                                <Input v-model="search" placeholder="Name or Code..." class="pl-9 bg-zinc-50 dark:bg-zinc-800/50" />
                            </div>
                        </div>
                        
                        <div>
                            <label class="text-xs font-medium text-zinc-500 mb-1 block">Filter by Unit</label>
                            <select 
                                v-model="selectedOrg"
                                class="w-full flex h-10 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-50"
                            >
                                <option value="">All Units</option>
                                <option v-for="org in organizations" :key="org.id" :value="org.id">
                                    {{ org.code }} - {{ org.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-zinc-500 mb-1 block">Status</label>
                            <select 
                                v-model="selectedStatus"
                                class="w-full flex h-10 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-50"
                            >
                                <option value="">Any Status</option>
                                <option value="ACTIVE">Active</option>
                                <option value="RESIGNED">Resigned</option>
                                <option value="TERMINATED">Terminated</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden flex-1">
                <div class="overflow-x-auto min-h-[400px]">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-6 py-4 font-medium">Employee</th>
                                <th class="px-6 py-4 font-medium">Unit / Organization</th>
                                <th class="px-6 py-4 font-medium">Details</th>
                                <th class="px-6 py-4 font-medium">Hire Date</th>
                                <th class="px-6 py-4 font-medium">Status</th>
                                <th class="px-6 py-4 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr v-for="emp in employees.data" :key="emp.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500">
                                            <Users class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <div class="font-medium text-zinc-900 dark:text-white">{{ emp.first_name }} {{ emp.last_name }}</div>
                                            <div class="text-xs text-zinc-500">{{ emp.employee_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="emp.organization" class="flex items-center gap-2">
                                        <Building class="h-4 w-4 text-zinc-400" />
                                        <span class="text-zinc-700 dark:text-zinc-300">{{ emp.organization.name }}</span>
                                    </div>
                                    <span v-else class="text-zinc-400 italic">No Unit</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span v-if="emp.position" class="text-sm font-medium text-zinc-900 dark:text-white">{{ emp.position.name }}</span>
                                        <span v-else class="text-sm text-zinc-400 italic">No Position</span>
                                        <div class="flex items-center gap-2 text-xs">
                                            <span v-if="emp.job_grade" class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">{{ emp.job_grade.code }}</span>
                                            <span class="px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/50">{{ emp.employee_type || 'Organic' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                    {{ new Date(emp.hire_date).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2.5 py-1 text-xs font-medium rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': emp.status === 'ACTIVE',
                                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': emp.status === 'RESIGNED' || emp.status === 'TERMINATED',
                                        }"
                                    >
                                        {{ emp.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="employeeEdit(emp.id).url" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Edit</Link>
                                </td>
                            </tr>
                            <tr v-if="employees.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                                    <Users class="h-12 w-12 mx-auto text-zinc-300 dark:text-zinc-700 mb-3" />
                                    <p class="text-base font-medium text-zinc-900 dark:text-zinc-100">No employees found</p>
                                    <p class="text-sm mt-1">Try adjusting your filters or add a new employee.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="employees.links && employees.links.length > 3" class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                    <p class="text-sm text-zinc-500">
                        Showing <span class="font-medium text-zinc-900 dark:text-white">{{ employees.from }}</span> to 
                        <span class="font-medium text-zinc-900 dark:text-white">{{ employees.to }}</span> of 
                        <span class="font-medium text-zinc-900 dark:text-white">{{ employees.total }}</span> results
                    </p>
                    <div class="flex gap-1">
                        <Link 
                            v-for="(link, i) in employees.links" 
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1 text-sm rounded-md transition-colors"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800',
                                !link.url ? 'opacity-50 cursor-not-allowed hidden' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
