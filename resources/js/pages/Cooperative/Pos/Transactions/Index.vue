<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { X } from "lucide-vue-next";
import { computed, ref } from "vue";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index, show } from "@/routes/cooperative/pos/transactions";
import type { PaginationMeta } from "@/types/pagination";

const props = defineProps<{
  transactions: PaginationMeta & {
    data: Array<{
      id: number;
      transaction_no: string;
      sold_at: string;
      total_amount: number | string;
      items_count: number;
    }>;
  };
  filters: {
    date_from?: string;
    date_to?: string;
    transaction_no?: string;
    member_id?: string;
    cashier_id?: string;
    payment_method?: string;
  };
  cashiers: { id: number; name: string }[];
  members: { id: number; member_no: string; name: string }[];
}>();

const dateFrom = ref(props.filters.date_from ?? "");
const dateTo = ref(props.filters.date_to ?? "");
const transactionNo = ref(props.filters.transaction_no ?? "");
const memberId = ref(props.filters.member_id ?? "");
const cashierId = ref(props.filters.cashier_id ?? "");
const paymentMethod = ref(props.filters.payment_method ?? "");

const applyFilters = () =>
  router.get(
    index().url,
    {
      date_from: dateFrom.value || undefined,
      date_to: dateTo.value || undefined,
      transaction_no: transactionNo.value || undefined,
      member_id: memberId.value || undefined,
      cashier_id: cashierId.value || undefined,
      payment_method: paymentMethod.value || undefined,
    },
    { preserveState: true, replace: true },
  );

const resetFilters = () => {
  dateFrom.value = "";
  dateTo.value = "";
  transactionNo.value = "";
  memberId.value = "";
  cashierId.value = "";
  paymentMethod.value = "";
  router.get(index().url, {}, { preserveState: true, replace: true });
};

const hasActiveFilters = computed(
  () =>
    dateFrom.value ||
    dateTo.value ||
    transactionNo.value ||
    memberId.value ||
    cashierId.value ||
    paymentMethod.value,
);

const columns = [
  { header: "Transaksi", key: "transaction_no", slot: "transaction" },
  { header: "Kasir", key: "cashier.name", slot: "cashier" },
  { header: "Anggota", key: "member.name", slot: "member" },
  { header: "Metode", key: "payment_method", slot: "method" },
  { header: "Items", key: "items_count", align: "right" as const },
  {
    header: "Total",
    key: "total_amount",
    slot: "total",
    align: "right" as const,
  },
];
</script>

