<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
    payrolls: {
        data: Array<{
            id: number;
            period: string;
            net_salary: number;
            status: string;
            organization?: { name: string } | null;
        }>;
    };
}>();
</script>

<template>
    <Head title="ESS Payslips" />

    <AppLayout :breadcrumbs="[{ title: 'ESS', href: '/ess' }, { title: 'Payslips', href: '/ess/payslips' }]">
        <PageContainer>
            <div>
                <h1 class="text-2xl font-semibold">Slip Gaji Saya</h1>
                <p class="text-sm text-muted-foreground">Lihat riwayat slip gaji pribadi dan unduh PDF jika diperlukan.</p>
            </div>

            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3">Period</th>
                            <th class="px-4 py-3">Organization</th>
                            <th class="px-4 py-3">Net Salary</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payroll in payrolls.data" :key="payroll.id" class="border-t">
                            <td class="px-4 py-3">{{ payroll.period }}</td>
                            <td class="px-4 py-3">{{ payroll.organization?.name || '-' }}</td>
                            <td class="px-4 py-3">{{ payroll.net_salary }}</td>
                            <td class="px-4 py-3">{{ payroll.status }}</td>
                            <td class="px-4 py-3">
                                <Button variant="outline" as-child>
                                    <a :href="`/payrolls/${payroll.id}/download-pdf`" target="_blank">Download PDF</a>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </PageContainer>
    </AppLayout>
</template>
