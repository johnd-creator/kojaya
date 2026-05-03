<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Banknote, Building, Zap } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index as payrollsIndex } from '@/actions/App/Http/Controllers/PayrollController';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    payrolls: any;
    organizations: any[];
    filters: Record<string, string>;
    stats: { total_net_salary: number; total_records: number; current_period: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Core Modules', href: '#' },
    { title: 'Payroll', href: payrollsIndex().url },
];

const selectedPeriod = ref(props.filters.period || '');
const selectedOrg = ref(props.filters.organization_id || '');
const selectedStatus = ref(props.filters.status || '');
const showGenerateModal = ref(false);

let filterTimeout: ReturnType<typeof setTimeout>;
watch([selectedPeriod, selectedOrg, selectedStatus], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(payrollsIndex().url, {
            period: selectedPeriod.value,
            organization_id: selectedOrg.value,
            status: selectedStatus.value,
        }, { preserveState: true, replace: true });
    }, 400);
});

const generateForm = useForm({
    period: new Date().toISOString().slice(0, 7),
    organization_id: '',
});

const submitGenerate = () => {
    generateForm.post('/payrolls/generate', {
        onSuccess: () => { showGenerateModal.value = false; },
    });
};

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);

const statusColors: Record<string, string> = {
    DRAFT: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
    PROCESSED: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    PAID: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
};
</script>

<template>
    <Head title="Payroll" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 w-full">

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Payroll</h1>
                    <p class="text-zinc-500 mt-1">Monthly payroll periods with PPh 21 TER & BPJS calculations.</p>
                </div>
                <Dialog v-model:open="showGenerateModal">
                    <DialogTrigger as-child>
                        <Button>
                            <Zap class="h-4 w-4 mr-2" />
                            Generate Payroll
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-sm">
                        <DialogHeader><DialogTitle>Generate Payroll</DialogTitle></DialogHeader>
                        <form @submit.prevent="submitGenerate" class="space-y-4 mt-2">
                            <p class="text-sm text-zinc-500">Generates payroll for all <strong>ACTIVE</strong> employees in the selected unit for the specified period. Skips already-generated records.</p>
                            <div class="grid gap-2">
                                <Label>Period (YYYY-MM)</Label>
                                <Input type="month" v-model="generateForm.period" required />
                                <span v-if="generateForm.errors.period" class="text-xs text-red-500">{{ generateForm.errors.period }}</span>
                            </div>
                            <div class="grid gap-2">
                                <Label>Unit / Organization</Label>
                                <select v-model="generateForm.organization_id" required class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                    <option value="" disabled>Select unit...</option>
                                    <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.code }} - {{ org.name }}</option>
                                </select>
                                <span v-if="generateForm.errors.organization_id" class="text-xs text-red-500">{{ generateForm.errors.organization_id }}</span>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="outline" @click="showGenerateModal = false">Cancel</Button>
                                <Button type="submit" :disabled="generateForm.processing">
                                    <span v-if="generateForm.processing">Generating...</span>
                                    <span v-else>Generate</span>
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Stats + Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-500">Total Net ({{ stats.current_period }})</p>
                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ formatCurrency(stats.total_net_salary) }}</h2>
                    </div>
                    <Banknote class="h-10 w-10 text-emerald-500 opacity-80" />
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-500">Employees Processed</p>
                        <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">{{ stats.total_records }}</h2>
                    </div>
                    <Building class="h-10 w-10 text-indigo-500 opacity-80" />
                </div>

                <!-- Filters span 2 cols -->
                <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm">
                    <div class="grid grid-cols-3 gap-4 items-end">
                        <div>
                            <label class="text-xs font-medium text-zinc-500 mb-1 block">Period</label>
                            <Input type="month" v-model="selectedPeriod" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-zinc-500 mb-1 block">Unit</label>
                            <select v-model="selectedOrg" class="w-full flex h-10 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-800/50">
                                <option value="">All Units</option>
                                <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-zinc-500 mb-1 block">Status</label>
                            <select v-model="selectedStatus" class="w-full flex h-10 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-800/50">
                                <option value="">Any</option>
                                <option value="DRAFT">Draft</option>
                                <option value="PROCESSED">Processed</option>
                                <option value="PAID">Paid</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-5 py-4">Employee</th>
                                <th class="px-5 py-4">Unit</th>
                                <th class="px-5 py-4">Period</th>
                                <th class="px-5 py-4 text-right">Basic Salary</th>
                                <th class="px-5 py-4 text-right">BPJS</th>
                                <th class="px-5 py-4 text-right">PPh 21</th>
                                <th class="px-5 py-4 text-right">Net Salary</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="p in payrolls.data" :key="p.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                                <td class="px-5 py-4 font-medium text-zinc-900 dark:text-white">
                                    {{ p.employee?.first_name }} {{ p.employee?.last_name }}
                                    <span class="block text-xs text-zinc-400">{{ p.employee?.employee_code }}</span>
                                </td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ p.organization?.name ?? '—' }}</td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300 font-mono">{{ p.period }}</td>
                                <td class="px-5 py-4 text-right text-zinc-700 dark:text-zinc-300 tabular-nums">{{ formatCurrency(p.basic_salary) }}</td>
                                <td class="px-5 py-4 text-right text-red-600 dark:text-red-400 tabular-nums">{{ formatCurrency(p.bpjs_amount) }}</td>
                                <td class="px-5 py-4 text-right text-orange-600 dark:text-orange-400 tabular-nums">{{ formatCurrency(p.tax_amount) }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-emerald-700 dark:text-emerald-400 tabular-nums">{{ formatCurrency(p.net_salary) }}</td>
                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full" :class="statusColors[p.status]">{{ p.status }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <a :href="`/payrolls/${p.id}/download-pdf`" target="_blank" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">
                                        Download PDF
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="!payrolls.data.length">
                                <td colspan="8" class="py-12 text-center text-zinc-500">
                                    <Banknote class="h-12 w-12 mx-auto text-zinc-300 dark:text-zinc-700 mb-3" />
                                    <p class="font-medium text-zinc-800 dark:text-zinc-200">No payroll records found</p>
                                    <p class="text-sm mt-1">Use "Generate Payroll" to create records for a period.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div v-if="payrolls.links?.length > 3" class="px-5 py-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                    <p class="text-sm text-zinc-500">Showing {{ payrolls.from }}–{{ payrolls.to }} of {{ payrolls.total }}</p>
                    <div class="flex gap-1">
                        <a v-for="(link, i) in payrolls.links" :key="i" :href="link.url || '#'"
                            class="px-3 py-1 text-sm rounded-md" :class="[link.active ? 'bg-indigo-600 text-white' : 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800', !link.url ? 'opacity-40 pointer-events-none' : '']"
                            v-html="link.label" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