<template>
  <Head title="Riwayat POS" />
  <AppLayout
    :breadcrumbs="[
      { title: 'POS Toko', href: '#' },
      { title: 'Riwayat Transaksi', href: index().url },
    ]"
  >
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Riwayat Transaksi POS"
        description="Telusuri penjualan kasir berdasarkan tanggal, transaksi, anggota, dan metode pembayaran."
      />
      <WorkspaceSummary
        :items="[
          {
            label: 'Hasil Filter',
            value: transactions.total ?? transactions.data.length,
          },
          { label: 'Transaksi Ditampilkan', value: transactions.data.length },
          {
            label: 'Nominal di Halaman Ini',
            value: formatCurrency(
              transactions.data.reduce(
                (sum, row) => sum + Number(row.total_amount),
                0,
              ),
            ),
            tone: 'emerald',
          },
        ]"
      />
      <div
        class="grid items-end gap-2 rounded-xl border p-3 sm:grid-cols-2 xl:grid-cols-[repeat(6,minmax(0,1fr))_auto]"
      >
        <div class="space-y-1">
          <label
            class="text-[11px] font-medium uppercase text-zinc-500"
            for="tx-from"
            >Mulai</label
          >
          <Input id="tx-from" v-model="dateFrom" type="date" class="w-full" />
        </div>
        <div class="space-y-1">
          <label
            class="text-[11px] font-medium uppercase text-zinc-500"
            for="tx-to"
            >Sampai</label
          >
          <Input id="tx-to" v-model="dateTo" type="date" class="w-full" />
        </div>
        <div class="space-y-1">
          <label
            class="text-[11px] font-medium uppercase text-zinc-500"
            for="tx-no"
            >No. Transaksi</label
          >
          <Input
            id="tx-no"
            v-model="transactionNo"
            placeholder="POS-…"
            class="w-full"
          />
        </div>
        <div class="space-y-1">
          <label
            class="text-[11px] font-medium uppercase text-zinc-500"
            for="tx-member"
            >Anggota</label
          >
          <select
            id="tx-member"
            v-model="memberId"
            class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300 dark:border-zinc-800 dark:bg-zinc-950"
          >
            <option value="">Semua anggota</option>
            <option v-for="m in members" :key="m.id" :value="m.id">
              {{ m.member_no }} - {{ m.name }}
            </option>
          </select>
        </div>
        <div class="space-y-1">
          <label
            class="text-[11px] font-medium uppercase text-zinc-500"
            for="tx-cashier"
            >Kasir</label
          >
          <select
            id="tx-cashier"
            v-model="cashierId"
            class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300 dark:border-zinc-800 dark:bg-zinc-950"
          >
            <option value="">Semua kasir</option>
            <option v-for="c in cashiers" :key="c.id" :value="c.id">
              {{ c.name }}
            </option>
          </select>
        </div>
        <div class="space-y-1">
          <label
            class="text-[11px] font-medium uppercase text-zinc-500"
            for="tx-method"
            >Metode</label
          >
          <select
            id="tx-method"
            v-model="paymentMethod"
            class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300 dark:border-zinc-800 dark:bg-zinc-950"
          >
            <option value="">Semua metode</option>
            <option value="CASH">CASH</option>
            <option value="TRANSFER">TRANSFER</option>
            <option value="QRIS">QRIS</option>
            <option value="MEMBER_CREDIT">Kredit Anggota</option>
          </select>
        </div>
        <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-1">
          <Button size="sm" @click="applyFilters">Terapkan Filter</Button>
          <Button
            v-if="hasActiveFilters"
            variant="outline"
            type="button"
            @click="resetFilters"
          >
            <X class="mr-1.5 size-4" /> Reset
          </Button>
        </div>
      </div>

      <Card
        class="gap-0 rounded-xl py-0 overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <div class="flex items-center justify-between border-b px-4 py-3">
          <h2 class="text-sm font-semibold">Daftar Transaksi</h2>
        </div>
        <CardContent class="px-0 pb-0">
          <DataTable
            :columns="
              columns.map((column) => ({
                ...column,
                headerClass: 'max-w-none! whitespace-nowrap',
                class: 'max-w-none! whitespace-nowrap',
              }))
            "
            compact
            table-class="min-w-[850px] table-auto"
            :data="transactions"
            :searchable="false"
            empty-message="Belum ada transaksi POS."
          >
            <template #transaction="{ row }">
              <Link
                class="font-semibold text-zinc-950 hover:text-indigo-600 dark:text-white"
                :href="show(row.id).url"
                prefetch
                >{{ row.transaction_no }}</Link
              >
              <div class="text-xs text-zinc-500">
                {{ new Date(row.sold_at).toLocaleString("id-ID") }}
              </div>
            </template>
            <template #cashier="{ value }">
              {{ value || "-" }}
            </template>
            <template #member="{ value }">
              {{ value || "-" }}
            </template>
            <template #method="{ row }">
              <Badge
                variant="outline"
                class="bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                >{{ row.payments?.[0]?.payment_method || "-" }}</Badge
              >
            </template>
            <template #total="{ value }">
              <span
                class="font-bold tabular-nums text-emerald-700 dark:text-emerald-300"
                >{{ formatCurrency(value) }}</span
              >
            </template>
          </DataTable>
        </CardContent>
      </Card>
    </PageContainer>
  </AppLayout>
</template>
