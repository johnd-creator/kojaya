<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/formatters';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    loans: { data: Array<{ id: number; principal_amount: number | string; installment_amount: number | string; outstanding_amount: number | string; term_months: number; first_due_date: string; status: string; loan_type?: { name: string } | null }> };
    loanTypes: Array<{ id: number; name: string; interest_rate: number | string; admin_fee: number | string; late_fee_per_day: number | string; min_amount: number | string; max_amount: number | string; min_term_months: number; max_term_months: number }>;
}>();

const form = useForm({
    loan_type_id: props.loanTypes[0]?.id ? String(props.loanTypes[0].id) : '',
    principal_amount: 1000000,
    term_months: props.loanTypes[0]?.min_term_months ?? 12,
    first_due_date: new Date(new Date().setMonth(new Date().getMonth() + 1)).toISOString().slice(0, 10),
    purpose: '',
    notes: '',
});

const selectedLoanType = computed(() => props.loanTypes.find((loanType) => String(loanType.id) === String(form.loan_type_id)) ?? null);
const estimatedInterest = computed(() => {
    if (!selectedLoanType.value) {
        return 0;
    }

    return Number(form.principal_amount) * (Number(selectedLoanType.value.interest_rate) / 100) * Number(form.term_months);
});
const estimatedMonthlyInstallment = computed(() => {
    if (!selectedLoanType.value || Number(form.term_months) === 0) {
        return 0;
    }

    return (Number(form.principal_amount) + estimatedInterest.value) / Number(form.term_months);
});

const submit = (): void => {
    form.post('/member/loans');
};
</script>

<template>
    <Head title="Pinjaman Saya" />
    <AppLayout :breadcrumbs="[{ title: 'Kojayaku', href: '/member' }, { title: 'Pinjaman', href: '/member/loans' }]">
        <PageContainer>
            <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <div class="rounded-lg border p-4">
                    <h2 class="text-lg font-semibold">Ajukan Pinjaman</h2>
                    <div class="mt-4 space-y-4">
                        <select v-model="form.loan_type_id" class="h-10 w-full rounded-md border bg-background px-3 text-sm">
                            <option v-for="loanType in loanTypes" :key="loanType.id" :value="String(loanType.id)">{{ loanType.name }}</option>
                        </select>
                        <Input v-model.number="form.principal_amount" type="number" min="1" step="1000" placeholder="Nominal pinjaman" />
                        <Input v-model.number="form.term_months" type="number" min="1" placeholder="Tenor (bulan)" />
                        <Input v-model="form.first_due_date" type="date" />
                        <textarea v-model="form.purpose" class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Tujuan pinjaman"></textarea>
                        <div class="rounded-md border bg-muted/30 p-3 text-sm">
                            <div>Estimasi bunga: <span class="font-medium">{{ formatCurrency(estimatedInterest) }}</span></div>
                            <div>Estimasi angsuran per bulan: <span class="font-medium">{{ formatCurrency(estimatedMonthlyInstallment) }}</span></div>
                            <div v-if="selectedLoanType" class="text-muted-foreground">Biaya admin {{ formatCurrency(selectedLoanType.admin_fee) }} · denda telat {{ formatCurrency(selectedLoanType.late_fee_per_day) }}/hari</div>
                        </div>
                        <Button class="w-full" @click="submit">Kirim Pengajuan</Button>
                    </div>
                </div>

                <div class="rounded-lg border overflow-x-auto">
                    <div class="border-b px-4 py-3 font-semibold">Riwayat Pinjaman</div>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Pokok</th>
                                <th class="px-4 py-3 text-right">Sisa</th>
                                <th class="px-4 py-3">Jatuh Tempo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="loan in loans.data" :key="loan.id" class="border-t">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ loan.loan_type?.name || 'Pinjaman' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ loan.term_months }} bulan · {{ formatCurrency(loan.installment_amount) }}/bulan</div>
                                </td>
                                <td class="px-4 py-3">{{ loan.status }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(loan.principal_amount) }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(loan.outstanding_amount) }}</td>
                                <td class="px-4 py-3">{{ formatDate(loan.first_due_date) }}</td>
                            </tr>
                            <tr v-if="loans.data.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">Belum ada pinjaman.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
