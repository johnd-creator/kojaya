<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { RefreshCw } from 'lucide-vue-next';
import { ref } from 'vue';
import { generate, index } from '@/routes/cooperative/dues';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{ invoices: any; filters: { period?: string; status?: string }; contributionTypes: any[] }>();
const period = ref(props.filters.period ?? new Date().toISOString().slice(0, 7));
const status = ref(props.filters.status ?? '');
const form = useForm({ period: period.value });

const applyFilters = () => router.get(index().url, { period: period.value, status: status.value }, { preserveState: true, replace: true });
const submitGenerate = () => {
    form.period = period.value;
    form.post(generate().url);
};
const formatCurrency = (amount: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(amount ?? 0));
</script>

<template>
    <Head title="Iuran & Simpanan" />
    <AppLayout :breadcrumbs="[{ title: 'Koperasi', href: '#' }, { title: 'Iuran & Simpanan', href: index().url }]">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Iuran & Simpanan</h1>
                    <p class="mt-1 text-sm text-zinc-500">Generate dan pantau tagihan anggota per periode.</p>
                </div>
                <Button @click="submitGenerate" :disabled="form.processing"><RefreshCw class="mr-2 h-4 w-4" />Generate Periode</Button>
            </div>
            <div class="flex flex-col gap-3 rounded-lg border bg-white p-4 dark:bg-zinc-900 md:flex-row">
                <Input v-model="period" type="month" class="md:w-48" />
                <select v-model="status" class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950">
                    <option value="">Semua status</option>
                    <option>UNPAID</option>
                    <option>PARTIAL</option>
                    <option>PAID</option>
                    <option>VOID</option>
                </select>
                <Button variant="outline" @click="applyFilters">Filter</Button>
            </div>
            <div class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900">
                        <tr><th class="px-4 py-3">Anggota</th><th>Jenis</th><th>Periode</th><th>Status</th><th class="px-4 py-3 text-right">Nominal</th><th class="px-4 py-3 text-right">Terbayar</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="invoice in invoices.data" :key="invoice.id">
                            <td class="px-4 py-3">{{ invoice.member?.name }}<div class="text-xs text-zinc-500">{{ invoice.member?.member_no }}</div></td>
                            <td>{{ invoice.contribution_type?.name }}</td>
                            <td>{{ invoice.period }}</td>
                            <td><span class="rounded-full border px-2 py-1 text-xs">{{ invoice.status }}</span></td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(invoice.amount) }}</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(invoice.paid_amount) }}</td>
                        </tr>
                        <tr v-if="invoices.data.length === 0"><td colspan="6" class="px-4 py-10 text-center text-zinc-500">Belum ada tagihan.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
