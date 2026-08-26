<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import {
  Banknote,
  CalendarCheck,
  ClipboardList,
  Filter,
  HandCoins,
  HelpCircle,
  Landmark,
  MoreHorizontal,
  PiggyBank,
  Search,
  ScrollText,
  Sparkles,
  Wallet,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import type { Component } from "vue";
import GradientKpiCard from "@/components/dashboard/GradientKpiCard.vue";
import InputError from "@/components/InputError.vue";
import PageContainer from "@/components/PageContainer.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { useTableFilters } from "@/composables/useTableFilters";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { index as ledgerIndex } from "@/routes/cooperative/ledger";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc";

const props = defineProps<{
  entries: any;
  filters: any;
  summary: {
    total_balance: number;
    by_category: Record<string, number>;
    uncategorized: number;
  };
  contributionTypes: Array<{
    id: number;
    name: string;
    category: string;
    code?: string;
  }>;
  categories: string[];
  entryTypes: string[];
  canManageLedger: boolean;
}>();

const filters = ref({
  member_search: props.filters.member_search ?? "",
  entry_type: props.filters.entry_type ?? "",
  ledger_scope: props.filters.ledger_scope ?? "SAVINGS",
  contribution_type_id: props.filters.contribution_type_id ?? "",
  start_date: props.filters.start_date ?? "",
  end_date: props.filters.end_date ?? "",
});

const { resetFilters, isFiltering } = useTableFilters(filters, {
  route: ledgerIndex().url,
  debounceMs: 300,
  only: [
    "entries",
    "filters",
    "summary",
    "contributionTypes",
    "entryTypes",
    "categories",
  ],
});

const scopeOptions = [
  { label: "Semua scope", value: "" },
  { label: "Simpanan", value: "SAVINGS" },
  { label: "Pinjaman", value: "LOAN" },
  { label: "POS", value: "POS" },
];

const contributionTypeOptions = computed(() => [
  { label: "Semua jenis simpanan", value: "" },
  ...props.contributionTypes.map((type) => ({
    label: type.name,
    value: type.id,
  })),
]);

const entryTypeOptions = computed(() => [
  { label: "Semua tipe mutasi", value: "" },
  ...props.entryTypes.map((type) => ({ label: type, value: type })),
]);

const ENTRY_TYPE_LABELS: Record<string, string> = {
  SAVING_PAYMENT: "Pembayaran Simpanan",
  OPENING_BALANCE: "Saldo Awal",
  LOAN_DISBURSEMENT: "Pencairan Pinjaman",
  POS_MEMBER_CREDIT: "Kredit POS",
  SAVINGS_WITHDRAWAL: "Penarikan Simpanan",
  MEMBER_RESIGNATION_SETTLEMENT: "Penyelesaian Resign",
};

const SCOPE_TONE: Record<string, string> = {
  SAVINGS:
    "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30",
  LOAN: "bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 border-blue-200 dark:border-blue-500/30",
  POS: "bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 border-purple-200 dark:border-purple-500/30",
};

const CATEGORY_TONE: Record<string, string> = {
  POKOK:
    "bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300 border-indigo-200 dark:border-indigo-500/30",
  WAJIB:
    "bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 border-amber-200 dark:border-amber-500/30",
  SUKARELA:
    "bg-teal-100 text-teal-700 dark:bg-teal-500/20 dark:text-teal-300 border-teal-200 dark:border-teal-500/30",
  KHUSUS:
    "bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border-rose-200 dark:border-rose-500/30",
};

const ENTRY_TYPE_TONE: Record<string, string> = {
  SAVING_PAYMENT:
    "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30",
  OPENING_BALANCE:
    "bg-zinc-100 text-zinc-700 dark:bg-zinc-500/20 dark:text-zinc-300 border-zinc-200 dark:border-zinc-500/30",
  LOAN_DISBURSEMENT:
    "bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 border-blue-200 dark:border-blue-500/30",
  POS_MEMBER_CREDIT:
    "bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 border-purple-200 dark:border-purple-500/30",
  SAVINGS_WITHDRAWAL:
    "bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border-rose-200 dark:border-rose-500/30",
};

const formatEntryType = (value: string | null | undefined) =>
  ENTRY_TYPE_LABELS[value ?? ""] ?? value ?? "-";

const scopeTone = (value: string | null | undefined) =>
  SCOPE_TONE[value ?? ""] ??
  "bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700";

const categoryTone = (code: string | null | undefined) =>
  CATEGORY_TONE[code ?? ""] ??
  "bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700";

const entryTypeTone = (value: string | null | undefined) =>
  ENTRY_TYPE_TONE[value ?? ""] ??
  "bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700";

const totalSimpanan = computed(() =>
  formatCurrency(props.summary.total_balance),
);
const uncategorizedLabel = formatCurrency(props.summary.uncategorized);
const totalEntries = computed(() => Number(props.entries?.total ?? 0));
const currentPageCount = computed(() =>
  Number(props.entries?.data?.length ?? 0),
);

const contributionTypeStats = computed(() =>
  props.contributionTypes.map((type) => ({
    ...type,
    value: formatCurrency(props.summary.by_category?.[type.category] ?? 0),
    rawValue: Number(props.summary.by_category?.[type.category] ?? 0),
    icon:
      type.code === "POKOK"
        ? (Landmark as Component)
        : type.code === "WAJIB"
          ? (CalendarCheck as Component)
          : type.code === "SUKARELA"
            ? (HandCoins as Component)
            : (Banknote as Component),
    tone:
      type.code === "POKOK"
        ? ("violet" as Tone)
        : type.code === "WAJIB"
          ? ("amber" as Tone)
          : type.code === "SUKARELA"
            ? ("sky" as Tone)
            : ("emerald" as Tone),
  })),
);

const kpiCards = computed(() => [
  {
    label: "Total Simpanan",
    value: totalSimpanan.value,
    meta: `${totalEntries.value} mutasi pada filter aktif`,
    icon: Wallet as Component,
    tone: "emerald" as Tone,
    href: ledgerIndex().url,
  },
  ...contributionTypeStats.value.map((stat) => ({
    label: stat.name,
    value: stat.value,
    meta: stat.code ? `Kategori ${stat.code}` : "Kategori simpanan",
    icon: stat.icon,
    tone: stat.tone,
    href: ledgerIndex({
      query: {
        ledger_scope: "SAVINGS",
        contribution_type_id: stat.id,
      },
    }).url,
  })),
  {
    label: "Belum Dikategorikan",
    value: uncategorizedLabel,
    meta: "Butuh pengecekan mapping jenis simpanan",
    icon: HelpCircle as Component,
    tone: "zinc" as Tone,
    href: ledgerIndex().url,
  },
]);

const breadcrumbs = [
  { title: "Iuran & Simpanan", href: "#" },
  { title: "Ledger Simpanan", href: ledgerIndex().url },
];

const columns = [
  { header: "Tanggal", key: "posted_at", slot: "posted_at" },
  { header: "Anggota", key: "member.name", slot: "member" },
  {
    header: "Jenis Simpanan",
    key: "contribution_type.name",
    slot: "contribution",
  },
  { header: "Tipe Mutasi", key: "entry_type", slot: "entry_type" },
  { header: "Scope", key: "ledger_scope", slot: "scope" },
  { header: "Debit", key: "debit", slot: "debit", align: "right" as const },
  { header: "Kredit", key: "credit", slot: "credit", align: "right" as const },
  { header: "Keterangan", key: "description", slot: "description" },
];

if (props.canManageLedger) {
  columns.push({
    header: "Aksi",
    key: "id",
    slot: "actions",
    align: "right" as const,
  });
}

const revisionDialogOpen = ref(false);
const cancelDialogOpen = ref(false);
const selectedEntry = ref<any>(null);
const selectedCancelEntry = ref<any>(null);

const revisionForm = useForm({
  amount: "",
  payment_method: "CASH",
  paid_at: "",
  notes: "",
  reason: "",
});

const cancelForm = useForm({
  reason: "",
});

const canCorrectEntry = (entry: any) =>
  props.canManageLedger &&
  entry.entry_type === "SAVING_PAYMENT" &&
  Boolean(entry.cooperative_payment_id);

const openRevisionDialog = (entry: any) => {
  selectedEntry.value = entry;
  revisionForm.clearErrors();
  revisionForm.amount = String(Number(entry.credit || entry.debit || 0));
  revisionForm.payment_method = entry.payment?.payment_method ?? "CASH";
  revisionForm.paid_at = String(entry.posted_at ?? "").slice(0, 10);
  revisionForm.notes = entry.description ?? "";
  revisionForm.reason = "";
  revisionDialogOpen.value = true;
};

const submitRevision = () => {
  if (!selectedEntry.value) {
    return;
  }

  revisionForm.post(
    `/cooperative/ledger/${selectedEntry.value.id}/revise-payment`,
    {
      preserveScroll: true,
      onSuccess: () => {
        revisionDialogOpen.value = false;
        selectedEntry.value = null;
        revisionForm.reset();
      },
    },
  );
};

const openCancelDialog = (entry: any) => {
  selectedCancelEntry.value = entry;
  cancelForm.clearErrors();
  cancelForm.reason = "";
  cancelDialogOpen.value = true;
};

const submitCancel = () => {
  if (!selectedCancelEntry.value || !cancelForm.reason.trim()) {
    return;
  }

  cancelForm.post(
    `/cooperative/ledger/${selectedCancelEntry.value.id}/cancel-payment`,
    {
      preserveScroll: true,
      onSuccess: () => {
        cancelDialogOpen.value = false;
        selectedCancelEntry.value = null;
        cancelForm.reset();
      },
    },
  );
};

const memberLabel = (entry: any) =>
  entry.member?.name || entry.member?.nama_anggota || "-";
const memberNo = (entry: any) =>
  entry.member?.member_no || entry.member?.no_anggota || "-";
</script>

<template>
  <Head title="Ledger Simpanan" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <PageContainer class="max-w-none">
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
              <Sparkles class="size-3.5" />
              Buku Besar Koperasi
            </span>
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
            >
              Ledger Simpanan
            </h1>
            <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
              Pantau mutasi simpanan anggota, klasifikasi kategori, dan koreksi
              transaksi dari satu tampilan operasional.
            </p>
          </div>
          <div
            class="grid min-w-0 grid-cols-2 gap-2 rounded-xl border border-white/70 bg-white/70 p-3 shadow-sm shadow-zinc-950/5 backdrop-blur dark:border-zinc-800/80 dark:bg-zinc-950/40"
          >
            <div class="min-w-0">
              <p class="text-[11px] font-medium uppercase text-zinc-500">
                Mutasi
              </p>
              <p
                class="mt-1 text-lg font-bold tabular-nums text-zinc-950 dark:text-white"
              >
                {{ totalEntries }}
              </p>
            </div>
            <div class="min-w-0">
              <p class="text-[11px] font-medium uppercase text-zinc-500">
                Ditampilkan
              </p>
              <p
                class="mt-1 text-lg font-bold tabular-nums text-zinc-950 dark:text-white"
              >
                {{ currentPageCount }}
              </p>
            </div>
          </div>
        </div>
      </section>

      <section
        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5"
        aria-label="Ringkasan ledger simpanan"
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
        data-testid="ledger-filter-card"
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <div
          class="flex flex-col gap-4 border-b border-zinc-200/70 px-5 py-4 lg:flex-row lg:items-center lg:justify-between dark:border-zinc-800/70"
        >
          <div class="flex items-start gap-3">
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60"
            >
              <Filter class="size-4" />
            </span>
            <div>
              <h2
                class="text-base font-semibold tracking-tight text-zinc-950 dark:text-white"
              >
                Filter Ledger
              </h2>
              <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                Saring berdasarkan anggota, scope, jenis simpanan, tipe mutasi,
                atau rentang tanggal.
              </p>
            </div>
          </div>
          <Button
            type="button"
            variant="ghost"
            size="sm"
            class="self-start text-zinc-500 hover:text-zinc-700 lg:self-auto dark:hover:text-zinc-200"
            @click="resetFilters"
          >
            Reset
          </Button>
        </div>
        <CardContent class="p-5">
          <div
            data-testid="ledger-filter-grid"
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6"
          >
            <div class="relative sm:col-span-2 lg:col-span-1">
              <Search
                class="pointer-events-none absolute inset-y-0 left-3 my-auto size-4 text-zinc-400"
                aria-hidden="true"
              />
              <Input
                v-model="filters.member_search"
                type="search"
                placeholder="Cari nama / no anggota"
                class="h-9 pl-9"
              />
            </div>
            <SelectFilter
              v-model="filters.ledger_scope"
              :options="scopeOptions"
              placeholder="Pilih scope"
              class="w-full lg:col-span-1"
            />
            <SelectFilter
              v-model="filters.contribution_type_id"
              :options="contributionTypeOptions"
              placeholder="Pilih jenis simpanan"
              class="w-full lg:col-span-1"
            />
            <SelectFilter
              v-model="filters.entry_type"
              :options="entryTypeOptions"
              placeholder="Pilih tipe mutasi"
              class="w-full lg:col-span-1"
            />
            <Input
              id="start_date"
              v-model="filters.start_date"
              type="date"
              aria-label="Tanggal mulai"
              title="Tanggal mulai"
              class="w-full lg:col-span-1"
            />
            <Input
              id="end_date"
              v-model="filters.end_date"
              type="date"
              aria-label="Tanggal sampai"
              title="Tanggal sampai"
              class="w-full lg:col-span-1"
            />
          </div>
        </CardContent>
      </Card>

      <section class="space-y-4">
        <div
          class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex items-start gap-3">
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 ring-1 ring-inset ring-violet-200/70 dark:bg-violet-900/40 dark:text-violet-300 dark:ring-violet-900/60"
            >
              <ClipboardList class="size-4" />
            </span>
            <div>
              <h2
                class="text-base font-semibold tracking-tight text-zinc-950 sm:text-lg dark:text-white"
              >
                Mutasi Ledger
              </h2>
              <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                {{ currentPageCount }} dari {{ totalEntries }} mutasi pada
                filter aktif
              </p>
            </div>
          </div>
        </div>

        <div class="relative">
          <div
            v-if="isFiltering"
            class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center bg-white/40 backdrop-blur-[1px] dark:bg-zinc-900/40"
          >
            <div
              class="flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs text-zinc-600 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
            >
              <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500" />
              Memperbarui data…
            </div>
          </div>

          <DataTable
            :columns="columns"
            :data="entries"
            :searchable="false"
            :empty-icon="ScrollText"
            empty-message="Belum ada transaksi ledger pada filter saat ini."
          >
            <template #posted_at="{ row }">
              <div class="font-medium text-zinc-900 dark:text-zinc-100">
                {{ formatDate(row.posted_at) }}
              </div>
              <div class="text-xs text-zinc-500">
                {{ row.period }}
              </div>
            </template>

            <template #member="{ row }">
              <div class="font-medium text-zinc-900 dark:text-zinc-100">
                {{ memberLabel(row) }}
              </div>
              <div class="text-xs text-zinc-500">
                {{ memberNo(row) }}
              </div>
            </template>

            <template #contribution="{ row }">
              <div>
                {{
                  row.contribution_type?.name || row.category_snapshot || "-"
                }}
              </div>
              <div
                v-if="row.contribution_type?.code || row.category_snapshot"
                class="mt-1"
              >
                <Badge
                  variant="outline"
                  :class="[
                    'px-2 py-0 text-[10px] font-medium',
                    categoryTone(
                      row.contribution_type?.code ?? row.category_snapshot,
                    ),
                  ]"
                >
                  {{ row.contribution_type?.code ?? row.category_snapshot }}
                </Badge>
              </div>
            </template>

            <template #entry_type="{ row }">
              <Badge
                variant="outline"
                :class="[
                  'px-2.5 py-0.5 text-xs',
                  entryTypeTone(row.entry_type),
                ]"
              >
                {{ formatEntryType(row.entry_type) }}
              </Badge>
            </template>

            <template #scope="{ row }">
              <Badge
                variant="outline"
                :class="['px-2.5 py-0.5 text-xs', scopeTone(row.ledger_scope)]"
              >
                {{ row.ledger_scope || "-" }}
              </Badge>
            </template>

            <template #debit="{ value }">
              <span
                v-if="Number(value) > 0"
                class="font-semibold text-rose-600 dark:text-rose-400"
              >
                {{ formatCurrency(value) }}
              </span>
              <span v-else class="text-zinc-400">-</span>
            </template>

            <template #credit="{ value }">
              <span
                v-if="Number(value) > 0"
                class="font-semibold text-emerald-600 dark:text-emerald-400"
              >
                {{ formatCurrency(value) }}
              </span>
              <span v-else class="text-zinc-400">-</span>
            </template>

            <template #description="{ row }">
              <span
                class="block max-w-[280px] truncate text-zinc-600 dark:text-zinc-400"
                :title="row.description || ''"
              >
                {{ row.description || "-" }}
              </span>
            </template>

            <template v-if="canManageLedger" #actions="{ row }">
              <div v-if="canCorrectEntry(row)" class="flex justify-end">
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button
                      type="button"
                      size="icon"
                      variant="ghost"
                      class="h-8 w-8"
                      :aria-label="`Aksi untuk mutasi ${row.id}`"
                    >
                      <MoreHorizontal class="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" class="w-44">
                    <DropdownMenuItem
                      class="cursor-pointer"
                      @select="openRevisionDialog(row)"
                    >
                      <PiggyBank class="h-4 w-4" />
                      Revisi
                    </DropdownMenuItem>
                    <DropdownMenuItem
                      variant="destructive"
                      class="cursor-pointer"
                      @select="openCancelDialog(row)"
                    >
                      <Banknote class="h-4 w-4" />
                      Batalkan
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
              <span v-else class="text-xs text-zinc-400">-</span>
            </template>
          </DataTable>
        </div>
      </section>
    </PageContainer>
  </AppLayout>

  <Dialog v-model:open="revisionDialogOpen">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>Revisi Transaksi Ledger</DialogTitle>
        <DialogDescription>
          Perubahan akan memperbarui payment, ledger, invoice, dan receipt
          terkait. Pastikan periode yang dipilih masih terbuka.
        </DialogDescription>
      </DialogHeader>

      <form class="space-y-4" @submit.prevent="submitRevision">
        <div
          v-if="selectedEntry"
          class="rounded-lg border border-zinc-200 bg-zinc-50/60 px-3 py-2 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900/40 dark:text-zinc-300"
        >
          <div class="flex items-center justify-between">
            <span class="font-medium">{{ memberLabel(selectedEntry) }}</span>
            <span class="text-zinc-500">{{ memberNo(selectedEntry) }}</span>
          </div>
          <div class="mt-0.5">
            <span class="text-zinc-500">Nominal sebelumnya:</span>
            <span class="ml-1 font-semibold text-zinc-700 dark:text-zinc-200">
              {{
                formatCurrency(
                  Number(selectedEntry.credit || selectedEntry.debit || 0),
                )
              }}
            </span>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="space-y-2">
            <Label for="revision_amount">Nominal</Label>
            <Input
              id="revision_amount"
              v-model="revisionForm.amount"
              type="number"
              min="1"
              required
            />
            <InputError :message="revisionForm.errors.amount" />
          </div>
          <div class="space-y-2">
            <Label for="revision_paid_at">Tanggal</Label>
            <Input
              id="revision_paid_at"
              v-model="revisionForm.paid_at"
              type="date"
              required
            />
            <InputError :message="revisionForm.errors.paid_at" />
          </div>
        </div>

        <div class="space-y-2">
          <Label for="revision_payment_method">Metode Pembayaran</Label>
          <select
            id="revision_payment_method"
            v-model="revisionForm.payment_method"
            class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-300 dark:border-zinc-800 dark:bg-zinc-950"
            required
          >
            <option value="CASH">Tunai</option>
            <option value="TRANSFER">Transfer</option>
            <option value="QRIS">QRIS</option>
          </select>
          <InputError :message="revisionForm.errors.payment_method" />
        </div>

        <div class="space-y-2">
          <Label for="revision_notes">Keterangan Transaksi</Label>
          <Textarea
            id="revision_notes"
            v-model="revisionForm.notes"
            :rows="3"
          />
          <InputError :message="revisionForm.errors.notes" />
        </div>

        <div class="space-y-2">
          <Label for="revision_reason">Alasan Revisi</Label>
          <Textarea
            id="revision_reason"
            v-model="revisionForm.reason"
            :rows="3"
            required
          />
          <InputError :message="revisionForm.errors.reason" />
          <InputError :message="(revisionForm.errors as any).ledger_entry" />
        </div>

        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            @click="revisionDialogOpen = false"
          >
            Batal
          </Button>
          <Button type="submit" :disabled="revisionForm.processing">
            {{ revisionForm.processing ? "Menyimpan…" : "Simpan Revisi" }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>

  <Dialog v-model:open="cancelDialogOpen">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>Batalkan Transaksi</DialogTitle>
        <DialogDescription>
          Transaksi ini akan di-void, ledger entries akan dihapus, dan invoice
          akan disesuaikan ulang. Tindakan ini tidak dapat dibatalkan.
        </DialogDescription>
      </DialogHeader>

      <div
        v-if="selectedCancelEntry"
        class="rounded-lg border border-zinc-200 bg-zinc-50/60 px-3 py-2 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900/40 dark:text-zinc-300"
      >
        <div class="flex items-center justify-between">
          <span class="font-medium">{{
            memberLabel(selectedCancelEntry)
          }}</span>
          <span class="text-zinc-500">{{ memberNo(selectedCancelEntry) }}</span>
        </div>
        <div class="mt-0.5">
          <span class="text-zinc-500">Nominal:</span>
          <span class="ml-1 font-semibold text-zinc-700 dark:text-zinc-200">
            {{
              formatCurrency(
                Number(
                  selectedCancelEntry.credit || selectedCancelEntry.debit || 0,
                ),
              )
            }}
          </span>
          <span class="ml-2 text-zinc-500">
            ({{ formatDate(selectedCancelEntry.posted_at) }})
          </span>
        </div>
      </div>

      <form class="space-y-4" @submit.prevent="submitCancel">
        <div class="space-y-2">
          <Label for="cancel_reason">Alasan Pembatalan</Label>
          <Textarea
            id="cancel_reason"
            v-model="cancelForm.reason"
            :rows="3"
            required
            placeholder="Contoh: Salah input nominal, transaksi duplikat, dll."
          />
          <InputError :message="cancelForm.errors.reason" />
        </div>

        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            @click="cancelDialogOpen = false"
          >
            Tutup
          </Button>
          <Button
            type="submit"
            variant="destructive"
            :disabled="cancelForm.processing || !cancelForm.reason.trim()"
          >
            {{ cancelForm.processing ? "Membatalkan…" : "Batalkan Transaksi" }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
