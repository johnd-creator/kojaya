<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";
import {
  ArrowRight,
  CalendarDays,
  CheckCircle2,
  ClipboardCheck,
  CreditCard,
  FileWarning,
  ReceiptText,
  UserCheck,
  UserRoundCheck,
  Users,
} from "lucide-vue-next";
import { computed } from "vue";
import type { Component } from "vue";
import EmptyState from "@/components/EmptyState.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Card, CardContent } from "@/components/ui/card";
import { hasAnyPermission } from "@/lib/role-experience";
import { formatCurrency, formatNumber } from "@/lib/formatters";
import { dashboard as dashboardRoute } from "@/routes";
import { index as duesIndex } from "@/routes/cooperative/dues";
import { index as membersIndex } from "@/routes/cooperative/members";
import { index as resignationsIndex } from "@/routes/cooperative/members/resignations";
import { index as paymentsIndex } from "@/routes/cooperative/payments";

type AdminDashboardPayload = {
  organization?: { name?: string; code?: string } | null;
  summary: {
    pending_members: number;
    revision_members: number;
    pending_payments: number;
    unpaid_dues_count: number;
    unpaid_dues_amount: number;
    active_members: number;
  };
  work_queue: {
    pending_payments: number;
    pending_members: number;
    revision_members: number;
    unpaid_dues: number;
    pending_resignations?: number | null;
  };
  collections: {
    period: string;
    total_due: number;
    paid: number;
    outstanding: number;
    collection_rate: number;
    pending_payment_amount: number;
  };
  generated_at?: string;
  generatedAt?: string;
};

type QueueItem = {
  label: string;
  description: string;
  count: number;
  href: string;
  permission: string[];
  icon: Component;
  tone: "amber" | "sky" | "rose" | "violet";
};

const props = defineProps<{ dashboard?: AdminDashboardPayload }>();
const page = usePage();
const permissions = computed<string[]>(
  () => (page.props.auth?.permissions as string[] | undefined) ?? [],
);
const dashboard = computed<AdminDashboardPayload | null>(
  () => props.dashboard ?? null,
);
const organizationLabel = computed(
  () => dashboard.value?.organization?.name ?? "Organisasi aktif",
);
const generatedAt = computed(
  () => dashboard.value?.generated_at ?? dashboard.value?.generatedAt ?? "",
);

const queueItems = computed<QueueItem[]>(() => {
  const data = dashboard.value;
  if (!data) return [];

  const items: QueueItem[] = [
    {
      label: "Pembayaran menunggu verifikasi",
      description: "Periksa identitas, invoice, bukti, dan metode pembayaran.",
      count: data.work_queue.pending_payments,
      href: paymentsIndex({ query: { status: "PENDING" } }).url,
      permission: ["manage_cooperative_payment"],
      icon: CreditCard,
      tone: "amber",
    },
    {
      label: "Calon anggota perlu divalidasi",
      description: "Validasi kelengkapan data sebelum diteruskan ke Pengurus.",
      count: data.work_queue.pending_members,
      href: membersIndex({ query: { validation_status: "PENDING" } }).url,
      permission: ["validate_cooperative_member"],
      icon: UserCheck,
      tone: "sky",
    },
    {
      label: "Data anggota perlu revisi",
      description:
        "Tinjau catatan revisi dan bantu menutup data yang belum lengkap.",
      count: data.work_queue.revision_members,
      href: membersIndex({ query: { validation_status: "REVISION" } }).url,
      permission: ["validate_cooperative_member"],
      icon: ClipboardCheck,
      tone: "violet",
    },
    {
      label: "Iuran belum tertagih",
      description:
        "Tindak lanjuti tagihan belum dibayar atau dibayar sebagian.",
      count: data.work_queue.unpaid_dues,
      href: duesIndex({ query: { period_scope: "all", status: "OPEN" } }).url,
      permission: ["manage_cooperative_dues"],
      icon: ReceiptText,
      tone: "rose",
    },
    {
      label: "Pengunduran diri menunggu review",
      description:
        "Periksa pengajuan yang membutuhkan tindak lanjut administratif.",
      count: data.work_queue.pending_resignations ?? 0,
      href: resignationsIndex({ query: { status: "PENDING" } }).url,
      permission: ["review_cooperative_resignation"],
      icon: FileWarning,
      tone: "violet",
    },
  ];

  return items.filter((item) =>
    hasAnyPermission(permissions.value, item.permission),
  );
});

