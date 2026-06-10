<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
  AlertCircle,
  Bell,
  CreditCard,
  Gift,
  Info,
  ReceiptText,
  WalletCards,
  type LucideIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import StatusJourney from '@/components/Kojayaku/StatusJourney.vue';
import PageContainer from '@/components/PageContainer.vue';
import StatsCard from '@/components/StatsCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/formatters';

const props = defineProps<{
    member: {
        name: string;
        member_no: string;
        validation_status?: string | null;
        onboarding_submitted_at?: string | null;
        organization?: { name: string } | null;
    };
    summary: {
        savings_balance: number;
        pending_invoices: number;
        active_loans: number;
        loan_outstanding: number;
        points_balance: number;
        member_tier: string;
        unread_notifications: number;
    };
    journeys: Record<
        string,
        {
            title: string;
            current_status: string;
            reference?: string | null;
            amount?: number | string | null;
            steps: Array<{ label: string; completed: boolean; completed_at?: string | null }>;
        }
    >;
    recentTransactions: Array<{
        id: number;
        transaction_no: string;
        total_amount: number | string;
        sold_at: string;
    }>;
    recentLoans: Array<{
        id: number;
        status: string;
        outstanding_amount: number | string;
        loan_type?: { name: string } | null;
    }>;
}>();

const validationStatus = computed<string>(() => props.member.validation_status ?? '');

const accessBanner = computed<{
    tone: 'warning' | 'success' | 'destructive' | 'info';
    title: string;
    description: string;
    icon: LucideIcon;
    cta?: { href: string; label: string };
} | null>(() => {
    switch (validationStatus.value) {
        case 'PENDING':
        case 'PENDING_VALIDATION':
            return {
                tone: 'warning',
                title: 'Lengkapi onboarding Anda',
                description:
                    'Data pribadi dan keanggotaan Anda belum lengkap. Simpanan, pinjaman, dan reward akan terbuka setelah pengurus menyetujui keanggotaan Anda.',
                icon: AlertCircle,
                cta: { href: '/member/onboarding', label: 'Lanjutkan Onboarding' },
            };
        case 'REVISION':
            return {
                tone: 'warning',
                title: 'Pengurus meminta revisi',
                description:
                    'Mohon tinjau catatan pengurus dan perbarui data Anda, lalu submit ulang agar proses validasi dapat dilanjutkan.',
                icon: Info,
                cta: { href: '/member/onboarding', label: 'Perbarui Data' },
            };
        case 'REJECTED':
            return {
                tone: 'destructive',
                title: 'Pendaftaran ditolak',
                description:
                    'Pengurus menolak pendaftaran Anda. Hubungi admin untuk informasi lebih lanjut.',
                icon: AlertCircle,
            };
        case 'ACTIVE':
            return null;
        default:
            return null;
    }
});

const bannerClass = computed(() => {
    const tone = accessBanner.value?.tone;
    if (tone === 'destructive') {
        return 'border-red-200 bg-red-50 text-red-900';
    }
    if (tone === 'warning') {
        return 'border-amber-200 bg-amber-50 text-amber-900';
    }
    if (tone === 'success') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-900';
    }
    return 'border-sky-200 bg-sky-50 text-sky-900';
});
</script>

