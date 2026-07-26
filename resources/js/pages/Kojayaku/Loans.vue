<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import {
    Coins,
    CalendarDays,
    Clock,
    Calculator,
    CheckCircle2,
    XCircle,
    Percent,
    ShieldCheck,
    History,
    TrendingUp,
    BookOpen
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import MidtransPaymentDialog from '@/components/Kojayaku/MidtransPaymentDialog.vue';
import StatusJourney from '@/components/Kojayaku/StatusJourney.vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import StatusBadge from '@/components/ui/status-badge/StatusBadge.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';

interface Installment {
    id: number;
    installment_no: number;
    due_date: string;
    principal_amount: number | string;
    interest_amount: number | string;
    fee_amount: number | string;
    penalty_amount: number | string;
    amount_due: number | string;
    amount_paid: number | string;
    paid_at: string | null;
    status: string;
}

interface Loan {
    id: number;
    reference_no: string | null;
    principal_amount: number | string;
    installment_amount: number | string;
    outstanding_amount: number | string;
    term_months: number;
    first_due_date: string;
    applied_at: string;
    approved_at: string | null;
    disbursed_at: string | null;
    status: string;
    purpose: string | null;
    notes: string | null;
    rejection_reason: string | null;
    loan_type?: { name: string } | null;
    installments: Installment[];
}

interface PaymentHistoryItem {
    id: string;
    loanType: string;
    loanReference: string | null;
    installmentNo: number;
    paidAmount: number | string;
    paidAt: string | null;
    dueDate: string;
    status: string;
}

const props = defineProps<{
    loans: { 
        data: Loan[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total?: number;
    };
    loanTypes: Array<{ 
        id: number; 
        name: string; 
        interest_rate: number | string; 
        admin_fee: number | string; 
        late_fee_per_day: number | string; 
        min_amount: number | string; 
        max_amount: number | string; 
        min_term_months: number; 
        max_term_months: number;
    }>;
    journey: { 
        title: string; 
        current_status: string; 
        reference?: string | null; 
        amount?: number | string | null; 
        steps: Array<{ label: string; completed: boolean; completed_at?: string | null }>;
    };
}>();

const form = useForm({
    loan_type_id: props.loanTypes[0]?.id ? String(props.loanTypes[0].id) : '',
    principal_amount: props.loanTypes[0] ? Number(props.loanTypes[0].min_amount) : 1000000,
    term_months: props.loanTypes[0]?.min_term_months ?? 12,
    first_due_date: new Date(new Date().setMonth(new Date().getMonth() + 1)).toISOString().slice(0, 10),
    purpose: '',
    notes: '',
});

const selectedLoanType = computed(() => props.loanTypes.find((type) => String(type.id) === String(form.loan_type_id)) ?? null);

const minAmount = computed(() => selectedLoanType.value ? Number(selectedLoanType.value.min_amount) : 1000000);
const maxAmount = computed(() => selectedLoanType.value ? Number(selectedLoanType.value.max_amount) : 100000000);
const minTerm = computed(() => selectedLoanType.value ? Number(selectedLoanType.value.min_term_months) : 1);
const maxTerm = computed(() => selectedLoanType.value ? Number(selectedLoanType.value.max_term_months) : 60);

// Adjust form limits when loan type changes
watch(() => form.loan_type_id, (newId) => {
    const type = props.loanTypes.find(t => String(t.id) === String(newId));
    if (type) {
        const minAmt = Number(type.min_amount);
        const maxAmt = Number(type.max_amount);
        if (form.principal_amount < minAmt) {
            form.principal_amount = minAmt;
        } else if (form.principal_amount > maxAmt) {
            form.principal_amount = maxAmt;
        }

        const minTrm = Number(type.min_term_months);
        const maxTrm = Number(type.max_term_months);
        if (form.term_months < minTrm) {
            form.term_months = minTrm;
        } else if (form.term_months > maxTrm) {
            form.term_months = maxTrm;
        }
    }
});

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

// Dashboard Metrics
const activeLoans = computed(() => props.loans.data.filter(l => l.status === 'ACTIVE'));
const totalOutstanding = computed(() => activeLoans.value.reduce((sum, l) => sum + Number(l.outstanding_amount), 0));
const totalMonthlyObligation = computed(() => activeLoans.value.reduce((sum, l) => sum + Number(l.installment_amount), 0));
const paidOffCount = computed(() => props.loans.data.filter(l => l.status === 'PAID_OFF').length);

const nextPayment = computed(() => {
    let earliestInstallment: Installment | null = null;
    let earliestDate = '';
    
    activeLoans.value.forEach(loan => {
        if (loan.installments) {
            loan.installments.forEach(inst => {
                if (inst.status !== 'PAID') {
                    if (!earliestDate || inst.due_date < earliestDate) {
                        earliestDate = inst.due_date;
                        earliestInstallment = inst;
                    }
                }
            });
        }
    });
    return earliestInstallment;
});

const paymentHistory = computed<PaymentHistoryItem[]>(() => props.loans.data
    .flatMap((loan) => (loan.installments ?? [])
        .filter((installment) => Number(installment.amount_paid) > 0 || ['PAID', 'PARTIAL'].includes(installment.status))
        .map((installment) => ({
            id: `${loan.id}-${installment.id}`,
            loanType: loan.loan_type?.name || 'Pinjaman',
            loanReference: loan.reference_no,
            installmentNo: installment.installment_no,
            paidAmount: installment.amount_paid,
            paidAt: installment.paid_at,
            dueDate: installment.due_date,
            status: installment.status,
        })))
    .sort((first, second) => new Date(second.paidAt ?? second.dueDate).getTime() - new Date(first.paidAt ?? first.dueDate).getTime())
    .slice(0, 6));

const applicationHistory = computed(() => props.loans.data.filter((loan) => loan.status?.toUpperCase() !== 'ACTIVE'));

// UI Controls
const hasLoanHistory = computed(() => (props.loans.total ?? props.loans.data.length) > 0);
const activeTab = ref<'simulasi' | 'riwayat'>(hasLoanHistory.value ? 'riwayat' : 'simulasi');
const selectedLoan = ref<Loan | null>(null);
const isDetailOpen = ref(false);
const selectedInstallment = ref<Installment | null>(null);

function openLoanDetails(loan: Loan) {
    selectedLoan.value = loan;
    isDetailOpen.value = true;
}

function openInstallmentPayment(installment: Installment): void {
    selectedInstallment.value = installment;
    isDetailOpen.value = false;
}

function closeInstallmentPayment(): void {
    selectedInstallment.value = null;
}

function nextUnpaidInstallment(loan: Loan): Installment | null {
    return loan.installments?.find((installment) =>
        ['PENDING', 'PARTIAL', 'OVERDUE'].includes(installment.status),
    ) ?? null;
}

function openNextInstallmentPayment(loan: Loan): void {
    const installment = nextUnpaidInstallment(loan);
    if (installment) {
        openInstallmentPayment(installment);
    }
}

function selectQuickAmount(amount: number) {
    if (amount >= minAmount.value && amount <= maxAmount.value) {
        form.principal_amount = amount;
    }
}

function selectQuickTerm(months: number) {
    if (months >= minTerm.value && months <= maxTerm.value) {
        form.term_months = months;
    }
}

function selectLoanType(id: number) {
    form.loan_type_id = String(id);
}

const submit = (): void => {
    form.post('/member/loans', {
        onSuccess: () => {
            form.reset('purpose', 'notes');
            activeTab.value = 'riwayat';
        }
    });
};

function formatLoanStatus(status: string): string {
    switch (status?.toUpperCase()) {
        case 'APPLIED': return 'Diajukan';
        case 'MANAGER_APPROVED': return 'Direview Manajer';
        case 'APPROVED': return 'Disetujui';
        case 'REJECTED': return 'Ditolak';
        case 'ACTIVE': return 'Aktif';
        case 'PAID_OFF': return 'Lunas';
        case 'DEFAULTED': return 'Kewajiban Menunggak';
        case 'WRITTEN_OFF': return 'Dihapuskan';
        default: return status;
    }
}

function formatInstallmentStatus(status: string): string {
    switch (status?.toUpperCase()) {
        case 'PENDING': return 'Belum Bayar';
        case 'PARTIAL': return 'Bayar Sebagian';
        case 'PAID': return 'Lunas';
        case 'OVERDUE': return 'Terlambat';
        default: return status;
    }
}

function getInstallmentStatusClasses(status: string): string {
    switch (status?.toUpperCase()) {
        case 'PAID':
            return 'bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20';
        case 'PARTIAL':
            return 'bg-amber-50 text-amber-700 border border-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20';
        case 'OVERDUE':
            return 'bg-rose-50 text-rose-700 border border-rose-100 animate-pulse dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20';
        case 'PENDING':
        default:
            return 'bg-zinc-50 text-zinc-600 border border-zinc-200 dark:bg-zinc-500/10 dark:text-zinc-400 dark:border-zinc-500/20';
    }
}
</script>

<template>
    <Head title="Pinjaman Saya" />
    <AppLayout :breadcrumbs="[{ title: 'Kojayaku', href: '/member' }, { title: 'Pinjaman', href: '/member/loans' }]">
        <PageContainer>
            <!-- Header Section -->
            <header class="flex items-center gap-5 mb-6">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 text-white shadow-lg shadow-emerald-800/20">
                    <TrendingUp class="h-8 w-8" />
                </div>
                <div>
                    <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight">Portal Pinjaman Anggota</h1>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Kalkulasi, ajukan pinjaman baru, dan pantau tagihan pinjaman aktif Anda.</p>
                </div>
            </header>

            <!-- Active Journey State -->
            <div v-if="journey && journey.current_status !== 'BELUM_ADA_PENGAJUAN'" class="mb-6">
                <StatusJourney 
                    :title="journey.title" 
                    :current-status="formatLoanStatus(journey.current_status)" 
                    :reference="journey.reference" 
                    :amount="journey.amount" 
                    :steps="journey.steps" 
                />
            </div>

            <!-- Quick Metrics Row -->
            <div class="grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-4 mb-6">
                <div class="p-4 rounded-3xl border border-zinc-100 bg-white shadow-sm dark:bg-zinc-900 dark:border-zinc-800 flex items-center justify-between group hover:shadow-md transition-all sm:p-6">
                    <div>
                        <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Sisa Pinjaman</span>
                        <p class="text-xl font-black text-zinc-900 dark:text-white mt-1 sm:text-2xl">{{ formatCurrency(totalOutstanding) }}</p>
                        <p class="text-xs text-emerald-600 mt-1 font-semibold">{{ activeLoans.length }} Pinjaman Aktif</p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <Coins class="h-6 w-6" />
                    </div>
                </div>

                <div class="p-4 rounded-3xl border border-zinc-100 bg-white shadow-sm dark:bg-zinc-900 dark:border-zinc-800 flex items-center justify-between group hover:shadow-md transition-all sm:p-6">
                    <div>
                        <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Kewajiban Bulanan</span>
                        <p class="text-xl font-black text-zinc-900 dark:text-white mt-1 sm:text-2xl">{{ formatCurrency(totalMonthlyObligation) }}</p>
                        <p class="text-xs text-zinc-500 mt-1 font-medium">Total angsuran berjalan</p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <CalendarDays class="h-6 w-6" />
                    </div>
                </div>

                <div class="p-4 rounded-3xl border border-zinc-100 bg-white shadow-sm dark:bg-zinc-900 dark:border-zinc-800 flex items-center justify-between group hover:shadow-md transition-all sm:p-6">
                    <div>
                        <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Tagihan Terdekat</span>
                        <p class="text-xl font-black text-zinc-900 dark:text-white mt-1 sm:text-2xl">
                            {{ nextPayment ? formatCurrency(Number(nextPayment.amount_due) - Number(nextPayment.amount_paid)) : 'Rp 0' }}
                        </p>
                        <p class="text-xs text-zinc-500 mt-1 font-medium truncate">
                            {{ nextPayment ? 'Jatuh tempo: ' + formatDate(nextPayment.due_date) : 'Tidak ada tagihan' }}
                        </p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <Clock class="h-6 w-6" />
                    </div>
                </div>

                <div class="p-4 rounded-3xl border border-zinc-100 bg-white shadow-sm dark:bg-zinc-900 dark:border-zinc-800 flex items-center justify-between group hover:shadow-md transition-all sm:p-6">
                    <div>
                        <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Pinjaman Lunas</span>
                        <p class="text-xl font-black text-zinc-900 dark:text-white mt-1 sm:text-2xl">{{ paidOffCount }}</p>
                        <p class="text-xs text-zinc-500 mt-1 font-medium">Pinjaman telah diselesaikan</p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-zinc-100 text-zinc-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <CheckCircle2 class="h-6 w-6" />
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <Tabs v-model="activeTab" class="space-y-6">
                <div class="flex justify-between items-center border-b border-zinc-100 dark:border-zinc-800 pb-1">
                    <TabsList class="bg-zinc-100/60 p-1 rounded-xl">
                        <TabsTrigger value="simulasi" class="rounded-lg px-4 py-2 text-xs font-bold tracking-wide uppercase">
                            Simulasi & Ajukan
                        </TabsTrigger>
                        <TabsTrigger value="riwayat" class="rounded-lg px-4 py-2 text-xs font-bold tracking-wide uppercase">
                            Daftar Pinjaman & Riwayat
                        </TabsTrigger>
                    </TabsList>
                </div>

                <!-- Tab: Simulasi & Pengajuan -->
                <TabsContent value="simulasi" class="mt-0 outline-none">
                    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                        <!-- Calculator Form -->
                        <div class="rounded-3xl border border-zinc-100 bg-white p-4 shadow-sm dark:bg-zinc-900 dark:border-zinc-800 space-y-6 sm:p-6">
                            <div class="flex items-center gap-3 border-b border-zinc-50 dark:border-zinc-800 pb-4">
                                <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                                    <Calculator class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-black text-zinc-900 tracking-tight dark:text-white">Kalkulator Pinjaman Baru</h2>
                                    <p class="text-xs text-zinc-400">Atur nominal dan tenor untuk simulasi pembayaran bulanan Anda.</p>
                                </div>
                            </div>

                            <div class="space-y-5">
                                <!-- Choose Loan Type -->
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-500">Pilih Jenis Pinjaman</label>
                                    <select v-model="form.loan_type_id" class="h-11 w-full rounded-xl border border-zinc-200 bg-background px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all dark:border-zinc-800 dark:text-white">
                                        <option v-for="type in loanTypes" :key="type.id" :value="String(type.id)">{{ type.name }} ({{ type.interest_rate }}% bunga)</option>
                                    </select>
                                </div>

                                <!-- Principal Amount Slider & Input -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-500">Nominal Pinjaman</label>
                                        <span class="text-lg font-black text-emerald-800 dark:text-emerald-500">{{ formatCurrency(form.principal_amount) }}</span>
                                    </div>
                                    
                                    <div class="relative flex items-center">
                                        <input 
                                            type="range" 
                                            v-model.number="form.principal_amount" 
                                            :min="minAmount" 
                                            :max="maxAmount" 
                                            step="500000"
                                            class="w-full h-2 bg-zinc-100 rounded-lg appearance-none cursor-pointer accent-emerald-600 dark:bg-zinc-800" 
                                        />
                                    </div>

                                    <!-- Quick Amount Buttons -->
                                    <div class="flex flex-wrap gap-2">
                                        <button 
                                            v-for="amt in [...new Set([minAmount, 2000000, 5000000, 10000000, 20000000, maxAmount])].filter(a => a >= minAmount && a <= maxAmount)" 
                                            :key="amt"
                                            type="button" 
                                            @click="selectQuickAmount(amt)"
                                            class="px-3 py-1 text-xs font-semibold rounded-lg border border-zinc-100 bg-zinc-50 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:border-zinc-800 dark:bg-zinc-800 dark:hover:bg-emerald-950 transition-colors text-zinc-600 dark:text-zinc-300"
                                        >
                                            {{ formatCurrency(amt) }}
                                        </button>
                                    </div>

                                    <div class="grid gap-2">
                                        <Input 
                                            v-model.number="form.principal_amount" 
                                            type="number" 
                                            :min="minAmount" 
                                            :max="maxAmount"
                                            step="100000" 
                                            placeholder="Masukkan nominal manual" 
                                            class="rounded-xl border-zinc-200 dark:border-zinc-800" 
                                        />
                                        <span class="text-[10px] text-zinc-400">Limit diperbolehkan: {{ formatCurrency(minAmount) }} - {{ formatCurrency(maxAmount) }}</span>
                                        <p v-if="form.errors.principal_amount" class="text-xs text-rose-600 font-medium">{{ form.errors.principal_amount }}</p>
                                    </div>
                                </div>

                                <!-- Term (Months) Slider & Input -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-500">Tenor Pinjaman</label>
                                        <span class="text-lg font-black text-zinc-900 dark:text-white">{{ form.term_months }} Bulan</span>
                                    </div>
                                    
                                    <div class="relative flex items-center">
                                        <input 
                                            type="range" 
                                            v-model.number="form.term_months" 
                                            :min="minTerm" 
                                            :max="maxTerm" 
                                            step="1"
                                            class="w-full h-2 bg-zinc-100 rounded-lg appearance-none cursor-pointer accent-emerald-600 dark:bg-zinc-800" 
                                        />
                                    </div>

                                    <!-- Quick Term Buttons -->
                                    <div class="flex flex-wrap gap-2">
                                        <button 
                                            v-for="trm in [6, 12, 24, 36]" 
                                            :key="trm"
                                            v-show="trm >= minTerm && trm <= maxTerm"
                                            type="button" 
                                            @click="selectQuickTerm(trm)"
                                            class="px-3 py-1 text-xs font-semibold rounded-lg border border-zinc-100 bg-zinc-50 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 dark:border-zinc-800 dark:bg-zinc-800 dark:hover:bg-emerald-950 transition-colors text-zinc-600 dark:text-zinc-300"
                                        >
                                            {{ trm }} Bulan
                                        </button>
                                    </div>

                                    <div class="grid gap-2">
                                        <Input 
                                            v-model.number="form.term_months" 
                                            type="number" 
                                            :min="minTerm" 
                                            :max="maxTerm" 
                                            placeholder="Tenor manual" 
                                            class="rounded-xl border-zinc-200 dark:border-zinc-800" 
                                        />
                                        <span class="text-[10px] text-zinc-400">Tenor diperbolehkan: {{ minTerm }} - {{ maxTerm }} bulan</span>
                                        <p v-if="form.errors.term_months" class="text-xs text-rose-600 font-medium">{{ form.errors.term_months }}</p>
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <!-- First due date -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-500">Tanggal Pembayaran Pertama</label>
                                        <Input v-model="form.first_due_date" type="date" class="rounded-xl border-zinc-200 dark:border-zinc-800" />
                                        <p v-if="form.errors.first_due_date" class="text-xs text-rose-600 font-medium">{{ form.errors.first_due_date }}</p>
                                    </div>

                                    <!-- Purpose -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-500">Tujuan Pinjaman</label>
                                        <Input v-model="form.purpose" placeholder="Misal: Biaya Sekolah, Renovasi Rumah" class="rounded-xl border-zinc-200 dark:border-zinc-800" />
                                        <p v-if="form.errors.purpose" class="text-xs text-rose-600 font-medium">{{ form.errors.purpose }}</p>
                                    </div>
                                </div>

                                <!-- Notes (optional) -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-500">Catatan Tambahan (Opsional)</label>
                                    <textarea v-model="form.notes" class="min-h-20 w-full rounded-xl border border-zinc-200 bg-background px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all dark:border-zinc-800 dark:text-white" placeholder="Tulis catatan jika ada..."></textarea>
                                    <p v-if="form.errors.notes" class="text-xs text-rose-600 font-medium">{{ form.errors.notes }}</p>
                                </div>

                                <!-- Interactive Calculation Summary -->
                                <div class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50/50 to-teal-50/30 p-5 space-y-3 shadow-inner dark:border-emerald-950/20 dark:from-emerald-950/20 dark:to-teal-950/10">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-zinc-500 dark:text-zinc-400 font-semibold flex items-center gap-1.5">
                                            <Percent class="h-4 w-4 text-emerald-600" />
                                            Bunga Bulanan:
                                        </span>
                                        <span class="font-extrabold text-zinc-800 dark:text-zinc-200">
                                            {{ selectedLoanType ? selectedLoanType.interest_rate : '0' }}%
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-zinc-500 dark:text-zinc-400 font-semibold flex items-center gap-1.5">
                                            <Coins class="h-4 w-4 text-emerald-600" />
                                            Estimasi Total Bunga:
                                        </span>
                                        <span class="font-extrabold text-zinc-800 dark:text-zinc-200">{{ formatCurrency(estimatedInterest) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm border-t border-emerald-100/60 dark:border-emerald-950/40 pt-3">
                                        <span class="text-zinc-600 dark:text-zinc-300 font-bold flex items-center gap-1.5">
                                            <CalendarDays class="h-4 w-4 text-emerald-700" />
                                            Angsuran / Bulan:
                                        </span>
                                        <span class="font-black text-emerald-800 dark:text-emerald-400 text-lg">{{ formatCurrency(estimatedMonthlyInstallment) }}</span>
                                    </div>
                                    
                                    <div v-if="selectedLoanType" class="text-[10px] text-zinc-400 border-t border-emerald-100/30 dark:border-emerald-950/30 pt-2.5 flex flex-wrap gap-x-3 gap-y-1 justify-between">
                                        <span>Biaya admin: <strong class="text-zinc-600 dark:text-zinc-300">{{ formatCurrency(selectedLoanType.admin_fee) }}</strong></span>
                                        <span>Denda keterlambatan: <strong class="text-zinc-600 dark:text-zinc-300">{{ formatCurrency(selectedLoanType.late_fee_per_day) }}/hari</strong></span>
                                    </div>
                                </div>

                                <Button 
                                    class="w-full rounded-xl py-6 font-bold uppercase tracking-wider text-xs bg-emerald-800 hover:bg-emerald-900 text-white shadow-md shadow-emerald-800/10 hover:scale-[1.01] transition-all dark:bg-emerald-700 dark:hover:bg-emerald-800" 
                                    @click="submit"
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'Mengirim Pengajuan...' : 'Kirim Pengajuan Pinjaman' }}
                                </Button>
                            </div>
                        </div>

                        <!-- Loan Info and Catalog -->
                        <div class="space-y-6">
                            <!-- Loan Catalog Selection Card -->
                            <div class="rounded-3xl border border-zinc-100 bg-white p-4 shadow-sm dark:bg-zinc-900 dark:border-zinc-800 sm:p-6">
                                <div class="flex items-center gap-3 mb-4 border-b border-zinc-50 dark:border-zinc-800 pb-3">
                                    <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                                        <BookOpen class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-black text-zinc-900 dark:text-white tracking-tight">Katalog Pinjaman</h2>
                                        <p class="text-xs text-zinc-400">Pilih jenis pinjaman di bawah untuk mengaplikasikannya ke kalkulator.</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div 
                                        v-for="type in loanTypes" 
                                        :key="type.id" 
                                        @click="selectLoanType(type.id)"
                                        class="p-4 rounded-2xl border bg-zinc-50/50 hover:bg-white hover:shadow-md transition-all cursor-pointer dark:bg-zinc-950/40 dark:hover:bg-zinc-900 dark:border-zinc-800 flex flex-col justify-between"
                                        :class="{ 'border-emerald-500 bg-white ring-2 ring-emerald-500/10 dark:border-emerald-500': String(form.loan_type_id) === String(type.id) }"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-sm text-zinc-900 dark:text-white">{{ type.name }}</span>
                                            <span class="text-xs font-black text-emerald-800 bg-emerald-50 dark:bg-emerald-950/50 dark:text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-100 dark:border-emerald-900/30">
                                                Bunga {{ type.interest_rate }}%
                                            </span>
                                        </div>
                                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-zinc-500 dark:text-zinc-400 pt-2 border-t border-dashed border-zinc-200/50 dark:border-zinc-800">
                                            <div>
                                                <span class="block text-[10px] text-zinc-400 uppercase tracking-wide">Batas Pinjaman</span>
                                                <span class="font-bold text-zinc-700 dark:text-zinc-200">{{ formatCurrency(type.min_amount) }} - {{ formatCurrency(type.max_amount) }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-[10px] text-zinc-400 uppercase tracking-wide">Pilihan Tenor</span>
                                                <span class="font-bold text-zinc-700 dark:text-zinc-200">{{ type.min_term_months }} - {{ type.max_term_months }} Bulan</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Shield Safe Info -->
                            <div class="rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50/50 to-teal-50/30 p-4 flex items-start gap-4 shadow-sm dark:from-emerald-950/10 dark:to-teal-950/5 dark:border-emerald-950/20 sm:p-6">
                                <div class="h-11 w-11 shrink-0 rounded-xl bg-white dark:bg-zinc-900 flex items-center justify-center text-emerald-700 border border-emerald-50 dark:border-zinc-800 shadow-sm">
                                    <ShieldCheck class="h-5 w-5" />
                                </div>
                                <div class="space-y-1">
                                    <h3 class="font-bold text-emerald-950 dark:text-emerald-300 text-sm">Persyaratan & Keamanan</h3>
                                    <p class="text-xs text-emerald-900/70 dark:text-zinc-400 leading-relaxed">
                                        Seluruh transaksi dan pengajuan pinjaman di koperasi Kojaya dijamin aman dan transparan. Harap perhatikan tanggal jatuh tempo untuk menghindari denda keterlambatan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </TabsContent>

                <!-- Tab: Daftar Pinjaman & Riwayat -->
                <TabsContent value="riwayat" class="mt-0 outline-none space-y-6">
                    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.55fr)_minmax(20rem,0.85fr)]">
                        <!-- Primary Loan Panel -->
                        <section class="overflow-hidden rounded-3xl border border-zinc-100 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex items-start gap-3 border-b border-zinc-50 p-4 dark:border-zinc-800 sm:p-6">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                    <TrendingUp class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-black tracking-tight text-zinc-900 dark:text-white">Pinjaman & Pengajuan</h2>
                                    <p class="mt-1 text-xs text-zinc-400">Pantau pinjaman aktif dan pengajuan Anda dalam satu tempat.</p>
                                </div>
                            </div>

                            <!-- Active Loans -->
                            <div class="border-b border-zinc-100 dark:border-zinc-800">
                                <div class="flex items-center justify-between gap-3 px-4 pb-3 pt-5 sm:px-6">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Pinjaman Aktif</h3>
                                    <span v-if="activeLoans.length" class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        {{ activeLoans.length }} aktif
                                    </span>
                                </div>

                                <div v-if="activeLoans.length" class="divide-y divide-zinc-50 dark:divide-zinc-800">
                                    <div v-for="loan in activeLoans" :key="loan.id" class="px-4 py-4 sm:px-6 sm:py-5">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="truncate text-sm font-extrabold text-zinc-900 dark:text-white">
                                                        {{ loan.loan_type?.name || 'Pinjaman Aktif' }}
                                                    </p>
                                                    <StatusBadge :status="loan.status" :label="formatLoanStatus(loan.status)" />
                                                </div>
                                                <p class="mt-1 text-xs text-zinc-400">Ref No: {{ loan.reference_no || 'Menunggu nomor referensi' }}</p>
                                            </div>
                                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    class="rounded-xl text-xs font-bold"
                                                    @click="openLoanDetails(loan)"
                                                >
                                                    Jadwal
                                                </Button>
                                                <Button
                                                    v-if="nextUnpaidInstallment(loan)"
                                                    type="button"
                                                    size="sm"
                                                    class="rounded-xl bg-emerald-700 text-xs font-bold text-white hover:bg-emerald-800"
                                                    @click="openNextInstallmentPayment(loan)"
                                                >
                                                    Bayar
                                                </Button>
                                            </div>
                                        </div>

                                        <div class="mt-4 grid grid-cols-2 gap-3 text-xs sm:grid-cols-3 sm:gap-4">
                                            <div class="rounded-2xl bg-zinc-50 px-3 py-2.5 dark:bg-zinc-950/50">
                                                <span class="block text-[10px] font-bold uppercase tracking-wide text-zinc-400">Sisa pinjaman</span>
                                                <span class="mt-1 block text-sm font-black text-zinc-900 dark:text-white">{{ formatCurrency(loan.outstanding_amount) }}</span>
                                            </div>
                                            <div class="rounded-2xl bg-zinc-50 px-3 py-2.5 dark:bg-zinc-950/50">
                                                <span class="block text-[10px] font-bold uppercase tracking-wide text-zinc-400">Tagihan berikutnya</span>
                                                <span class="mt-1 block text-sm font-black text-amber-700 dark:text-amber-400">
                                                    {{ nextUnpaidInstallment(loan) ? formatCurrency(Number(nextUnpaidInstallment(loan)?.amount_due) - Number(nextUnpaidInstallment(loan)?.amount_paid)) : 'Rp 0' }}
                                                </span>
                                            </div>
                                            <div class="col-span-2 rounded-2xl bg-zinc-50 px-3 py-2.5 dark:bg-zinc-950/50 sm:col-span-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-[10px] font-bold uppercase tracking-wide text-zinc-400">Progres</span>
                                                    <span class="text-xs font-black text-emerald-700 dark:text-emerald-400">
                                                        {{ Math.round(((Number(loan.principal_amount) - Number(loan.outstanding_amount)) / Number(loan.principal_amount)) * 100) }}%
                                                    </span>
                                                </div>
                                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                                                    <div
                                                        class="h-full rounded-full bg-emerald-600 transition-all duration-500"
                                                        :style="{ width: `${((Number(loan.principal_amount) - Number(loan.outstanding_amount)) / Number(loan.principal_amount)) * 100}%` }"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="px-6 pb-6 pt-2 text-sm text-zinc-400">
                                    Belum ada pinjaman aktif saat ini.
                                </div>
                            </div>

                            <!-- Application History -->
                            <div>
                                <div class="px-4 pb-3 pt-5 sm:px-6">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Riwayat Pengajuan</h3>
                                </div>
                                <div v-if="applicationHistory.length" class="divide-y divide-zinc-50 dark:divide-zinc-800">
                                    <div v-for="loan in applicationHistory" :key="loan.id" class="flex items-center gap-3 px-4 py-4 transition-colors hover:bg-zinc-50/60 dark:hover:bg-zinc-950/20 sm:px-6">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="truncate text-sm font-extrabold text-zinc-900 dark:text-white">{{ loan.loan_type?.name || 'Pinjaman' }}</p>
                                                <StatusBadge :status="loan.status" :label="formatLoanStatus(loan.status)" />
                                            </div>
                                            <p class="mt-1 truncate text-xs text-zinc-400">
                                                {{ loan.reference_no || 'Menunggu nomor referensi' }} · Diajukan {{ formatDate(loan.applied_at) }}
                                            </p>
                                            <p class="mt-1 text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                                                {{ formatCurrency(loan.principal_amount) }} · {{ loan.term_months }} bulan
                                            </p>
                                        </div>
                                        <Button variant="secondary" size="sm" class="shrink-0 rounded-xl text-xs font-bold" @click="openLoanDetails(loan)">
                                            Detail
                                        </Button>
                                    </div>
                                </div>
                                <div v-else class="px-6 pb-6 pt-2 text-sm text-zinc-400">
                                    {{ activeLoans.length ? 'Belum ada pengajuan lain di luar pinjaman aktif.' : 'Belum ada riwayat pengajuan pinjaman.' }}
                                </div>
                            </div>

                            <!-- Pagination Links -->
                            <div v-if="loans.links && loans.links.length > 3" class="flex flex-wrap justify-center gap-1.5 border-t border-zinc-100 px-4 py-4 dark:border-zinc-800 sm:px-6">
                                <template v-for="(link, key) in loans.links" :key="key">
                                    <div v-if="link.url === null" class="rounded-lg border border-zinc-100 px-3 py-1.5 text-xs font-bold text-zinc-400 dark:border-zinc-800" v-html="link.label" />
                                    <Link
                                        v-else
                                        class="rounded-lg border border-zinc-100 px-3 py-1.5 text-xs font-bold text-zinc-700 transition-colors hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                        :class="{ 'border-emerald-600 bg-emerald-600 text-white hover:border-emerald-700 hover:bg-emerald-700 dark:border-emerald-700 dark:bg-emerald-700': link.active }"
                                        :href="link.url"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </section>

                        <!-- Payment History Card -->
                        <section class="overflow-hidden rounded-3xl border border-zinc-100 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex items-start gap-3 border-b border-zinc-50 p-4 dark:border-zinc-800 sm:p-6">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                    <CheckCircle2 class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-black tracking-tight text-zinc-900 dark:text-white">Pembayaran Terbaru</h2>
                                    <p class="mt-1 text-xs text-zinc-400">Cicilan yang sudah tercatat pada pinjaman Anda.</p>
                                </div>
                            </div>

                            <div v-if="paymentHistory.length" class="divide-y divide-zinc-50 dark:divide-zinc-800">
                                <div v-for="payment in paymentHistory" :key="payment.id" class="flex items-center gap-3 px-4 py-4 sm:px-6">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        <CheckCircle2 class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-extrabold text-zinc-900 dark:text-white">Cicilan ke-{{ payment.installmentNo }}</p>
                                        <p class="mt-1 truncate text-xs text-zinc-400">
                                            {{ payment.loanType }}<span v-if="payment.loanReference"> · {{ payment.loanReference }}</span>
                                        </p>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ formatDate(payment.paidAt ?? payment.dueDate) }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-sm font-black text-emerald-700 dark:text-emerald-400">{{ formatCurrency(payment.paidAmount) }}</p>
                                        <span :class="['mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold', getInstallmentStatusClasses(payment.status)]">
                                            {{ formatInstallmentStatus(payment.status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="px-6 py-12 text-center text-sm text-zinc-400">Belum ada pembayaran cicilan.</div>
                        </section>
                    </div>
                </TabsContent>
            </Tabs>

            <!-- Detailed Loan Modal -->
            <Dialog :open="isDetailOpen" @update:open="isDetailOpen = $event">
                <DialogContent class="w-[calc(100vw-2rem)] max-w-5xl max-h-[90vh] overflow-x-hidden overflow-y-auto rounded-3xl p-4 dark:bg-zinc-900 dark:border-zinc-800 sm:p-6">
                    <DialogHeader v-if="selectedLoan">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <DialogTitle class="text-xl font-black text-zinc-900 dark:text-white">
                                Detail Pengajuan Pinjaman
                            </DialogTitle>
                            <StatusBadge :status="selectedLoan.status" :label="formatLoanStatus(selectedLoan.status)" />
                        </div>
                        <DialogDescription class="text-xs text-zinc-400 mt-1">
                            Ref No: {{ selectedLoan.reference_no || 'Menunggu Persetujuan Pengurus' }}
                        </DialogDescription>
                    </DialogHeader>

                    <div v-if="selectedLoan" class="mt-4 space-y-6">
                        <!-- Rejection Warning Banner -->
                        <div v-if="selectedLoan.status === 'REJECTED' && selectedLoan.rejection_reason" class="p-4 rounded-2xl border border-rose-100 bg-rose-50/50 text-sm flex gap-3 text-rose-800 dark:bg-rose-950/20 dark:border-rose-900/30 dark:text-rose-400">
                            <XCircle class="h-5 w-5 shrink-0 mt-0.5 text-rose-600" />
                            <div>
                                <span class="font-bold">Pengajuan Ditolak:</span>
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ selectedLoan.rejection_reason }}</p>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 p-4 rounded-2xl bg-zinc-50 border border-zinc-100 dark:bg-zinc-950/40 dark:border-zinc-800">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-500">Pokok Pinjaman</span>
                                <p class="font-extrabold text-zinc-900 dark:text-white mt-0.5">{{ formatCurrency(selectedLoan.principal_amount) }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-500">Sisa Pinjaman</span>
                                <p class="font-extrabold text-emerald-800 dark:text-emerald-400 mt-0.5">{{ formatCurrency(selectedLoan.outstanding_amount) }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-500">Angsuran Bulanan</span>
                                <p class="font-extrabold text-zinc-900 dark:text-white mt-0.5">{{ formatCurrency(selectedLoan.installment_amount) }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-500">Tenor Pinjaman</span>
                                <p class="font-bold text-zinc-700 dark:text-zinc-400 mt-0.5">{{ selectedLoan.term_months }} Bulan</p>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-500">Tanggal Diajukan</span>
                                <p class="font-bold text-zinc-700 dark:text-zinc-400 mt-0.5">{{ formatDate(selectedLoan.applied_at) }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-500">Pembayaran Pertama</span>
                                <p class="font-bold text-zinc-700 dark:text-zinc-400 mt-0.5">{{ formatDate(selectedLoan.first_due_date) }}</p>
                            </div>
                        </div>

                        <!-- Notes & Purpose details -->
                        <div class="space-y-4">
                            <div v-if="selectedLoan.purpose" class="space-y-1">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-500">Tujuan Pinjaman</span>
                                <p class="text-sm text-zinc-700 dark:text-zinc-400 bg-white dark:bg-zinc-950 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                                    {{ selectedLoan.purpose }}
                                </p>
                            </div>
                            <div v-if="selectedLoan.notes" class="space-y-1">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-500">Catatan Pengurus</span>
                                <p class="text-sm text-zinc-700 dark:text-zinc-400 bg-white dark:bg-zinc-950 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                                    {{ selectedLoan.notes }}
                                </p>
                            </div>
                        </div>

                        <!-- Repayment Schedule List -->
                        <div v-if="['ACTIVE', 'APPROVED', 'PAID_OFF'].includes(selectedLoan.status) && selectedLoan.installments && selectedLoan.installments.length > 0">
                            <h3 class="font-black text-sm text-zinc-950 dark:text-white tracking-tight mb-3">Jadwal Angsuran Cicilan</h3>
                            <div class="overflow-hidden rounded-2xl border border-zinc-100 bg-white dark:bg-zinc-950 dark:border-zinc-800">
                                <div class="max-h-60 overflow-y-auto">
                                    <table class="w-full table-fixed text-left text-xs">
                                        <thead class="bg-zinc-50 dark:bg-zinc-900 text-[10px] font-bold uppercase tracking-wider text-zinc-500 sticky top-0 border-b border-zinc-100 dark:border-zinc-800">
                                            <tr>
                                                <th class="px-4 py-3">No.</th>
                                                <th class="px-4 py-3">Jatuh Tempo</th>
                                                <th class="px-4 py-3 text-right">Tagihan</th>
                                                <th class="px-4 py-3 text-right">Jumlah Dibayar</th>
                                                <th class="px-4 py-3">Status</th>
                                                <th class="px-4 py-3 text-right">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800">
                                            <tr v-for="inst in selectedLoan.installments" :key="inst.id" class="hover:bg-zinc-50/40 dark:hover:bg-zinc-900/30 transition-colors">
                                                <td class="px-4 py-3 font-bold text-zinc-800 dark:text-zinc-200">#{{ inst.installment_no }}</td>
                                                <td class="px-4 py-3 font-medium text-zinc-500">{{ formatDate(inst.due_date) }}</td>
                                                <td class="px-4 py-3 text-right font-extrabold text-zinc-800 dark:text-zinc-200">{{ formatCurrency(inst.amount_due) }}</td>
                                                <td class="px-4 py-3 text-right font-semibold text-emerald-800 dark:text-emerald-400">
                                                    {{ inst.amount_paid > 0 ? formatCurrency(inst.amount_paid) : '-' }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide"
                                                          :class="getInstallmentStatusClasses(inst.status)">
                                                        {{ formatInstallmentStatus(inst.status) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <Button
                                                        v-if="['PENDING', 'PARTIAL', 'OVERDUE'].includes(inst.status)"
                                                        type="button"
                                                        size="sm"
                                                        class="rounded-xl bg-emerald-700 text-xs font-bold text-white hover:bg-emerald-800"
                                                        @click="openInstallmentPayment(inst)"
                                                    >
                                                        Bayar
                                                    </Button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" class="rounded-xl text-xs font-bold" @click="isDetailOpen = false">
                            Tutup Detail
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <MidtransPaymentDialog
                :open="selectedInstallment !== null"
                :installment="selectedInstallment"
                kind="loan"
                @update:open="closeInstallmentPayment"
            />
        </PageContainer>
    </AppLayout>
</template>