const primaryAction = computed(
  () => queueItems.value.find((item) => item.count > 0) ?? queueItems.value[0],
);

const kpis = computed(() => {
  const data = dashboard.value;
  if (!data) return [];

  return [
    {
      label: "Pembayaran pending",
      value: formatNumber(data.summary.pending_payments),
      detail:
        formatCurrency(data.collections.pending_payment_amount) +
        " menunggu review",
      href: paymentsIndex({ query: { status: "PENDING" } }).url,
      permission: ["manage_cooperative_payment"],
      icon: CreditCard,
      tone: "amber",
    },
    {
      label: "Calon anggota pending",
      value: formatNumber(
        data.summary.pending_members + data.summary.revision_members,
      ),
      detail: String(data.summary.revision_members) + " perlu revisi",
      href: membersIndex({ query: { validation_status: "PENDING" } }).url,
      permission: ["validate_cooperative_member"],
      icon: Users,
      tone: "sky",
    },
    {
      label: "Outstanding iuran",
      value: formatCurrency(data.summary.unpaid_dues_amount),
      detail: String(data.summary.unpaid_dues_count) + " tagihan terbuka",
      href: duesIndex({ query: { period_scope: "all", status: "OPEN" } }).url,
      permission: ["manage_cooperative_dues"],
      icon: ReceiptText,
      tone: "rose",
    },
    {
      label: "Anggota aktif",
      value: formatNumber(data.summary.active_members),
      detail: "Dalam organisasi aktif",
      href: membersIndex({ query: { status: "ACTIVE" } }).url,
      permission: ["view_cooperative_member"],
      icon: UserRoundCheck,
      tone: "emerald",
    },
  ].filter((item) => hasAnyPermission(permissions.value, item.permission));
});

const toneClasses: Record<string, string> = {
  amber:
    "border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200",
  sky: "border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-200",
  rose: "border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200",
  violet:
    "border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-900/60 dark:bg-violet-950/30 dark:text-violet-200",
  emerald:
    "border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200",
};

const formatGeneratedAt = (value: string): string => {
  if (!value) return "Data diperbarui saat halaman dibuka";
  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value));
};
</script>

