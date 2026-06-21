<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { Layers, Sparkles, X } from "lucide-vue-next";
import { computed, ref } from "vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index, show } from "@/routes/cooperative/pos/transactions";

const props = defineProps<{
  transactions: any;
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
    <PageContainer class="max-w-none">
      <section
        class="relative overflow-hidden rounded-2xl border border-emerald-200/60 bg-gradient-to-br from-white via-emerald-50/60 to-sky-50/40 p-6 shadow-sm shadow-emerald-950/5 sm:p-7 dark:border-emerald-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      >
        <div
          class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"
          aria-hidden="true"
        />
        <div
          class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
        >
          <div class="space-y-3">
            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-800/60"
            >
              <Sparkles class="size-3.5" />
              Riwayat Kasir
            </span>
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
            >
              Riwayat Transaksi POS
            </h1>
            <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
              Transaksi kasir toko koperasi.
            </p>
          </div>
        </div>

        <div
          class="relative mt-4 grid grid-cols-1 gap-3 rounded-xl border border-white/70 bg-white/70 p-3 shadow-sm backdrop-blur sm:grid-cols-2 lg:grid-cols-6 dark:border-zinc-800/80 dark:bg-zinc-950/40"
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
          <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
            <Button class="flex-1" @click="applyFilters">Terapkan Filter</Button>
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
      </section>

      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Daftar Transaksi"
          :description="`${transactions.total ?? 0} transaksi`"
          :icon="Layers"
          tone="emerald"
        />
        <CardContent class="px-0 pb-0">
          <DataTable
            :columns="columns"
            :data="transactions"
            :searchable="false"
            empty-message="Belum ada transaksi POS."
          >
            <template #transaction="{ row }">
              <Link
                class="font-semibold text-zinc-950 hover:text-indigo-600 dark:text-white"
                :href="show(row.id).url" prefetch
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
          <div
            v-if="transactions.data?.length === 0"
            class="flex flex-col items-center gap-2 px-6 py-12 text-sm text-zinc-500"
          >
            <Layers class="size-8 text-zinc-300 dark:text-zinc-700" />
            <p>Belum ada transaksi dengan filter saat ini.</p>
          </div>
        </CardContent>
      </Card>
    </PageContainer>
  </AppLayout>
</template>
