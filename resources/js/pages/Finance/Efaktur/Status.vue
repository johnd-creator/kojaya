<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/formatters';

defineProps<{
    submissions: { data: Array<{ id: string; provider: string; status: string; created_at: string; invoice?: { invoice_no: string; client?: { name: string } | null } | null }> };
}>();
</script>

<template>
    <Head title="Status E-Faktur" />
    <AppLayout :breadcrumbs="[{ title: 'Finance', href: '#' }, { title: 'E-Faktur', href: '/finance/efaktur' }, { title: 'Status', href: '/finance/efaktur/status' }]">
        <PageContainer>
            <div>
                <h1 class="text-2xl font-semibold">Status Submission</h1>
                <p class="text-sm text-muted-foreground">Pantau hasil submission invoice ke provider e-Faktur.</p>
            </div>
            <div class="rounded-lg border overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3">Client</th>
                            <th class="px-4 py-3">Provider</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="submission in submissions.data" :key="submission.id" class="border-t">
                            <td class="px-4 py-3">{{ submission.invoice?.invoice_no || '-' }}</td>
                            <td class="px-4 py-3">{{ submission.invoice?.client?.name || '-' }}</td>
                            <td class="px-4 py-3">{{ submission.provider }}</td>
                            <td class="px-4 py-3">{{ submission.status }}</td>
                            <td class="px-4 py-3">{{ formatDateTime(submission.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </PageContainer>
    </AppLayout>
</template>
