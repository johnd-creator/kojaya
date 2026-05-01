<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { FileText, Plus, Search } from 'lucide-vue-next';
import { ref } from 'vue';
import { index as invoicesIndex, create as invoicesCreate, show as invoicesShow } from '@/actions/App/Http/Controllers/InvoiceController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    invoices: any;
    filters: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Finance', href: '#' },
    { title: 'Invoices', href: '#' },
];

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');

let searchTimeout: ReturnType<typeof setTimeout>;
const applyFilters = () => {
    router.get('/invoices', {
        search: search.value,
        status: selectedStatus.value,
    }, { preserveState: true, replace: true });
};

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);

const setStatus = (status: string) => {
    selectedStatus.value = status;
    applyFilters();
};

const statusColors: Record<string, string> = {
    DRAFT: 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400',
    PENDING: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    APPROVED: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    PAID: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    OVERDUE: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    CANCELLED: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400',
};

const statusCounts: Record<string, number> = {
    DRAFT: 0,
    PENDING: 0,
    APPROVED: 0,
    PAID: 0,
    OVERDUE: 0,
    CANCELLED: 0,
};

if (props.invoices.data) {
    props.invoices.data.forEach((invoice: any) => {
        if (statusCounts[invoice.status] !== undefined) {
            statusCounts[invoice.status]++;
        }
    });
}

const totalCount = props.invoices.data?.length || 0;
</script>

<template>
    <Head title="Invoices" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Invoices</h1>
                    <p class="text-zinc-500 mt-1">Manage client billing and invoices.</p>
                </div>
                <Button as-child>
                    <Link :href="invoicesCreate().url">
                        <Plus class="h-4 w-4 mr-2" />
                        New Invoice
                    </Link>
                </Button>
            </div>

            <!-- Quick Filters -->
            <div class="space-y-4">
                <!-- Search -->
                <div class="relative">
                    <Search class="absolute left-3 top-3 h-4 w-4 text-zinc-400" />
                    <Input 
                        v-model="search" 
                        placeholder="Search invoices by number..." 
                        @input="applyFilters"
                        class="pl-10"
                    />
                </div>
                
                <!-- Quick Filter Chips -->
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="text-sm text-zinc-500">Status:</span>
                    <Button 
                        size="sm"
                        :variant="selectedStatus === '' ? 'default' : 'outline'"
                        @click="setStatus('')"
                    >
                        All ({{ totalCount }})
                    </Button>
                    <Button 
                        size="sm"
                        :variant="selectedStatus === 'DRAFT' ? 'default' : 'outline'"
                        @click="setStatus('DRAFT')"
                    >
                        Draft ({{ statusCounts.DRAFT }})
                    </Button>
                    <Button 
                        size="sm"
                        :variant="selectedStatus === 'PENDING' ? 'default' : 'outline'"
                        @click="setStatus('PENDING')"
                    >
                        Pending ({{ statusCounts.PENDING }})
                    </Button>
                    <Button 
                        size="sm"
                        :variant="selectedStatus === 'APPROVED' ? 'default' : 'outline'"
                        @click="setStatus('APPROVED')"
                    >
                        Approved ({{ statusCounts.APPROVED }})
                    </Button>
                    <Button 
                        size="sm"
                        :variant="selectedStatus === 'PAID' ? 'default' : 'outline'"
                        @click="setStatus('PAID')"
                    >
                        Paid ({{ statusCounts.PAID }})
                    </Button>
                    <Button 
                        size="sm"
                        :variant="selectedStatus === 'OVERDUE' ? 'default' : 'outline'"
                        @click="setStatus('OVERDUE')"
                    >
                        Overdue ({{ statusCounts.OVERDUE }})
                    </Button>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-6 py-4 font-medium">Invoice No.</th>
                                <th class="px-6 py-4 font-medium">Client</th>
                                <th class="px-6 py-4 font-medium">Unit</th>
                                <th class="px-6 py-4 font-medium">Invoice Date</th>
                                <th class="px-6 py-4 font-medium">Due Date</th>
                                <th class="px-6 py-4 font-medium">Amount</th>
                                <th class="px-6 py-4 font-medium">Tax</th>
                                <th class="px-6 py-4 font-medium">Total</th>
                                <th class="px-6 py-4 font-medium">Status</th>
                                <th class="px-6 py-4 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ invoice.invoice_no }}</div>
                                </td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ invoice.client?.name || '-' }}</td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ invoice.unit?.name || '-' }}</td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ invoice.invoice_date }}</td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ invoice.due_date }}</td>
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ formatCurrency(invoice.amount) }}</td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ formatCurrency(invoice.tax_amount) }}</td>
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ formatCurrency(invoice.total_amount) }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full" :class="statusColors[invoice.status]">
                                        {{ invoice.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="invoicesShow({ id: invoice.id }).url" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">View</Link>
                                </td>
                            </tr>
                            <tr v-if="!invoices.data || invoices.data.length === 0">
                                <td colspan="10" class="px-6 py-12 text-center text-zinc-500">
                                    <FileText class="h-12 w-12 mx-auto mb-4 text-zinc-300" />
                                    <p>No invoices found.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
