<script setup lang="ts">
import { Deferred, Head, Link } from "@inertiajs/vue3";
import {
  AlertTriangle,
  ArrowRight,
  Award,
  BadgeDollarSign,
  BarChart3,
  Boxes,
  Calendar,
  CalendarClock,
  CheckCircle2,
  ClipboardList,
  Clock,
  CreditCard,
  Download,
  HandCoins,
  PackageCheck,
  PiggyBank,
  ReceiptText,
  ShieldCheck,
  ShoppingCart,
  Sparkles,
  Store,
  TrendingUp,
  Trophy,
  UserPlus,
  Users,
  Wallet,
  WalletCards,
} from "lucide-vue-next";
import { computed, h } from "vue";
import type { Component } from "vue";
import CollectionDonut from "@/components/dashboard/CollectionDonut.vue";
import GradientKpiCard from "@/components/dashboard/GradientKpiCard.vue";
import ProgressBar from "@/components/dashboard/ProgressBar.vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import StatusPill from "@/components/dashboard/StatusPill.vue";
import TopProductsBar from "@/components/dashboard/TopProductsBar.vue";
import EmptyState from "@/components/EmptyState.vue";
import PageContainer from "@/components/PageContainer.vue";
import {
  Card,
  CardContent,
} from "@/components/ui/card";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatNumber } from "@/lib/formatters";
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

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc";

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

const formatPercent = (value: number): string =>
  `${Number(value ?? 0).toFixed(1)}%`;

const formatDateTime = (value: string): string =>
  new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value));

const formatLongDate = (value: string): string =>
  new Intl.DateTimeFormat("id-ID", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  }).format(new Date(value));

// Deterministic synthetic sparkline from a scalar value.
// Decorative only — real trend is communicated via TrendBadge label.
function sparklineFor(value: number, points = 8): number[] {
  const safeValue = Math.max(0, Number(value) || 0);
  const base = Math.min(1, Math.log10(safeValue + 1) / 7.5);
  const seed = Math.abs(Math.sin(safeValue * 12.9898) * 43758.5453);
  return Array.from({ length: points }, (_, i) => {
    const t = i / (points - 1);
    const noise = (Math.sin((seed + i) * 1.7) + 1) / 2;
    return Math.max(0.05, base * (0.35 + t * 0.85) + noise * 0.12);
  });
}

// A 3-point short trendline for the POS stat tiles.
function miniSpark(value: number): number[] {
  const safe = Math.max(0, Number(value) || 0);
  const low = safe * 0.7;
  const mid = safe * 0.88;
  return [low, mid, safe];
}

interface KpiCard {
  label: string;
  value: () => string;
  meta: () => string;
  href: string;
  icon: Component;
  tone: Tone;
  sparkline: () => number[];
  trend: () => number | null;
  trendLabel: string;
}

const kpiCards = computed<KpiCard[]>(() => [
  {
    label: "Omzet POS Hari Ini",
    value: () => formatCurrency(dashboard.value.summary.today_sales),
    meta: () =>
      `${formatNumber(dashboard.value.summary.today_transactions)} transaksi hari ini`,
    href: cooperativePosIndex().url,
    icon: Store,
    tone: "emerald",
    sparkline: () => sparklineFor(dashboard.value.summary.today_sales),
    trend: () =>
      dashboard.value.pos.monthly_sales > 0
        ? ((dashboard.value.summary.today_sales * 30 -
            dashboard.value.pos.monthly_sales) /
            Math.max(1, dashboard.value.pos.monthly_sales)) *
          100
        : null,
    trendLabel: "vs bulan lalu",
  },
  {
    label: "Pembayaran Pending",
    value: () => formatNumber(dashboard.value.summary.pending_payments),
    meta: () =>
      `${formatCurrency(dashboard.value.collections.pending_payment_amount)} nilai tertunda`,
    href: cooperativePaymentsIndex({ query: { status: "PENDING" } }).url,
    icon: CreditCard,
    tone: "amber",
    sparkline: () => sparklineFor(dashboard.value.summary.pending_payments),
    trend: () => null,
    trendLabel: "butuh review",
  },
  {
    label: "Tunggakan Iuran Semua Periode",
    value: () => formatCurrency(dashboard.value.summary.unpaid_dues_amount),
    meta: () =>
      `${formatNumber(dashboard.value.workQueue.unpaid_dues)} tagihan perlu follow-up`,
    href: cooperativeDuesIndex({
      query: { period_scope: "all", status: "OPEN" },
    }).url,
    icon: ReceiptText,
    tone: "rose",
    sparkline: () => sparklineFor(dashboard.value.workQueue.unpaid_dues),
    trend: () => null,
    trendLabel: "perlu ditagih",
  },
  {
    label: "Produk Stok Kritis",
    value: () => formatNumber(dashboard.value.summary.low_stock_products),
    meta: () => "Di bawah atau sama dengan stok minimum",
    href: cooperativePosProductsIndex({ query: { low_stock: 1 } }).url,
    icon: Boxes,
    tone: "sky",
    sparkline: () => sparklineFor(dashboard.value.summary.low_stock_products),
    trend: () => null,
    trendLabel: "perlu restock",
  },
]);

