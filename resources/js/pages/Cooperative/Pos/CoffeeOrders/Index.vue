<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { Check, Coffee, Search, X } from "lucide-vue-next";
import { computed, ref } from "vue";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspacePagination from "@/components/dashboard/WorkspacePagination.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import InfoTooltip from "@/components/InfoTooltip.vue";
import InputError from "@/components/InputError.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index, updateStatus } from "@/routes/cooperative/pos/coffee-orders";
import type { PaginationMeta } from "@/types/pagination";

type CoffeeOrder = {
  id: number;
  status: string;
  status_label: string;
  quantity: number;
  customization?: Record<string, string>;
  received_at?: string;
  brewing_at?: string;
  ready_at?: string;
  notes?: string;
  transaction?: {
    transaction_no?: string;
    total_amount?: number;
    payment_method?: string;
  };
  member?: {
    member_no?: string;
    name?: string;
  };
  product?: {
    name?: string;
  };
  preparer?: {
    name?: string;
  };
};

const props = defineProps<{
  orders: PaginationMeta & {
    data: CoffeeOrder[];
    total?: number;
  };
  filters: {
    status?: string;
    search?: string;
  };
  statuses: string[];
}>();

const columns = [
  { header: "Pesanan", key: "transaction.transaction_no", slot: "order" },
  { header: "Anggota", key: "member.name", slot: "member" },
  { header: "Menu & Pilihan", key: "product.name", slot: "menu", width: "30%" },
  { header: "Status", key: "status", slot: "status" },
  {
    header: "Total",
    key: "transaction.total_amount",
    slot: "total",
    align: "right" as const,
  },
  { header: "Aksi", key: "id", slot: "actions", align: "right" as const },
].map((column) => ({
  ...column,
  headerClass: "max-w-none! whitespace-nowrap",
  class:
    column.slot === "menu"
      ? "max-w-none! min-w-[260px] whitespace-normal"
      : "max-w-none! whitespace-nowrap",
}));

const status = ref(props.filters.status ?? "");
const search = ref(props.filters.search ?? "");
const activeId = ref<number | null>(null);
const form = useForm({ status: "RECEIVED", notes: "" });

const statusLabels: Record<string, string> = {
  RECEIVED: "Diterima",
  BREWING: "Diseduh",
  READY: "Siap Diambil",
  PICKED_UP: "Selesai",
  CANCELLED: "Batal",
};

const statusTone: Record<string, string> = {
  RECEIVED:
    "border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200",
  BREWING:
    "border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-200",
  READY:
    "border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200",
  PICKED_UP:
    "border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200",
  CANCELLED:
    "border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200",
};

const hasActiveFilters = computed(() => status.value || search.value);

const applyFilters = () => {
  router.get(
    index().url,
    {
      status: status.value || undefined,
      search: search.value || undefined,
    },
    { preserveState: true, replace: true },
  );
};

const resetFilters = () => {
  status.value = "";
  search.value = "";
  router.get(index().url, {}, { preserveState: true, replace: true });
};

const update = (order: CoffeeOrder, nextStatus: string) => {
  activeId.value = order.id;
  form.status = nextStatus;
  form.put(updateStatus(order.id).url, {
    preserveScroll: true,
    onFinish: () => {
      activeId.value = null;
    },
  });
};

const nextActions = (order: CoffeeOrder) => {
  if (order.status === "RECEIVED") return ["BREWING", "CANCELLED"];
  if (order.status === "BREWING") return ["READY", "CANCELLED"];
  if (order.status === "READY") return ["PICKED_UP"];
  return [];
};

const formatTime = (value?: string) =>
  value ? new Date(value).toLocaleString("id-ID") : "-";
</script>

