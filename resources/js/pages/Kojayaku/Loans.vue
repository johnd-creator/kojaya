<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatusJourney from '@/components/Kojayaku/StatusJourney.vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';

const props = defineProps<{
    loans: { data: Array<{ id: number; principal_amount: number | string; installment_amount: number | string; outstanding_amount: number | string; term_months: number; first_due_date: string; status: string; loan_type?: { name: string } | null }> };
    loanTypes: Array<{ id: number; name: string; interest_rate: number | string; admin_fee: number | string; late_fee_per_day: number | string; min_amount: number | string; max_amount: number | string; min_term_months: number; max_term_months: number }>;
    journey: { title: string; current_status: string; reference?: string | null; amount?: number | string | null; steps: Array<{ label: string; completed: boolean; completed_at?: string | null }> };
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
            <StatusJourney :title="journey.title" :current-status="journey.current_status" :reference="journey.reference" :amount="journey.amount" :steps="journey.steps" />

            <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <!-- Ajukan Pinjaman Card -->
                <div class="rounded-3xl border border-zinc-100 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-zinc-900 tracking-tight">Ajukan Pinjaman Baru</h2>
                    <p class="text-xs text-zinc-400 mt-1">
                        Isi formulir pengajuan pinjaman di bawah ini dengan lengkap.
                    </p>
                    <div class="mt-6 space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold uppercase tracking-wider text-zinc-450">Jenis Pinjaman</label>
                            <select v-model="form.loan_type_id" class="h-10 w-full rounded-xl border border-zinc-200 bg-background px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all">
                                <option v-for="loanType in loanTypes" :key="loanType.id" :value="String(loanType.id)">{{ loanType.name }}</option>
                            </select>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold uppercase tracking-wider text-zinc-450">Nominal Pinjaman</label>
                                <Input v-model.number="form.principal_amount" type="number" min="1" step="1000" placeholder="Nominal pinjaman" class="rounded-xl" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold uppercase tracking-wider text-zinc-450">Tenor (Bulan)</label>
                                <Input v-model.number="form.term_months" type="number" min="1" placeholder="Tenor (bulan)" class="rounded-xl" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold uppercase tracking-wider text-zinc-450">Tanggal Pembayaran Pertama</label>
                            <Input v-model="form.first_due_date" type="date" class="rounded-xl" />
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold uppercase tracking-wider text-zinc-450">Tujuan Pinjaman</label>
                            <textarea v-model="form.purpose" class="min-h-24 w-full rounded-xl border border-zinc-200 bg-background px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all" placeholder="Tuliskan tujuan pengajuan pinjaman Anda..."></textarea>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50/50 to-teal-50/30 p-5 text-sm space-y-3 shadow-inner">
                            <div class="flex justify-between items-center">
                                <span class="text-zinc-500 font-medium">Estimasi Bunga:</span>
                                <span class="font-extrabold text-emerald-800 text-base">{{ formatCurrency(estimatedInterest) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-zinc-500 font-medium">Estimasi Angsuran / Bulan:</span>
                                <span class="font-extrabold text-zinc-900 text-base">{{ formatCurrency(estimatedMonthlyInstallment) }}</span>
                            </div>
                            <div v-if="selectedLoanType" class="text-xs text-zinc-400 border-t border-emerald-100/50 pt-2.5 flex flex-wrap gap-x-2 gap-y-1 justify-between">
                                <span>Biaya admin: <strong class="text-zinc-600">{{ formatCurrency(selectedLoanType.admin_fee) }}</strong></span>
                                <span>Denda keterlambatan: <strong class="text-zinc-600">{{ formatCurrency(selectedLoanType.late_fee_per_day) }}/hari</strong></span>
                            </div>
                        </div>

                        <Button class="w-full rounded-xl py-6 font-bold uppercase tracking-wider text-xs bg-emerald-800 hover:bg-emerald-900 shadow-md shadow-emerald-800/10 hover:scale-[1.01] transition-all" @click="submit">Kirim Pengajuan</Button>
                    </div>
                </div>

                <!-- Riwayat Pinjaman Card -->
                <div class="rounded-3xl border border-zinc-100 bg-white shadow-sm overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="border-b border-zinc-50 px-6 py-5 font-bold text-zinc-900 tracking-tight">Riwayat Pinjaman</div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-zinc-50/50 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                                    <tr>
                                        <th class="px-6 py-4">Tipe & Cicilan</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-right">Pokok</th>
                                        <th class="px-6 py-4 text-right">Sisa Pinjaman</th>
                                        <th class="px-6 py-4">Jatuh Tempo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="loan in loans.data" :key="loan.id" class="border-t border-zinc-50 transition-colors hover:bg-zinc-50/50">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-zinc-850">{{ loan.loan_type?.name || 'Pinjaman' }}</div>
                                            <div class="text-xs text-zinc-400 mt-1 font-medium">{{ loan.term_months }} bulan · {{ formatCurrency(loan.installment_amount) }}/bulan</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="rounded-full px-2.5 py-0.5 text-xs font-extrabold uppercase tracking-wide bg-amber-100 text-amber-800">
                                                {{ loan.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium text-zinc-600">{{ formatCurrency(loan.principal_amount) }}</td>
                                        <td class="px-6 py-4 text-right font-extrabold text-zinc-900">{{ formatCurrency(loan.outstanding_amount) }}</td>
                                        <td class="px-6 py-4 text-zinc-500 font-medium">{{ formatDate(loan.first_due_date) }}</td>
                                    </tr>
                                    <tr v-if="loans.data.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-zinc-400">Belum ada riwayat pengajuan pinjaman.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
