<script setup lang="ts">
import { Deferred, Head, Link } from "@inertiajs/vue3";
import {
  AlertTriangle,
  ArrowRight,
  BadgeDollarSign,
  BarChart3,
  Boxes,
  CalendarClock,
  CheckCircle2,
  ClipboardList,
  CreditCard,
  PackageCheck,
  ReceiptText,
  ShieldCheck,
  ShoppingCart,
  Store,
  Users,
  WalletCards,
} from "lucide-vue-next";
import { computed } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import { dashboard as dashboardRoute } from "@/routes";
import { index as cooperativeDuesIndex } from "@/routes/cooperative/dues";
import { index as cooperativeLedgerIndex } from "@/routes/cooperative/ledger";
import { index as cooperativeMembersIndex } from "@/routes/cooperative/members";
import { index as cooperativePaymentsIndex } from "@/routes/cooperative/payments";
import { index as cooperativePosIndex } from "@/routes/cooperative/pos";
import { index as cooperativePosReportsIndex } from "@/routes/cooperative/pos/reports";
import { index as cooperativePosProductsIndex } from "@/routes/cooperative/pos-products";
import { index as cooperativeReportsIndex } from "@/routes/cooperative/reports";
import { index as cooperativeShuIndex } from "@/routes/cooperative/shu";
import type { BreadcrumbItem } from "@/types";

interface DashboardPayload {
  summary: {
    today_sales: number;
    today_transactions: number;
    pending_payments: number;
    low_stock_products: number;
    active_members: number;
    unpaid_dues_amount: number;
  };
  workQueue: {
    pending_members: number;
    pending_payments: number;
    unpaid_dues: number;
    low_stock_products: number;
  };
  collections: {
    period: string;
    total_due: number;
    paid: number;
    outstanding: number;
    collection_rate: number;
    pending_payment_amount: number;
    saving_balance: number;
    member_credit_balance: number;
  };
  pos: {
    today_sales: number;
    today_transactions: number;
    monthly_sales: number;
    monthly_transactions: number;
    annual_gross_profit: number;
    member_transactions: number;
    top_products: Array<{
      id: number;
      name: string;
      category?: string | null;
      quantity: number;
      revenue: number;
      gross_profit: number;
    }>;
  };
  inventory: {
    low_stock_count: number;
    critical_products: Array<{
      id: number;
      sku: string;
      name: string;
      category?: string | null;
      stock: number;
      minimum_stock: number;
    }>;
  };
  members: {
    active: number;
    pending: number;
    resigned: number;
    new_this_month: number;
  };
  shu: {
    year: number;
    annual_pos_profit: number;
    annual_pos_points: number;
    latest_closed_year?: number | null;
    latest_closed_total: number;
  };
  generatedAt: string;
}

const props = defineProps<{
  dashboard?: DashboardPayload;
}>();

const emptyDashboard: DashboardPayload = {
  summary: {
    today_sales: 0,
    today_transactions: 0,
    pending_payments: 0,
    low_stock_products: 0,
    active_members: 0,
    unpaid_dues_amount: 0,
  },
  workQueue: {
    pending_members: 0,
    pending_payments: 0,
    unpaid_dues: 0,
    low_stock_products: 0,
  },
  collections: {
    period: "",
    total_due: 0,
    paid: 0,
    outstanding: 0,
    collection_rate: 0,
    pending_payment_amount: 0,
    saving_balance: 0,
    member_credit_balance: 0,
  },
  pos: {
    today_sales: 0,
    today_transactions: 0,
    monthly_sales: 0,
    monthly_transactions: 0,
    annual_gross_profit: 0,
    member_transactions: 0,
    top_products: [],
  },
  inventory: {
    low_stock_count: 0,
    critical_products: [],
  },
  members: {
    active: 0,
    pending: 0,
    resigned: 0,
    new_this_month: 0,
  },
  shu: {
    year: new Date().getFullYear(),
    annual_pos_profit: 0,
    annual_pos_points: 0,
    latest_closed_year: null,
    latest_closed_total: 0,
  },
  generatedAt: new Date().toISOString(),
};

const dashboard = computed(() => props.dashboard ?? emptyDashboard);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: "Dashboard",
    href: dashboardRoute(),
  },
];

