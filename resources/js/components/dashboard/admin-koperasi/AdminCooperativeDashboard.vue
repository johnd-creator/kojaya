<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";
import {
  ArrowRight,
  Boxes,
  Calendar,
  CalendarClock,
  CheckCircle2,
  ClipboardCheck,
  CreditCard,
  FileWarning,
  PackageCheck,
  ReceiptText,
  ShieldCheck,
  Sparkles,
  Store,
  UserCheck,
  WalletCards,
} from "lucide-vue-next";
import { computed } from "vue";
import type { Component } from "vue";
import CollectionDonut from "@/components/dashboard/CollectionDonut.vue";
import EmptyState from "@/components/EmptyState.vue";
import GradientKpiCard from "@/components/dashboard/GradientKpiCard.vue";
import PageContainer from "@/components/PageContainer.vue";
import ProgressBar from "@/components/dashboard/ProgressBar.vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import { Card, CardContent } from "@/components/ui/card";
import { formatCurrency, formatNumber } from "@/lib/formatters";
import { hasAnyPermission } from "@/lib/role-experience";
import { dashboard as dashboardRoute } from "@/routes";
import { index as duesIndex } from "@/routes/cooperative/dues";
import { index as membersIndex } from "@/routes/cooperative/members";
import { index as resignationsIndex } from "@/routes/cooperative/members/resignations";
import { index as paymentsIndex } from "@/routes/cooperative/payments";
import { index as posIndex } from "@/routes/cooperative/pos";
import { index as posProductsIndex } from "@/routes/cooperative/pos-products";
import { index as posReportsIndex } from "@/routes/cooperative/pos/reports";
import type { AdminCooperativeDashboardPayload } from "@/types/dashboard";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet";

type Kpi = {
  label: string;
  value: string;
  meta: string;
  href: string;
  icon: Component;
  tone: Tone;
  sparkline: number[];
  permissions: string[];
};

type QueueItem = {
  label: string;
  description: string;
  count: number;
  href: string;
  permission: string[];
  icon: Component;
  tone: Tone;
};

const props = defineProps<{ dashboard?: AdminCooperativeDashboardPayload }>();
const page = usePage();
const permissions = computed<string[]>(
  () => (page.props.auth?.permissions as string[] | undefined) ?? [],
);
const dashboard = computed<AdminCooperativeDashboardPayload | null>(
  () => props.dashboard ?? null,
);

const generatedAt = computed(
  () => dashboard.value?.generated_at ?? dashboard.value?.generatedAt ?? "",
);
const organizationLabel = computed(
  () => dashboard.value?.organization?.name ?? "Organisasi aktif",
);

const formatDate = (value: string): string =>
  new Intl.DateTimeFormat("id-ID", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  }).format(new Date(value));

const formatUpdatedAt = (value: string): string =>
  value
    ? new Intl.DateTimeFormat("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
      }).format(new Date(value))
    : "Data diperbarui saat halaman dibuka";

const sparklineFor = (value: number): number[] => {
  const safeValue = Math.max(0, Number(value) || 0);
  const base = Math.min(1, Math.log10(safeValue + 1) / 7.5);

  return Array.from({ length: 8 }, (_, index) => {
    const progress = index / 7;
    const variation = (Math.sin((safeValue + index) * 1.7) + 1) / 2;

    return Math.max(0.05, base * (0.35 + progress * 0.85) + variation * 0.12);
  });
};

const reportHref = computed(() =>
  hasAnyPermission(permissions.value, ["view_pos_reports"])
    ? posReportsIndex().url
    : dashboardRoute().url,
);

const kpis = computed<Kpi[]>(() => {
  const data = dashboard.value;

  if (!data) {
    return [];
  }

  return [
    {
      label: "Omzet POS Hari Ini",
      value: formatCurrency(data.summary.today_sales),
      meta: `${formatNumber(data.summary.today_transactions)} transaksi hari ini`,
      href: posIndex().url,
      icon: Store,
      tone: "emerald",
      sparkline: sparklineFor(data.summary.today_sales),
      permissions: ["access_cooperative_pos"],
    },
    {
      label: "Pembayaran Pending",
      value: formatNumber(data.summary.pending_payments),
      meta: `${formatCurrency(data.collections.pending_payment_amount)} nilai tertunda`,
      href: paymentsIndex({ query: { status: "PENDING" } }).url,
      icon: CreditCard,
      tone: "amber",
      sparkline: sparklineFor(data.summary.pending_payments),
      permissions: ["manage_cooperative_payment"],
    },
    {
      label: "Tunggakan Iuran Semua Periode",
      value: formatCurrency(data.summary.unpaid_dues_amount),
      meta: `${formatNumber(data.work_queue.unpaid_dues)} tagihan perlu follow-up`,
      href: duesIndex({ query: { period_scope: "all", status: "OPEN" } }).url,
      icon: ReceiptText,
      tone: "rose",
      sparkline: sparklineFor(data.work_queue.unpaid_dues),
      permissions: ["manage_cooperative_dues"],
    },
    {
      label: "Produk Stok Kritis",
      value: formatNumber(data.summary.low_stock_products),
      meta: "Di bawah atau sama dengan stok minimum",
      href: posProductsIndex({ query: { low_stock: 1 } }).url,
      icon: Boxes,
      tone: "sky",
      sparkline: sparklineFor(data.summary.low_stock_products),
      permissions: ["manage_pos_products"],
    },
  ].filter((kpi) => hasAnyPermission(permissions.value, kpi.permissions));
});

