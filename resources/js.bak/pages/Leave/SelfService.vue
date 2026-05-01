<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Calendar, Plus, Clock, CheckCircle2, XCircle, FileText } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    leaves: any;
    leaveTypes: any[];
    employee: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Employee Self Service', href: '#' },
    { title: 'My Leaves', href: '#' },
];

const showModal = ref(false);

const form = useForm({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    reason: '',
    attachment: null as File | null,
});

const openModal = () => {
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    form.post('/leaves/self-service', {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        },
    });
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.attachment = target.files[0];
    }
};

const getStatusColor = (status: string) => {
    switch(status) {
        case 'Approved': return 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/30 border-green-200 dark:border-green-800';
        case 'Rejected': return 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/30 border-red-200 dark:border-red-800';
        default: return 'text-amber-600 bg-amber-50 dark:text-amber-400 dark:bg-amber-900/30 border-amber-200 dark:border-amber-800';
    }
};

const getStatusIcon = (status: string) => {
    switch(status) {
        case 'Approved': return CheckCircle2;
        case 'Rejected': return XCircle;
        default: return Clock;
    }
};
</script>

<template>
    <Head title="My Leaves" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                        <Calendar class="h-6 w-6 text-blue-500" />
                        My Leave Requests
                    </h1>
                    <p class="text-sm text-zinc-500 mt-1">Submit and track your time off requests</p>
                </div>
                
                <Button @click="openModal" class="w-full sm:w-auto shadow-sm">
                    <Plus class="h-4 w-4 mr-2" />
                    Request Leave
                </Button>
            </div>

            <!-- Global Success Message -->
            <div v-if="($page.props as any).flash?.success" class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-800 dark:text-green-300 flex items-center gap-3">
                <CheckCircle2 class="h-5 w-5" />
                <p class="text-sm font-medium">{{ ($page.props as any).flash?.success }}</p>
            </div>

            <!-- List of Leaves -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <div v-if="leaves.data && leaves.data.length === 0" class="p-12 text-center flex flex-col items-center justify-center">
                    <div class="h-12 w-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                        <Calendar class="h-6 w-6 text-zinc-400" />
                    </div>
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-1">No Leave Requests</h3>
                    <p class="text-sm text-zinc-500">You haven't submitted any leave requests yet.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 bg-zinc-50 dark:bg-zinc-800 uppercase border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-6 py-4 font-medium">Leave Type</th>
                                <th class="px-6 py-4 font-medium">Dates</th>
                                <th class="px-6 py-4 font-medium">Duration</th>
                                <th class="px-6 py-4 font-medium">Status & Approver</th>
                                <th class="px-6 py-4 font-medium">Reason</th>
                                <th class="px-6 py-4 text-right">Attachment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr v-for="leave in leaves.data" :key="leave.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                                    {{ leave.type?.name }}
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                    {{ leave.start_date }} <span class="text-zinc-400 mx-1">to</span> {{ leave.end_date }}
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ leave.total_days }} day(s)
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="getStatusColor(leave.status)">
                                            <component :is="getStatusIcon(leave.status)" class="w-3 h-3 mr-1.5" />
                                            {{ leave.status }}
                                        </span>
                                        <span v-if="leave.approver" class="text-[11px] text-zinc-500 mt-1">
                                            by {{ leave.approver.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2 max-w-[200px]" :title="leave.reason">
                                        {{ leave.reason }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a v-if="leave.attachment_path" :href="`/storage/${leave.attachment_path}`" target="_blank" class="inline-flex items-center justify-center h-8 w-8 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:text-blue-400 dark:hover:bg-blue-900/30 transition-colors">
                                        <FileText class="h-4 w-4" />
                                    </a>
                                    <span v-else class="text-zinc-400 text-xs">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- New Leave Modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl w-full max-w-lg border border-zinc-200 dark:border-zinc-800 overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center bg-zinc-50 dark:bg-zinc-900/50">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white flex items-center">
                            <Calendar class="h-5 w-5 mr-2 text-zinc-500" />
                            Request Time Off
                        </h3>
                        <button type="button" @click="showModal = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                            <XCircle class="h-5 w-5" />
                        </button>
                    </div>
                    
                    <div class="overflow-y-auto p-6">
                        <form @submit.prevent="submit" class="space-y-5">
                            <div class="grid gap-2">
                                <Label>Leave Type</Label>
                                <select v-model="form.leave_type_id" required class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none dark:border-zinc-800 dark:bg-zinc-950">
                                    <option value="" disabled>Select a leave type</option>
                                    <option v-for="type in leaveTypes" :key="type.id" :value="type.id">
                                        {{ type.name }}
                                    </option>
                                </select>
                                <span v-if="form.errors.leave_type_id" class="text-xs text-red-500">{{ form.errors.leave_type_id }}</span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label>Start Date</Label>
                                    <Input type="date" v-model="form.start_date" required />
                                    <span v-if="form.errors.start_date" class="text-xs text-red-500">{{ form.errors.start_date }}</span>
                                </div>
                                <div class="grid gap-2">
                                    <Label>End Date</Label>
                                    <Input type="date" v-model="form.end_date" required />
                                    <span v-if="form.errors.end_date" class="text-xs text-red-500">{{ form.errors.end_date }}</span>
                                </div>
                            </div>
                            <p class="text-xs text-zinc-500">Note: Weekends will be automatically excluded from the total duration.</p>

                            <div class="grid gap-2">
                                <Label>Reason / Notes</Label>
                                <textarea v-model="form.reason" required rows="3" class="flex w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none dark:border-zinc-800 dark:bg-zinc-950 resize-y" placeholder="Briefly explain the reason for your time off..."></textarea>
                                <span v-if="form.errors.reason" class="text-xs text-red-500">{{ form.errors.reason }}</span>
                            </div>

                            <div class="grid gap-2">
                                <Label>Attachment (Optional for most, required for Sick Leave)</Label>
                                <Input type="file" @change="handleFileChange" accept=".jpg,.jpeg,.png,.pdf" class="cursor-pointer file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-zinc-100 file:text-zinc-700 dark:file:bg-zinc-800 dark:file:text-zinc-300" />
                                <span v-if="form.errors.attachment" class="text-xs text-red-500">{{ form.errors.attachment }}</span>
                            </div>
                        </form>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex justify-end gap-3 bg-zinc-50 dark:bg-zinc-900/50">
                        <Button type="button" variant="outline" @click="showModal = false">Cancel</Button>
                        <Button type="button" @click="submit" :disabled="form.processing" class="w-32">
                            <span v-if="form.processing">Submitting...</span>
                            <span v-else>Submit Request</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