<template>
  <Head title="Pesanan Kopi" />
  <AppLayout
    :breadcrumbs="[
      { title: 'POS Toko', href: '#' },
      { title: 'Pesanan Kopi', href: index().url },
    ]"
  >
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Pesanan Kopi"
        description="Pantau pesanan anggota, pilihan menu, dan status persiapan sampai selesai diambil."
      />
      <WorkspaceSummary
        :items="[
          { label: 'Hasil Filter', value: orders.total ?? orders.data.length },
          {
            label: 'Aktif di Halaman Ini',
            value: orders.data.filter((order) =>
              ['RECEIVED', 'BREWING', 'READY'].includes(order.status),
            ).length,
          },
          {
            label: 'Siap di Halaman Ini',
            value: orders.data.filter((order) => order.status === 'READY')
              .length,
            tone: 'emerald',
          },
          {
            label: 'Selesai di Halaman Ini',
            value: orders.data.filter((order) => order.status === 'PICKED_UP')
              .length,
          },
        ]"
      />

      <div
        class="grid gap-2 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-[220px_1fr_auto]"
      >
        <select
          aria-label="Status pesanan"
          v-model="status"
          class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-800 dark:bg-zinc-950"
        >
          <option value="">Status aktif</option>
          <option v-for="s in statuses" :key="s" :value="s">
            {{ statusLabels[s] ?? s }}
          </option>
        </select>
        <div class="relative">
          <Search
            class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400"
          />
          <Input
            v-model="search"
            class="pl-9"
            placeholder="Cari kode transaksi, anggota, atau menu"
            aria-label="Cari pesanan kopi"
            @keyup.enter="applyFilters"
          />
        </div>
        <div class="flex gap-2">
          <Button type="button" @click="applyFilters">Terapkan</Button>
          <Button
            v-if="hasActiveFilters"
            type="button"
            variant="outline"
            @click="resetFilters"
          >
            <X class="mr-1.5 size-4" /> Reset
          </Button>
        </div>
      </div>

      <InputError
        v-for="(message, field) in form.errors"
        :key="field"
        :message="message"
        role="alert"
      />
      <section
        class="hidden min-w-0 space-y-3 md:block"
        aria-label="Antrean pesanan kopi"
      >
        <h2 class="text-sm font-semibold">Daftar Pesanan</h2>
        <DataTable
          :columns="columns"
          :data="orders"
          :searchable="false"
          compact
          table-class="min-w-[1100px] table-auto"
          :empty-icon="Coffee"
          empty-message="Belum ada pesanan kopi pada filter ini."
        >
          <template #order="{ row }"
            ><div class="flex items-center gap-1">
              <span class="font-semibold">{{
                row.transaction?.transaction_no || "-"
              }}</span
              ><InfoTooltip :label="`Waktu pesanan ${row.id}`"
                ><p>Diterima: {{ formatTime(row.received_at) }}</p>
                <p>Diseduh: {{ formatTime(row.brewing_at) }}</p>
                <p>Siap: {{ formatTime(row.ready_at) }}</p>
                <p>Petugas: {{ row.preparer?.name || "-" }}</p></InfoTooltip
              >
            </div>
            <div class="text-xs text-zinc-500">
              {{ formatTime(row.received_at) }}
            </div></template
          >
          <template #member="{ row }"
            ><div class="font-medium">{{ row.member?.name || "-" }}</div>
            <div class="text-xs text-zinc-500">
              {{ row.member?.member_no || "-" }}
            </div></template
          >
          <template #menu="{ row }"
            ><div class="font-semibold">
              {{ row.product?.name || "-" }} · {{ row.quantity }} cup
            </div>
            <div class="mt-1 text-xs text-zinc-500">
              Gula {{ row.customization?.sugar_level || "Normal" }} ·
              {{ row.customization?.ice_level || "Normal" }} ·
              {{ row.customization?.cup_size || "Reguler" }}
            </div>
            <p
              v-if="row.notes"
              class="mt-1 whitespace-normal break-words text-xs"
            >
              {{ row.notes }}
            </p></template
          >
          <template #status="{ row }"
            ><Badge variant="outline" :class="statusTone[row.status]">{{
              statusLabels[row.status] ?? row.status
            }}</Badge></template
          >
          <template #total="{ value }"
            ><span class="font-semibold tabular-nums">{{
              formatCurrency(value)
            }}</span></template
          >
          <template #actions="{ row }"
            ><div class="flex justify-end gap-2">
              <Button
                v-for="next in nextActions(row)"
                :key="next"
                size="sm"
                :variant="next === 'CANCELLED' ? 'destructive' : 'default'"
                :disabled="form.processing && activeId === row.id"
                @click="update(row, next)"
                >{{
                  next === "BREWING"
                    ? "Mulai Seduh"
                    : next === "READY"
                      ? "Siap Diambil"
                      : next === "PICKED_UP"
                        ? "Selesai Diambil"
                        : "Batalkan"
                }}</Button
              ><span
                v-if="nextActions(row).length === 0"
                class="text-xs text-zinc-500"
                >Selesai diproses</span
              >
            </div></template
          >
        </DataTable>
      </section>
      <div
        v-if="orders.data.length === 0"
        class="md:hidden rounded-xl border border-dashed border-zinc-200 bg-white/60 p-10 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/40"
      >
        <Coffee class="mx-auto mb-2 size-8 text-zinc-300" />
        Belum ada pesanan kopi pada filter ini.
      </div>

      <div v-else class="grid gap-3 md:hidden">
        <Card
          v-for="order in orders.data"
          :key="order.id"
          class="gap-0 rounded-xl py-0 overflow-hidden"
        >
          <CardHeader
            class="flex flex-row items-start justify-between gap-3 p-4"
          >
            <div>
              <CardTitle class="text-base">
                {{ order.transaction?.transaction_no }}
              </CardTitle>
              <p class="mt-1 text-xs text-zinc-500">
                {{ formatTime(order.received_at) }} ·
                {{ order.member?.member_no || "-" }}
                {{ order.member?.name || "-" }}
              </p>
            </div>
            <Badge variant="outline" :class="statusTone[order.status]">
              {{ statusLabels[order.status] ?? order.status }}
            </Badge>
          </CardHeader>
          <CardContent class="space-y-3 px-4 pb-4">
            <div class="grid grid-cols-[minmax(0,1fr)_auto_auto] gap-3 text-sm">
              <div>
                <div class="text-xs text-zinc-500">Menu</div>
                <div class="font-semibold">
                  {{ order.product?.name || "-" }}
                </div>
              </div>
              <div>
                <div class="text-xs text-zinc-500">Qty</div>
                <div class="font-semibold">{{ order.quantity }} cup</div>
              </div>
              <div>
                <div class="text-xs text-zinc-500">Total</div>
                <div class="font-semibold">
                  {{ formatCurrency(order.transaction?.total_amount) }}
                </div>
              </div>
            </div>

            <div
              class="grid grid-cols-3 gap-2 rounded-md bg-zinc-50 p-3 text-xs break-words dark:bg-zinc-950"
            >
              <div>
                <div class="text-zinc-500">Gula</div>
                <div class="font-medium">
                  {{ order.customization?.sugar_level || "Normal" }}
                </div>
              </div>
              <div>
                <div class="text-zinc-500">Es/Suhu</div>
                <div class="font-medium">
                  {{ order.customization?.ice_level || "Normal" }}
                </div>
              </div>
              <div>
                <div class="text-zinc-500">Cup</div>
                <div class="font-medium">
                  {{ order.customization?.cup_size || "Reguler" }}
                </div>
              </div>
            </div>

            <div
              class="flex items-center justify-between gap-2 text-xs text-zinc-500"
            >
              <span>Petugas: {{ order.preparer?.name || "-" }}</span>
              <InfoTooltip :label="`Waktu pesanan ${order.id}`"
                ><p>Diterima: {{ formatTime(order.received_at) }}</p>
                <p>Diseduh: {{ formatTime(order.brewing_at) }}</p>
                <p>Siap: {{ formatTime(order.ready_at) }}</p></InfoTooltip
              >
            </div>
            <p
              v-if="order.notes"
              class="whitespace-normal break-words text-xs leading-relaxed"
            >
              {{ order.notes }}
            </p>
            <div
              v-if="nextActions(order).length > 0"
              class="flex flex-wrap gap-2"
            >
              <Button
                v-for="next in nextActions(order)"
                :key="next"
                type="button"
                :variant="next === 'CANCELLED' ? 'destructive' : 'default'"
                :disabled="form.processing && activeId === order.id"
                @click="update(order, next)"
              >
                <Check v-if="next !== 'CANCELLED'" class="mr-1.5 size-4" />
                {{
                  next === "BREWING"
                    ? "Mulai Seduh"
                    : next === "READY"
                      ? "Siap Diambil"
                      : next === "PICKED_UP"
                        ? "Selesai Diambil"
                        : "Batalkan"
                }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
      <WorkspacePagination
        :pagination="orders"
        label="Halaman pesanan kopi"
        class="md:hidden"
      />
    </PageContainer>
  </AppLayout>
</template>
