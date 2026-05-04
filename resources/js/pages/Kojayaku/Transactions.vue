<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/formatters';

defineProps<{
    transactions: { data: Array<{ id: number; transaction_no: string; sold_at: string; total_amount: number | string; items: Array<{ id: number; quantity: number; unit_price: number | string; product?: { name: string } | null }>; payments: Array<{ id: number; payment_method: string; amount: number | string }> }> };
}>();
</script>

<template>
    <Head title="Transaksi Saya" />
    <AppLayout :breadcrumbs="[{ title: 'Kojayaku', href: '/member' }, { title: 'Transaksi', href: '/member/transactions' }]">
        <PageContainer>
            <div class="space-y-4">
                <div v-for="transaction in transactions.data" :key="transaction.id" class="rounded-lg border p-4">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="font-semibold">{{ transaction.transaction_no }}</div>
                            <div class="text-sm text-muted-foreground">{{ new Date(transaction.sold_at).toLocaleString('id-ID') }}</div>
                        </div>
                        <div class="text-right font-semibold">{{ formatCurrency(transaction.total_amount) }}</div>
                    </div>
                    <div class="mt-3 space-y-2 text-sm">
                        <div v-for="item in transaction.items" :key="item.id" class="flex items-center justify-between rounded-md bg-muted/30 px-3 py-2">
                            <span>{{ item.product?.name || 'Produk' }} x{{ item.quantity }}</span>
                            <span>{{ formatCurrency(item.unit_price) }}</span>
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-muted-foreground">
                        Pembayaran: {{ transaction.payments.map((payment) => `${payment.payment_method} (${formatCurrency(payment.amount)})`).join(', ') || '-' }}
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
