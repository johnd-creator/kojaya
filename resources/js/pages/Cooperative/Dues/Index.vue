<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import {
  CalendarPlus,
  CheckCircle2,
  Funnel,
  RotateCcw,
  Settings2,
  X,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import {
  generate,
  index,
  markPaid,
  markUnpaid,
} from "@/routes/cooperative/dues";
import { edit as editSavings } from "@/routes/settings/savings";

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
}>();
const period = ref(
  props.filters.period ?? new Date().toISOString().slice(0, 7),
);
const status = ref(props.filters.status ?? "");
const memberSearch = ref(props.filters.member_search ?? "");
const memberId = ref<number | null>(props.filters.member_id ?? null);
const contributionTypeId = ref(props.filters.contribution_type_id ?? "");
const category = ref(props.filters.category ?? "");
const selectedInvoiceIds = ref<number[]>([]);
const showBatchConfirm = ref(false);
const rowPaymentAmounts = ref<Record<number, string>>({});
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
const applyFilters = () =>
  router.get(
    index().url,
    {
      period: period.value,
      status: status.value,
      member_id: memberId.value || undefined,
      member_search: memberSearch.value || undefined,
      contribution_type_id: contributionTypeId.value,
      category: category.value,
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
const remainingAmount = (invoice: any) =>
  Number(invoice.amount ?? 0) - Number(invoice.paid_amount ?? 0);
const isPayable = (invoice: any) =>
  invoice.status !== "PAID" && remainingAmount(invoice) > 0;
const payableInvoiceIds = computed(() =>
  props.invoices.data.filter(isPayable).map((invoice: any) => invoice.id),
);
const allPayableSelected = computed(
  () =>
    payableInvoiceIds.value.length > 0 &&
    payableInvoiceIds.value.every((id: number) =>
      selectedInvoiceIds.value.includes(id),
    ),
);
const selectedInvoices = computed(() =>
  props.invoices.data.filter((invoice: any) =>
    selectedInvoiceIds.value.includes(invoice.id),
  ),
);
const selectedRemainingTotal = computed(() =>
  selectedInvoices.value.reduce(
    (total: number, invoice: any) => total + remainingAmount(invoice),
    0,
  ),
);

const toggleAllPayable = () => {
  selectedInvoiceIds.value = allPayableSelected.value
    ? []
    : [...payableInvoiceIds.value];
};

const submitMarkPaid = (invoiceIds: number[], amount?: string | number) => {
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
    },
  });
};
const submitMarkUnpaid = (invoice: any) => {
  if (
    !window.confirm(
      `Kembalikan tagihan ${invoice.member?.name ?? "anggota"} periode ${invoice.period} menjadi belum bayar?`,
    )
  ) {
    return;
  }

  resetPaidForm.post(markUnpaid(invoice.id).url, {
    preserveScroll: true,
  });
};
const clearSelection = () => {
  selectedInvoiceIds.value = [];
  showBatchConfirm.value = false;
};
</script>

