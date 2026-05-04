<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';

const props = defineProps<{
    invoices: { data: Array<{ id: string; invoice_no: string; invoice_date: string; status: string; total_amount: number | string; client?: { name: string } | null }> };
    batches: { data: Array<{ id: string; reference?: string | null; status: string; items_count: number; created_at: string }> };
}>();

const form = reactive({ invoice_ids: [] as string[] });
const toggleInvoice = (id: string): void => {
    form.invoice_ids = form.invoice_ids.includes(id) ? form.invoice_ids.filter((value) => value !== id) : [...form.invoice_ids, id];
};
const submitBatch = (): void => {
    router.post('/invoices/efaktur/batch', form);
};
</script>

<template>
    <Head title="E-Faktur" />
    <AppLayout :breadcrumbs="[{ title: 'Finance', href: '#' }, { title: 'E-Faktur', href: '/finance/efaktur' }]">
        <PageContainer>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">E-Faktur</h1>
                    <p class="text-sm text-muted-foreground">Kelola invoice siap ekspor CSV dan sinkronisasi status e-Faktur.</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/finance/efaktur/submit"><Button variant="outline">Submit API</Button></Link>
                    <Link href="/finance/efaktur/status"><Button variant="outline">Status</Button></Link>
                </div>
            </div>
            <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-lg border">
                    <div class="border-b px-4 py-3 font-semibold">Invoice Eligible</div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-3">Pilih</th>
                                    <th class="px-4 py-3">Invoice</th>
                                    <th class="px-4 py-3">Client</th>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="invoice in invoices.data" :key="invoice.id" class="border-t">
                                    <td class="px-4 py-3"><input type="checkbox" :checked="form.invoice_ids.includes(invoice.id)" @change="toggleInvoice(invoice.id)" /></td>
                                    <td class="px-4 py-3">{{ invoice.invoice_no }}</td>
                                    <td class="px-4 py-3">{{ invoice.client?.name || '-' }}</td>
                                    <td class="px-4 py-3">{{ formatDate(invoice.invoice_date) }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatCurrency(invoice.total_amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t px-4 py-3">
                        <Button :disabled="form.invoice_ids.length === 0" @click="submitBatch">Buat Batch CSV</Button>
                    </div>
                </div>
                <div class="rounded-lg border">
                    <div class="border-b px-4 py-3 font-semibold">Batch Export</div>
                    <div class="divide-y">
                        <div v-for="batch in batches.data" :key="batch.id" class="p-4 text-sm">
                            <div class="font-medium">{{ batch.reference || batch.id }}</div>
                            <div class="mt-1 text-muted-foreground">{{ batch.status }} · {{ batch.items_count }} item · {{ formatDate(batch.created_at) }}</div>
                            <a class="mt-2 inline-flex text-primary underline" :href="`/invoices/efaktur/batches/${batch.id}/csv`">Unduh CSV</a>
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
