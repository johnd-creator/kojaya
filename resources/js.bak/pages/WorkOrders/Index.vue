<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Search, Plus, FileText } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface WorkOrder {
    id: string;
    asset_id: string;
    organization_id: string;
    type: 'PREVENTIVE' | 'CORRECTIVE';
    priority: 'LOW' | 'MEDIUM' | 'HIGH' | 'EMERGENCY';
    status: 'OPEN' | 'IN_PROGRESS' | 'COMPLETED' | 'CLOSED';
    description: string | null;
    assigned_to: string | null;
    completed_at: string | null;
    created_at: string;
    asset?: {
        id: string;
        code: string;
        name: string;
    };
    organization?: {
        id: string;
        name: string;
        code: string;
    };
    assignedTo?: {
        id: string;
        name: string;
        email: string;
    };
}

interface Props {
    workOrders: WorkOrder[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Asset Management', href: '#' },
    { title: 'Work Orders', href: '/work-orders' },
];

const search = ref('');
const statusFilter = ref('');
const priorityFilter = ref('');
const typeFilter = ref('');

const filteredWorkOrders = () => {
    return props.workOrders.filter(wo => {
        const matchesSearch = !search.value || 
            wo.description?.toLowerCase().includes(search.value.toLowerCase()) ||
            wo.asset?.code.toLowerCase().includes(search.value.toLowerCase()) ||
            wo.asset?.name.toLowerCase().includes(search.value.toLowerCase());
        
        const matchesStatus = !statusFilter.value || wo.status === statusFilter.value;
        const matchesPriority = !priorityFilter.value || wo.priority === priorityFilter.value;
        const matchesType = !typeFilter.value || wo.type === typeFilter.value;

        return matchesSearch && matchesStatus && matchesPriority && matchesType;
    });
};

const getStatusColor = (status: string) => {
    const colors = {
        'OPEN': 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400',
        'IN_PROGRESS': 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400',
        'COMPLETED': 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400',
        'CLOSED': 'bg-zinc-50 text-zinc-700 border-zinc-200 dark:bg-zinc-900/30 dark:text-zinc-400',
    };
    return colors[status as keyof typeof colors] || 'bg-zinc-50 text-zinc-700 border-zinc-200';
};

const getPriorityColor = (priority: string) => {
    const colors = {
        'LOW': 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900/30 dark:text-slate-400',
        'MEDIUM': 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/30 dark:text-sky-400',
        'HIGH': 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-900/30 dark:text-orange-400',
        'EMERGENCY': 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[priority as keyof typeof colors] || 'bg-zinc-50 text-zinc-700 border-zinc-200';
};

const getTypeColor = (type: string) => {
    const colors = {
        'PREVENTIVE': 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-900/30 dark:text-teal-400',
        'CORRECTIVE': 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400',
    };
    return colors[type as keyof typeof colors] || 'bg-zinc-50 text-zinc-700 border-zinc-200';
};
</script>

<template>
    <Head title="Work Orders" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Work Orders</h1>
                    <p class="text-zinc-500 mt-1">Track and manage maintenance work orders across all assets.</p>
                </div>
                
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input 
                            v-model="search" 
                            placeholder="Search work orders..." 
                            class="pl-9 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800"
                        />
                    </div>
                    
                    <Link href="/work-orders/create">
                        <Button class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0">
                            <Plus class="h-4 w-4 mr-2" />
                            Create WO
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select 
                    v-model="statusFilter"
                    class="px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300"
                >
                    <option value="">All Statuses</option>
                    <option value="OPEN">Open</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CLOSED">Closed</option>
                </select>

                <select 
                    v-model="priorityFilter"
                    class="px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300"
                >
                    <option value="">All Priorities</option>
                    <option value="LOW">Low</option>
                    <option value="MEDIUM">Medium</option>
                    <option value="HIGH">High</option>
                    <option value="EMERGENCY">Emergency</option>
                </select>

                <select 
                    v-model="typeFilter"
                    class="px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300"
                >
                    <option value="">All Types</option>
                    <option value="PREVENTIVE">Preventive</option>
                    <option value="CORRECTIVE">Corrective</option>
                </select>
            </div>

            <!-- Work Orders Table -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">WO ID</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Asset</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Type / Priority</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Description</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Assigned To</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-if="filteredWorkOrders().length === 0">
                                <td colspan="6" class="py-12 text-center text-zinc-500">
                                    <FileText class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700 mb-3" />
                                    No work orders found.
                                </td>
                            </tr>
                            <tr 
                                v-for="wo in filteredWorkOrders()" 
                                :key="wo.id" 
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer"
                            >
                                <td class="py-4 px-6">
                                    <Link :href="`/work-orders/${wo.id}`" class="font-mono text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:underline">
                                        {{ wo.id.slice(0, 8) }}
                                    </Link>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100 text-sm">
                                            {{ wo.asset?.name || '-' }}
                                        </span>
                                        <span v-if="wo.asset" class="text-xs text-zinc-500 mt-0.5">
                                            {{ wo.asset.code }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium border"
                                             :class="getTypeColor(wo.type)">
                                            {{ wo.type }}
                                        </div>
                                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium border"
                                             :class="getPriorityColor(wo.priority)">
                                            {{ wo.priority }}
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2 max-w-xs">
                                        {{ wo.description || '-' }}
                                    </p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ wo.assignedTo?.name || 'Unassigned' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border"
                                         :class="getStatusColor(wo.status)">
                                        {{ wo.status.replace('_', ' ') }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
