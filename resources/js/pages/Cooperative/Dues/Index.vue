<script setup lang="ts">
import { Deferred, Head, Link, router, useForm } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import {
  ArrowDown,
  ArrowUp,
  Banknote,
  Building2,
  CheckCircle2,
  ClipboardList,
  CreditCard,
  FileText,
  Filter,
  Hash,
  Info,
  Layers,
  Receipt,
  RotateCcw,
  Search,
  Settings2,
  Sparkles,
  Wallet,
  X,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import type { Component } from "vue";
import GradientKpiCard from "@/components/dashboard/GradientKpiCard.vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import StatusPill from "@/components/dashboard/StatusPill.vue";
import EmptyState from "@/components/EmptyState.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
} from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatNumber } from "@/lib/formatters";
import {
  index,
  markPaid,
  markUnpaid,
} from "@/routes/cooperative/dues";
import { edit as editSavings } from "@/routes/settings/savings";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc";

const emptyValue = "__all__";

const props = defineProps<{
  invoices: any;
  filters: {
    period?: string;
    status?: string;
    member_id?: number | null;
    member_search?: string;
    contribution_type_id?: string | number;
    category?: string;
  };
  contributionTypes: any[];
  categories: string[];
  canResetPaidDues: boolean;
  stats?: {
    total_invoices: number;
    total_nominal: number;
    total_paid: number;
    total_outstanding: number;
    paid_count: number;
  };
}>();

const period = ref(
  props.filters.period ?? new Date().toISOString().slice(0, 7),
);
const memberSearch = ref(props.filters.member_search ?? "");
const memberId = ref<number | null>(props.filters.member_id ?? null);
const status = ref<string>(props.filters.status || emptyValue);
const category = ref<string>(props.filters.category || emptyValue);
const contributionTypeId = ref<string>(
  props.filters.contribution_type_id !== undefined &&
    props.filters.contribution_type_id !== null &&
    String(props.filters.contribution_type_id) !== ""
    ? String(props.filters.contribution_type_id)
    : emptyValue,
);

const selectedInvoiceIds = ref<number[]>([]);
const rowPaymentAmounts = ref<Record<number, string>>({});
const showBatchConfirm = ref(false);
const showResetConfirm = ref(false);
const invoicePendingReset = ref<any | null>(null);

const resetPaidForm = useForm({});

const markPaidForm = useForm<{
  invoice_ids: number[];
  amount: string | number | null;
  paid_at: string;
  payment_method: string;
  reference_no: string;
  notes: string;
}>({
  invoice_ids: [],
  amount: null,
  paid_at: new Date().toISOString().slice(0, 10),
  payment_method: "CASH",
  reference_no: "",
  notes: "",
});

const applyFilters = (): void =>
  router.get(
    index().url,
    {
      period: period.value,
      status: status.value === emptyValue ? "" : status.value,
      member_id: memberId.value || undefined,
      member_search: memberSearch.value || undefined,
      contribution_type_id:
        contributionTypeId.value === emptyValue ? "" : contributionTypeId.value,
      category: category.value === emptyValue ? "" : category.value,
    },
    { preserveState: true, replace: true },
  );

watch(memberId, () => {
  applyFilters();
});

const debouncedApplyMemberSearch = useDebounceFn(() => {
  memberId.value = null;
  applyFilters();
}, 350);

watch(memberSearch, () => {
  debouncedApplyMemberSearch();
});

const remainingAmount = (invoice: any): number =>
  Number(invoice.amount ?? 0) - Number(invoice.paid_amount ?? 0);
const isPayable = (invoice: any): boolean =>
  invoice.status !== "PAID" && invoice.status !== "VOID" && remainingAmount(invoice) > 0;

const payableInvoiceIds = computed<number[]>(() =>
  props.invoices.data.filter(isPayable).map((invoice: any) => invoice.id),
);
const allPayableSelected = computed<boolean>(
  () =>
    payableInvoiceIds.value.length > 0 &&
    payableInvoiceIds.value.every((id: number) =>
      selectedInvoiceIds.value.includes(id),
    ),
);
const selectedInvoices = computed<any[]>(() =>
  props.invoices.data.filter((invoice: any) =>
    selectedInvoiceIds.value.includes(invoice.id),
  ),
);
const selectedRemainingTotal = computed<number>(() =>
  selectedInvoices.value.reduce(
    (total: number, invoice: any) => total + remainingAmount(invoice),
    0,
  ),
);

