<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { ArrowLeft, Download, FileSpreadsheet, FileText, TrendingUp } from "lucide-vue-next";
import { computed, ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";

const props = defineProps<{
    from: string;
    to: string;
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
    products: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
    cashiers: Array<{ id: number; name: string }>;
}>();

const f = ref({
    from: props.from,
    to: props.to,
    pos_product_id: "",
    category_id: "",
    cashier_id: "",
    payment_method: "",
});

function applyFilters(): void {
    router.get("/cooperative/pos/reports", f.value, { preserveState: true });
}

const exportQuery = computed(() => {
    const params = new URLSearchParams();
    if (f.value.from) {
        params.set("from", f.value.from);
    }
    if (f.value.to) {
        params.set("to", f.value.to);
    }
    if (f.value.pos_product_id) {
        params.set("pos_product_id", f.value.pos_product_id);
    }
    if (f.value.category_id) {
        params.set("category_id", f.value.category_id);
    }
    if (f.value.cashier_id) {
        params.set("cashier_id", f.value.cashier_id);
    }
    if (f.value.payment_method) {
        params.set("payment_method", f.value.payment_method);
    }
    return params.toString();
});

const csvHref = computed(() => `/cooperative/pos/reports/export.csv${exportQuery.value ? `?${exportQuery.value}` : ""}`);
const pdfHref = computed(() => `/cooperative/pos/reports/export.pdf${exportQuery.value ? `?${exportQuery.value}` : ""}`);

const maxRevenue = computed(() => Math.max(1, ...props.daily_trend.map((d) => d.revenue)));
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
                        <Link href="/cooperative/pos">
                            <Button variant="ghost" size="icon" class="rounded-full">
                                <ArrowLeft class="h-5 w-5" />
                            </Button>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-extrabold text-zinc-900 tracking-tight">Laporan POS</h1>
                            <p class="text-sm text-zinc-500">Ringkasan penjualan, pembayaran, dan tren.</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a :href="csvHref">
                            <Button variant="outline" size="sm" class="rounded-xl">
                                <FileSpreadsheet class="mr-2 h-4 w-4" /> CSV
                            </Button>
                        </a>
                        <a :href="pdfHref">
                            <Button variant="outline" size="sm" class="rounded-xl">
                                <FileText class="mr-2 h-4 w-4" /> PDF
                            </Button>
                        </a>
                    </div>
                </header>

                <div class="grid gap-4 rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm md:grid-cols-5">
                    <div>
                        <Label class="text-xs">Dari</Label>
                        <Input v-model="f.from" type="date" class="mt-1 rounded-xl" />
                    </div>
                    <div>
                        <Label class="text-xs">Sampai</Label>
                        <Input v-model="f.to" type="date" class="mt-1 rounded-xl" />
                    </div>
                    <div>
                        <Label class="text-xs">Kasir</Label>
                        <select v-model="f.cashier_id" class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">
                            <option value="">Semua</option>
                            <option v-for="c in cashiers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <Label class="text-xs">Metode</Label>
                        <select v-model="f.payment_method" class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">
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

                <div class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Penjualan Kotor</p>
                        <p class="mt-1 text-2xl font-extrabold text-zinc-900">{{ formatCurrency(summary.gross_sales) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Laba Kotor</p>
                        <p class="mt-1 text-2xl font-extrabold text-emerald-600">{{ formatCurrency(summary.gross_profit) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Penjualan Bersih</p>
                        <p class="mt-1 text-2xl font-extrabold text-blue-600">{{ formatCurrency(summary.net_sales) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Transaksi</p>
                        <p class="mt-1 text-2xl font-extrabold text-zinc-900">{{ summary.transactions }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ summary.voided_transactions }} void · {{ summary.returns.count }} retur</p>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-bold text-zinc-900">Tren Harian</h2>
                        <div v-if="daily_trend.length === 0" class="mt-6 text-center text-sm text-zinc-500">Belum ada data</div>
                        <div v-else class="mt-4 space-y-2">
                            <div v-for="d in daily_trend" :key="d.date" class="flex items-center gap-3">
                                <span class="w-24 text-xs text-zinc-500">{{ d.date }}</span>
                                <div class="h-3 flex-1 overflow-hidden rounded-full bg-zinc-100">
                                    <div class="h-full bg-blue-500" :style="{ width: ((d.revenue / maxRevenue) * 100) + '%' }"></div>
                                </div>
                                <span class="w-32 text-right text-xs font-bold text-zinc-700">{{ formatCurrency(d.revenue) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-bold text-zinc-900">Rekonsiliasi Pembayaran</h2>
                        <table class="mt-4 w-full text-left text-sm">
                            <thead class="border-b border-zinc-100 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                                <tr>
                                    <th class="py-2">Metode</th>
                                    <th class="py-2 text-right">Jumlah</th>
                                    <th class="py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in payment_reconciliation" :key="r.method" class="border-b border-zinc-50">
                                    <td class="py-2 font-medium">{{ r.method }}</td>
                                    <td class="py-2 text-right">{{ r.count }}</td>
                                    <td class="py-2 text-right font-bold">{{ formatCurrency(r.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-lg font-bold text-zinc-900">Top 20 Produk</h2>
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-100 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                            <tr>
                                <th class="py-2">Produk</th>
                                <th class="py-2 text-right">Qty</th>
                                <th class="py-2 text-right">Pendapatan</th>
                                <th class="py-2 text-right">Laba</th>
                                <th class="py-2 text-right">Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(p, idx) in top_products" :key="idx" class="border-b border-zinc-50">
                                <td class="py-2 font-medium">{{ p.product_name }}</td>
                                <td class="py-2 text-right">{{ p.quantity }}</td>
                                <td class="py-2 text-right">{{ formatCurrency(p.revenue) }}</td>
                                <td class="py-2 text-right text-emerald-600">{{ formatCurrency(p.gross_profit) }}</td>
                                <td class="py-2 text-right">{{ p.margin_percent }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <h2 class="mb-3 text-lg font-bold text-zinc-900">Top Anggota</h2>
                        <ul class="divide-y divide-zinc-50">
                            <li v-for="m in top_members" :key="m.member_name" class="flex items-center justify-between py-2 text-sm">
                                <span class="font-medium">{{ m.member_name }}</span>
                                <span class="text-right text-xs text-zinc-500">{{ m.transactions }} trx · {{ formatCurrency(m.total) }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <h2 class="mb-3 text-lg font-bold text-zinc-900">Performa Kasir</h2>
                        <ul class="divide-y divide-zinc-50">
                            <li v-for="c in cashier_performance" :key="c.cashier_name" class="flex items-center justify-between py-2 text-sm">
                                <span class="font-medium">{{ c.cashier_name }}</span>
                                <span class="text-right text-xs text-zinc-500">{{ c.transactions }} trx · {{ formatCurrency(c.total) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
