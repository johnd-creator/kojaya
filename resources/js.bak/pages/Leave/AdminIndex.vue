<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ShieldCheck, CheckCircle2, XCircle, FileText, Clock } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    leaves: any;
    filters: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'HR Administration', href: '#' },
    { title: 'Leave Approvals', href: '#' },
];

const updateStatus = (id: number, status: string) => {
    if (confirm(`Are you sure you want to mark this leave request as ${status}?`)) {
        router.put(`/leaves/${id}/status`, { status }, {
            preserveScroll: true
        });
    }
};

const getStatusColor = (status: string) => {
    switch(status) {
        case 'Approved': return 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/30 border-green-200 dark:border-green-800';
        case 'Rejected': return 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/30 border-red-200 dark:border-red-800';
        default: return 'text-amber-600 bg-amber-50 dark:text-amber-400 dark:bg-amber-900/30 border-amber-200 dark:border-amber-800';
    }
};
</script>

<template>
    <Head title="Leave Approvals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                        <ShieldCheck class="h-6 w-6 text-purple-600" />
                        Leave Request Approvals
                    </h1>
                    <p class="text-sm text-zinc-500 mt-1">Review and manage employee time off requests</p>
                </div>

                <div class="flex items-center gap-2">
                    <select v-model="filters.status" @change="router.get('/leaves', { status: ($event.target as HTMLSelectElement).value }, { preserveState: true })" class="flex h-9 rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none dark:border-zinc-800 dark:bg-zinc-950">
                        <option value="">All Statuses</option>
                        <option value="Pending">Pending Only</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <!-- Global Success Message -->
            <div v-if="($page.props as any).flash?.success" class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-800 dark:text-green-300 flex items-center gap-3">
                <CheckCircle2 class="h-5 w-5" />
                <p class="text-sm font-medium">{{ ($page.props as any).flash?.success }}</p>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <div v-if="leaves.data && leaves.data.length === 0" class="p-12 text-center flex flex-col items-center justify-center">
                    <div class="h-12 w-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                        <CheckCircle2 class="h-6 w-6 text-zinc-400" />
                    </div>
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-1">All Caught Up!</h3>
                    <p class="text-sm text-zinc-500">No leave requests found matching your filter.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 bg-zinc-50 dark:bg-zinc-800 uppercase border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-6 py-4 font-medium">Employee</th>
                                <th class="px-6 py-4 font-medium">Leave Details</th>
                                <th class="px-6 py-4 font-medium">Dates & Duration</th>
                                <th class="px-6 py-4 font-medium">Reason</th>
                                <th class="px-6 py-4 text-center">Attachment</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr v-for="leave in leaves.data" :key="leave.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ leave.employee?.first_name }} {{ leave.employee?.last_name }}</div>
                                    <div class="text-xs text-zinc-500">{{ leave.employee?.employee_code }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium flex items-center gap-2">
                                        {{ leave.type?.name }}
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold border" :class="getStatusColor(leave.status)">
                                            {{ leave.status }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-zinc-500 mt-1 flex items-center">
                                        <Clock class="h-3 w-3 mr-1" />
                                        Requested {{ new Date(leave.created_at).toLocaleDateString() }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-200 whitespace-nowrap">{{ leave.start_date }} to {{ leave.end_date }}</div>
                                    <div class="text-xs">{{ leave.total_days }} day(s)</div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2 max-w-[200px]" :title="leave.reason">
                                        {{ leave.reason }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a v-if="leave.attachment_path" :href="`/storage/${leave.attachment_path}`" target="_blank" class="inline-flex items-center justify-center p-2 rounded bg-zinc-100 dark:bg-zinc-800 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors" title="View Attachment">
                                        <FileText class="h-4 w-4 mr-1.5" />
                                        <span class="text-xs font-medium">View</span>
                                    </a>
                                    <span v-else class="text-zinc-400 text-xs">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="leave.status === 'Pending'" class="flex items-center justify-end gap-2">
                                        <Button @click="updateStatus(leave.id, 'Approved')" variant="default" size="sm" class="bg-green-600 hover:bg-green-700 text-white border-transparent">
                                            <CheckCircle2 class="h-4 w-4 mr-1.5" /> Approve
                                        </Button>
                                        <Button @click="updateStatus(leave.id, 'Rejected')" variant="destructive" size="sm">
                                            <XCircle class="h-4 w-4 mr-1.5" /> Reject
                                        </Button>
                                    </div>
                                    <div v-else class="flex justify-end">
                                        <span class="text-xs text-zinc-500">Processed by {{ leave.approver?.name || 'Unknown' }}</span>
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