<template>
    <Head title="Kojayaku Dashboard" />
    <AppLayout :breadcrumbs="[{ title: 'Kojayaku', href: '/member' }]">
        <PageContainer>
            <div class="rounded-2xl border bg-gradient-to-r from-primary/10 via-primary/5 to-transparent p-6">
                <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Portal Anggota</p>
                        <h1 class="text-3xl font-bold tracking-tight">{{ member.name }}</h1>
                        <p class="text-sm text-muted-foreground">{{ member.member_no }} · {{ member.organization?.name || 'Koperasi' }}</p>
                    </div>
                    <div class="rounded-lg bg-background/70 px-4 py-3 text-sm">
                        Tier poin: <span class="font-semibold">{{ summary.member_tier }}</span>
                    </div>
                </div>
            </div>

            <div
                v-if="accessBanner"
                data-test="member-access-banner"
                class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                :class="bannerClass"
            >
                <div class="flex items-start gap-3">
                    <component :is="accessBanner.icon" class="mt-0.5 h-5 w-5 shrink-0" />
                    <div>
                        <p class="font-semibold">{{ accessBanner.title }}</p>
                        <p class="text-sm">{{ accessBanner.description }}</p>
                    </div>
                </div>
                <Link
                    v-if="accessBanner.cta"
                    :href="accessBanner.cta.href"
                    class="inline-flex items-center justify-center rounded-md bg-current/10 px-3 py-1.5 text-sm font-semibold underline-offset-2 hover:underline"
                >
                    {{ accessBanner.cta.label }}
                </Link>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <StatsCard label="Saldo Simpanan" :value="formatCurrency(summary.savings_balance)" :icon="WalletCards" />
                <StatsCard label="Pinjaman Aktif" :value="summary.active_loans" :icon="CreditCard" />
                <StatsCard label="Poin Saya" :value="summary.points_balance" :icon="Gift" />
                <StatsCard label="Notifikasi Baru" :value="summary.unread_notifications" :icon="Bell" />
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Link href="/member/savings" class="rounded-lg border p-4 transition hover:bg-muted/40">Simpanan & Tagihan</Link>
                <Link href="/member/loans" class="rounded-lg border p-4 transition hover:bg-muted/40">Pinjaman</Link>
                <Link href="/member/points" class="rounded-lg border p-4 transition hover:bg-muted/40">Poin</Link>
                <Link href="/member/transactions" class="rounded-lg border p-4 transition hover:bg-muted/40">Transaksi POS</Link>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <StatusJourney
                    v-for="journey in journeys"
                    :key="journey.title"
                    :title="journey.title"
                    :current-status="journey.current_status"
                    :reference="journey.reference"
                    :amount="journey.amount"
                    :steps="journey.steps"
                />
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-lg border p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="font-semibold">Transaksi Terbaru</h2>
                        <Link href="/member/transactions" class="text-sm text-primary underline">Lihat semua</Link>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div v-for="transaction in recentTransactions" :key="transaction.id" class="flex items-center justify-between rounded-md border p-3">
                            <div>
                                <div class="font-medium">{{ transaction.transaction_no }}</div>
                                <div class="text-muted-foreground">{{ new Date(transaction.sold_at).toLocaleString('id-ID') }}</div>
                            </div>
                            <div class="font-semibold">{{ formatCurrency(transaction.total_amount) }}</div>
                        </div>
                        <div v-if="recentTransactions.length === 0" class="text-sm text-muted-foreground">Belum ada transaksi.</div>
                    </div>
                </div>
                <div class="rounded-lg border p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="font-semibold">Status Pinjaman</h2>
                        <Link href="/member/loans" class="text-sm text-primary underline">Lihat semua</Link>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div v-for="loan in recentLoans" :key="loan.id" class="flex items-center justify-between rounded-md border p-3">
                            <div>
                                <div class="font-medium">{{ loan.loan_type?.name || 'Pinjaman' }}</div>
                                <div class="text-muted-foreground">{{ loan.status }}</div>
                            </div>
                            <div class="font-semibold">{{ formatCurrency(loan.outstanding_amount) }}</div>
                        </div>
                        <div v-if="recentLoans.length === 0" class="text-sm text-muted-foreground">Belum ada pinjaman.</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border p-4">
                    <div class="text-sm text-muted-foreground">Tagihan Belum Lunas</div>
                    <div class="mt-2 text-xl font-semibold">{{ summary.pending_invoices }}</div>
                </div>
                <div class="rounded-lg border p-4">
                    <div class="text-sm text-muted-foreground">Sisa Pinjaman</div>
                    <div class="mt-2 text-xl font-semibold">{{ formatCurrency(summary.loan_outstanding) }}</div>
                </div>
                <Link href="/member/notifications" class="rounded-lg border p-4 transition hover:bg-muted/40">
                    <div class="text-sm text-muted-foreground">Pusat Notifikasi</div>
                    <div class="mt-2 flex items-center gap-2 text-xl font-semibold"><ReceiptText class="h-5 w-5" /> Buka Notifikasi</div>
                </Link>
            </div>
        </PageContainer>
    </AppLayout>
</template>