<template>
  <Head title="Iuran Simpanan Wajib" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Iuran & Simpanan', href: index().url },
    ]"
  >
    <div class="flex w-full flex-col gap-6 p-6">
      <div
        class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
      >
        <div>
          <h1 class="text-3xl font-bold tracking-tight">
            Iuran Simpanan Wajib
          </h1>
          <p class="mt-1 text-sm text-zinc-500">
            Monitoring tagihan iuran simpanan wajib anggota per periode.
          </p>
        </div>
      </div>
      <div
        class="grid gap-3 rounded-xl border border-zinc-200/80 bg-white/95 p-4 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900 md:grid-cols-3 xl:grid-cols-7"
      >
        <Input
          v-model="memberSearch"
          placeholder="Cari nama / no anggota"
          @keyup.enter="applyFilters"
        />
        <Input v-model="period" type="month" />
        <select
          v-model="status"
          class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
        >
          <option value="">Semua status</option>
          <option value="OPEN">Belum lunas</option>
          <option>UNPAID</option>
          <option>PARTIAL</option>
          <option>PAID</option>
          <option>VOID</option>
        </select>
        <select
          v-model="category"
          class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
        >
          <option value="">Semua kategori</option>
          <option v-for="item in categories" :key="item" :value="item">
            {{ item }}
          </option>
        </select>
        <select
          v-model="contributionTypeId"
          class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
        >
          <option value="">Semua jenis</option>
          <option
            v-for="type in contributionTypes"
            :key="type.id"
            :value="type.id"
          >
            {{ type.name }}
          </option>
        </select>
        <Button class="shadow-sm" @click="applyFilters">
          <Funnel class="mr-2 h-4 w-4" />Filter
        </Button>
        <Button as-child variant="outline">
          <Link :href="editSavings().url">
            <Settings2 class="mr-2 h-4 w-4" />
            Atur Simpanan
          </Link>
        </Button>
      </div>

      <div
        class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900"
      >
        <div
          class="mb-4 flex flex-col justify-between gap-2 md:flex-row md:items-center"
        >
          <div>
            <div class="text-sm font-medium">Batch payment</div>
            <div class="text-sm text-zinc-500">
              {{ selectedInvoiceIds.length }} tagihan dipilih dengan total sisa
              {{ formatCurrency(selectedRemainingTotal) }}.
            </div>
          </div>
          <Button
            v-if="selectedInvoiceIds.length > 0"
            variant="ghost"
            size="sm"
            @click="clearSelection"
          >
            <X class="mr-2 h-4 w-4" />Clear
          </Button>
        </div>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
          <label class="space-y-1">
            <span class="text-sm">Tanggal Bayar</span>
            <Input v-model="markPaidForm.paid_at" type="date" class="lg:w-44" />
          </label>
          <label class="space-y-1">
            <span class="text-sm">Metode</span>
            <select
              v-model="markPaidForm.payment_method"
              class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950 lg:w-40"
            >
              <option>CASH</option>
              <option>TRANSFER</option>
              <option>QRIS</option>
            </select>
          </label>
          <label class="space-y-1">
            <span class="text-sm">Nominal</span>
            <Input
              v-model="markPaidForm.amount"
              type="number"
              min="0"
              step="1000"
              :placeholder="formatCurrency(selectedRemainingTotal)"
            />
          </label>
          <label class="space-y-1 lg:flex-1">
            <span class="text-sm">Referensi</span>
            <Input
              v-model="markPaidForm.reference_no"
              placeholder="Nomor bukti"
            />
          </label>
          <Button
            :disabled="
              selectedInvoiceIds.length === 0 || markPaidForm.processing
            "
            @click="showBatchConfirm = true"
          >
            <CheckCircle2 class="mr-2 h-4 w-4" />Proses Batch Payment
          </Button>
        </div>
      </div>
      <div
        class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
      >
        <table class="w-full text-left text-sm">
          <thead
            class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"
          >
            <tr>
              <th class="px-4 py-3">
                <input
                  type="checkbox"
                  :checked="allPayableSelected"
                  :disabled="payableInvoiceIds.length === 0"
                  @change="toggleAllPayable"
                />
              </th>
              <th class="px-4 py-3">Anggota</th>
              <th>Jenis</th>
              <th>Periode</th>
              <th>Status</th>
              <th class="px-4 py-3 text-right">Nominal</th>
              <th class="px-4 py-3 text-right">Terbayar</th>
              <th class="px-4 py-3 text-right">Sisa</th>
              <th class="px-4 py-3 text-right">Collect</th>
              <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="invoice in invoices.data" :key="invoice.id">
              <td class="px-4 py-3">
                <input
                  v-model="selectedInvoiceIds"
                  type="checkbox"
                  :value="invoice.id"
                  :disabled="!isPayable(invoice)"
                />
              </td>
              <td class="px-4 py-3">
                {{ invoice.member?.name }}
                <div class="text-xs text-zinc-500">
                  {{ invoice.member?.member_no }}
                </div>
              </td>
              <td>{{ invoice.contribution_type?.name }}</td>
              <td>{{ invoice.period }}</td>
              <td>
                <span class="rounded-full border px-2 py-1 text-xs">{{
                  invoice.status
                }}</span>
              </td>
              <td class="px-4 py-3 text-right">
                {{ formatCurrency(invoice.amount) }}
              </td>
              <td class="px-4 py-3 text-right">
                {{ formatCurrency(invoice.paid_amount) }}
              </td>
              <td class="px-4 py-3 text-right">
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
                  class="ml-auto w-36 text-right"
                  :placeholder="String(remainingAmount(invoice))"
                />
                <span v-else class="text-xs text-zinc-400">-</span>
              </td>
              <td class="px-4 py-3 text-right">
                <Button
                  v-if="isPayable(invoice)"
                  size="sm"
                  variant="outline"
                  :disabled="markPaidForm.processing"
                  @click="
                    submitMarkPaid(
                      [invoice.id],
                      rowPaymentAmounts[invoice.id] || remainingAmount(invoice),
                    )
                  "
                  >Collect</Button
                >
                <Button
                  v-else-if="canResetPaidDues && invoice.status === 'PAID'"
                  size="sm"
                  variant="outline"
                  :disabled="resetPaidForm.processing"
                  @click="submitMarkUnpaid(invoice)"
                >
                  <RotateCcw class="mr-2 h-4 w-4" />Belum Bayar
                </Button>
              </td>
            </tr>
            <tr v-if="invoices.data.length === 0">
              <td colspan="10" class="px-4 py-10 text-center text-zinc-500">
                Belum ada tagihan.
              </td>
            </tr>
          </tbody>
        </table>
        <div
          v-if="invoices.links?.length > 3"
          class="flex flex-col gap-3 border-t px-4 py-3 text-sm text-zinc-500 md:flex-row md:items-center md:justify-between"
        >
          <div>
            Menampilkan {{ invoices.from }}-{{ invoices.to }} dari
            {{ invoices.total }} tagihan
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
      </div>
      <div
        v-if="showBatchConfirm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      >
        <div
          class="w-full max-w-2xl rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 shadow-xl dark:bg-zinc-950"
        >
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold">Konfirmasi Batch Payment</h2>
              <p class="mt-1 text-sm text-zinc-500">
                {{ selectedInvoiceIds.length }} tagihan akan dilunasi penuh
                dengan total {{ formatCurrency(selectedRemainingTotal) }}.
              </p>
            </div>
            <Button variant="ghost" size="sm" @click="showBatchConfirm = false">
              <X class="h-4 w-4" />
            </Button>
          </div>
          <div class="mt-4 max-h-80 overflow-auto rounded-md border">
            <table class="w-full text-left text-sm">
              <thead
                class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"
              >
                <tr>
                  <th class="px-3 py-2">Anggota</th>
                  <th>Jenis</th>
                  <th class="px-3 py-2 text-right">Sisa</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="invoice in selectedInvoices" :key="invoice.id">
                  <td class="px-3 py-2">
                    {{ invoice.member?.name }}
                    <div class="text-xs text-zinc-500">
                      {{ invoice.member?.member_no }}
                    </div>
                  </td>
                  <td>{{ invoice.contribution_type?.name }}</td>
                  <td class="px-3 py-2 text-right">
                    {{ formatCurrency(remainingAmount(invoice)) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-5 flex justify-end gap-2">
            <Button variant="outline" @click="showBatchConfirm = false">
              Batal
            </Button>
            <Button
              :disabled="markPaidForm.processing"
              @click="submitMarkPaid(selectedInvoiceIds)"
            >
              <CheckCircle2 class="mr-2 h-4 w-4" />Konfirmasi Bayar
            </Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