interface WorkItem {
  label: string;
  description: string;
  href: string;
  icon: Component;
  tone: () => Tone;
  pill: () => { tone: Tone; text: string };
}

const workItems = computed<WorkItem[]>(() => [
  {
    label: "Verifikasi anggota baru",
    description: "Calon anggota menunggu aktivasi status.",
    href: cooperativeMembersIndex({ query: { status: "PENDING" } }).url,
    icon: UserPlus,
    tone: () =>
      dashboard.value.workQueue.pending_members > 0 ? "sky" : "emerald",
    pill: () => ({
      tone:
        dashboard.value.workQueue.pending_members > 0 ? "sky" : "emerald",
      text:
        dashboard.value.workQueue.pending_members > 0
          ? "Menunggu"
          : "Terkendali",
    }),
  },
  {
    label: "Approve pembayaran",
    description: "Pembayaran perlu diperiksa agar ledger segera akurat.",
    href: cooperativePaymentsIndex({ query: { status: "PENDING" } }).url,
    icon: ShieldCheck,
    tone: () =>
      dashboard.value.workQueue.pending_payments > 0 ? "amber" : "emerald",
    pill: () => ({
      tone:
        dashboard.value.workQueue.pending_payments > 0 ? "amber" : "emerald",
      text:
        dashboard.value.workQueue.pending_payments > 0
          ? "Prioritas"
          : "Aman",
    }),
  },
  {
    label: "Tindak lanjut tagihan",
    description: "Tagihan unpaid atau partial lintas periode yang perlu ditagih.",
    href: cooperativeDuesIndex({
      query: { period_scope: "all", status: "OPEN" },
    }).url,
    icon: ClipboardList,
    tone: () =>
      dashboard.value.workQueue.unpaid_dues > 0 ? "rose" : "emerald",
    pill: () => ({
      tone: dashboard.value.workQueue.unpaid_dues > 0 ? "rose" : "emerald",
      text:
        dashboard.value.workQueue.unpaid_dues > 0
          ? "Tinggi"
          : "Terkendali",
    }),
  },
  {
    label: "Restock produk",
    description: "Produk POS sudah mencapai stok minimum.",
    href: cooperativePosProductsIndex({ query: { low_stock: 1 } }).url,
    icon: PackageCheck,
    tone: () =>
      dashboard.value.workQueue.low_stock_products > 0 ? "amber" : "emerald",
    pill: () => ({
      tone:
        dashboard.value.workQueue.low_stock_products > 0 ? "amber" : "emerald",
      text:
        dashboard.value.workQueue.low_stock_products > 0
          ? "Restock"
          : "Aman",
    }),
  },
]);

const collectionStats = [
  {
    label: "Tagihan periode ini",
    value: () => formatCurrency(dashboard.value.collections.total_due),
    icon: ReceiptText,
  },
  {
    label: "Sudah dibayar",
    value: () => formatCurrency(dashboard.value.collections.paid),
    icon: CheckCircle2,
  },
  {
    label: "Outstanding",
    value: () => formatCurrency(dashboard.value.collections.outstanding),
    icon: AlertTriangle,
  },
];

const collectionTone = computed<Tone>(() => {
  const rate = dashboard.value.collections.collection_rate;
  if (rate >= 80) {
    return "emerald";
  }
  if (rate >= 50) {
    return "amber";
  }
  return "rose";
});