const toggleAllPayable = (): void => {
  selectedInvoiceIds.value = allPayableSelected.value
    ? []
    : [...payableInvoiceIds.value];
};

const toggleInvoice = (id: number, payable: boolean): void => {
  if (!payable) {
    return;
  }
  selectedInvoiceIds.value = selectedInvoiceIds.value.includes(id)
    ? selectedInvoiceIds.value.filter((x) => x !== id)
    : [...selectedInvoiceIds.value, id];
};

const submitMarkPaid = (
  invoiceIds: number[],
  amount?: string | number,
): void => {
  markPaidForm.invoice_ids = invoiceIds;
  markPaidForm.amount = amount || null;
  markPaidForm.post(markPaid().url, {
    preserveScroll: true,
    onSuccess: () => {
      selectedInvoiceIds.value = selectedInvoiceIds.value.filter(
        (id) => !invoiceIds.includes(id),
      );
      invoiceIds.forEach((id) => {
        delete rowPaymentAmounts.value[id];
      });
      showBatchConfirm.value = false;
      markPaidForm.amount = null;
      markPaidForm.reference_no = "";
      markPaidForm.notes = "";
    },
  });
};

const submitMarkUnpaid = (): void => {
  if (!invoicePendingReset.value) {
    showResetConfirm.value = false;
    return;
  }
  resetPaidForm.post(markUnpaid(invoicePendingReset.value.id).url, {
    preserveScroll: true,
    onFinish: () => {
      showResetConfirm.value = false;
      invoicePendingReset.value = null;
    },
  });
};

const askReset = (invoice: any): void => {
  invoicePendingReset.value = invoice;
  showResetConfirm.value = true;
};

const clearSelection = (): void => {
  selectedInvoiceIds.value = [];
  showBatchConfirm.value = false;
};

const statusOptions: Array<{ value: string; label: string; tone: Tone }> = [
  { value: "", label: "Semua status", tone: "zinc" },
  { value: "OPEN", label: "Belum lunas", tone: "amber" },
  { value: "UNPAID", label: "Belum bayar", tone: "rose" },
  { value: "PARTIAL", label: "Sebagian", tone: "amber" },
  { value: "PAID", label: "Lunas", tone: "emerald" },
  { value: "VOID", label: "Void", tone: "zinc" },
];

const paymentMethodOptions: Array<{ value: string; label: string }> = [
  { value: "CASH", label: "Tunai" },
  { value: "TRANSFER", label: "Transfer Bank" },
  { value: "QRIS", label: "QRIS" },
];

const categoryOptions = computed(() => [
  { value: "", label: "Semua kategori" },
  ...props.categories.map((c) => ({ value: c, label: c })),
]);

const contributionOptions = computed(() => [
  { value: "", label: "Semua jenis" },
  ...props.contributionTypes.map((t) => ({ value: String(t.id), label: t.name })),
]);

const statusTone = (status: string): Tone => {
  switch (status) {
    case "PAID":
      return "emerald";
    case "PARTIAL":
      return "amber";
    case "UNPAID":
      return "rose";
    case "VOID":
      return "zinc";
    default:
      return "zinc";
  }
};
const statusLabel = (status: string): string => {
  const found = statusOptions.find((o) => o.value === status);
  return found?.label ?? status;
};

