<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Clock, CheckCircle, XCircle, FileText, Plus, Filter, Search } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    overtimeRequests: any;
    filters: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'HR Management', href: '#' },
    { title: 'Overtime Requests', href: '/overtime' },
];

const rejectionModal = ref(false);
const selectedRequest = ref<any>(null);
const rejectionForm = useForm({
    rejection_reason: '',
});

const approve = (id: string) => {
    if (confirm('Are you sure you want to approve this overtime request?')) {
        router.post(`/overtime/${id}/approve`);
    }
};

const openRejectModal = (request: any) => {
    selectedRequest.value = request;
    rejectionForm.reset();
    rejectionModal.value = true;
};

const reject = () => {
    rejectionForm.post(`/overtime/${selectedRequest.value.id}/reject`, {
        onSuccess: () => {
            rejectionModal.value = false;
            selectedRequest.value = null;
        },
    });
};

const deleteRequest = (id: string) => {
    if (confirm('Are you sure you want to delete this request?')) {
        router.delete(`/overtime/${id}`);
    }
};

const getStatusColor = (status: string) => {
    switch(status) {
        case 'APPROVED': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'REJECTED': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        default: return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
    }
};

const formatTime = (time: string) => {
    return time ? time.substring(0, 5) : '-';
};
</script>

<template>
    <Head title="Overtime Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                        <Clock class="h-6 w-6 text-indigo-600" />
                        Overtime Requests
                    </h1>
                    <p class="text-sm text-zinc-500 mt-1">Manage employee overtime submissions and approvals</p>
                </div>

                <div class="flex items-center gap-2">
                    <Button as-child>
                        <Link href="/overtime/create">
                            <Plus class="h-4 w-4 mr-2" />
                            New Request
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 flex gap-4 items-center">
                <Filter class="h-4 w-4 text-zinc-500" />
                <select 
                    :value="filters.status" 
                    @change="router.get('/overtime', { ...filters, status: ($event.target as HTMLSelectElement).value }, { preserveState: true })"
                    class="h-9 rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none dark:border-zinc-800 dark:bg-zinc-950"
                >
                    <option value="">All Statuses</option>
                    <option value="PENDING">Pending</option>
                    <option value="APPROVED">Approved</option>
                    <option value="REJECTED">Rejected</option>
                </select>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 bg-zinc-50 dark:bg-zinc-800 uppercase border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-6 py-3 font-medium">Employee</th>
                                <th class="px-6 py-3 font-medium">Date & Time</th>
                                <th class="px-6 py-3 font-medium">Hours</th>
                                <th class="px-6 py-3 font-medium">Reason</th>
                                <th class="px-6 py-3 font-medium">Evidence</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr v-if="overtimeRequests.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                                    No overtime requests found.
                                </td>
                            </tr>
                            <tr v-for="req in overtimeRequests.data" :key="req.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-zinc-900 dark:text-white">
                                        {{ req.employee?.first_name }} {{ req.employee?.last_name }}
                                    </div>
                                    <div class="text-xs text-zinc-500">{{ req.employee?.employee_code }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium">{{ req.date }}</div>
                                    <div class="text-xs text-zinc-500">
                                        {{ formatTime(req.start_time) }} - {{ formatTime(req.end_time) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-medium">
                                    {{ req.total_hours }} hrs
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate" :title="req.reason">
                                    {{ req.reason || '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <a v-if="req.evidence_path" :href="`/storage/${req.evidence_path}`" target="_blank" class="flex items-center text-indigo-600 hover:underline">
                                        <FileText class="h-4 w-4 mr-1" /> View
                                    </a>
                                    <span v-else class="text-zinc-400">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusColor(req.status)">
                                        {{ req.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right flex justify-end gap-2">
                                    <template v-if="req.status === 'PENDING'">
                                        <Button variant="outline" size="sm" class="h-8 w-8 p-0 text-green-600 hover:text-green-700 hover:bg-green-50" @click="approve(req.id)" title="Approve">
                                            <CheckCircle class="h-4 w-4" />
                                        </Button>
                                        <Button variant="outline" size="sm" class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50" @click="openRejectModal(req)" title="Reject">
                                            <XCircle class="h-4 w-4" />
                                        </Button>
                                    </template>
                                    <Button v-if="req.status !== 'APPROVED'" variant="ghost" size="sm" class="h-8 w-8 p-0 text-zinc-400 hover:text-red-600" @click="deleteRequest(req.id)" title="Delete">
                                        <span class="sr-only">Delete</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rejection Modal -->
            <Dialog v-model:open="rejectionModal">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Reject Overtime Request</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="reject" class="space-y-4 mt-2">
                        <div class="grid gap-2">
                            <Label>Reason for Rejection</Label>
                            <textarea 
                                v-model="rejectionForm.rejection_reason" 
                                class="flex min-h-[80px] w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white placeholder:text-zinc-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 dark:focus-visible:ring-zinc-300"
                                required
                                placeholder="Please explain why this request is rejected..."
                            ></textarea>
                            <span v-if="rejectionForm.errors.rejection_reason" class="text-xs text-red-500">{{ rejectionForm.errors.rejection_reason }}</span>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" @click="rejectionModal = false">Cancel</Button>
                            <Button type="submit" variant="destructive" :disabled="rejectionForm.processing">Reject Request</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