const queueItems = computed<QueueItem[]>(() => {
  const data = dashboard.value;

  if (!data) {
    return [];
  }

  return [
    {
      label: "Verifikasi anggota baru",
      description: "Calon anggota menunggu aktivasi status.",
      count: data.work_queue.pending_members,
      href: membersIndex({ query: { validation_status: "PENDING" } }).url,
      permission: ["validate_cooperative_member"],
      icon: UserCheck,
      tone: "sky",
    },
    {
      label: "Data anggota perlu revisi",
      description: "Tinjau catatan revisi dan lengkapi data anggota.",
      count: data.work_queue.revision_members,
      href: membersIndex({ query: { validation_status: "REVISION" } }).url,
      permission: ["validate_cooperative_member"],
      icon: ClipboardCheck,
      tone: "violet",
    },
    {
      label: "Approve pembayaran",
      description: "Periksa bukti dan metode pembayaran agar ledger akurat.",
      count: data.work_queue.pending_payments,
      href: paymentsIndex({ query: { status: "PENDING" } }).url,
      permission: ["manage_cooperative_payment"],
      icon: ShieldCheck,
      tone: "amber",
    },
    {
      label: "Tindak lanjut tagihan",
      description: "Tagihan unpaid atau partial lintas periode perlu ditagih.",
      count: data.work_queue.unpaid_dues,
      href: duesIndex({ query: { period_scope: "all", status: "OPEN" } }).url,
      permission: ["manage_cooperative_dues"],
      icon: ReceiptText,
      tone: "rose",
    },
    {
      label: "Pengunduran diri menunggu review",
      description: "Periksa pengajuan yang membutuhkan tindak lanjut.",
      count: data.work_queue.pending_resignations ?? 0,
      href: resignationsIndex({ query: { status: "PENDING" } }).url,
      permission: ["review_cooperative_resignation"],
      icon: FileWarning,
      tone: "violet",
    },
    {
      label: "Restock produk",
      description: "Produk POS sudah mencapai stok minimum.",
      count: data.work_queue.low_stock_products,
      href: posProductsIndex({ query: { low_stock: 1 } }).url,
      permission: ["manage_pos_products"],
      icon: PackageCheck,
      tone: "amber",
    },
  ]
    .filter((item) => hasAnyPermission(permissions.value, item.permission))
    .filter((item) => item.count > 0);
});

const collectionTone = computed<"emerald" | "amber" | "rose">(() => {
  const rate = dashboard.value?.collections.collection_rate ?? 0;

  if (rate >= 80) {
    return "emerald";
  }

  if (rate >= 50) {
    return "amber";
  }

  return "rose";
});

const collectionStats = computed(() => {
  const data = dashboard.value;

  return [
    {
      label: "Tagihan periode ini",
      value: formatCurrency(data?.collections.total_due ?? 0),
    },
    {
      label: "Sudah dibayar",
      value: formatCurrency(data?.collections.paid ?? 0),
    },
    {
      label: "Outstanding",
      value: formatCurrency(data?.collections.outstanding ?? 0),
    },
  ];
});
</script>

