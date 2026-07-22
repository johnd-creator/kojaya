<script setup lang="ts">
import { Deferred, Head, Link, router } from "@inertiajs/vue3";
import { ArrowLeft, Download, FileSpreadsheet, FileText, TrendingDown } from "lucide-vue-next";
import { computed, ref } from "vue";
import EmptyState from "@/components/EmptyState.vue";
import JobProgressTracker from "@/components/JobProgressTracker.vue";
import PageContainer from "@/components/PageContainer.vue";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { useBackgroundJob } from "@/composables/useBackgroundJob";

interface AnalyticsData {
    summary: {
        transactions: number;
        voided_transactions: number;
        gross_sales: number;
        total_discount: number;
        gross_profit: number;
        voided_amount: number;
        returns: { count: number; total: number };
        net_sales: number;
        member_transactions: number;
    };
    payment_reconciliation: Array<{ method: string; count: number; total: number }>;
    daily_trend: Array<{ date: string; transactions: number; revenue: number }>;
    top_products: Array<{ product_name: string; quantity: number; revenue: number; gross_profit: number; margin_percent: number }>;
    top_members: Array<{ member_name: string; transactions: number; total: number }>;
    cashier_performance: Array<{ cashier_name: string; transactions: number; total: number }>;
}

interface FilterValues {
    pos_product_id: string;
    category_id: string;
    cashier_id: string;
    payment_method: string;
}

const props = defineProps<{
    from: string;
    to: string;
    analytics: AnalyticsData;
    filters: FilterValues;
    products: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
    cashiers: Array<{ id: number; name: string }>;
}>();

function cleanFilters(filters: Record<string, string>): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(([, v]) => v !== "" && v !== undefined),
    );
}

const f = ref<FilterValues>({
    pos_product_id: props.filters.pos_product_id ?? "",
    category_id: props.filters.category_id ?? "",
    cashier_id: props.filters.cashier_id ?? "",
    payment_method: props.filters.payment_method ?? "",
});

const dateFilter = ref({
    from: props.from,
    to: props.to,
});

function applyFilters(): void {
    router.get(
        "/cooperative/pos/reports",
        { ...cleanFilters(f.value), ...dateFilter.value },
        { preserveState: true },
    );
}

const exportQuery = computed(() => {
    const params = new URLSearchParams();
    if (dateFilter.value.from) {
        params.set("from", dateFilter.value.from);
    }
    if (dateFilter.value.to) {
        params.set("to", dateFilter.value.to);
    }
    for (const [key, value] of Object.entries(cleanFilters(f.value))) {
        params.set(key, value);
    }
    return params.toString();
});

const csvHref = computed(() => `/cooperative/pos/reports/export.csv${exportQuery.value ? `?${exportQuery.value}` : ""}`);

const pdfJob = useBackgroundJob();

function buildPdfPayload(): Record<string, unknown> {
    const payload: Record<string, unknown> = {
        from: dateFilter.value.from,
        to: dateFilter.value.to,
    };
    const cleaned = cleanFilters(f.value);
    if (Object.keys(cleaned).length > 0) {
        payload.filters = cleaned;
    }
    return payload;
}

function generatePdf(): void {
    void pdfJob.enqueue(buildPdfPayload());
}

function retryPdf(): void {
    void pdfJob.enqueue(buildPdfPayload());
}

function dismissPdf(): void {
    pdfJob.reset();
}

const maxRevenue = computed(() => Math.max(1, ...(props.analytics?.daily_trend ?? []).map((d) => d.revenue)));
</script>

