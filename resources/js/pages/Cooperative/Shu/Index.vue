<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import {
  BadgeDollarSign,
  CalendarCheck,
  Layers,
  LockKeyhole,
  RefreshCw,
  Sparkles,
  Star,
  Users,
} from "lucide-vue-next";
import { computed, reactive } from "vue";
import type { Component } from "vue";
import GradientKpiCard from "@/components/dashboard/GradientKpiCard.vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { close, index } from "@/routes/cooperative/shu";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc";

const props = defineProps<{ preview: any; closedPeriod: any; filters: any }>();

const filter = reactive({
  year: Number(props.filters?.year ?? new Date().getFullYear()),
  cooperative_pool: Number(props.filters?.cooperative_pool ?? 0),
  pos_profit_pool: props.filters?.pos_profit_pool ?? "",
});

const closeForm = useForm({
  year: filter.year,
  cooperative_pool: filter.cooperative_pool,
  pos_profit_pool: filter.pos_profit_pool,
});

const isClosed = computed(() => props.closedPeriod?.status === "CLOSED");
const allocations = computed(
  () =>
    (isClosed.value
      ? props.closedPeriod?.allocations
      : props.preview?.allocations) ?? [],
);
const totals = computed(() =>
  isClosed.value ? props.closedPeriod : props.preview,
);

const kpiCards = computed(() => [
  {
    label: "Pool SHU Koperasi",
    value: formatCurrency(Number(totals.value.cooperative_pool ?? 0)),
    meta: "Total dana yang dialokasikan",
    icon: BadgeDollarSign as Component,
    tone: "emerald" as Tone,
    href: index().url,
  },
  {
    label: "Total Skor SHU",
    value: String(Number(totals.value.total_shu_score ?? 0)),
    meta: "Jumlah bulan aktif + iuran lunas",
    icon: Star as Component,
    tone: "amber" as Tone,
    href: index().url,
  },
  {
    label: "Skor Keanggotaan",
    value: String(Number(totals.value.total_membership_score ?? 0)),
    meta: "Total bulan aktif seluruh anggota",
    icon: Users as Component,
    tone: "sky" as Tone,
    href: index().url,
  },
  {
    label: "Skor Iuran Wajib",
    value: String(Number(totals.value.total_dues_score ?? 0)),
    meta: "Total bulan iuran lunas",
    icon: CalendarCheck as Component,
    tone: "violet" as Tone,
    href: index().url,
  },
  {
    label: "Status Tahun",
    value: isClosed.value ? "TERTUTUP" : "PREVIEW",
    meta: isClosed.value
      ? `Ditutup ${props.closedPeriod?.closed_at ?? ""}`
      : "Belum difinalisasi",
    icon: LockKeyhole as Component,
    tone: isClosed.value ? ("rose" as Tone) : ("amber" as Tone),
    href: index().url,
  },
]);

const refreshPreview = () => {
  router.get(
    index().url,
    {
      year: filter.year,
      cooperative_pool: filter.cooperative_pool,
      pos_profit_pool: filter.pos_profit_pool || undefined,
    },
    { preserveState: true, preserveScroll: true },
  );
};

const closePeriod = () => {
  closeForm.year = filter.year;
  closeForm.cooperative_pool = filter.cooperative_pool;
  closeForm.pos_profit_pool = filter.pos_profit_pool;
  closeForm.post(close().url, { preserveScroll: true });
};
</script>

