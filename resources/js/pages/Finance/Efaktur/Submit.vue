<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';

defineProps<{
    eligibleInvoices: Array<{ id: string; invoice_no: string; invoice_date: string; total_amount: number | string; client?: { name: string } | null }>;
}>();

const submitInvoice = (invoiceId: string): void => {
    router.post(`/invoices/${invoiceId}/efaktur/api/submit`);
};
</script>

<template>
    <Head title="Submit E-Faktur" />
    <AppLayout :breadcrumbs="[{ title: 'Finance', href: '#' }, { title: 'E-Faktur', href: '/finance/efaktur' }, { title: 'Submit', href: '/finance/efaktur/submit' }]">
        <PageContainer>
            <div>
                <h1 class="text-2xl font-semibold">Submit E-Faktur API</h1>
                <p class="text-sm text-muted-foreground">Kirim invoice terpilih ke provider e-Faktur yang sudah terkonfigurasi.</p>
            </div>
            <div class="rounded-lg border overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3">Client</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="invoice in eligibleInvoices" :key="invoice.id" class="border-t">
                            <td class="px-4 py-3">{{ invoice.invoice_no }}</td>
                            <td class="px-4 py-3">{{ invoice.client?.name || '-' }}</td>
                            <td class="px-4 py-3">{{ formatDate(invoice.invoice_date) }}</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(invoice.total_amount) }}</td>
                            <td class="px-4 py-3"><Button size="sm" @click="submitInvoice(invoice.id)">Submit</Button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </PageContainer>
    </AppLayout>
</template>
