<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { CheckCircle2, RefreshCw } from "lucide-vue-next";
import { computed, ref } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { generate, index, markPaid } from "@/routes/cooperative/dues";

const props = defineProps<{
  invoices: any;
  filters: { period?: string; status?: string };
  contributionTypes: any[];
}>();
const period = ref(
  props.filters.period ?? new Date().toISOString().slice(0, 7),
);
const status = ref(props.filters.status ?? "");
const form = useForm({ period: period.value });
const selectedInvoiceIds = ref<number[]>([]);
const markPaidForm = useForm<{
  invoice_ids: number[];
  paid_at: string;
  payment_method: string;
  reference_no: string;
  notes: string;
}>({
  invoice_ids: [],
  paid_at: new Date().toISOString().slice(0, 10),
  payment_method: "CASH",
  reference_no: "",
  notes: "",
});

const applyFilters = () =>
  router.get(
    index().url,
    { period: period.value, status: status.value },
    { preserveState: true, replace: true },
  );
const submitGenerate = () => {
  form.period = period.value;
  form.post(generate().url);
};
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

const toggleAllPayable = () => {
  selectedInvoiceIds.value = allPayableSelected.value
    ? []
    : [...payableInvoiceIds.value];
};

const submitMarkPaid = (invoiceIds: number[]) => {
  markPaidForm.invoice_ids = invoiceIds;
  markPaidForm.post(markPaid().url, {
    preserveScroll: true,
    onSuccess: () => {
      selectedInvoiceIds.value = selectedInvoiceIds.value.filter(
        (id) => !invoiceIds.includes(id),
      );
    },
  });
};
</script>

<template>
  <Head title="Iuran & Simpanan" />
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
          <h1 class="text-3xl font-bold tracking-tight">Iuran & Simpanan</h1>
          <p class="mt-1 text-sm text-zinc-500">
            Generate dan pantau tagihan anggota per periode.
          </p>
        </div>
        <Button v-can="'manage_cooperative_dues'" @click="submitGenerate" :disabled="form.processing"
          ><RefreshCw class="mr-2 h-4 w-4" />Generate Periode</Button
        >
      </div>
      <div
        class="flex flex-col gap-3 rounded-lg border bg-white p-4 dark:bg-zinc-900 md:flex-row"
      >
        <Input v-model="period" type="month" class="md:w-48" />
        <select
          v-model="status"
          class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
        >
          <option value="">Semua status</option>
          <option>UNPAID</option>
          <option>PARTIAL</option>
          <option>PAID</option>
          <option>VOID</option>
        </select>
        <Button variant="outline" @click="applyFilters">Filter</Button>
      </div>
      <div
        class="flex flex-col gap-3 rounded-lg border bg-white p-4 dark:bg-zinc-900 lg:flex-row lg:items-end"
      >
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
        <label class="space-y-1 lg:flex-1">
          <span class="text-sm">Referensi</span>
          <Input
            v-model="markPaidForm.reference_no"
            placeholder="Nomor bukti"
          />
        </label>
        <Button
          v-can="'manage_cooperative_dues'"
          :disabled="selectedInvoiceIds.length === 0 || markPaidForm.processing"
          @click="submitMarkPaid(selectedInvoiceIds)"
        >
          <CheckCircle2 class="mr-2 h-4 w-4" />Sudah Membayar
        </Button>
      </div>
      <div class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
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
                <Button
                  v-if="isPayable(invoice)"
                  size="sm"
                  variant="outline"
                  :disabled="markPaidForm.processing"
                  @click="submitMarkPaid([invoice.id])"
                  >Sudah Bayar</Button
                >
              </td>
            </tr>
            <tr v-if="invoices.data.length === 0">
              <td colspan="9" class="px-4 py-10 text-center text-zinc-500">
                Belum ada tagihan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