<template>
    <Head title="Laporan POS" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '/cooperative' },
            { title: 'POS', href: '/cooperative/pos' },
            { title: 'Laporan', href: '/cooperative/pos/reports' },
        ]"
    >
        <PageContainer>
            <div class="flex flex-col gap-6">
                <header class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Link href="/cooperative/pos" prefetch>
                            <Button variant="ghost" size="icon" class="rounded-full">
                                <ArrowLeft class="h-5 w-5" />
                            </Button>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">Laporan POS</h1>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Ringkasan penjualan, pembayaran, dan tren.</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a :href="csvHref">
                            <Button variant="outline" size="sm" class="rounded-xl">
                                <FileSpreadsheet class="mr-2 h-4 w-4" /> CSV
                            </Button>
                        </a>
                        <Button
                            variant="outline"
                            size="sm"
                            class="rounded-xl"
                            :disabled="pdfJob.submitting.value"
                            @click="generatePdf"
                        >
                            <FileText class="mr-2 h-4 w-4" /> Buat PDF
                        </Button>
                    </div>
                </header>

                <JobProgressTracker
                    v-if="pdfJob.hasJobStarted.value"
                    :state="pdfJob.state.value"
                    :submitting="pdfJob.submitting.value"
                    :error="pdfJob.error.value"
                    label="Laporan POS · PDF"
                    @retry="retryPdf"
                    @reset="dismissPdf"
                />

                <div class="grid gap-4 rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80 md:grid-cols-6">
                    <div>
                        <Label class="text-xs text-zinc-600 dark:text-zinc-300">Dari</Label>
                        <Input v-model="dateFilter.from" type="date" class="mt-1 rounded-xl" />
                    </div>
                    <div>
                        <Label class="text-xs text-zinc-600 dark:text-zinc-300">Sampai</Label>
                        <Input v-model="dateFilter.to" type="date" class="mt-1 rounded-xl" />
                    </div>
                    <div>
                        <Label class="text-xs text-zinc-600 dark:text-zinc-300">Produk</Label>
                        <select
                            v-model="f.pos_product_id"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                        >
                            <option value="">Semua</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <Label class="text-xs text-zinc-600 dark:text-zinc-300">Kategori</Label>
                        <select
                            v-model="f.category_id"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                        >
                            <option value="">Semua</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <Label class="text-xs text-zinc-600 dark:text-zinc-300">Kasir</Label>
                        <select
                            v-model="f.cashier_id"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                        >
                            <option value="">Semua</option>
                            <option v-for="c in cashiers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <Label class="text-xs text-zinc-600 dark:text-zinc-300">Metode</Label>
                        <select
                            v-model="f.payment_method"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                        >
                            <option value="">Semua</option>
                            <option value="CASH">Tunai</option>
                            <option value="TRANSFER">Transfer</option>
                            <option value="QRIS">QRIS</option>
                            <option value="MEMBER_CREDIT">Kredit Anggota</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <Button class="w-full rounded-xl" @click="applyFilters">
                            <Download class="mr-2 h-4 w-4" /> Terapkan
                        </Button>
                    </div>
                </div>

                <Deferred data="analytics">
                  <template #fallback>
                    <div aria-live="polite" class="sr-only">Memuat laporan POS.</div>
                    <div class="grid gap-4 md:grid-cols-4">
                      <Skeleton v-for="i in 4" :key="i" class="h-[88px] rounded-2xl" />
                    </div>
                    <div class="grid gap-6 md:grid-cols-2">
                      <Skeleton class="h-64 rounded-2xl" />
                      <Skeleton class="h-64 rounded-2xl" />
                    </div>
                    <Skeleton class="h-80 rounded-2xl" />
                    <div class="grid gap-6 md:grid-cols-2">
                      <Skeleton class="h-48 rounded-2xl" />
                      <Skeleton class="h-48 rounded-2xl" />
                    </div>
                  </template>

                <div class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Penjualan Kotor</p>
                        <p class="mt-1 text-2xl font-extrabold text-zinc-900 dark:text-zinc-100">{{ formatCurrency(analytics.summary.gross_sales) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Laba Kotor</p>
                        <p class="mt-1 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(analytics.summary.gross_profit) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Penjualan Bersih</p>
                        <p class="mt-1 text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ formatCurrency(analytics.summary.net_sales) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Transaksi</p>
                        <p class="mt-1 text-2xl font-extrabold text-zinc-900 dark:text-zinc-100">{{ analytics.summary.transactions }}</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ analytics.summary.voided_transactions }} void · {{ analytics.summary.returns.count }} retur</p>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Tren Harian</h2>
                        <div v-if="analytics.daily_trend.length === 0" class="mt-4">
                            <EmptyState
                                description="Belum ada transaksi POS pada periode ini."
                                :icon="TrendingDown"
                            />
                        </div>
                        <div v-else class="mt-4 space-y-2">
                            <div v-for="d in analytics.daily_trend" :key="d.date" class="flex items-center gap-3">
                                <span class="w-24 text-xs text-zinc-500 dark:text-zinc-400">{{ d.date }}</span>
                                <div class="h-3 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <div class="h-full bg-blue-500 dark:bg-blue-400" :style="{ width: ((d.revenue / maxRevenue) * 100) + '%' }"></div>
                                </div>
                                <span class="w-32 text-right text-xs font-bold text-zinc-700 dark:text-zinc-200">{{ formatCurrency(d.revenue) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Rekonsiliasi Pembayaran</h2>
                        <table class="mt-4 w-full text-left text-sm text-zinc-700 dark:text-zinc-200">
                            <thead class="border-b border-zinc-100 text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                                <tr>
                                    <th class="py-2">Metode</th>
                                    <th class="py-2 text-right">Jumlah</th>
                                    <th class="py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in analytics.payment_reconciliation" :key="r.method" class="border-b border-zinc-50 dark:border-zinc-800">
                                    <td class="py-2 font-medium">{{ r.method }}</td>
                                    <td class="py-2 text-right">{{ r.count }}</td>
                                    <td class="py-2 text-right font-bold">{{ formatCurrency(r.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
                    <h2 class="mb-4 text-lg font-bold text-zinc-900 dark:text-zinc-100">Top 20 Produk</h2>
                    <table class="w-full text-left text-sm text-zinc-700 dark:text-zinc-200">
                        <thead class="border-b border-zinc-100 text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th class="py-2">Produk</th>
                                <th class="py-2 text-right">Qty</th>
                                <th class="py-2 text-right">Pendapatan</th>
                                <th class="py-2 text-right">Laba</th>
                                <th class="py-2 text-right">Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(p, idx) in analytics.top_products" :key="idx" class="border-b border-zinc-50 transition-colors hover:bg-zinc-50/60 dark:border-zinc-800 dark:hover:bg-zinc-900/40">
                                <td class="py-2.5 font-medium">{{ p.product_name }}</td>
                                <td class="py-2.5 text-right tabular-nums">{{ p.quantity }}</td>
                                <td class="py-2.5 text-right tabular-nums">{{ formatCurrency(p.revenue) }}</td>
                                <td class="py-2.5 text-right tabular-nums text-emerald-600 dark:text-emerald-400">{{ formatCurrency(p.gross_profit) }}</td>
                                <td class="py-2.5 text-right tabular-nums">{{ p.margin_percent }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
                        <h2 class="mb-3 text-lg font-bold text-zinc-900 dark:text-zinc-100">Top Anggota</h2>
                        <ul class="divide-y divide-zinc-50 dark:divide-zinc-800">
                            <li v-for="m in analytics.top_members" :key="m.member_name" class="flex items-center justify-between py-2 text-sm text-zinc-700 dark:text-zinc-200">
                                <span class="font-medium">{{ m.member_name }}</span>
                                <span class="text-right text-xs text-zinc-500 dark:text-zinc-400">{{ m.transactions }} trx · {{ formatCurrency(m.total) }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
                        <h2 class="mb-3 text-lg font-bold text-zinc-900 dark:text-zinc-100">Performa Kasir</h2>
                        <ul class="divide-y divide-zinc-50 dark:divide-zinc-800">
                            <li v-for="c in analytics.cashier_performance" :key="c.cashier_name" class="flex items-center justify-between py-2 text-sm text-zinc-700 dark:text-zinc-200">
                                <span class="font-medium">{{ c.cashier_name }}</span>
                                <span class="text-right text-xs text-zinc-500 dark:text-zinc-400">{{ c.transactions }} trx · {{ formatCurrency(c.total) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
                </Deferred>
            </div>
        </PageContainer>
    </AppLayout>
</template>
