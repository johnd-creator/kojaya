<script setup lang="ts">
import { Deferred, Head, Link, router, useForm } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import {
  CheckCircle2,
  CreditCard,
  Filter,
  Hash,
  Info,
  Layers,
  Receipt,
  RotateCcw,
  Search,
  Settings2,
  X,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import StatusPill from "@/components/dashboard/StatusPill.vue";
import EmptyState from "@/components/EmptyState.vue";
import InfoTooltip from "@/components/InfoTooltip.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
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
import { index, markPaid, markUnpaid } from "@/routes/cooperative/dues";
import { edit as editSavings } from "@/routes/settings/savings";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc";

const emptyValue = "__all__";

const props = defineProps<{
  bankCollectionWindow: {
    period: string;
    opens_on: string;
    closes_on: string;
    is_open: boolean;
  };
  invoices: any;
  filters: {
    period?: string;
    period_scope?: string;
    status?: string;
    member_id?: number | null;
    member_search?: string;
    contribution_type_id?: string | number;
    category?: string;
  };
  contributionTypes: any[];
  categories: string[];
  canResetPaidDues: boolean;
  monthlyDuesInfo?: {
    title: string;
    period: string;
    period_label: string;
    next_period_label: string;
    type_name: string;
    amount: number;
    due_date: string;
    total_invoices: number;
    total_nominal: number;
    total_paid: number;
    total_outstanding: number;
  } | null;
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
const periodScope = ref<string>(
  props.filters.period_scope === "all" ? "all" : "period",
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
const showFilters = ref(false);
const showBatchPayment = ref(false);
const showResetConfirm = ref(false);
const invoicePendingReset = ref<any | null>(null);

const resetPaidForm = useForm({});

const markPaidForm = useForm<{
  invoice_ids: number[];
  amount: string | number;
  paid_at: string;
  payment_method: string;
  reference_no: string;
  notes: string;
}>({
  invoice_ids: [],
  amount: "",
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
      period_scope: periodScope.value === "all" ? "all" : undefined,
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
  invoice.status !== "PAID" &&
  invoice.status !== "VOID" &&
  remainingAmount(invoice) > 0;

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
  markPaidForm.amount = amount || "";
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
      markPaidForm.amount = "";
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

const periodScopeOptions = [
  { value: "period", label: "Periode dipilih" },
  { value: "all", label: "Semua periode" },
];

const categoryOptions = computed(() => [
  { value: "", label: "Semua kategori" },
  ...props.categories.map((c) => ({ value: c, label: c })),
]);

const contributionOptions = computed(() => [
  { value: "", label: "Semua jenis" },
  ...props.contributionTypes.map((t) => ({
    value: String(t.id),
    label: t.name,
  })),
]);

const activeFilterCount = computed(
  () =>
    [
      periodScope.value === "all",
      status.value !== emptyValue,
      category.value !== emptyValue,
      contributionTypeId.value !== emptyValue,
    ].filter(Boolean).length,
);

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

const kpiCards = computed(() => [
  {
    label: "Total Tagihan",
    value: formatNumber(summary.value.totalInvoices),
    href: index().url,
    meta:
      periodScope.value === "all"
        ? "Semua periode"
        : `Periode ${period.value || "—"}`,
  },
  {
    label: "Total Nominal",
    value: formatCurrency(summary.value.totalNominal),
    href: index().url,
    meta:
      periodScope.value === "all"
        ? "Seluruh tagihan di semua periode"
        : "Seluruh tagihan di periode ini",
  },
  {
    label: "Sudah Dibayar",
    value: formatCurrency(summary.value.totalPaid),
    href: index({
      query: {
        period: period.value,
        period_scope: periodScope.value === "all" ? "all" : undefined,
        status: "PAID",
      },
    }).url,
    meta: `${summary.value.paidCount} tagihan lunas · ${summary.value.collectionRate.toFixed(1)}%`,
  },
  {
    label: "Outstanding",
    value: formatCurrency(summary.value.totalOutstanding),
    href: index({
      query: {
        period: period.value,
        period_scope: periodScope.value === "all" ? "all" : undefined,
        status: "OPEN",
      },
    }).url,
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
    <PageContainer class="max-w-none gap-4">
      <header class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-2">
          <h1
            class="text-xl font-semibold tracking-tight text-zinc-950 sm:text-2xl dark:text-white"
          >
            Iuran Simpanan Wajib
          </h1>
          <InfoTooltip label="Informasi iuran dan autodebit">
            <p>
              Monitoring tagihan simpanan wajib per periode. Pilih tagihan untuk
              pembayaran sekaligus.
            </p>
            <p class="font-semibold">
              Jendela autodebit {{ bankCollectionWindow.period }}
            </p>
            <p>
              {{ bankCollectionWindow.opens_on }} s/d
              {{ bankCollectionWindow.closes_on }}.
            </p>
            <p>
              {{
                bankCollectionWindow.is_open
                  ? "Jendela operasional terbuka."
                  : "Di luar jendela operasional."
              }}
              Bank mengeksekusi autodebit; admin mencatat dan menyetujui hasil
              bank atau penerimaan manual berdasarkan bukti.
            </p>
          </InfoTooltip>
        </div>
        <Button as-child variant="outline" size="sm">
          <Link :href="editSavings().url" prefetch>
            <Settings2 class="mr-2 size-4" />
            Atur Simpanan
          </Link>
        </Button>
      </header>

      <section aria-label="Ringkasan iuran">
        <dl
          class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-zinc-200 bg-zinc-200 sm:grid-cols-4 dark:border-zinc-800 dark:bg-zinc-800"
          :class="
            monthlyDuesInfo
              ? 'xl:grid-cols-[minmax(0,1.35fr)_repeat(7,minmax(0,1fr))]'
              : ''
          "
        >
          <template v-if="monthlyDuesInfo">
            <div
              class="min-w-0 bg-emerald-50/70 px-3 py-3 dark:bg-emerald-950/40"
            >
              <dt
                class="flex items-center justify-between gap-1 text-xs text-emerald-700 dark:text-emerald-300"
              >
                Periode aktif
                <InfoTooltip label="Informasi periode aktif">
                  <p class="font-semibold">{{ monthlyDuesInfo.title }}</p>
                  <p>
                    Bulan berikutnya otomatis mengikuti periode berjalan:
                    {{ monthlyDuesInfo.next_period_label }}.
                  </p>
                </InfoTooltip>
              </dt>
              <dd
                class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white"
              >
                {{ monthlyDuesInfo.period_label }}
              </dd>
            </div>
            <div class="min-w-0 bg-white px-3 py-3 dark:bg-zinc-900">
              <dt
                class="flex min-h-7 items-center text-xs text-zinc-500 dark:text-zinc-400"
              >
                Per anggota
              </dt>
              <dd
                class="mt-1 break-words text-sm font-semibold tabular-nums text-zinc-950 dark:text-white"
              >
                {{ formatCurrency(monthlyDuesInfo.amount) }}
              </dd>
            </div>
            <div class="min-w-0 bg-white px-3 py-3 dark:bg-zinc-900">
              <dt
                class="flex min-h-7 items-center text-xs text-zinc-500 dark:text-zinc-400"
              >
                Tagihan dibuat
              </dt>
              <dd
                class="mt-1 text-sm font-semibold tabular-nums text-zinc-950 dark:text-white"
              >
                {{ formatNumber(monthlyDuesInfo.total_invoices) }}
              </dd>
            </div>
            <div class="min-w-0 bg-white px-3 py-3 dark:bg-zinc-900">
              <dt
                class="flex min-h-7 items-center text-xs text-zinc-500 dark:text-zinc-400"
              >
                Jatuh tempo
              </dt>
              <dd
                class="mt-1 text-sm font-semibold tabular-nums text-zinc-950 dark:text-white"
              >
                {{ monthlyDuesInfo.due_date }}
              </dd>
            </div>
          </template>
          <Deferred data="stats">
            <template #fallback>
              <div
                v-for="i in 4"
                :key="i"
                class="min-h-24 animate-pulse bg-zinc-50 px-3 py-3 dark:bg-zinc-900"
                :aria-label="i === 1 ? 'Memuat ringkasan iuran' : undefined"
                :role="i === 1 ? 'status' : undefined"
              >
                <dt class="mt-2 h-3 w-20 rounded bg-zinc-200 dark:bg-zinc-800">
                  <span class="sr-only">Ringkasan</span>
                </dt>
                <dd
                  class="mt-4 h-4 w-24 rounded bg-zinc-200 dark:bg-zinc-800"
                />
              </div>
            </template>
            <div
              v-for="card in kpiCards"
              :key="card.label"
              class="min-w-0 bg-white px-3 py-3 dark:bg-zinc-900"
            >
              <dt
                class="flex items-center justify-between gap-1 text-xs text-zinc-500 dark:text-zinc-400"
              >
                {{ card.label }}
                <InfoTooltip :label="`Keterangan ${card.label}`">{{
                  card.meta
                }}</InfoTooltip>
              </dt>
              <dd
                class="mt-1 break-words text-sm font-semibold tabular-nums"
                :class="
                  card.label === 'Outstanding'
                    ? 'text-rose-700 dark:text-rose-300'
                    : card.label === 'Sudah Dibayar'
                      ? 'text-emerald-700 dark:text-emerald-300'
                      : 'text-zinc-950 dark:text-white'
                "
              >
                <Link
                  :href="card.href"
                  class="rounded-sm hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600"
                  >{{ card.value }}</Link
                >
              </dd>
            </div>
          </Deferred>
        </dl>
      </section>

      <Card class="gap-0 overflow-hidden rounded-xl py-0">
        <div
          class="flex flex-wrap items-center gap-2 p-3"
          role="group"
          aria-label="Filter tagihan dan batch payment"
        >
          <div class="relative min-w-0 flex-1 basis-40 sm:max-w-72">
            <Search
              class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"
              aria-hidden="true"
            />
            <Input
              v-model="memberSearch"
              type="search"
              placeholder="Cari nama / no. anggota…"
              class="h-9 pl-9"
              aria-label="Cari anggota"
              @keyup.enter="applyFilters"
            />
          </div>
          <Input
            v-model="period"
            type="month"
            class="h-9 w-40"
            aria-label="Periode"
            @change="applyFilters"
          />
          <Button
            variant="outline"
            size="sm"
            :aria-expanded="showFilters"
            aria-controls="dues-filter-options"
            @click="showFilters = !showFilters"
          >
            <Filter class="mr-2 size-3.5" />
            Filter Tagihan
            <span
              v-if="activeFilterCount"
              class="ml-2 rounded bg-zinc-100 px-1.5 text-xs dark:bg-zinc-800"
              >{{ activeFilterCount }}</span
            >
          </Button>
          <InfoTooltip label="Keterangan filter tagihan"
            >Saring berdasarkan cakupan periode, status, anggota, atau jenis
            iuran. Buka Filter Tagihan untuk pilihan lainnya.</InfoTooltip
          >
          <div class="ml-auto flex items-center gap-2">
            <span
              v-if="summary.selectedCount > 0"
              class="hidden text-xs tabular-nums text-zinc-500 lg:inline dark:text-zinc-400"
              >Sisa {{ formatCurrency(selectedRemainingTotal) }}</span
            >
            <Button
              :variant="showBatchPayment ? 'secondary' : 'outline'"
              size="sm"
              :aria-expanded="showBatchPayment"
              aria-controls="dues-batch-options"
              @click="showBatchPayment = !showBatchPayment"
            >
              <CreditCard class="mr-2 size-4" />
              Batch Payment
              <span
                class="ml-2 rounded bg-emerald-100 px-1.5 text-xs text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200"
                >{{ summary.selectedCount }}</span
              >
            </Button>
            <InfoTooltip label="Keterangan batch payment"
              >Pilih tagihan pada tabel, buka Batch Payment untuk mengatur
              pembayaran, lalu klik Proses untuk meninjau sebelum
              konfirmasi.</InfoTooltip
            >
          </div>
        </div>

        <div
          v-show="showFilters"
          id="dues-filter-options"
          class="border-t border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-950/30"
          role="region"
          aria-label="Pilihan filter tagihan"
        >
          <form
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[repeat(4,minmax(0,1fr))_auto] xl:items-end"
            @submit.prevent="applyFilters"
          >
            <div class="space-y-1.5">
              <Label
                for="dues-period-scope"
                class="text-xs text-zinc-500 dark:text-zinc-400"
                >Cakupan periode</Label
              >
              <Select v-model="periodScope">
                <SelectTrigger
                  id="dues-period-scope"
                  class="h-9"
                  aria-label="Cakupan periode"
                  ><SelectValue placeholder="Cakupan periode"
                /></SelectTrigger>
                <SelectContent
                  ><SelectItem
                    v-for="opt in periodScopeOptions"
                    :key="opt.value"
                    :value="opt.value"
                    >{{ opt.label }}</SelectItem
                  ></SelectContent
                >
              </Select>
            </div>
            <div class="space-y-1.5">
              <Label
                for="dues-status"
                class="text-xs text-zinc-500 dark:text-zinc-400"
                >Status tagihan</Label
              >
              <Select v-model="status">
                <SelectTrigger
                  id="dues-status"
                  class="h-9"
                  aria-label="Status tagihan"
                  ><SelectValue placeholder="Semua status"
                /></SelectTrigger>
                <SelectContent
                  ><SelectItem
                    v-for="opt in statusOptions"
                    :key="opt.value || emptyValue"
                    :value="opt.value || emptyValue"
                    >{{ opt.label }}</SelectItem
                  ></SelectContent
                >
              </Select>
            </div>
            <div class="space-y-1.5">
              <Label
                for="dues-category"
                class="text-xs text-zinc-500 dark:text-zinc-400"
                >Kategori iuran</Label
              >
              <Select v-model="category">
                <SelectTrigger
                  id="dues-category"
                  class="h-9"
                  aria-label="Kategori iuran"
                  ><SelectValue placeholder="Semua kategori"
                /></SelectTrigger>
                <SelectContent
                  ><SelectItem
                    v-for="opt in categoryOptions"
                    :key="opt.value || emptyValue"
                    :value="opt.value || emptyValue"
                    >{{ opt.label }}</SelectItem
                  ></SelectContent
                >
              </Select>
            </div>
            <div class="space-y-1.5">
              <Label
                for="dues-type"
                class="text-xs text-zinc-500 dark:text-zinc-400"
                >Jenis iuran</Label
              >
              <Select v-model="contributionTypeId">
                <SelectTrigger
                  id="dues-type"
                  class="h-9"
                  aria-label="Jenis iuran"
                  ><SelectValue placeholder="Semua jenis"
                /></SelectTrigger>
                <SelectContent
                  ><SelectItem
                    v-for="opt in contributionOptions"
                    :key="opt.value || emptyValue"
                    :value="opt.value || emptyValue"
                    >{{ opt.label }}</SelectItem
                  ></SelectContent
                >
              </Select>
            </div>
            <Button type="submit" variant="secondary" size="sm" class="h-9"
              >Terapkan</Button
            >
          </form>
        </div>

        <div
          v-show="showBatchPayment"
          id="dues-batch-options"
          class="border-t border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-950/30"
          role="region"
          aria-label="Pengaturan batch payment"
        >
          <div
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.1fr)_auto] xl:items-end"
          >
            <div class="space-y-1.5">
              <Label
                for="batch-paid-at"
                class="text-xs text-zinc-500 dark:text-zinc-400"
                >Tanggal bayar</Label
              >
              <Input
                id="batch-paid-at"
                v-model="markPaidForm.paid_at"
                type="date"
                class="h-9"
              />
            </div>
            <div class="space-y-1.5">
              <Label
                for="batch-method"
                class="text-xs text-zinc-500 dark:text-zinc-400"
                >Metode pembayaran</Label
              >
              <Select v-model="markPaidForm.payment_method">
                <SelectTrigger
                  id="batch-method"
                  class="h-9"
                  aria-label="Metode pembayaran"
                  ><SelectValue placeholder="Metode"
                /></SelectTrigger>
                <SelectContent
                  ><SelectItem
                    v-for="opt in paymentMethodOptions"
                    :key="opt.value"
                    :value="opt.value"
                    >{{ opt.label }}</SelectItem
                  ></SelectContent
                >
              </Select>
            </div>
            <div class="space-y-1.5">
              <div class="flex items-center gap-1">
                <Label
                  for="batch-amount"
                  class="text-xs text-zinc-500 dark:text-zinc-400"
                  >Nominal per tagihan</Label
                >
                <InfoTooltip label="Keterangan nominal pembayaran"
                  >Kosongkan untuk melunasi sisa setiap tagihan. Nominal yang
                  diisi berlaku per tagihan, maksimal sebesar sisa masing-masing
                  tagihan.</InfoTooltip
                >
              </div>
              <Input
                id="batch-amount"
                v-model="markPaidForm.amount"
                type="number"
                min="1"
                step="1000"
                placeholder="Lunasi sisa tagihan"
                class="h-9"
              />
            </div>
            <div class="space-y-1.5">
              <Label
                for="batch-reference"
                class="text-xs text-zinc-500 dark:text-zinc-400"
                >No. referensi</Label
              >
              <Input
                id="batch-reference"
                v-model="markPaidForm.reference_no"
                placeholder="Opsional"
                class="h-9"
              />
            </div>
            <div class="flex items-center gap-2">
              <Button
                v-if="summary.selectedCount > 0"
                variant="ghost"
                size="sm"
                aria-label="Hapus pilihan tagihan"
                @click="clearSelection"
                ><X class="size-4"
              /></Button>
              <Button
                size="sm"
                class="h-9 bg-emerald-700 hover:bg-emerald-800"
                :disabled="
                  summary.selectedCount === 0 || markPaidForm.processing
                "
                @click="showBatchConfirm = true"
                ><CheckCircle2 class="mr-2 size-4" />Proses ({{
                  summary.selectedCount
                }})</Button
              >
            </div>
          </div>
          <p
            v-if="summary.selectedCount > 0"
            class="mt-2 text-xs text-zinc-500 dark:text-zinc-400"
          >
            {{ summary.selectedCount }} tagihan dipilih · Total sisa
            {{ formatCurrency(selectedRemainingTotal) }}
          </p>
        </div>
      </Card>

      <!-- TABEL TAGIHAN -->
      <Card class="gap-0 overflow-hidden rounded-xl py-0">
        <div
          class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800"
        >
          <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">
            Daftar Tagihan
          </h2>
          <span class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400"
            >{{ summary.currentPageCount }} dari
            {{ summary.totalInvoices }} tagihan</span
          >
        </div>
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
                        id="dues-select-all"
                        type="checkbox"
                        aria-label="Pilih semua tagihan yang dapat dibayar"
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
              <tbody
                class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70"
              >
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
                        :id="'dues-select-' + invoice.id"
                        type="checkbox"
                        :aria-label="
                          'Pilih tagihan ' +
                          (invoice.member?.name ?? invoice.id)
                        "
                        class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/30 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-950"
                        :checked="selectedInvoiceIds.includes(invoice.id)"
                        :disabled="!isPayable(invoice)"
                        @change="toggleInvoice(invoice.id, isPayable(invoice))"
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
                  class="rounded-md border px-3 py-1.5 text-zinc-500 dark:text-zinc-400"
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
                  }} </span
                >.
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
              <tbody
                class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70"
              >
                <tr
                  v-for="invoice in selectedInvoices"
                  :key="invoice.id"
                  class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40"
                >
                  <td class="px-4 py-2.5">
                    <p
                      class="text-sm font-medium text-zinc-950 dark:text-white"
                    >
                      {{ invoice.member?.name ?? "—" }}
                    </p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                      {{ invoice.member?.member_no ?? "—" }}
                    </p>
                  </td>
                  <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">
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
          <Button variant="outline" @click="showBatchConfirm = false">
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