<template>
  <Head title="SHU Koperasi Tahunan" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Iuran & Simpanan', href: '#' },
      { title: 'SHU Koperasi', href: index().url },
    ]"
  >
    <PageContainer class="max-w-none">
      <section
        class="relative overflow-hidden rounded-2xl border border-violet-200/60 bg-gradient-to-br from-white via-violet-50/60 to-sky-50/40 p-6 shadow-sm shadow-violet-950/5 sm:p-7 dark:border-violet-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      >
        <div
          class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-violet-300/20 blur-3xl dark:bg-violet-500/10"
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
              class="inline-flex items-center gap-1.5 rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800 ring-1 ring-inset ring-violet-200/70 dark:bg-violet-900/40 dark:text-violet-200 dark:ring-violet-800/60"
            >
              <Sparkles class="size-3.5" />
              Alokasi Tahunan
            </span>
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
            >
              SHU Koperasi Tahunan
            </h1>
            <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
              Alokasi dari pool SHU koperasi berdasarkan bulan aktif dan
              kedisiplinan iuran wajib. Preview dulu, lalu finalisasi.
            </p>
          </div>

          <div
            class="grid min-w-0 grid-cols-2 gap-2 rounded-xl border border-white/70 bg-white/70 p-3 shadow-sm shadow-zinc-950/5 backdrop-blur sm:grid-cols-5 dark:border-zinc-800/80 dark:bg-zinc-950/40"
          >
            <Input
              id="shu_year"
              v-model.number="filter.year"
              type="number"
              min="2020"
              placeholder="Tahun"
              aria-label="Tahun SHU"
            />
            <Input
              id="shu_pool"
              v-model.number="filter.cooperative_pool"
              type="number"
              min="0"
              placeholder="Pool SHU"
              aria-label="Pool SHU koperasi"
            />
            <Input
              id="shu_pos"
              v-model="filter.pos_profit_pool"
              type="number"
              min="0"
              placeholder="Pool POS"
              aria-label="Pool POS opsional"
            />
            <Button
              type="button"
              variant="outline"
              size="sm"
              @click="refreshPreview"
            >
              <RefreshCw class="mr-1.5 h-3.5 w-3.5" />
              Preview
            </Button>
            <Button
              v-can="'manage_cooperative_shu'"
              type="button"
              size="sm"
              :disabled="isClosed || closeForm.processing"
              @click="closePeriod"
            >
              <LockKeyhole class="mr-1.5 h-3.5 w-3.5" />
              {{ closeForm.processing ? "Menyimpan…" : "Tutup" }}
            </Button>
          </div>
        </div>
      </section>

      <section
        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5"
        aria-label="Ringkasan SHU"
      >
        <GradientKpiCard
          v-for="card in kpiCards"
          :key="card.label"
          :label="card.label"
          :value="card.value"
          :meta="card.meta"
          :icon="card.icon"
          :tone="card.tone"
          :href="card.href"
        />
      </section>

      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Alokasi per Anggota"
          :description="
            allocations.length > 0
              ? `${allocations.length} anggota dialokasikan`
              : 'Belum ada anggota aktif untuk periode ini'
          "
          :icon="Layers"
          tone="violet"
        />
        <CardContent class="px-0 pb-0">
          <div class="overflow-x-auto">
            <table
              aria-label="Tabel alokasi SHU per anggota"
              class="w-full text-sm"
              role="table"
            >
              <thead
                class="border-b bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-zinc-950"
              >
                <tr>
                  <th class="px-4 py-3">Anggota</th>
                  <th class="px-4 py-3 text-right">Bulan Aktif</th>
                  <th class="px-4 py-3 text-right">Iuran Lunas</th>
                  <th class="px-4 py-3 text-right">Skor</th>
                  <th class="px-4 py-3 text-right">Alokasi SHU</th>
                </tr>
              </thead>
              <tbody
                class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70"
              >
                <tr
                  v-for="allocation in allocations"
                  :key="allocation.member.id"
                  class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-900/50"
                >
                  <td class="px-4 py-3">
                    <div class="font-semibold text-zinc-950 dark:text-white">
                      {{ allocation.member.name }}
                    </div>
                    <div class="text-xs text-zinc-500">
                      {{ allocation.member.member_no }}
                    </div>
                  </td>
                  <td class="px-4 py-3 text-right tabular-nums">
                    {{ allocation.membership_score }}
                  </td>
                  <td class="px-4 py-3 text-right tabular-nums">
                    {{ allocation.dues_score }}
                  </td>
                  <td class="px-4 py-3 text-right tabular-nums font-semibold">
                    {{ allocation.shu_score }}
                  </td>
                  <td
                    class="px-4 py-3 text-right font-bold tabular-nums text-emerald-700 dark:text-emerald-300"
                  >
                    {{ formatCurrency(allocation.cooperative_shu_amount) }}
                  </td>
                </tr>
                <tr v-if="allocations.length === 0">
                  <td colspan="5" class="px-4 py-16 text-center text-zinc-500">
                    <div class="flex flex-col items-center gap-2">
                      <Users class="size-8 text-zinc-300 dark:text-zinc-700" />
                      <p class="text-sm">
                        Belum ada anggota aktif untuk periode ini.
                      </p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </PageContainer>
  </AppLayout>
</template>
