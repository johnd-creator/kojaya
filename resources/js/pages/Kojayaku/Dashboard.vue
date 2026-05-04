<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell, CreditCard, Gift, ReceiptText, WalletCards } from 'lucide-vue-next';
import PageContainer from '@/components/PageContainer.vue';
import StatsCard from '@/components/StatsCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/lib/formatters';

defineProps<{
    member: { name: string; member_no: string; organization?: { name: string } | null };
    summary: { savings_balance: number; pending_invoices: number; active_loans: number; loan_outstanding: number; points_balance: number; member_tier: string; unread_notifications: number };
    recentTransactions: Array<{ id: number; transaction_no: string; total_amount: number | string; sold_at: string }>;
    recentLoans: Array<{ id: number; status: string; outstanding_amount: number | string; loan_type?: { name: string } | null }>;
}>();
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