const formatNumber = (value: number): string =>
  new Intl.NumberFormat("id-ID").format(Number(value ?? 0));

const formatPercent = (value: number): string =>
  `${Number(value ?? 0).toFixed(1)}%`;

const formatDateTime = (value: string): string =>
  new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value));

const kpiCards = [
  {
    label: "Omzet POS Hari Ini",
    value: () => formatCurrency(dashboard.value.summary.today_sales),
    meta: () =>
      `${formatNumber(dashboard.value.summary.today_transactions)} transaksi`,
    href: cooperativePosIndex().url,
    icon: Store,
    tone: "emerald",
  },
  {
    label: "Pembayaran Pending",
    value: () => formatNumber(dashboard.value.summary.pending_payments),
    meta: () =>
      formatCurrency(dashboard.value.collections.pending_payment_amount),
    href: cooperativePaymentsIndex({ query: { status: "PENDING" } }).url,
    icon: CreditCard,
    tone: "amber",
  },
  {
    label: "Tagihan Belum Beres",
    value: () => formatCurrency(dashboard.value.summary.unpaid_dues_amount),
    meta: () =>
      `${formatNumber(dashboard.value.workQueue.unpaid_dues)} tagihan perlu follow-up`,
    href: cooperativeDuesIndex({ query: { status: "UNPAID" } }).url,
    icon: ReceiptText,
    tone: "rose",
  },
  {
    label: "Produk Stok Kritis",
    value: () => formatNumber(dashboard.value.summary.low_stock_products),
    meta: () => "Di bawah atau sama dengan stok minimum",
    href: cooperativePosProductsIndex({ query: { low_stock: 1 } }).url,
    icon: Boxes,
    tone: "sky",
  },
];

const workItems = [
  {
    label: "Verifikasi anggota baru",
    value: () => formatNumber(dashboard.value.workQueue.pending_members),
    description: "Calon anggota menunggu aktivasi status.",
    href: cooperativeMembersIndex({ query: { status: "PENDING" } }).url,
    icon: Users,
  },
  {
    label: "Approve pembayaran",
    value: () => formatNumber(dashboard.value.workQueue.pending_payments),
    description: "Pembayaran perlu diperiksa agar ledger segera akurat.",
    href: cooperativePaymentsIndex({ query: { status: "PENDING" } }).url,
    icon: ShieldCheck,
  },
  {
    label: "Tindak lanjut tagihan",
    value: () => formatNumber(dashboard.value.workQueue.unpaid_dues),
    description: "Tagihan unpaid atau partial yang perlu ditagih.",
    href: cooperativeDuesIndex({ query: { status: "UNPAID" } }).url,
    icon: ClipboardList,
  },
  {
    label: "Restock produk",
    value: () => formatNumber(dashboard.value.workQueue.low_stock_products),
    description: "Produk POS sudah mencapai stok minimum.",
    href: cooperativePosProductsIndex({ query: { low_stock: 1 } }).url,
    icon: PackageCheck,
  },
];

const collectionStats = [
  {
    label: "Tagihan periode ini",
    value: () => formatCurrency(dashboard.value.collections.total_due),
  },
  {
    label: "Sudah dibayar",
    value: () => formatCurrency(dashboard.value.collections.paid),
  },
  {
    label: "Outstanding",
    value: () => formatCurrency(dashboard.value.collections.outstanding),
  },
  {
    label: "Collection rate",
    value: () => formatPercent(dashboard.value.collections.collection_rate),
  },
];

const managementStats = [
  {
    label: "Saldo Simpanan",
    value: () => formatCurrency(dashboard.value.collections.saving_balance),
    href: cooperativeLedgerIndex().url,
  },
  {
    label: "Kredit Anggota",
    value: () =>
      formatCurrency(dashboard.value.collections.member_credit_balance),
    href: cooperativeLedgerIndex().url,
  },
  {
    label: "Profit POS Tahun Ini",
    value: () => formatCurrency(dashboard.value.shu.annual_pos_profit),
    href: cooperativePosReportsIndex().url,
  },
  {
    label: "Poin POS Tahun Ini",
    value: () => formatNumber(dashboard.value.shu.annual_pos_points),
    href: cooperativeShuIndex().url,
  },
];
</script>

