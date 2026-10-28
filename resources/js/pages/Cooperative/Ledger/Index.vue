<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
  Banknote,
  CalendarCheck,
  HandCoins,
  HelpCircle,
  Info,
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
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
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
  canCorrectLedgerPayment?: boolean;
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

const pageTitle = computed(() => {
  if (filters.value.ledger_scope === "POS") return "Ledger POS";
  if (filters.value.ledger_scope === "LOAN") return "Ledger Pinjaman";
  if (filters.value.ledger_scope === "SAVINGS") return "Ledger Simpanan";
  return "Buku Besar Koperasi";
});

const pageDescription = computed(() => {
  if (filters.value.ledger_scope === "POS") {
    return "Pantau mutasi operasional POS, transaksi kasir, dan piutang belanja anggota.";
  }
  if (filters.value.ledger_scope === "LOAN") {
    return "Pantau mutasi pencairan pinjaman, angsuran pokok, jasa, dan write-off.";
  }
  if (filters.value.ledger_scope === "SAVINGS") {
    return "Pantau mutasi simpanan anggota, klasifikasi kategori, dan koreksi transaksi dari satu tampilan operasional.";
  }
  return "Pantau seluruh mutasi buku besar koperasi lintas simpanan, pinjaman, dan transaksi operasional.";
});

const totalBalanceLabel = computed(() => {
  if (filters.value.ledger_scope === "POS") return "Net Mutasi Ledger POS";
  if (filters.value.ledger_scope === "LOAN") return "Saldo Mutasi Pinjaman";
  if (filters.value.ledger_scope === "SAVINGS") return "Total Saldo Simpanan";
  return "Total Mutasi Ledger";
});

const kpiCards = computed(() => {
  const cards = [
    {
      label: totalBalanceLabel.value,
      value: totalSimpanan.value,
      meta:
        filters.value.ledger_scope === "POS"
          ? "Nilai mutasi subledger operasional; bukan omzet penjualan."
          : `${totalEntries.value} mutasi pada filter aktif`,
      icon: (filters.value.ledger_scope === "POS"
        ? Sparkles
        : Wallet) as Component,
      tone: (filters.value.ledger_scope === "POS"
        ? "violet"
        : "emerald") as Tone,
      href: ledgerIndex().url,
    },
  ];

  if (filters.value.ledger_scope === "SAVINGS" || !filters.value.ledger_scope) {
    cards.push(
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
    );
  }

  return cards;
});

const breadcrumbs = computed(() => [
  { title: "Buku Besar & Keuangan", href: "#" },
  { title: pageTitle.value, href: ledgerIndex().url },
]);

const columns: Array<{
  header: string;
  key: string;
  slot: string;
  align?: "right";
  class?: string;
  width?: string;
}> = [
  {
    header: "Tanggal",
    key: "posted_at",
    slot: "posted_at",
    class: "max-w-none! min-w-[105px] whitespace-nowrap",
  },
  {
    header: "Anggota",
    key: "member.name",
    slot: "member",
    class: "max-w-none! min-w-[150px] whitespace-normal",
  },
  {
    header: "Jenis Simpanan",
    key: "contribution_type.name",
    slot: "contribution",
    class: "max-w-none! min-w-[145px] whitespace-normal",
  },
  {
    header: "Tipe Mutasi",
    key: "entry_type",
    slot: "entry_type",
    class: "max-w-none! min-w-[150px] whitespace-normal",
  },
  {
    header: "Debit",
    key: "debit",
    slot: "debit",
    class: "max-w-none! min-w-[85px] whitespace-nowrap",
    align: "right" as const,
  },
  {
    header: "Kredit",
    key: "credit",
    slot: "credit",
    class: "max-w-none! whitespace-nowrap",
    align: "right" as const,
  },
  {
    header: "Keterangan",
    key: "description",
    slot: "description",
    width: "32%",
    class: "max-w-none! min-w-[280px] whitespace-normal",
  },
];

if (props.canCorrectLedgerPayment) {
  columns.push({
    header: "Aksi",
    key: "id",
    slot: "actions",
    class: "max-w-none! whitespace-nowrap",
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
  Boolean(props.canCorrectLedgerPayment) &&
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
  <Head :title="pageTitle" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader :title="pageTitle" :description="pageDescription" />
      <WorkspaceSummary
        :items="
          kpiCards.map((card) => ({
            label: card.label,
            value: card.value,
            hint: card.meta,
            href: card.href,
            tone: card.tone,
          }))
        "
        label="Ringkasan ledger"
      />
      <div
        v-if="filters.ledger_scope === 'POS'"
        class="flex flex-col gap-2 rounded-xl border border-violet-200/80 bg-violet-50/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-violet-900/50 dark:bg-violet-950/20"
      >
        <div class="flex items-start gap-3">
          <Info
            class="mt-0.5 size-5 shrink-0 text-violet-600 dark:text-violet-400"
          />
          <div class="text-sm text-violet-900 dark:text-violet-200">
            <span class="font-semibold">Informasi Subledger POS:</span> Nilai
            mutasi subledger operasional mencakup pencatatan penjualan, HPP,
            retur, dan rekap harian; ini bukan omzet penjualan atau laba. Untuk
            angka resmi omzet kotor (gross sales), omzet bersih (net sales), dan
            laba kotor (gross profit), silakan buka
            <Link
              href="/cooperative/pos/reports"
              class="font-semibold underline hover:text-violet-700 dark:hover:text-violet-300"
            >
              Laporan POS </Link
            >.
          </div>
        </div>
      </div>

      <Card
        data-testid="ledger-filter-card"
        class="gap-0 rounded-xl py-0 overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <CardContent
          class="flex flex-col items-stretch gap-2 p-3 xl:flex-row xl:items-center"
        >
          <div
            data-testid="ledger-filter-grid"
            class="grid flex-1 gap-2 sm:grid-cols-2 lg:grid-cols-6"
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
                aria-label="Cari anggota ledger"
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
          <Button type="button" variant="ghost" size="sm" @click="resetFilters"
            >Reset</Button
          >
        </CardContent>
      </Card>

      <section class="space-y-4">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-sm font-semibold">Mutasi Ledger</h2>
          <span class="text-xs text-zinc-500"
            >{{ currentPageCount }} dari {{ totalEntries }} mutasi</span
          >
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
            compact
            table-class="min-w-[1100px] table-auto"
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
                  'whitespace-normal px-2.5 py-0.5 text-left text-xs',
                  entryTypeTone(row.entry_type),
                ]"
              >
                {{ formatEntryType(row.entry_type) }}
              </Badge>
              <div
                v-if="!filters.ledger_scope"
                class="mt-1 text-[10px] text-zinc-500"
              >
                {{ row.ledger_scope }}
              </div>
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
                class="font-semibold text-emerald-700 dark:text-emerald-400"
              >
                {{ formatCurrency(value) }}
              </span>
              <span v-else class="text-zinc-400">-</span>
            </template>

            <template #description="{ row }">
              <span
                class="block whitespace-normal break-words leading-relaxed text-zinc-600 dark:text-zinc-400"
                :title="row.description || ''"
              >
                {{ row.description || "-" }}
              </span>
            </template>

            <template v-if="canCorrectLedgerPayment" #actions="{ row }">
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
