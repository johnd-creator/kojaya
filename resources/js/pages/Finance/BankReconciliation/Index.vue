<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';

defineProps<{
    batches: { data: Array<{ id: string; bank_name: string; account_number: string; status: string; batch_date: string; items_count: number }> };
}>();
</script>

<template>
    <Head title="Bank Reconciliation" />
    <AppLayout :breadcrumbs="[{ title: 'Finance', href: '#' }, { title: 'Bank Reconciliation', href: '/finance/bank-reconciliation' }]">
        <PageContainer>
            <div>
                <h1 class="text-2xl font-semibold">Bank Reconciliation</h1>
                <p class="text-sm text-muted-foreground">Tinjau batch transfer bank dan cocokkan item yang sudah tereferensi invoice.</p>
            </div>
            <div class="rounded-lg border overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Bank</th>
                            <th class="px-4 py-3">Rekening</th>
                            <th class="px-4 py-3">Tanggal Batch</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="batch in batches.data" :key="batch.id" class="border-t">
                            <td class="px-4 py-3"><Link class="font-medium text-primary underline" :href="`/finance/bank-reconciliation/${batch.id}`">{{ batch.bank_name }}</Link></td>
                            <td class="px-4 py-3">{{ batch.account_number }}</td>
                            <td class="px-4 py-3">{{ formatDate(batch.batch_date) }}</td>
                            <td class="px-4 py-3">{{ batch.status }}</td>
                            <td class="px-4 py-3 text-right">{{ batch.items_count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </PageContainer>
    </AppLayout>
</template>
