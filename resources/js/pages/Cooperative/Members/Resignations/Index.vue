<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import {
  Ban,
  CalendarClock,
  CheckCircle2,
  ClipboardCheck,
  Hash,
  Mail,
  Phone,
  Search,
  ShieldCheck,
  UserCheck,
  UserMinus,
  UserRound,
  UserX,
  X,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import type { Component } from "vue";
import GradientKpiCard from "@/components/dashboard/GradientKpiCard.vue";
import StatusPill from "@/components/dashboard/StatusPill.vue";
import PageContainer from "@/components/PageContainer.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { useTableFilters } from "@/composables/useTableFilters";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatNumber } from "@/lib/formatters";
import {
  index as resignationsIndex,
  process as processResignation,
} from "@/routes/cooperative/members/resignations";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc";

const props = defineProps<{
  requests: any;
  filters?: { search?: string; status?: string };
  stats?: {
    pending?: number;
    approved?: number;
    rejected?: number;
    cancelled?: number;
    total?: number;
  };
}>();

const filters = ref({
  search: props.filters?.search ?? "",
  status: props.filters?.status ?? "",
});

const activeId = ref<number | null>(null);
const form = useForm({ decision: "APPROVE", review_notes: "" });

const { resetFilters } = useTableFilters(filters, {
  route: resignationsIndex().url,
  debounceMs: 400,
  only: ["requests", "filters", "stats"],
});

const items = computed(() => props.requests?.data ?? []);
const stats = computed(() => props.stats ?? {});

const statusOptions = [
  { label: "Semua status", value: "" },
  { label: "Menunggu", value: "PENDING" },
  { label: "Disetujui", value: "APPROVED" },
  { label: "Ditolak", value: "REJECTED" },
  { label: "Dibatalkan", value: "CANCELLED" },
];

const statusTone = (status: string): Tone => {
  switch (status) {
    case "PENDING":
      return "amber";
    case "APPROVED":
      return "emerald";
    case "REJECTED":
      return "rose";
    case "CANCELLED":
      return "zinc";
    default:
      return "zinc";
  }
};

const statusLabel = (status: string): string => {
  switch (status) {
    case "PENDING":
      return "Menunggu";
    case "APPROVED":
      return "Disetujui";
    case "REJECTED":
      return "Ditolak";
    case "CANCELLED":
      return "Dibatalkan";
    default:
      return status;
  }
};

const memberName = (item: any): string =>
  item.member?.nama_anggota_clean ||
  item.member?.nama_anggota ||
  item.member?.name ||
  "Tanpa nama";

const formatDate = (value?: string | null) =>
  value ? new Date(value).toLocaleString("id-ID", { dateStyle: "medium", timeStyle: "short" }) : "-";

const process = (id: number, decision: "APPROVE" | "REJECT") => {
  activeId.value = id;
  form.decision = decision;
  form.post(processResignation(id).url, {
    preserveScroll: true,
    onFinish: () => {
      activeId.value = null;
      form.review_notes = "";
    },
  });
};

const avatarTones: Tone[] = ["sky", "violet", "emerald", "amber", "rose"];
const avatarToneFor = (name: string): Tone => {
  const trimmed = (name || "").trim();
  if (!trimmed) return "zinc";
  let sum = 0;
  for (let i = 0; i < trimmed.length; i++) sum += trimmed.charCodeAt(i);
  return avatarTones[sum % avatarTones.length];
};

const initialsOf = (name: string): string => {
  const parts = (name || "").trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return "??";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const avatarBgClass: Record<Tone, string> = {
  emerald:
    "bg-emerald-100 text-emerald-700 ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60",
  amber:
    "bg-amber-100 text-amber-700 ring-amber-200/70 dark:bg-amber-900/40 dark:text-amber-300 dark:ring-amber-900/60",
  rose: "bg-rose-100 text-rose-700 ring-rose-200/70 dark:bg-rose-900/40 dark:text-rose-300 dark:ring-rose-900/60",
  sky: "bg-sky-100 text-sky-700 ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60",
  violet:
    "bg-violet-100 text-violet-700 ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/60",
  zinc: "bg-zinc-100 text-zinc-700 ring-zinc-200/70 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700/60",
};

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

const kpiCards = computed(() => {
  const baseQuery = (status: string): string => {
    const params = new URLSearchParams();
    params.set("status", status);

    return `${resignationsIndex().url}?${params.toString()}`;
  };

  return [
    {
      label: "Menunggu",
      value: formatNumber(stats.value.pending ?? 0),
      icon: ClipboardCheck as Component,
      tone: "amber" as Tone,
      href: baseQuery("PENDING"),
      sparklinePoints: sparklineFor(stats.value.pending ?? 0),
      meta: "Perlu ditinjau",
    },
    {
      label: "Disetujui",
      value: formatNumber(stats.value.approved ?? 0),
      icon: UserCheck as Component,
      tone: "emerald" as Tone,
      href: baseQuery("APPROVED"),
      sparklinePoints: sparklineFor(stats.value.approved ?? 0),
      meta: "Anggota resign",
    },
    {
      label: "Ditolak",
      value: formatNumber(stats.value.rejected ?? 0),
      icon: ShieldCheck as Component,
      tone: "rose" as Tone,
      href: baseQuery("REJECTED"),
      sparklinePoints: sparklineFor(stats.value.rejected ?? 0),
      meta: "Pengajuan ditolak",
    },
    {
      label: "Dibatalkan",
      value: formatNumber(stats.value.cancelled ?? 0),
      icon: Ban as Component,
      tone: "zinc" as Tone,
      href: baseQuery("CANCELLED"),
      sparklinePoints: sparklineFor(stats.value.cancelled ?? 0),
      meta: "Dicabut anggota",
    },
  ];
});

const activeFilterChips = computed(() => {
  const chips: Array<{ key: string; label: string; tone: Tone }> = [];
  if (filters.value.status) {
    chips.push({
      key: "status",
      label: `Status: ${statusLabel(filters.value.status)}`,
      tone: statusTone(filters.value.status),
    });
  }
  if (filters.value.search) {
    chips.push({ key: "search", label: `Cari: "${filters.value.search}"`, tone: "sky" });
  }
  return chips;
});

const clearFilter = (key: string): void => {
  (filters.value as Record<string, string>)[key] = "";
};

watch(
  () => props.filters,
  (next) => {
    filters.value.search = next?.search ?? "";
    filters.value.status = next?.status ?? "";
  },
);
</script>

<template>
  <Head title="Pengunduran Diri Anggota" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Keanggotaan', href: '#' },
      { title: 'Pengunduran Diri', href: '#' },
    ]"
  >
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
        <div
          class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
        >
          <div class="space-y-3">
            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-800/60"
            >
              <UserMinus class="size-3.5" />
              Manajemen Keanggotaan
            </span>
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
            >
              Pengunduran Diri Anggota
            </h1>
            <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
              Tinjau dan putuskan pengajuan pengunduran diri anggota. Persetujuan
              akhir mengubah status anggota menjadi
              <strong class="font-semibold text-zinc-900 dark:text-zinc-100">RESIGNED</strong>.
              Total
              <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{
                formatNumber(stats.total ?? 0)
              }}</span>
              pengajuan tercatat.
            </p>
          </div>
          <div
            v-if="(stats.pending ?? 0) > 0"
            class="flex items-center gap-2 rounded-xl border border-amber-200/70 bg-amber-50/80 px-4 py-2.5 text-sm font-medium text-amber-800 shadow-sm shadow-amber-950/5 backdrop-blur dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200"
          >
            <ClipboardCheck class="size-4 shrink-0" />
            {{ formatNumber(stats.pending ?? 0) }} pengajuan menunggu tinjauan
          </div>
        </div>
      </section>

      <!-- KPI BAND -->
      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan pengunduran diri">
        <GradientKpiCard
          v-for="card in kpiCards"
          :key="card.label"
          :label="card.label"
          :value="card.value"
          :meta="card.meta"
          :icon="card.icon"
          :tone="card.tone"
          :href="card.href"
          :sparkline-points="card.sparklinePoints"
        />
      </section>

      <!-- FILTER + LIST -->
      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <div
          class="flex flex-col gap-3 border-b border-zinc-200/70 px-5 py-4 lg:flex-row lg:items-center lg:justify-between dark:border-zinc-800/70"
        >
          <div class="flex items-start gap-3">
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60"
            >
              <UserRound class="size-4" />
            </span>
            <div>
              <h2
                class="text-base font-semibold tracking-tight text-zinc-950 dark:text-white"
              >
                Daftar Pengajuan
              </h2>
              <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                Cari dan tinjau pengajuan pengunduran diri anggota.
              </p>
            </div>
          </div>
          <div
            class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3"
          >
            <div class="relative w-full sm:max-w-xs">
              <Search
                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"
                aria-hidden="true"
              />
              <Input
                v-model="filters.search"
                type="search"
                placeholder="Cari nama / nomor anggota…"
                class="pl-9"
                aria-label="Cari pengajuan"
              />
            </div>
            <SelectFilter
              v-model="filters.status"
              :options="statusOptions"
              placeholder="Status"
              class="w-full sm:w-44"
            />
            <Button
              variant="ghost"
              size="sm"
              class="shrink-0 text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-200"
              type="button"
              @click="resetFilters"
            >
              Reset
            </Button>
          </div>
        </div>

        <div
          v-if="activeFilterChips.length > 0"
          class="flex flex-wrap items-center gap-2 border-b border-zinc-200/70 bg-zinc-50/60 px-5 py-2.5 dark:border-zinc-800/70 dark:bg-zinc-950/40"
        >
          <span
            class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
          >
            Filter aktif
          </span>
          <button
            v-for="chip in activeFilterChips"
            :key="chip.key"
            type="button"
            class="group inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset transition hover:opacity-80"
            :class="avatarBgClass[chip.tone]"
            @click="clearFilter(chip.key)"
          >
            {{ chip.label }}
            <X class="size-3 transition-transform group-hover:scale-110" />
          </button>
        </div>

        <!-- LIST BODY -->
        <div class="p-4 sm:p-5">
          <div
            v-if="items.length === 0"
            class="rounded-xl border border-dashed border-zinc-200 bg-white/60 p-10 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/40"
          >
            <UserMinus class="mx-auto mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
            <p class="font-medium text-zinc-600 dark:text-zinc-300">
              Tidak ada pengajuan pengunduran diri
            </p>
            <p class="mt-1 text-xs">
              Pengajuan baru dari anggota akan muncul di sini untuk ditinjau.
            </p>
          </div>

          <div v-else class="grid gap-3">
            <article
              v-for="item in items"
              :key="item.id"
              class="group rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm transition hover:shadow-md hover:shadow-zinc-950/5 sm:p-5 dark:border-zinc-800/80 dark:bg-zinc-900/60"
            >
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <!-- Member identity -->
                <div class="flex min-w-0 items-start gap-3">
                  <span
                    :class="[
                      'inline-flex size-11 shrink-0 items-center justify-center rounded-full text-sm font-bold ring-2 ring-white dark:ring-zinc-900',
                      avatarBgClass[avatarToneFor(memberName(item))],
                    ]"
                    aria-hidden="true"
                  >
                    {{ initialsOf(memberName(item)) }}
                  </span>
                  <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <h3
                        class="truncate text-sm font-semibold text-zinc-950 dark:text-white"
                      >
                        {{ memberName(item) }}
                      </h3>
                      <span
                        class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                      >
                        <Hash class="size-3" />
                        {{ item.member?.member_code || item.member?.no_anggota || item.member?.member_no || "-" }}
                      </span>
                      <StatusPill
                        :tone="statusTone(item.status)"
                        :label="statusLabel(item.status)"
                      />
                    </div>
                    <p class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                      <span class="inline-flex items-center gap-1">
                        <CalendarClock class="size-3.5" />
                        Diajukan {{ formatDate(item.requested_at || item.created_at) }}
                      </span>
                      <span v-if="item.effective_date" class="inline-flex items-center gap-1">
                        · Efektif {{ new Date(item.effective_date).toLocaleDateString("id-ID") }}
                      </span>
                    </p>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                      <span
                        v-if="item.member?.email"
                        class="inline-flex items-center gap-1 truncate"
                      >
                        <Mail class="size-3.5 shrink-0" />
                        {{ item.member.email }}
                      </span>
                      <span
                        v-if="item.member?.phone || item.member?.no_telp"
                        class="inline-flex items-center gap-1"
                      >
                        <Phone class="size-3.5 shrink-0" />
                        {{ item.member.phone || item.member.no_telp }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Decision meta (non-pending) -->
                <div
                  v-if="item.status !== 'PENDING' && (item.reviewed_at || item.reviewer)"
                  class="flex shrink-0 items-center gap-2 rounded-lg bg-zinc-50 px-3 py-1.5 text-xs text-zinc-500 dark:bg-zinc-950/40 dark:text-zinc-400"
                >
                  <CheckCircle2
                    class="size-3.5"
                    :class="item.status === 'APPROVED' ? 'text-emerald-600' : 'text-rose-600'"
                  />
                  <span>
                    Diputuskan {{ formatDate(item.reviewed_at) }}
                    <span v-if="item.reviewer"> oleh {{ item.reviewer.name }}</span>
                  </span>
                </div>
              </div>

              <!-- Reason -->
              <div
                v-if="item.reason"
                class="mt-3 rounded-xl border border-zinc-100 bg-zinc-50/60 p-3 text-sm dark:border-zinc-800/60 dark:bg-zinc-950/30"
              >
                <div class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                  Alasan anggota
                </div>
                <p class="text-zinc-700 dark:text-zinc-200">{{ item.reason }}</p>
                <p v-if="item.review_notes" class="mt-2 border-t border-zinc-200/70 pt-2 text-xs text-zinc-500 dark:border-zinc-800/70">
                  Catatan: {{ item.review_notes }}
                </p>
              </div>

              <!-- Actions -->
              <div v-if="item.status === 'PENDING'" class="mt-3 space-y-2">
                <Input
                  v-model="form.review_notes"
                  placeholder="Catatan tinjauan (wajib jika menolak)"
                />
                <div class="flex flex-wrap gap-2">
                  <Button
                    class="flex-1 bg-emerald-700 shadow-emerald-950/20 hover:bg-emerald-800"
                    :disabled="form.processing && activeId === item.id"
                    @click="process(item.id, 'APPROVE')"
                  >
                    <CheckCircle2 class="mr-1.5 size-4" /> Setujui & Proses
                  </Button>
                  <Button
                    class="flex-1"
                    variant="destructive"
                    :disabled="
                      (form.processing && activeId === item.id) ||
                      (form.review_notes || '').length < 3
                    "
                    @click="process(item.id, 'REJECT')"
                  >
                    <UserX class="mr-1.5 size-4" /> Tolak
                  </Button>
                </div>
              </div>
            </article>
          </div>

          <!-- Pagination -->
          <div
            v-if="requests?.last_page > 1"
            class="mt-5 flex flex-wrap items-center justify-center gap-2 text-sm"
          >
            <Link
              v-for="link in requests.links"
              :key="link.label"
              :href="link.url || '#'"
              :class="[
                'rounded-lg border px-3 py-1.5 transition',
                link.active
                  ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm'
                  : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800',
                !link.url && 'pointer-events-none opacity-40',
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </Card>
    </PageContainer>
  </AppLayout>
</template>