const inventoryTone = computed<Tone>(() =>
  dashboard.value.inventory.low_stock_count > 0 ? "amber" : "emerald",
);

interface ManagementStat {
  label: string;
  value: () => string;
  href: string;
  icon: Component;
  tone: Tone;
  description: string;
}

const managementStats: ManagementStat[] = [
  {
    label: "Saldo Simpanan",
    value: () => formatCurrency(dashboard.value.collections.saving_balance),
    href: cooperativeLedgerIndex().url,
    icon: PiggyBank,
    tone: "emerald",
    description: "Akumulasi simpanan seluruh anggota",
  },
  {
    label: "Kredit Anggota",
    value: () =>
      formatCurrency(dashboard.value.collections.member_credit_balance),
    href: cooperativeLedgerIndex().url,
    icon: HandCoins,
    tone: "sky",
    description: "Sisa piutang anggota berjalan",
  },
  {
    label: "Profit POS Tahun Ini",
    value: () => formatCurrency(dashboard.value.shu.annual_pos_profit),
    href: cooperativePosReportsIndex().url,
    icon: TrendingUp,
    tone: "violet",
    description: "Laba kotor POS berjalan",
  },
  {
    label: "Poin POS Tahun Ini",
    value: () => formatNumber(dashboard.value.shu.annual_pos_points),
    href: cooperativeShuIndex().url,
    icon: Award,
    tone: "amber",
    description: "Total poin transaksi anggota",
  },
];

const memberPulse = [
  {
    label: "Anggota aktif",
    value: () => formatNumber(dashboard.value.members.active),
    icon: Users,
    tone: "emerald" as Tone,
  },
  {
    label: "Anggota baru bulan ini",
    value: () => formatNumber(dashboard.value.members.new_this_month),
    icon: UserPlus,
    tone: "sky" as Tone,
  },
  {
    label: "SHU terakhir",
    value: () =>
      dashboard.value.shu.latest_closed_year !== null &&
      dashboard.value.shu.latest_closed_year !== undefined
        ? String(dashboard.value.shu.latest_closed_year)
        : "-",
    icon: Trophy,
    tone: "violet" as Tone,
  },
  {
    label: "Pool SHU terakhir",
    value: () => formatCurrency(dashboard.value.shu.latest_closed_total),
    icon: Wallet,
    tone: "amber" as Tone,
  },
];

const toneBgClass: Record<Tone, string> = {
  emerald:
    "bg-emerald-100 text-emerald-700 ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60",
  amber:
    "bg-amber-100 text-amber-700 ring-amber-200/70 dark:bg-amber-900/40 dark:text-amber-300 dark:ring-amber-900/60",
  rose: "bg-rose-100 text-rose-700 ring-rose-200/70 dark:bg-rose-900/40 dark:text-rose-300 dark:ring-rose-900/60",
  sky: "bg-sky-100 text-sky-700 ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60",
  violet:
    "bg-violet-100 text-violet-700 ring-violet-200/70 dark:bg-violet-900/40 dark:text-violet-300 dark:ring-violet-900/60",
  zinc: "bg-zinc-100 text-zinc-700 ring-zinc-200/70 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700/60",
};

const renderToneIcon = (icon: Component, tone: Tone) =>
  h(
    "span",
    {
      class: [
        "inline-flex size-8 items-center justify-center rounded-lg ring-1 ring-inset",
        toneBgClass[tone],
      ],
    },
    h(icon, { class: "size-4" }),
  );
</script>

