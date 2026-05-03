<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Briefcase, Plus, Filter } from 'lucide-vue-next';
import { ref } from 'vue';
import { index as projectsIndex, create as projectCreate, show as projectShow } from '@/actions/App/Http/Controllers/ProjectController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    projects: any;
    organizations: any[];
    clients: any[];
    filters: Record<string, string>;
    stats: {
        total_projects: number;
        ongoing_projects: number;
        completed_projects: number;
        total_budget: number;
        total_actual_cost: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Operations', href: '#' },
    { title: 'Projects', href: '#' },
];

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedOrg = ref(props.filters.organization_id || '');

let searchTimeout: ReturnType<typeof setTimeout>;
const applyFilters = () => {
    router.get('/projects', {
        search: search.value,
        status: selectedStatus.value,
        organization_id: selectedOrg.value,
    }, { preserveState: true, replace: true });
};

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

const formatDateRange = (startDate: string, endDate: string) => {
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    const startFormatted = start.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
    
    const endFormatted = end.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
    
    return `${startFormatted} - ${endFormatted}`;
};

const setStatus = (status: string) => {
    selectedStatus.value = status;
    applyFilters();
};

const statusColors: Record<string, string> = {
    PLANNING: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    ONGOING: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    ON_HOLD: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400',
    COMPLETED: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    CANCELLED: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

const statusCounts: Record<string, number> = {
    PLANNING: 0,
    ONGOING: 0,
    ON_HOLD: 0,
    COMPLETED: 0,
    CANCELLED: 0,
};

if (props.projects.data) {
    props.projects.data.forEach((p: any) => {
        if (statusCounts[p.status] !== undefined) {
            statusCounts[p.status]++;
        }
    });
}
</script>

<template>
    <Head title="Projects" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Projects</h1>
                    <p class="text-zinc-500 mt-1">Manage projects, tasks, and team assignments.</p>
                </div>
                <Button as-child>
                    <Link :href="projectCreate().url">
                        <Plus class="h-4 w-4 mr-2" />
                        New Project
                    </Link>
                </Button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Projects</p>
                    <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">{{ stats.total_projects }}</h2>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Ongoing</p>
                    <h2 class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ stats.ongoing_projects }}</h2>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Completed</p>
                    <h2 class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.completed_projects }}</h2>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Budget</p>
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ formatCurrency(stats.total_budget) }}</h2>
                </div>
            </div>

            <!-- Quick Filters -->
            <div class="space-y-4">
                <!-- Search -->
                <div class="relative">
                    <Input v-model="search" placeholder="Search projects by name or code..." @input="applyFilters" />
                </div>
                
                <!-- Quick Filter Chips -->
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="text-sm text-zinc-500">Status:</span>
                    <Button 
                        size="sm"
                        :variant="selectedStatus === '' ? 'default' : 'outline'"
                        @click="setStatus('')"
                    >
                        All ({{ stats.total_projects }})
                    </Button>
                    <Button 
                        size="sm"
                        :variant="selectedStatus === 'PLANNING' ? 'default' : 'outline'"
                        @click="setStatus('PLANNING')"
                    >
                        Planning ({{ statusCounts.PLANNING }})
                    </Button>
                    <Button 
                        size="sm"
                        :variant="selectedStatus === 'ONGOING' ? 'default' : 'outline'"
                        @click="setStatus('ONGOING')"
                    >
                        Ongoing ({{ statusCounts.ONGOING }})
                    </Button>
                    <Button 
                        size="sm"
                        :variant="selectedStatus === 'ON_HOLD' ? 'default' : 'outline'"
                        @click="setStatus('ON_HOLD')"
                    >
                        On Hold ({{ statusCounts.ON_HOLD }})
                    </Button>
                    <Button 
                        size="sm"
                        :variant="selectedStatus === 'COMPLETED' ? 'default' : 'outline'"
                        @click="setStatus('COMPLETED')"
                    >
                        Completed ({{ statusCounts.COMPLETED }})
                    </Button>
                </div>

                <!-- Organization Filter -->
                <div class="flex items-center gap-2">
                    <span class="text-sm text-zinc-500">Unit:</span>
                    <select 
                        v-model="selectedOrg" 
                        @change="applyFilters" 
                        class="flex h-9 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                    >
                        <option value="">All Units</option>
                        <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.name }}</option>
                    </select>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-6 py-4 font-medium">Project</th>
                                <th class="px-6 py-4 font-medium">Organization</th>
                                <th class="px-6 py-4 font-medium">Client</th>
                                <th class="px-6 py-4 font-medium">Period</th>
                                <th class="px-6 py-4 font-medium">Budget</th>
                                <th class="px-6 py-4 font-medium">Progress</th>
                                <th class="px-6 py-4 font-medium">Status</th>
                                <th class="px-6 py-4 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr v-for="project in projects.data" :key="project.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-white">{{ project.name }}</div>
                                        <div class="text-xs text-zinc-500">{{ project.project_code }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ project.organization?.name }}</td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ project.client?.name || '-' }}</td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                    {{ formatDateRange(project.start_date, project.end_date) }}
                                </td>
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ formatCurrency(project.budget) }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                            <div class="h-full bg-indigo-600 rounded-full" :style="{ width: project.progress_percentage + '%' }"></div>
                                        </div>
                                        <span class="text-xs text-zinc-500">{{ project.progress_percentage }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full" :class="statusColors[project.status]">
                                        {{ project.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="projectShow(project.id).url" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">View</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