<template>
  <PageContainer class="max-w-none">
    <Head title="Dashboard Koperasi" />

    <section
      class="relative overflow-hidden rounded-2xl border border-emerald-200/60 bg-gradient-to-br from-white via-emerald-50/60 to-sky-50/40 p-6 shadow-sm shadow-emerald-950/5 sm:p-7 dark:border-emerald-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      aria-labelledby="cooperative-dashboard-title"
    >
      <div
        class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"
        aria-hidden="true"
      />
      <div
        class="pointer-events-none absolute -bottom-24 -left-12 size-64 rounded-full bg-sky-300/15 blur-3xl dark:bg-sky-500/10"
        aria-hidden="true"
      />
      <div
        class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
      >
        <div class="space-y-3">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-800/60"
          >
            <Sparkles class="size-3.5" aria-hidden="true" />
            Operasional Harian
          </span>
          <h1
            id="cooperative-dashboard-title"
            class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
          >
            Dashboard Koperasi
          </h1>
          <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Prioritas kerja hari ini, kas iuran, POS toko, stok, dan ringkasan
            keputusan manajemen — semua di satu tempat.
          </p>
          <p class="text-xs text-zinc-500 dark:text-zinc-400">
            {{ organizationLabel }}
          </p>
        </div>
        <div
          class="flex w-full flex-col gap-2 rounded-xl border border-zinc-200/80 bg-white/70 p-3 text-sm shadow-sm shadow-zinc-950/5 backdrop-blur sm:flex-row sm:items-center sm:gap-4 sm:p-4 dark:border-zinc-800/80 dark:bg-zinc-950/40"
        >
          <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
            <Calendar class="size-4 text-emerald-600" aria-hidden="true" />
            <span class="font-medium">{{ formatDate(generatedAt) }}</span>
          </div>
          <div
            class="hidden h-5 w-px bg-zinc-200 sm:block dark:bg-zinc-800"
            aria-hidden="true"
          />
          <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
            <CalendarClock class="size-4 text-emerald-600" aria-hidden="true" />
            <span>Update {{ formatUpdatedAt(generatedAt) }}</span>
          </div>
          <Link
            :href="reportHref"
            class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm shadow-emerald-950/15 transition hover:bg-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950"
          >
            Laporan
            <ArrowRight class="size-3.5" aria-hidden="true" />
          </Link>
        </div>
      </div>
    </section>

    <section
      class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
      aria-label="Ringkasan KPI operasional"
    >
      <GradientKpiCard
        v-for="kpi in kpis"
        :key="kpi.label"
        :label="kpi.label"
        :value="kpi.value"
        :meta="kpi.meta"
        :icon="kpi.icon"
        :tone="kpi.tone"
        :href="kpi.href"
        :sparkline-points="kpi.sparkline"
        :trend="null"
      />
    </section>

    <section class="grid gap-6 xl:grid-cols-3">
      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 xl:col-span-2 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Prioritas Hari Ini"
          description="Antrian kerja yang paling memengaruhi operasional koperasi."
          :icon="CalendarClock"
          tone="amber"
          :href="queueItems[0]?.href"
          href-label="Buka prioritas"
        />
        <CardContent class="px-3 pb-3 sm:px-4 sm:pb-4">
          <div v-if="queueItems.length > 0" class="grid gap-2 sm:grid-cols-2">
            <Link
              v-for="item in queueItems"
              :key="item.label"
              :href="item.href"
              prefetch
              class="group relative flex flex-col gap-3 rounded-xl border border-zinc-200/70 bg-white/60 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-zinc-300/80 hover:bg-white hover:shadow-md hover:shadow-zinc-950/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50 dark:border-zinc-800/70 dark:bg-zinc-950/40 dark:hover:border-zinc-700/80 dark:hover:bg-zinc-900"
            >
              <div class="flex items-start justify-between gap-3">
                <span
                  class="inline-flex size-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                >
                  <component
                    :is="item.icon"
                    class="size-4"
                    aria-hidden="true"
                  />
                </span>
                <span
                  class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold tabular-nums text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                >
                  {{ formatNumber(item.count) }}
                </span>
              </div>
              <div class="space-y-1">
                <p class="text-sm font-semibold text-zinc-950 dark:text-white">
                  {{ item.label }}
                </p>
                <p
                  class="text-xs leading-relaxed text-zinc-500 dark:text-zinc-400"
                >
                  {{ item.description }}
                </p>
              </div>
              <span
                class="flex items-center justify-end text-xs font-semibold text-zinc-500 transition-colors group-hover:text-emerald-600 dark:group-hover:text-emerald-300"
              >
                Buka
                <ArrowRight
                  class="ml-1 size-3.5 transition-transform group-hover:translate-x-0.5"
                  aria-hidden="true"
                />
              </span>
            </Link>
          </div>
          <EmptyState
            v-else
            :icon="CheckCircle2"
            title="Semua prioritas tertangani"
            description="Tidak ada pekerjaan tertunda yang membutuhkan tindakan dari akun ini."
          />
        </CardContent>
      </Card>

      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Kas & Iuran"
          :description="`Periode ${dashboard?.collections.period ?? '—'}`"
          :icon="WalletCards"
          tone="emerald"
        />
        <CardContent v-if="dashboard" class="space-y-5 px-6 py-5">
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
                {{ stat.value }}
              </p>
            </div>
          </div>
          <div class="space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-medium text-zinc-500 dark:text-zinc-400">
                Progress koleksi
              </span>
              <span class="font-semibold tabular-nums">
                {{ dashboard.collections.collection_rate.toFixed(1) }}%
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
              v-if="
                hasAnyPermission(permissions, ['manage_cooperative_payment'])
              "
              :href="paymentsIndex().url"
              class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm shadow-emerald-950/15 transition hover:bg-emerald-800"
            >
              Input pembayaran
              <ArrowRight class="size-4" aria-hidden="true" />
            </Link>
            <Link
              v-if="hasAnyPermission(permissions, ['manage_cooperative_dues'])"
              :href="duesIndex().url"
              class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3.5 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
              Kelola tagihan
            </Link>
          </div>
        </CardContent>
      </Card>
    </section>
  </PageContainer>
</template>