<template>
  <Head title="Dashboard Operasional" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Deferred data="dashboard">
      <template #fallback>
        <PageContainer>
          <div aria-live="polite" class="sr-only">
            Memuat dashboard operasional.
          </div>
          <Skeleton class="h-24 rounded-lg" />
          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Skeleton v-for="index in 4" :key="index" class="h-36 rounded-lg" />
          </div>
          <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <Skeleton class="h-96 rounded-lg" />
            <Skeleton class="h-96 rounded-lg" />
          </div>
        </PageContainer>
      </template>

      <PageContainer>
        <div
          class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
          <div>
            <div
              class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300"
            >
              <CalendarClock class="size-4" />
              Operasional Harian
            </div>
            <h1
              class="mt-3 text-3xl font-bold tracking-tight text-zinc-950 dark:text-white"
            >
              Dashboard Koperasi
            </h1>
            <p class="mt-1 max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">
              Prioritas kerja hari ini, kas iuran, POS toko, stok, dan ringkasan
              keputusan manajemen.
            </p>
          </div>
          <div
            class="flex items-center gap-2 rounded-lg border bg-white px-3 py-2 text-sm text-zinc-500 shadow-sm dark:bg-zinc-900"
          >
            <CheckCircle2 class="size-4 text-emerald-600" />
            <span>Update {{ formatDateTime(dashboard.generatedAt) }}</span>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <Link
            prefetch
            v-for="card in kpiCards"
            :key="card.label"
            :href="card.href"
            class="group rounded-lg border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-zinc-900"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                  {{ card.label }}
                </p>
                <p
                  class="mt-2 text-2xl font-bold text-zinc-950 dark:text-white"
                >
                  {{ card.value() }}
                </p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                  {{ card.meta() }}
                </p>
              </div>
              <div
                class="rounded-lg p-3"
                :class="{
                  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300':
                    card.tone === 'emerald',
                  'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300':
                    card.tone === 'amber',
                  'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300':
                    card.tone === 'rose',
                  'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300':
                    card.tone === 'sky',
                }"
              >
                <component :is="card.icon" class="size-6" />
              </div>
            </div>
            <div
              class="mt-4 flex items-center gap-1 text-sm font-semibold text-emerald-700 opacity-0 transition group-hover:opacity-100 dark:text-emerald-300"
            >
              Buka modul
              <ArrowRight class="size-4" />
            </div>
          </Link>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
          <section
            class="rounded-lg border bg-white shadow-sm dark:bg-zinc-900"
          >
            <div class="border-b p-5 dark:border-zinc-800">
              <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">
                Prioritas Hari Ini
              </h2>
              <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Antrian kerja yang paling memengaruhi operasional koperasi.
              </p>
            </div>
            <div class="divide-y dark:divide-zinc-800">
              <Link
                prefetch
                v-for="item in workItems"
                :key="item.label"
                :href="item.href"
                class="flex items-center gap-4 p-5 transition hover:bg-zinc-50 dark:hover:bg-zinc-800/60"
              >
                <div
                  class="rounded-lg bg-zinc-100 p-3 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                >
                  <component :is="item.icon" class="size-5" />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center justify-between gap-3">
                    <p class="font-semibold text-zinc-950 dark:text-white">
                      {{ item.label }}
                    </p>
                    <span
                      class="text-xl font-bold text-zinc-950 dark:text-white"
                      >{{ item.value() }}</span
                    >
                  </div>
                  <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ item.description }}
                  </p>
                </div>
                <ArrowRight class="size-4 text-zinc-400" />
              </Link>
            </div>
          </section>

          <section
            class="rounded-lg border bg-white p-5 shadow-sm dark:bg-zinc-900"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">
                  Kas & Iuran
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                  Periode {{ dashboard.collections.period }}
                </p>
              </div>
              <WalletCards class="size-6 text-emerald-600" />
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
              <div
                v-for="stat in collectionStats"
                :key="stat.label"
                class="rounded-lg border bg-zinc-50 p-4 dark:bg-zinc-950/50"
              >
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                  {{ stat.label }}
                </p>
                <p class="mt-2 text-xl font-bold text-zinc-950 dark:text-white">
                  {{ stat.value() }}
                </p>
              </div>
            </div>

            <div class="mt-5">
              <div class="flex items-center justify-between text-sm">
                <span class="text-zinc-500 dark:text-zinc-400"
                  >Progress koleksi</span
                >
                <span class="font-semibold">{{
                  formatPercent(dashboard.collections.collection_rate)
                }}</span>
              </div>
              <div class="mt-2 h-3 rounded-full bg-zinc-100 dark:bg-zinc-800">
                <div
                  class="h-3 rounded-full bg-emerald-600"
                  :style="{
                    width: `${Math.min(dashboard.collections.collection_rate, 100)}%`,
                  }"
                />
              </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
              <Link
                :href="cooperativePaymentsIndex().url"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
              >
                Input pembayaran
                <ArrowRight class="size-4" />
              </Link>
              <Link
                :href="cooperativeDuesIndex().url"
                class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800"
              >
                Kelola tagihan
              </Link>
            </div>
          </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_0.95fr]">
          <section
            class="rounded-lg border bg-white shadow-sm dark:bg-zinc-900"
          >
            <div
              class="flex items-start justify-between gap-4 border-b p-5 dark:border-zinc-800"
            >
              <div>
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">
                  POS Toko
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                  Performa transaksi dan produk teratas tahun berjalan.
                </p>
              </div>
              <ShoppingCart class="size-6 text-emerald-600" />
            </div>
            <div class="grid gap-3 p-5 sm:grid-cols-3">
              <div class="rounded-lg border p-4 dark:border-zinc-800">
                <p class="text-sm text-zinc-500">Omzet bulan ini</p>
                <p class="mt-2 text-xl font-bold">
                  {{ formatCurrency(dashboard.pos.monthly_sales) }}
                </p>
              </div>
              <div class="rounded-lg border p-4 dark:border-zinc-800">
                <p class="text-sm text-zinc-500">Transaksi bulan ini</p>
                <p class="mt-2 text-xl font-bold">
                  {{ formatNumber(dashboard.pos.monthly_transactions) }}
                </p>
              </div>
              <div class="rounded-lg border p-4 dark:border-zinc-800">
                <p class="text-sm text-zinc-500">Transaksi anggota</p>
                <p class="mt-2 text-xl font-bold">
                  {{ formatNumber(dashboard.pos.member_transactions) }}
                </p>
              </div>
            </div>
            <div class="px-5 pb-5">
              <div
                class="overflow-hidden rounded-lg border dark:border-zinc-800"
              >
                <table
                  aria-label="Produk teratas POS"
                  class="w-full text-sm"
                  role="table"
                >
                  <thead
                    class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-zinc-800"
                  >
                    <tr>
                      <th class="px-4 py-3">Produk</th>
                      <th class="px-4 py-3 text-right">Qty</th>
                      <th class="px-4 py-3 text-right">Omzet</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y dark:divide-zinc-800">
                    <tr v-if="dashboard.pos.top_products.length === 0">
                      <td
                        colspan="3"
                        class="px-4 py-6 text-center text-zinc-500"
                      >
                        Belum ada penjualan produk tahun ini.
                      </td>
                    </tr>
                    <tr
                      v-for="product in dashboard.pos.top_products"
                      :key="product.id"
                    >
                      <td class="px-4 py-3">
                        <p class="font-medium text-zinc-950 dark:text-white">
                          {{ product.name }}
                        </p>
                        <p class="text-xs text-zinc-500">
                          {{ product.category ?? "Tanpa kategori" }}
                        </p>
                      </td>
                      <td class="px-4 py-3 text-right">
                        {{ formatNumber(product.quantity) }}
                      </td>
                      <td class="px-4 py-3 text-right font-semibold">
                        {{ formatCurrency(product.revenue) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="mt-4 flex flex-wrap gap-3">
                <Link
                  :href="cooperativePosIndex().url"
                  class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                >
                  Buka kasir
                  <ArrowRight class="size-4" />
                </Link>
                <Link
                  :href="cooperativePosReportsIndex().url"
                  class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800"
                >
                  Laporan POS
                </Link>
              </div>
            </div>
          </section>

          <section
            class="rounded-lg border bg-white shadow-sm dark:bg-zinc-900"
          >
            <div
              class="flex items-start justify-between gap-4 border-b p-5 dark:border-zinc-800"
            >
              <div>
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">
                  Stok Perlu Tindakan
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                  {{ formatNumber(dashboard.inventory.low_stock_count) }} produk
                  mencapai minimum.
                </p>
              </div>
              <AlertTriangle class="size-6 text-amber-600" />
            </div>
            <div class="divide-y dark:divide-zinc-800">
              <div
                v-if="dashboard.inventory.critical_products.length === 0"
                class="p-6 text-center text-sm text-zinc-500"
              >
                Semua produk aktif berada di atas stok minimum.
              </div>
              <div
                v-for="product in dashboard.inventory.critical_products"
                :key="product.id"
                class="flex items-center justify-between gap-4 p-4"
              >
                <div class="min-w-0">
                  <p class="font-semibold text-zinc-950 dark:text-white">
                    {{ product.name }}
                  </p>
                  <p class="text-sm text-zinc-500">
                    {{ product.sku }} ·
                    {{ product.category ?? "Tanpa kategori" }}
                  </p>
                </div>
                <div class="text-right">
                  <p
                    class="text-lg font-bold text-amber-700 dark:text-amber-300"
                  >
                    {{ formatNumber(product.stock) }}
                  </p>
                  <p class="text-xs text-zinc-500">
                    Min {{ formatNumber(product.minimum_stock) }}
                  </p>
                </div>
              </div>
            </div>
            <div class="border-t p-5 dark:border-zinc-800">
              <Link
                :href="
                  cooperativePosProductsIndex({ query: { low_stock: 1 } }).url
                "
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800"
              >
                Kelola stok minimum
                <ArrowRight class="size-4" />
              </Link>
            </div>
          </section>
        </div>

        <section
          class="rounded-lg border bg-white p-5 shadow-sm dark:bg-zinc-900"
        >
          <div
            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">
                Ringkasan Manajemen
              </h2>
              <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Sinyal cepat untuk rapat harian dan keputusan pengurus.
              </p>
            </div>
            <Link
              :href="cooperativeReportsIndex().url"
              class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-300"
            >
              Laporan koperasi
              <ArrowRight class="size-4" />
            </Link>
          </div>

          <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Link
              v-for="stat in managementStats"
              :key="stat.label"
              :href="stat.href"
              class="rounded-lg border bg-zinc-50 p-4 transition hover:bg-zinc-100 dark:bg-zinc-950/50 dark:hover:bg-zinc-800"
            >
              <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ stat.label }}
              </p>
              <p class="mt-2 text-xl font-bold text-zinc-950 dark:text-white">
                {{ stat.value() }}
              </p>
            </Link>
          </div>

          <div class="mt-5 grid gap-4 md:grid-cols-4">
            <div class="rounded-lg border p-4 dark:border-zinc-800">
              <div class="flex items-center gap-2 text-sm text-zinc-500">
                <Users class="size-4" />
                Anggota aktif
              </div>
              <p class="mt-2 text-xl font-bold">
                {{ formatNumber(dashboard.members.active) }}
              </p>
            </div>
            <div class="rounded-lg border p-4 dark:border-zinc-800">
              <div class="flex items-center gap-2 text-sm text-zinc-500">
                <BadgeDollarSign class="size-4" />
                Anggota baru bulan ini
              </div>
              <p class="mt-2 text-xl font-bold">
                {{ formatNumber(dashboard.members.new_this_month) }}
              </p>
            </div>
            <div class="rounded-lg border p-4 dark:border-zinc-800">
              <div class="flex items-center gap-2 text-sm text-zinc-500">
                <BarChart3 class="size-4" />
                SHU terakhir
              </div>
              <p class="mt-2 text-xl font-bold">
                {{ dashboard.shu.latest_closed_year ?? "-" }}
              </p>
            </div>
            <div class="rounded-lg border p-4 dark:border-zinc-800">
              <div class="flex items-center gap-2 text-sm text-zinc-500">
                <WalletCards class="size-4" />
                Pool SHU terakhir
              </div>
              <p class="mt-2 text-xl font-bold">
                {{ formatCurrency(dashboard.shu.latest_closed_total) }}
              </p>
            </div>
          </div>
        </section>
      </PageContainer>
    </Deferred>
  </AppLayout>
</template>