<template>
  <Head title="Dashboard Operasional" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Deferred data="dashboard">
      <template #fallback>
        <PageContainer class="max-w-none">
          <div aria-live="polite" class="sr-only">
            Memuat dashboard operasional.
          </div>
          <Skeleton class="h-32 rounded-2xl" />
          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Skeleton v-for="index in 4" :key="index" class="h-44 rounded-2xl" />
          </div>
          <div class="grid gap-6 xl:grid-cols-3">
            <Skeleton class="h-[28rem] rounded-xl xl:col-span-2" />
            <Skeleton class="h-[28rem] rounded-xl" />
          </div>
          <div class="grid gap-6 xl:grid-cols-3">
            <Skeleton class="h-[26rem] rounded-xl xl:col-span-2" />
            <Skeleton class="h-[26rem] rounded-xl" />
          </div>
          <Skeleton class="h-64 rounded-xl" />
        </PageContainer>
      </template>

      <PageContainer class="max-w-none">
        <!-- HERO -->
        <section
          class="relative overflow-hidden rounded-2xl border border-emerald-200/60 bg-gradient-to-br from-white via-emerald-50/60 to-sky-50/40 p-6 shadow-sm shadow-emerald-950/5 sm:p-7 dark:border-emerald-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
        >
          <div
            class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"
            aria-hidden="true"
          />
          <div
            class="pointer-events-none absolute -bottom-24 -left-12 size-64 rounded-full bg-sky-300/15 blur-3xl dark:bg-sky-500/10"
            aria-hidden="true"
          />
          <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-3">
              <span
                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-800/60"
              >
                <Sparkles class="size-3.5" />
                Operasional Harian
              </span>
              <h1
                class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
              >
                Dashboard Koperasi
              </h1>
              <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
                Prioritas kerja hari ini, kas iuran, POS toko, stok, dan
                ringkasan keputusan manajemen — semua di satu tempat.
              </p>
            </div>
            <div
              class="flex w-full flex-col gap-2 rounded-xl border border-zinc-200/80 bg-white/70 p-3 text-sm shadow-sm shadow-zinc-950/5 backdrop-blur sm:flex-row sm:items-center sm:gap-4 sm:p-4 dark:border-zinc-800/80 dark:bg-zinc-950/40"
            >
              <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                <Calendar class="size-4 text-emerald-600" />
                <span class="font-medium">{{
                  formatLongDate(dashboard.generatedAt)
                }}</span>
              </div>
              <div
                class="hidden h-5 w-px bg-zinc-200 sm:block dark:bg-zinc-800"
                aria-hidden="true"
              />
              <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                <Clock class="size-4 text-emerald-600" />
                <span>Update {{ formatDateTime(dashboard.generatedAt) }}</span>
              </div>
              <Link
                :href="cooperativeReportsIndex().url"
                class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm shadow-emerald-950/15 transition hover:bg-emerald-800"
              >
                <Download class="size-3.5" />
                Laporan
              </Link>
            </div>
          </div>
        </section>

        <!-- KPI BAND -->
        <section
          class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
          aria-label="Ringkasan KPI"
        >
          <GradientKpiCard
            v-for="card in kpiCards"
            :key="card.label"
            :label="card.label"
            :value="card.value()"
            :meta="card.meta()"
            :icon="card.icon"
            :tone="card.tone"
            :href="card.href"
            :sparkline-points="card.sparkline()"
            :trend="card.trend()"
            :trend-label="card.trendLabel"
          />
        </section>

        <!-- WORK QUEUE + COLLECTIONS -->
        <section class="grid gap-6 xl:grid-cols-3">
          <Card
            class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 xl:col-span-2 dark:border-zinc-800/80 dark:bg-zinc-900/80"
          >
            <SectionHeader
              title="Prioritas Hari Ini"
              description="Antrian kerja yang paling memengaruhi operasional koperasi."
              :icon="CalendarClock"
              tone="amber"
              :href="cooperativePaymentsIndex().url"
              href-label="Buka pembayaran"
            />
            <CardContent class="px-3 pb-3 sm:px-4 sm:pb-4">
              <div class="grid gap-2 sm:grid-cols-2">
                <Link
                  v-for="item in workItems"
                  :key="item.label"
                  :href="item.href"
                  prefetch
                  class="group relative flex flex-col gap-3 rounded-xl border border-zinc-200/70 bg-white/60 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-zinc-300/80 hover:bg-white hover:shadow-md hover:shadow-zinc-950/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50 dark:border-zinc-800/70 dark:bg-zinc-950/40 dark:hover:border-zinc-700/80 dark:hover:bg-zinc-900"
                >
                  <div class="flex items-start justify-between gap-3">
                    <component
                      :is="renderToneIcon(item.icon, item.tone())"
                    />
                    <StatusPill
                      :tone="item.pill().tone"
                      :label="item.pill().text"
                      :dot="false"
                    />
                  </div>
                  <div class="space-y-1">
                    <p class="text-2xl font-bold tabular-nums text-zinc-950 dark:text-white">
                      {{
                        item.label === "Verifikasi anggota baru"
                          ? formatNumber(dashboard.workQueue.pending_members)
                          : item.label === "Approve pembayaran"
                            ? formatNumber(
                                dashboard.workQueue.pending_payments,
                              )
                            : item.label === "Tindak lanjut tagihan"
                              ? formatNumber(dashboard.workQueue.unpaid_dues)
                              : formatNumber(
                                  dashboard.workQueue.low_stock_products,
                                )
                      }}
                    </p>
                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">
                      {{ item.label }}
                    </p>
                    <p class="text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                      {{ item.description }}
                    </p>
                  </div>
                  <div class="flex items-center justify-end text-xs font-semibold text-zinc-400 transition-colors group-hover:text-emerald-600 dark:group-hover:text-emerald-300">
                    Buka
                    <ArrowRight class="ml-1 size-3.5 transition-transform group-hover:translate-x-0.5" />
                  </div>
                </Link>
              </div>
            </CardContent>
          </Card>

          <Card
            class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
          >
            <SectionHeader
              title="Kas & Iuran"
              :description="`Periode ${dashboard.collections.period}`"
              :icon="WalletCards"
              tone="emerald"
            />
            <CardContent class="space-y-5 px-6 py-5">
              <CollectionDonut
                :paid="dashboard.collections.paid"
                :outstanding="dashboard.collections.outstanding"
                :rate="dashboard.collections.collection_rate"
              />

              <div class="grid gap-2.5 sm:grid-cols-3">
                <div
                  v-for="stat in collectionStats"
                  :key="stat.label"
                  class="rounded-lg border border-zinc-200/70 bg-zinc-50/70 p-3 dark:border-zinc-800/70 dark:bg-zinc-950/50"
                >
                  <p
                    class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                  >
                    {{ stat.label }}
                  </p>
                  <p
                    class="mt-1 text-sm font-bold tabular-nums text-zinc-950 sm:text-base dark:text-white"
                  >
                    {{ stat.value() }}
                  </p>
                </div>
              </div>

              <div class="space-y-2">
                <div class="flex items-center justify-between text-xs">
                  <span class="font-medium text-zinc-500 dark:text-zinc-400">
                    Progress koleksi
                  </span>
                  <span
                    class="font-semibold tabular-nums"
                    :class="{
                      'text-emerald-600 dark:text-emerald-300':
                        collectionTone === 'emerald',
                      'text-amber-600 dark:text-amber-300':
                        collectionTone === 'amber',
                      'text-rose-600 dark:text-rose-300':
                        collectionTone === 'rose',
                    }"
                  >
                    {{ formatPercent(dashboard.collections.collection_rate) }}
                  </span>
                </div>
                <ProgressBar
                  :value="dashboard.collections.collection_rate"
                  :tone="collectionTone"
                  label="Collection rate"
                />
              </div>

              <div class="flex flex-wrap gap-2 pt-1">
                <Link
                  :href="cooperativePaymentsIndex().url"
                  class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm shadow-emerald-950/15 transition hover:bg-emerald-800"
                >
                  Input pembayaran
                  <ArrowRight class="size-4" />
                </Link>
                <Link
                  :href="cooperativeDuesIndex().url"
                  class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3.5 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                  Kelola tagihan
                </Link>
              </div>
            </CardContent>
          </Card>
        </section>

        <!-- POS + INVENTORY -->
        <section class="grid gap-6 xl:grid-cols-3">
          <Card
            class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 xl:col-span-2 dark:border-zinc-800/80 dark:bg-zinc-900/80"
          >
            <SectionHeader
              title="POS Toko"
              description="Performa transaksi dan produk teratas tahun berjalan."
              :icon="ShoppingCart"
              tone="emerald"
              :href="cooperativePosReportsIndex().url"
              href-label="Laporan POS"
            />
            <CardContent class="space-y-5 px-6 py-5">
              <div class="grid gap-3 sm:grid-cols-3">
                <div
                  class="group relative overflow-hidden rounded-xl border border-emerald-200/60 bg-gradient-to-br from-emerald-50/80 to-white p-4 transition-all hover:shadow-md hover:shadow-emerald-950/5 dark:border-emerald-900/40 dark:from-emerald-950/20 dark:to-zinc-900"
                >
                  <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-12 opacity-50"
                    aria-hidden="true"
                  >
                    <svg
                      viewBox="0 0 100 24"
                      preserveAspectRatio="none"
                      class="h-full w-full text-emerald-500"
                    >
                      <path
                        d="M0,18 L20,14 L40,16 L60,8 L80,10 L100,4"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                    </svg>
                  </div>
                  <div class="relative flex items-center gap-2 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                    <TrendingUp class="size-3.5" />
                    Omzet bulan ini
                  </div>
                  <p class="relative mt-1.5 text-xl font-bold tabular-nums text-zinc-950 dark:text-white">
                    {{ formatCurrency(dashboard.pos.monthly_sales) }}
                  </p>
                </div>
                <div
                  class="group relative overflow-hidden rounded-xl border border-sky-200/60 bg-gradient-to-br from-sky-50/80 to-white p-4 transition-all hover:shadow-md hover:shadow-sky-950/5 dark:border-sky-900/40 dark:from-sky-950/20 dark:to-zinc-900"
                >
                  <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-12 opacity-50"
                    aria-hidden="true"
                  >
                    <svg
                      viewBox="0 0 100 24"
                      preserveAspectRatio="none"
                      class="h-full w-full text-sky-500"
                    >
                      <path
                        d="M0,16 L25,12 L50,14 L75,6 L100,2"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                    </svg>
                  </div>
                  <div class="relative flex items-center gap-2 text-xs font-medium text-sky-700 dark:text-sky-300">
                    <BarChart3 class="size-3.5" />
                    Transaksi bulan ini
                  </div>
                  <p class="relative mt-1.5 text-xl font-bold tabular-nums text-zinc-950 dark:text-white">
                    {{ formatNumber(dashboard.pos.monthly_transactions) }}
                  </p>
                </div>
                <div
                  class="group relative overflow-hidden rounded-xl border border-violet-200/60 bg-gradient-to-br from-violet-50/80 to-white p-4 transition-all hover:shadow-md hover:shadow-violet-950/5 dark:border-violet-900/40 dark:from-violet-950/20 dark:to-zinc-900"
                >
                  <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-12 opacity-50"
                    aria-hidden="true"
                  >
                    <svg
                      viewBox="0 0 100 24"
                      preserveAspectRatio="none"
                      class="h-full w-full text-violet-500"
                    >
                      <path
                        d="M0,14 L25,16 L50,10 L75,12 L100,6"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                    </svg>
                  </div>
                  <div class="relative flex items-center gap-2 text-xs font-medium text-violet-700 dark:text-violet-300">
                    <BadgeDollarSign class="size-3.5" />
                    Transaksi anggota
                  </div>
                  <p class="relative mt-1.5 text-xl font-bold tabular-nums text-zinc-950 dark:text-white">
                    {{ formatNumber(dashboard.pos.member_transactions) }}
                  </p>
                </div>
              </div>

              <div
                v-if="dashboard.pos.top_products.length > 0"
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/40 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/30"
              >
                <div class="mb-3 flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                    Top 5 produk tahun ini
                  </h3>
                  <span class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ dashboard.pos.top_products.length }} produk teratas
                  </span>
                </div>
                <TopProductsBar :products="dashboard.pos.top_products" />
              </div>
              <EmptyState
                v-else
                :icon="BarChart3"
                title="Belum ada penjualan produk"
                description="Produk teratas akan muncul di sini setelah ada transaksi POS."
              />

              <div
                v-if="dashboard.pos.top_products.length > 0"
                class="overflow-hidden rounded-xl border border-zinc-200/70 dark:border-zinc-800/70"
              >
                <table
                  aria-label="Detail produk teratas POS"
                  class="w-full text-sm"
                  role="table"
                >
                  <thead
                    class="bg-zinc-50 text-left text-[11px] uppercase tracking-wide text-zinc-500 dark:bg-zinc-900"
                  >
                    <tr>
                      <th class="px-4 py-2.5 font-medium">Produk</th>
                      <th class="px-4 py-2.5 text-right font-medium">Qty</th>
                      <th class="px-4 py-2.5 text-right font-medium">Omzet</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70">
                    <tr
                      v-for="product in dashboard.pos.top_products"
                      :key="product.id"
                      class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-900/50"
                    >
                      <td class="px-4 py-3">
                        <p class="font-medium text-zinc-950 dark:text-white">
                          {{ product.name }}
                        </p>
                        <p class="text-xs text-zinc-500">
                          {{ product.category ?? "Tanpa kategori" }}
                        </p>
                      </td>
                      <td class="px-4 py-3 text-right tabular-nums">
                        {{ formatNumber(product.quantity) }}
                      </td>
                      <td class="px-4 py-3 text-right font-semibold tabular-nums">
                        {{ formatCurrency(product.revenue) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="flex flex-wrap gap-2 pt-1">
                <Link
                  :href="cooperativePosIndex().url"
                  class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm shadow-emerald-950/15 transition hover:bg-emerald-800"
                >
                  Buka kasir
                  <ArrowRight class="size-4" />
                </Link>
                <Link
                  :href="cooperativePosReportsIndex().url"
                  class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3.5 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                  Laporan POS
                </Link>
              </div>
            </CardContent>
          </Card>

          <Card
            class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
          >
            <SectionHeader
              title="Stok Perlu Tindakan"
              :description="`${formatNumber(dashboard.inventory.low_stock_count)} produk di bawah minimum`"
              :icon="AlertTriangle"
              :tone="inventoryTone"
              :href="
                cooperativePosProductsIndex({ query: { low_stock: 1 } }).url
              "
              href-label="Kelola stok"
            />
            <CardContent class="px-2 pb-2 sm:px-3 sm:pb-3">
              <StatusPill
                v-if="dashboard.inventory.low_stock_count > 0"
                :tone="inventoryTone"
                label="Butuh restock"
                class="mx-3 mt-3"
              />
              <StatusPill
                v-else
                tone="emerald"
                label="Aman"
                class="mx-3 mt-3"
              />
              <div
                v-if="dashboard.inventory.critical_products.length > 0"
                class="mt-3 max-h-80 divide-y divide-zinc-200/70 overflow-y-auto dark:divide-zinc-800/70"
              >
                <div
                  v-for="product in dashboard.inventory.critical_products"
                  :key="product.id"
                  class="group flex items-center gap-3 px-4 py-3 transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40"
                >
                  <div class="min-w-0 flex-1 space-y-1">
                    <p
                      class="truncate text-sm font-semibold text-zinc-950 dark:text-white"
                    >
                      {{ product.name }}
                    </p>
                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                      {{ product.sku }} ·
                      {{ product.category ?? "Tanpa kategori" }}
                    </p>
                    <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                      <div
                        :class="[
                          'h-full rounded-full transition-all duration-500',
                          product.minimum_stock > 0 &&
                          product.stock / product.minimum_stock < 0.5
                            ? 'bg-gradient-to-r from-rose-500 to-rose-600'
                            : 'bg-gradient-to-r from-amber-500 to-amber-600',
                        ]"
                        :style="{
                          width: `${Math.min(100, product.minimum_stock > 0 ? (product.stock / product.minimum_stock) * 100 : 0)}%`,
                        }"
                      />
                    </div>
                  </div>
                  <div class="shrink-0 text-right">
                    <p
                      :class="[
                        'text-lg font-bold tabular-nums',
                        product.minimum_stock > 0 &&
                        product.stock / product.minimum_stock < 0.5
                          ? 'text-rose-600 dark:text-rose-300'
                          : 'text-amber-600 dark:text-amber-300',
                      ]"
                    >
                      {{ formatNumber(product.stock) }}
                    </p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                      Min {{ formatNumber(product.minimum_stock) }}
                    </p>
                  </div>
                </div>
              </div>
              <EmptyState
                v-else
                :icon="CheckCircle2"
                title="Stok aman"
                description="Semua produk aktif berada di atas stok minimum."
                class="py-8"
              />
              <div class="border-t border-zinc-200/70 p-3 dark:border-zinc-800/70">
                <Link
                  :href="
                    cooperativePosProductsIndex({
                      query: { low_stock: 1 },
                    }).url
                  "
                  class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                  Kelola stok minimum
                  <ArrowRight class="size-4" />
                </Link>
              </div>
            </CardContent>
          </Card>
        </section>

        <!-- MANAGEMENT SUMMARY -->
        <section
          class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-gradient-to-br from-emerald-50/40 via-white to-sky-50/40 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:from-emerald-950/20 dark:via-zinc-900 dark:to-sky-950/10"
        >
          <div
            class="flex flex-col gap-3 border-b border-zinc-200/70 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800/70"
          >
            <div class="flex items-start gap-3">
              <span
                class="inline-flex size-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60"
              >
                <BadgeDollarSign class="size-4" />
              </span>
              <div>
                <h2
                  class="text-base font-semibold tracking-tight text-zinc-950 sm:text-lg dark:text-white"
                >
                  Ringkasan Manajemen
                </h2>
                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                  Sinyal cepat untuk rapat harian dan keputusan pengurus.
                </p>
              </div>
            </div>
            <Link
              :href="cooperativeReportsIndex().url"
              prefetch
              class="group inline-flex items-center gap-1 self-start text-sm font-semibold text-emerald-700 transition-colors hover:text-emerald-800 sm:self-auto dark:text-emerald-300 dark:hover:text-emerald-200"
            >
              Laporan koperasi
              <ArrowRight class="size-3.5 transition-transform group-hover:translate-x-0.5" />
            </Link>
          </div>

          <div class="grid gap-4 p-5 sm:p-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
              <Link
                v-for="stat in managementStats"
                :key="stat.label"
                :href="stat.href"
                prefetch
                :class="[
                  'group relative overflow-hidden rounded-xl border border-zinc-200/70 bg-white/85 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:shadow-zinc-950/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50 dark:border-zinc-800/70 dark:bg-zinc-950/40',
                ]"
              >
                <span
                  :class="[
                    'absolute inset-x-0 top-0 h-1 origin-left scale-x-0 transition-transform duration-300 group-hover:scale-x-100',
                    stat.tone === 'emerald' && 'bg-emerald-500',
                    stat.tone === 'sky' && 'bg-sky-500',
                    stat.tone === 'violet' && 'bg-violet-500',
                    stat.tone === 'amber' && 'bg-amber-500',
                  ]"
                />
                <div class="flex items-start justify-between gap-3">
                  <div class="space-y-1">
                    <p
                      class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                    >
                      {{ stat.label }}
                    </p>
                    <p
                      class="text-xl font-bold tabular-nums text-zinc-950 sm:text-2xl dark:text-white"
                    >
                      {{ stat.value() }}
                    </p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                      {{ stat.description }}
                    </p>
                  </div>
                  <span
                    :class="[
                      'inline-flex size-9 items-center justify-center rounded-lg ring-1 ring-inset transition-transform duration-300 group-hover:scale-110',
                      stat.tone === 'emerald' &&
                        'bg-emerald-100 text-emerald-700 ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60',
                      stat.tone === 'sky' &&
                        'bg-sky-100 text-sky-700 ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60',
                      stat.tone === 'violet' &&
                        'bg-violet-100 text-violet-700 ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/60',
                      stat.tone === 'amber' &&
                        'bg-amber-100 text-amber-700 ring-amber-200/70 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900/60',
                    ]"
                  >
                    <component :is="stat.icon" class="size-4" />
                  </span>
                </div>
              </Link>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <div
                v-for="pulse in memberPulse"
                :key="pulse.label"
                :class="[
                  'flex items-center gap-3 rounded-xl border p-3.5 transition-colors',
                  pulse.tone === 'emerald' &&
                    'border-emerald-200/60 bg-white/80 dark:border-emerald-900/40 dark:bg-zinc-950/40',
                  pulse.tone === 'sky' &&
                    'border-sky-200/60 bg-white/80 dark:border-sky-900/40 dark:bg-zinc-950/40',
                  pulse.tone === 'violet' &&
                    'border-violet-200/60 bg-white/80 dark:border-violet-900/40 dark:bg-zinc-950/40',
                  pulse.tone === 'amber' &&
                    'border-amber-200/60 bg-white/80 dark:border-amber-900/40 dark:bg-zinc-950/40',
                ]"
              >
                <span
                  :class="[
                    'inline-flex size-9 shrink-0 items-center justify-center rounded-lg ring-1 ring-inset',
                    toneBgClass[pulse.tone],
                  ]"
                >
                  <component :is="pulse.icon" class="size-4" />
                </span>
                <div class="min-w-0">
                  <p
                    class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                  >
                    {{ pulse.label }}
                  </p>
                  <p
                    class="truncate text-base font-bold tabular-nums text-zinc-950 dark:text-white"
                  >
                    {{ pulse.value() }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </PageContainer>
    </Deferred>
  </AppLayout>
</template>