<template>
  <Head title="Ruang kerja Admin Koperasi" />
  <PageContainer class="max-w-none">
    <section
      class="rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-white via-emerald-50/70 to-sky-50/50 p-6 shadow-sm shadow-emerald-950/5 sm:p-8 dark:border-emerald-900/50 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      aria-labelledby="admin-workspace-title"
    >
      <div
        class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
      >
        <div class="space-y-3">
          <span
            class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-800/60"
          >
            <ClipboardCheck class="size-3.5" aria-hidden="true" />
            Admin Koperasi
          </span>
          <h1
            id="admin-workspace-title"
            class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
          >
            Operasional koperasi hari ini
          </h1>
          <p
            class="max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-400"
          >
            Periksa anggota baru, pembayaran, tagihan, dan pekerjaan
            administrasi yang memerlukan tindak lanjut.
          </p>
          <p
            class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400"
          >
            <CalendarDays class="size-3.5" aria-hidden="true" />
            {{ organizationLabel }} · {{ formatGeneratedAt(generatedAt) }}
          </p>
        </div>
        <Link
          :href="primaryAction?.href ?? dashboardRoute().url"
          class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950"
        >
          {{ primaryAction?.label ?? "Buka dashboard" }}
          <ArrowRight class="size-4" aria-hidden="true" />
        </Link>
      </div>
    </section>

    <section
      v-if="kpis.length > 0"
      class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
      aria-label="KPI operasional Admin Koperasi"
    >
      <Link
        v-for="kpi in kpis"
        :key="kpi.label"
        :href="kpi.href"
        class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-900 dark:focus-visible:ring-offset-zinc-950"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
              {{ kpi.label }}
            </p>
            <p
              class="mt-2 text-2xl font-bold tabular-nums text-zinc-950 dark:text-white"
            >
              {{ kpi.value }}
            </p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
              {{ kpi.detail }}
            </p>
          </div>
          <span
            :class="[
              'inline-flex size-10 items-center justify-center rounded-xl border',
              toneClasses[kpi.tone],
            ]"
          >
            <component :is="kpi.icon" class="size-5" aria-hidden="true" />
          </span>
        </div>
      </Link>
    </section>

    <section
      class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(20rem,0.65fr)]"
    >
      <Card
        class="overflow-hidden border-zinc-200/80 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <div
          class="border-b border-zinc-200/70 px-5 py-4 dark:border-zinc-800/70"
        >
          <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">
            Pekerjaan yang perlu ditindaklanjuti
          </h2>
          <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Urutan dimulai dari dampak operasional paling mendesak.
          </p>
        </div>
        <CardContent class="p-4 sm:p-5">
          <div v-if="queueItems.length > 0" class="grid gap-3 sm:grid-cols-2">
            <Link
              v-for="item in queueItems"
              :key="item.label"
              :href="item.href"
              class="group flex min-h-32 flex-col justify-between rounded-xl border p-4 transition hover:-translate-y-0.5 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-900"
              :class="toneClasses[item.tone]"
            >
              <div class="flex items-start justify-between gap-3">
                <component :is="item.icon" class="size-5" aria-hidden="true" />
                <span
                  class="rounded-full bg-white/75 px-2.5 py-1 text-xs font-bold tabular-nums dark:bg-zinc-950/40"
                  >{{ formatNumber(item.count) }}</span
                >
              </div>
              <div class="mt-4">
                <h3 class="text-sm font-semibold">{{ item.label }}</h3>
                <p class="mt-1 text-xs leading-5">
                  {{ item.description }}
                </p>
              </div>
              <span
                class="mt-3 inline-flex items-center gap-1 text-xs font-semibold"
                >Buka antrean
                <ArrowRight
                  class="size-3.5 transition group-hover:translate-x-0.5"
                  aria-hidden="true"
              /></span>
            </Link>
          </div>
          <EmptyState
            v-else
            :icon="CheckCircle2"
            title="Tidak ada pekerjaan tertunda"
            description="Semua pekerjaan Admin Koperasi yang tersedia untuk akun ini sudah tertangani."
          />
        </CardContent>
      </Card>

      <Card
        class="border-zinc-200/80 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <div
          class="border-b border-zinc-200/70 px-5 py-4 dark:border-zinc-800/70"
        >
          <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">
            Ringkasan iuran
          </h2>
          <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Periode aktif {{ dashboard?.collections.period ?? "—" }}
          </p>
        </div>
        <CardContent v-if="dashboard" class="space-y-5 p-5">
          <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
            <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/50">
              <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Total tagihan
              </p>
              <p
                class="mt-1 font-semibold tabular-nums text-zinc-950 dark:text-white"
              >
                {{ formatCurrency(dashboard.collections.total_due) }}
              </p>
            </div>
            <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950/30">
              <p class="text-xs text-emerald-700 dark:text-emerald-300">
                Sudah dibayar
              </p>
              <p
                class="mt-1 font-semibold tabular-nums text-emerald-900 dark:text-emerald-100"
              >
                {{ formatCurrency(dashboard.collections.paid) }}
              </p>
            </div>
            <div class="rounded-xl bg-rose-50 p-3 dark:bg-rose-950/30">
              <p class="text-xs text-rose-700 dark:text-rose-300">
                Outstanding
              </p>
              <p
                class="mt-1 font-semibold tabular-nums text-rose-900 dark:text-rose-100"
              >
                {{ formatCurrency(dashboard.collections.outstanding) }}
              </p>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between gap-3 text-sm">
              <span class="font-medium text-zinc-700 dark:text-zinc-300"
                >Collection rate</span
              ><span
                class="font-bold tabular-nums text-emerald-700 dark:text-emerald-300"
                >{{ dashboard.collections.collection_rate.toFixed(1) }}%</span
              >
            </div>
            <div
              class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800"
              role="progressbar"
              aria-label="Collection rate"
              :aria-valuenow="dashboard.collections.collection_rate"
              aria-valuemin="0"
              aria-valuemax="100"
            >
              <div
                class="h-full rounded-full bg-emerald-600 transition-all"
                :style="{
                  width:
                    String(
                      Math.min(
                        100,
                        Math.max(0, dashboard.collections.collection_rate),
                      ),
                    ) + '%',
                }"
              />
            </div>
          </div>
          <Link
            v-if="hasAnyPermission(permissions, ['manage_cooperative_dues'])"
            :href="
              duesIndex({ query: { period_scope: 'all', status: 'OPEN' } }).url
            "
            class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:text-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-emerald-300"
            >Lihat iuran terbuka <ArrowRight class="size-4" aria-hidden="true"
          /></Link>
        </CardContent>
      </Card>
    </section>
  </PageContainer>
</template>
