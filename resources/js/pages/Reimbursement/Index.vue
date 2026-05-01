<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Plus, Eye, FileText, CheckCircle2, XCircle, AlertCircle, DollarSign } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { index, create, show } from '@/actions/App/Http/Controllers/ReimbursementController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';

interface User {
    id: number;
    name: string;
}

interface Reimbursement {
    id: string;
    submission_date: string;
    total_amount: number;
    status: 'DRAFT' | 'SUBMITTED' | 'APPROVED' | 'REJECTED' | 'PAID';
    description: string;
    user: User;
    user_id: number;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Props {
    reimbursements: {
        data: Reimbursement[];
        links: any[];
        meta: Pagination; 
    } | Pagination & { data: Reimbursement[] };
}

const props = defineProps<Props>();

const search = ref('');

const breadcrumbs = [
    { title: 'Finance', href: '#' },
    { title: 'Reimbursements', href: index().url },
];

watch(search, (value) => {
    router.get(
        index().url,
        { search: value },
        { preserveState: true, replace: true }
    );
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'APPROVED':
        case 'PAID':
            return 'default'; // dark/black in shadcn usually
        case 'REJECTED':
            return 'destructive';
        case 'SUBMITTED':
            return 'secondary';
        default:
            return 'outline';
    }
};

const getStatusColorClass = (status: string) => {
    switch (status) {
        case 'APPROVED':
        case 'PAID':
            return 'text-emerald-600 dark:text-emerald-400';
        case 'REJECTED':
            return 'text-red-600 dark:text-red-400';
        case 'SUBMITTED':
            return 'text-blue-600 dark:text-blue-400';
        default:
            return 'text-zinc-500';
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'APPROVED':
        case 'PAID':
            return CheckCircle2;
        case 'REJECTED':
            return XCircle;
        case 'SUBMITTED':
            return AlertCircle;
        default:
            return FileText;
    }
};
</script>

<template>
    <Head title="Reimbursements" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Reimbursements</h1>
                    <p class="text-zinc-500 mt-1">Manage and track reimbursement requests.</p>
                </div>
                
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input 
                            v-model="search" 
                            placeholder="Search requests..." 
                            class="pl-9 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800"
                        />
                    </div>
                    
                    <Button as-child class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0">
                        <Link :href="create().url">
                            <Plus class="h-4 w-4 mr-2" />
                            New Request
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Date</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">User</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Description</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider text-right">Amount</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Status</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-if="reimbursements.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-zinc-500">
                                    <FileText class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700 mb-3" />
                                    No reimbursement requests found.
                                </td>
                            </tr>
                            <tr 
                                v-for="item in reimbursements.data" 
                                :key="item.id" 
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group"
                            >
                                <td class="py-4 px-6 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ item.submission_date }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ item.user?.name }}</div>
                                </td>
                                <td class="py-4 px-6 text-sm text-zinc-600 dark:text-zinc-400 max-w-[300px] truncate" :title="item.description">
                                    {{ item.description }}
                                </td>
                                <td class="py-4 px-6 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ formatCurrency(item.total_amount) }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-1.5 text-sm" :class="getStatusColorClass(item.status)">
                                        <component :is="getStatusIcon(item.status)" class="h-4 w-4" />
                                        {{ item.status }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Link :href="show({ reimbursement: item.id }).url">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-zinc-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
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
