<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';

defineProps<{
    batch: { id: string; bank_name: string; account_number: string; batch_date: string; status: string; items: Array<{ id: string; beneficiary_name: string; beneficiary_account: string; amount: number | string; reference?: string | null; status: string; invoice?: { invoice_no: string; client?: { name: string } | null } | null }> };
    stats: { items_count: number; matched_items: number; pending_items: number; total_amount: number };
}>();
</script>

<template>
    <Head title="Detail Reconciliation" />
    <AppLayout :breadcrumbs="[{ title: 'Finance', href: '#' }, { title: 'Bank Reconciliation', href: '/finance/bank-reconciliation' }, { title: batch.bank_name, href: '#' }]">
        <PageContainer>
            <div>
                <h1 class="text-2xl font-semibold">{{ batch.bank_name }}</h1>
                <p class="text-sm text-muted-foreground">{{ batch.account_number }} · {{ formatDate(batch.batch_date) }} · {{ batch.status }}</p>
            </div>
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Total Item</div><div class="text-xl font-semibold">{{ stats.items_count }}</div></div>
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Matched</div><div class="text-xl font-semibold">{{ stats.matched_items }}</div></div>
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Pending</div><div class="text-xl font-semibold">{{ stats.pending_items }}</div></div>
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Nominal</div><div class="text-xl font-semibold">{{ formatCurrency(stats.total_amount) }}</div></div>
            </div>
            <div class="rounded-lg border overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Beneficiary</th>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Invoice Match</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in batch.items" :key="item.id" class="border-t">
                            <td class="px-4 py-3">
                                <div>{{ item.beneficiary_name }}</div>
                                <div class="text-xs text-muted-foreground">{{ item.beneficiary_account }}</div>
                            </td>
                            <td class="px-4 py-3">{{ item.reference || '-' }}</td>
                            <td class="px-4 py-3">{{ item.invoice ? `${item.invoice.invoice_no} - ${item.invoice.client?.name || '-'}` : 'Belum cocok' }}</td>
                            <td class="px-4 py-3">{{ item.status }}</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(item.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </PageContainer>
    </AppLayout>
</template>
