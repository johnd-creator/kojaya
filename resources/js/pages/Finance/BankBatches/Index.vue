<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Download, UploadCloud, FileSpreadsheet } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    batches: Array<{
        id: string;
        bank_name: string;
        account_number: string;
        format: string;
        status: string;
        batch_date: string;
        reference: string | null;
        items?: Array<{
            id: string;
            beneficiary_name: string;
            beneficiary_account: string;
            amount: string;
            currency: string;
            reference: string | null;
        }>;
    }>;
}>();

const showCreate = ref(false);
const form = ref({
    bank_name: '',
    account_number: '',
    format: 'CSV',
    batch_date: new Date().toISOString().slice(0, 10),
    reference: '',
    items: [
        { beneficiary_name: '', beneficiary_account: '', amount: 0, currency: 'IDR', reference: '' },
    ],
});

const addItem = () => {
    form.value.items.push({ beneficiary_name: '', beneficiary_account: '', amount: 0, currency: 'IDR', reference: '' });
};

const submitCreate = () => {
    router.post('/finance/bank-batches', form.value, { onSuccess: () => { showCreate.value = false; } });
};

const statementCsv = ref('');
const submitReconcile = () => {
    router.post('/finance/bank-batches/reconcile', { statement_csv: statementCsv.value });
};
</script>

<template>
    <Head title="Bank Batches" />
    <AppLayout :breadcrumbs="[{ title: 'Finance', href: '#' }, { title: 'Bank Batches', href: '/finance/bank-batches' }]">
        <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Bank Batches</h1>
                    <p class="text-zinc-500 mt-1">Mass transfer and auto-reconciliation</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button @click="showCreate = true" class="bg-indigo-600 text-white">
                        <Plus class="h-4 w-4 mr-2" /> New Batch
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-zinc-500 text-xs">
                                <tr>
                                    <th class="px-6 py-3">Batch</th>
                                    <th class="px-6 py-3">Bank</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                <tr v-if="!props.batches || props.batches.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-zinc-500">
                                        <FileSpreadsheet class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700 mb-3" />
                                        No batches yet. Create one to start mass transfer.
                                    </td>
                                </tr>
                                <tr v-for="b in props.batches" :key="b.id">
                                    <td class="px-6 py-3 font-mono text-sm">{{ b.reference || b.id.slice(0,8) }}</td>
                                    <td class="px-6 py-3">{{ b.bank_name }} — {{ b.account_number }}</td>
                                    <td class="px-6 py-3">{{ new Date(b.batch_date).toLocaleDateString() }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs rounded border">{{ b.status }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <Button variant="outline" as-child>
                                            <a :href="`/finance/bank-batches/${b.id}/export`" target="_blank">
                                                <Download class="h-4 w-4 mr-2" /> Export CSV
                                            </a>
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><UploadCloud class="h-5 w-5" /> Reconcile Statement</h2>
                        <div class="space-y-2">
                            <Label>Paste Statement CSV</Label>
                            <textarea v-model="statementCsv" rows="8" class="w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="mt-3">
                            <Button @click="submitReconcile">Import & Match</Button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center p-6">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-lg w-full max-w-2xl p-6">
                    <h2 class="text-lg font-semibold mb-4">New Bank Batch</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Bank Name</Label>
                            <Input v-model="form.bank_name" />
                        </div>
                        <div class="space-y-2">
                            <Label>Account Number</Label>
                            <Input v-model="form.account_number" />
                        </div>
                        <div class="space-y-2">
                            <Label>Format</Label>
                            <select v-model="form.format" class="h-10 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 text-sm">
                                <option value="CSV">CSV</option>
                                <option value="XML" disabled>XML</option>
                                <option value="FW" disabled>Fixed‑Width</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label>Batch Date</Label>
                            <Input type="date" v-model="form.batch_date" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label>Reference</Label>
                            <Input v-model="form.reference" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <h3 class="text-sm font-semibold mb-2">Items</h3>
                        <div class="space-y-3">
                            <div v-for="(it, idx) in form.items" :key="idx" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                <Input v-model="it.beneficiary_name" placeholder="Beneficiary Name" />
                                <Input v-model="it.beneficiary_account" placeholder="Account" />
                                <Input v-model.number="it.amount" type="number" step="0.01" placeholder="Amount" />
                                <Input v-model="it.reference" placeholder="Reference" />
                                <Button variant="outline" @click="addItem">Add</Button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-2">
                        <Button class="bg-indigo-600 text-white" @click="submitCreate">Create Batch</Button>
                        <Button variant="outline" @click="showCreate = false">Cancel</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