const memberInitial = (name: string | null | undefined): string => {
  const n = (name || "").trim();
  if (!n) return "??";
  const parts = n.split(/\s+/);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const avatarTones: Tone[] = ["sky", "violet", "emerald", "amber", "rose"];
const avatarToneFor = (name: string | null | undefined): Tone => {
  const sum = (name || "")
    .split("")
    .reduce((acc: number, ch: string) => acc + ch.charCodeAt(0), 0);
  return avatarTones[sum % avatarTones.length] ?? "sky";
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

const summary = computed(() => {
  const stats = props.stats ?? {
    total_invoices: 0,
    total_nominal: 0,
    total_paid: 0,
    total_outstanding: 0,
    paid_count: 0,
  };
  const totalNominal = Number(stats.total_nominal) || 0;
  const totalPaid = Number(stats.total_paid) || 0;
  const totalOutstanding = Number(stats.total_outstanding) || 0;
  const totalInvoices = Number(stats.total_invoices) || 0;
  const paidCount = Number(stats.paid_count) || 0;
  const collectionRate =
    totalNominal > 0 ? (totalPaid / totalNominal) * 100 : 0;
  return {
    totalInvoices,
    totalNominal,
    totalPaid,
    totalOutstanding,
    paidCount,
    collectionRate,
    selectedCount: selectedInvoiceIds.value.length,
    currentPageCount: props.invoices?.data?.length ?? 0,
  };
});

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

const kpiCards = computed(() => [
  {
    label: "Total Tagihan",
    value: formatNumber(summary.value.totalInvoices),
    icon: FileText as Component,
    tone: "sky" as Tone,
    href: index().url,
    sparklinePoints: sparklineFor(summary.value.totalInvoices),
    meta: `Periode ${period.value || "—"}`,
  },
  {
    label: "Total Nominal",
    value: formatCurrency(summary.value.totalNominal),
    icon: Banknote as Component,
    tone: "violet" as Tone,
    href: index().url,
    sparklinePoints: sparklineFor(summary.value.totalNominal),
    meta: "Seluruh tagihan di periode ini",
  },
  {
    label: "Sudah Dibayar",
    value: formatCurrency(summary.value.totalPaid),
    icon: CheckCircle2 as Component,
    tone: "emerald" as Tone,
    href: index({ query: { status: "PAID" } }).url,
    sparklinePoints: sparklineFor(summary.value.totalPaid),
    meta: `${summary.value.paidCount} tagihan lunas · ${summary.value.collectionRate.toFixed(1)}%`,
  },
  {
    label: "Outstanding",
    value: formatCurrency(summary.value.totalOutstanding),
    icon: Wallet as Component,
    tone: "rose" as Tone,
    href: index({ query: { status: "UNPAID" } }).url,
    sparklinePoints: sparklineFor(summary.value.totalOutstanding),
    meta: "Tagihan belum & sebagian dibayar",
  },
]);
</script>

<template>
  <Head title="Iuran Simpanan Wajib" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Iuran & Simpanan', href: index().url },
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
              <Sparkles class="size-3.5" />
              Iuran & Simpanan
            </span>
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
            >
              Iuran Simpanan Wajib
            </h1>
            <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
              Monitoring tagihan iuran simpanan wajib anggota per periode.
              Pilih beberapa tagihan dan proses pelunasan sekaligus.
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <Button
              as-child
              variant="outline"
              class="border-zinc-200 bg-white/80 shadow-sm shadow-zinc-950/5 backdrop-blur hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950/40 dark:hover:bg-zinc-900"
            >
              <Link :href="editSavings().url" prefetch>
                <Settings2 class="mr-2 size-4" />
                Atur Simpanan
              </Link>
            </Button>
          </div>
        </div>
      </section>

      <!-- KPI BAND -->
      <Deferred data="stats">
        <template #fallback>
          <div aria-live="polite" class="sr-only">
            Memuat ringkasan iuran.
          </div>
          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
              v-for="i in 4"
              :key="i"
              class="h-36 animate-pulse rounded-2xl bg-zinc-100 dark:bg-zinc-800/60"
            />
          </div>
        </template>
        <section
          class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
          aria-label="Ringkasan iuran"
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
            :sparkline-points="card.sparklinePoints"
          />
        </section>
      </Deferred>

      <!-- FILTER BAR -->
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
              <Filter class="size-4" />
            </span>
            <div>
              <h2
                class="text-base font-semibold tracking-tight text-zinc-950 dark:text-white"
              >
                Filter Tagihan
              </h2>
              <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                Saring berdasarkan periode, status, anggota, atau jenis iuran.
              </p>
            </div>
          </div>
          <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
            <div class="relative w-full sm:max-w-xs">
              <Search
                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"
                aria-hidden="true"
              />
              <Input
                v-model="memberSearch"
                type="search"
                placeholder="Cari nama / no anggota…"
                class="pl-9"
                aria-label="Cari anggota"
                @keyup.enter="applyFilters"
              />
            </div>
            <Input
              v-model="period"
              type="month"
              class="sm:w-40"
              aria-label="Periode"
            />
            <Select v-model="status">
              <SelectTrigger class="sm:w-40">
                <SelectValue placeholder="Semua status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="opt in statusOptions"
                  :key="opt.value || emptyValue"
                  :value="opt.value || emptyValue"
                >
                  {{ opt.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Select v-model="category">
              <SelectTrigger class="sm:w-40">
                <SelectValue placeholder="Semua kategori" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="opt in categoryOptions"
                  :key="opt.value || emptyValue"
                  :value="opt.value || emptyValue"
                >
                  {{ opt.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Select v-model="contributionTypeId">
              <SelectTrigger class="sm:w-44">
                <SelectValue placeholder="Semua jenis" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="opt in contributionOptions"
                  :key="opt.value || emptyValue"
                  :value="opt.value || emptyValue"
                >
                  {{ opt.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Button
              variant="ghost"
              size="sm"
              class="shrink-0 text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-200"
              type="button"
              @click="applyFilters"
            >
              Terapkan
            </Button>
          </div>
        </div>

        <!-- BATCH PAYMENT BAR -->
        <div
          class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between"
        >
          <div class="flex items-start gap-3">
            <span
              :class="[
                'inline-flex size-9 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset transition-colors',
                summary.selectedCount > 0
                  ? 'bg-emerald-100 text-emerald-700 ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60'
                  : 'bg-zinc-100 text-zinc-500 ring-zinc-200/70 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700/60',
              ]"
            >
              <CreditCard class="size-4" />
            </span>
            <div>
              <p
                class="text-sm font-semibold text-zinc-950 dark:text-white"
              >
                Batch Payment
                <span
                  v-if="summary.selectedCount > 0"
                  class="ml-1.5 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60"
                >
                  {{ summary.selectedCount }} dipilih
                </span>
              </p>
              <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                {{
                  summary.selectedCount > 0
                    ? `Total sisa ${formatCurrency(selectedRemainingTotal)}.`
                    : "Pilih tagihan pada tabel di bawah untuk melakukan pembayaran sekaligus."
                }}
              </p>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-2">
              <Label
                for="batch-paid-at"
                class="shrink-0 text-xs font-medium text-zinc-500 dark:text-zinc-400"
              >
                Tanggal
              </Label>
              <Input
                id="batch-paid-at"
                v-model="markPaidForm.paid_at"
                type="date"
                class="h-9 w-40"
              />
            </div>
            <Select v-model="markPaidForm.payment_method">
              <SelectTrigger class="h-9 w-40">
                <SelectValue placeholder="Metode" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="opt in paymentMethodOptions"
                  :key="opt.value"
                  :value="opt.value"
                >
                  {{ opt.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <div class="flex items-center gap-2">
              <Label
                for="batch-amount"
                class="shrink-0 text-xs font-medium text-zinc-500 dark:text-zinc-400"
              >
                Nominal
              </Label>
              <Input
                id="batch-amount"
                v-model="markPaidForm.amount"
                type="number"
                min="1"
                step="1000"
                :placeholder="formatCurrency(selectedRemainingTotal)"
                class="h-9 w-40"
              />
            </div>
            <Input
              v-model="markPaidForm.reference_no"
              placeholder="No. referensi"
              class="h-9 w-44"
            />
            <Button
              v-if="summary.selectedCount > 0"
              variant="ghost"
              size="sm"
              class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-200"
              @click="clearSelection"
            >
              <X class="mr-1.5 size-3.5" />
              Clear
            </Button>
            <Button
              class="bg-emerald-700 hover:bg-emerald-800"
              :disabled="
                summary.selectedCount === 0 || markPaidForm.processing
              "
              @click="showBatchConfirm = true"
            >
              <CheckCircle2 class="mr-2 size-4" />
              Proses ({{ summary.selectedCount }})
            </Button>
          </div>
        </div>
      </Card>

      <!-- TABEL TAGIHAN -->
      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Daftar Tagihan"
          :description="`${summary.currentPageCount} dari ${summary.totalInvoices} tagihan pada filter ini`"
          :icon="ClipboardList"
          tone="violet"
        />
        <CardContent class="p-0">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead
                class="border-b border-zinc-200/70 bg-zinc-50/60 text-left text-[11px] uppercase tracking-wide text-zinc-500 dark:border-zinc-800/70 dark:bg-zinc-900/60"
              >
                <tr>
                  <th class="w-12 px-4 py-3">
                    <label
                      class="inline-flex h-4 w-4 cursor-pointer items-center justify-center"
                    >
                      <input
                        type="checkbox"
                        class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/30 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-950"
                        :checked="allPayableSelected"
                        :disabled="payableInvoiceIds.length === 0"
                        @change="toggleAllPayable"
                      />
                    </label>
                  </th>
                  <th class="px-4 py-3 font-medium">Anggota</th>
                  <th class="px-4 py-3 font-medium">Jenis</th>
                  <th class="px-4 py-3 font-medium">Periode</th>
                  <th class="px-4 py-3 font-medium">Status</th>
                  <th class="px-4 py-3 text-right font-medium">Nominal</th>
                  <th class="px-4 py-3 text-right font-medium">Terbayar</th>
                  <th class="px-4 py-3 text-right font-medium">Sisa</th>
                  <th class="px-4 py-3 text-right font-medium">Collect</th>
                  <th class="px-4 py-3 text-right font-medium">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70">
                <tr
                  v-for="invoice in invoices.data"
                  :key="invoice.id"
                  :class="[
                    'transition-colors',
                    selectedInvoiceIds.includes(invoice.id)
                      ? 'bg-emerald-50/60 dark:bg-emerald-950/20'
                      : 'hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40',
                  ]"
                >
                  <td class="px-4 py-3">
                    <label class="inline-flex items-center">
                      <input
                        type="checkbox"
                        class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/30 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-950"
                        :checked="selectedInvoiceIds.includes(invoice.id)"
                        :disabled="!isPayable(invoice)"
                        @change="
                          toggleInvoice(invoice.id, isPayable(invoice))
                        "
                      />
                    </label>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                      <span
                        :class="[
                          'inline-flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-bold ring-2 ring-white dark:ring-zinc-900',
                          avatarBgClass[avatarToneFor(invoice.member?.name)],
                        ]"
                        aria-hidden="true"
                      >
                        {{ memberInitial(invoice.member?.name) }}
                      </span>
                      <div class="min-w-0">
                        <Link
                          v-if="invoice.member?.id"
                          :href="`/cooperative/members/${invoice.member.id}`"
                          class="block max-w-[200px] truncate text-sm font-semibold text-zinc-950 hover:text-emerald-700 dark:text-white dark:hover:text-emerald-300"
                        >
                          {{ invoice.member?.name ?? "—" }}
                        </Link>
                        <span
                          v-else
                          class="block max-w-[200px] truncate text-sm font-semibold text-zinc-950 dark:text-white"
                        >
                          {{ invoice.member?.name ?? "—" }}
                        </span>
                        <p
                          class="inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400"
                        >
                          <Hash class="size-3" />
                          {{ invoice.member?.member_no ?? "—" }}
                        </p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span
                      class="inline-flex items-center gap-1 rounded-md bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/60"
                    >
                      <Layers class="size-3" />
                      {{ invoice.contribution_type?.name ?? "—" }}
                    </span>
                  </td>
                  <td
                    class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400"
                  >
                    {{ invoice.period }}
                  </td>
                  <td class="px-4 py-3">
                    <StatusPill
                      :tone="statusTone(invoice.status)"
                      :label="statusLabel(invoice.status)"
                    />
                  </td>
                  <td
                    class="px-4 py-3 text-right font-semibold tabular-nums text-zinc-950 dark:text-white"
                  >
                    {{ formatCurrency(invoice.amount) }}
                  </td>
                  <td
                    class="px-4 py-3 text-right tabular-nums text-emerald-700 dark:text-emerald-300"
                  >
                    {{ formatCurrency(invoice.paid_amount) }}
                  </td>
                  <td
                    class="px-4 py-3 text-right tabular-nums"
                    :class="
                      remainingAmount(invoice) > 0
                        ? 'font-semibold text-rose-700 dark:text-rose-300'
                        : 'text-zinc-400'
                    "
                  >
                    {{ formatCurrency(remainingAmount(invoice)) }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    <Input
                      v-if="isPayable(invoice)"
                      v-model="rowPaymentAmounts[invoice.id]"
                      type="number"
                      min="1"
                      :max="remainingAmount(invoice)"
                      step="1000"
                      :placeholder="String(remainingAmount(invoice))"
                      class="ml-auto h-9 w-32 text-right"
                    />
                    <span v-else class="text-xs text-zinc-400">—</span>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <Button
                      v-if="isPayable(invoice)"
                      size="sm"
                      class="bg-emerald-700 hover:bg-emerald-800"
                      :disabled="markPaidForm.processing"
                      @click="
                        submitMarkPaid(
                          [invoice.id],
                          rowPaymentAmounts[invoice.id] ||
                            remainingAmount(invoice),
                        )
                      "
                    >
                      <CheckCircle2 class="mr-1.5 size-3.5" />
                      Collect
                    </Button>
                    <Button
                      v-else-if="canResetPaidDues && invoice.status === 'PAID'"
                      size="sm"
                      variant="outline"
                      :disabled="resetPaidForm.processing"
                      @click="askReset(invoice)"
                    >
                      <RotateCcw class="mr-1.5 size-3.5" />
                      Belum Bayar
                    </Button>
                    <span v-else class="text-xs text-zinc-400">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <EmptyState
            v-if="!invoices.data || invoices.data.length === 0"
            :icon="Receipt"
            title="Belum ada tagihan"
            description="Belum ada tagihan iuran untuk filter saat ini. Coba ubah filter atau cek pengaturan simpanan wajib."
            class="py-12"
          />
          <div
            v-if="invoices.links?.length > 3"
            class="flex flex-col gap-3 border-t border-zinc-200/70 px-6 py-4 text-sm text-zinc-500 md:flex-row md:items-center md:justify-between dark:border-zinc-800/70"
          >
            <div>
              Menampilkan
              <span class="font-medium text-zinc-700 dark:text-zinc-200">
                {{ invoices.from }}-{{ invoices.to }}
              </span>
              dari
              <span class="font-medium text-zinc-700 dark:text-zinc-200">
                {{ invoices.total }}
              </span>
              tagihan
            </div>
            <div class="flex flex-wrap gap-1">
              <template v-for="(link, index) in invoices.links" :key="index">
                <Button
                  v-if="link.url"
                  as-child
                  size="sm"
                  :variant="link.active ? 'default' : 'outline'"
                >
                  <Link :href="link.url" preserve-scroll preserve-state>
                    <span v-html="link.label" />
                  </Link>
                </Button>
                <span
                  v-else
                  class="rounded-md border px-3 py-1.5 text-zinc-400"
                  v-html="link.label"
                />
              </template>
            </div>
          </div>
        </CardContent>
      </Card>
    </PageContainer>

    <!-- BATCH CONFIRMATION -->
    <Dialog v-model:open="showBatchConfirm">
      <DialogContent class="max-h-[90vh] sm:max-w-2xl">
        <DialogHeader>
          <div class="flex items-start gap-3">
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60"
            >
              <CheckCircle2 class="size-4" />
            </span>
            <div>
              <DialogTitle>Konfirmasi Batch Payment</DialogTitle>
              <DialogDescription>
                {{ selectedInvoiceIds.length }} tagihan akan diproses dengan
                nominal
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                  {{
                    markPaidForm.amount
                      ? formatCurrency(Number(markPaidForm.amount))
                      : formatCurrency(selectedRemainingTotal)
                  }}
                </span>
                menggunakan metode
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                  {{
                    paymentMethodOptions.find(
                      (o) => o.value === markPaidForm.payment_method,
                    )?.label
                  }}
                </span>.
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>
        <div
          class="rounded-xl border border-zinc-200/70 dark:border-zinc-800/70"
        >
          <div class="max-h-72 overflow-auto">
            <table class="w-full text-left text-sm">
              <thead
                class="border-b border-zinc-200/70 bg-zinc-50/60 text-[11px] uppercase tracking-wide text-zinc-500 dark:border-zinc-800/70 dark:bg-zinc-900/60"
              >
                <tr>
                  <th class="px-4 py-2.5 font-medium">Anggota</th>
                  <th class="px-4 py-2.5 font-medium">Jenis</th>
                  <th class="px-4 py-2.5 font-medium">Periode</th>
                  <th class="px-4 py-2.5 text-right font-medium">Sisa</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70">
                <tr
                  v-for="invoice in selectedInvoices"
                  :key="invoice.id"
                  class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40"
                >
                  <td class="px-4 py-2.5">
                    <p class="text-sm font-medium text-zinc-950 dark:text-white">
                      {{ invoice.member?.name ?? "—" }}
                    </p>
                    <p
                      class="text-[11px] text-zinc-500 dark:text-zinc-400"
                    >
                      {{ invoice.member?.member_no ?? "—" }}
                    </p>
                  </td>
                  <td
                    class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400"
                  >
                    {{ invoice.contribution_type?.name ?? "—" }}
                  </td>
                  <td
                    class="whitespace-nowrap px-4 py-2.5 text-zinc-600 dark:text-zinc-400"
                  >
                    {{ invoice.period }}
                  </td>
                  <td
                    class="px-4 py-2.5 text-right font-semibold tabular-nums text-rose-700 dark:text-rose-300"
                  >
                    {{ formatCurrency(remainingAmount(invoice)) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div
          class="flex items-start gap-2 rounded-lg border border-sky-200/60 bg-sky-50/60 p-3 text-xs text-sky-700 dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-300"
        >
          <Info class="size-4 shrink-0" />
          <p>
            {{
              markPaidForm.amount
                ? "Nominal tersebut akan diterapkan ke setiap tagihan terpilih dan dibatasi sesuai sisa tagihan masing-masing."
                : "Pembayaran akan otomatis melunasi seluruh sisa tagihan pada anggota terkait."
            }}
            Tanggal bayar: {{ markPaidForm.paid_at }}.
          </p>
        </div>
        <DialogFooter class="gap-2">
          <Button
            variant="outline"
            @click="showBatchConfirm = false"
          >
            Batal
          </Button>
          <Button
            class="bg-emerald-700 hover:bg-emerald-800"
            :disabled="markPaidForm.processing"
            @click="submitMarkPaid(selectedInvoiceIds)"
          >
            <CheckCircle2 class="mr-2 size-4" />
            Konfirmasi Bayar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- RESET CONFIRMATION -->
    <Dialog v-model:open="showResetConfirm">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <div class="flex items-start gap-3">
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200/70 dark:bg-amber-900/40 dark:text-amber-300 dark:ring-amber-900/60"
            >
              <RotateCcw class="size-4" />
            </span>
            <div>
              <DialogTitle>Kembalikan ke Belum Bayar</DialogTitle>
              <DialogDescription>
                Tindakan ini akan mengembalikan tagihan
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                  {{ invoicePendingReset?.member?.name ?? "" }}
                </span>
                periode
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                  {{ invoicePendingReset?.period ?? "" }}
                </span>
                menjadi belum bayar.
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>
        <DialogFooter class="gap-2">
          <Button variant="outline" @click="showResetConfirm = false">
            Batal
          </Button>
          <Button
            variant="destructive"
            :disabled="resetPaidForm.processing"
            @click="submitMarkUnpaid"
          >
            <RotateCcw class="mr-2 size-4" />
            Kembalikan
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
