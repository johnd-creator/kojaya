<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { Check, Coffee, PackageCheck, Search, Timer, X } from "lucide-vue-next";
import { computed, ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index, updateStatus } from "@/routes/cooperative/pos/coffee-orders";

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
  orders: {
    data: CoffeeOrder[];
    total?: number;
  };
  filters: {
    status?: string;
    search?: string;
  };
  statuses: string[];
}>();

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
  RECEIVED: "border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200",
  BREWING: "border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-200",
  READY: "border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200",
  PICKED_UP: "border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200",
  CANCELLED: "border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200",
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
    <PageContainer class="max-w-none">
      <section
        class="grid gap-4 border-b border-zinc-200 pb-5 dark:border-zinc-800 lg:grid-cols-[1fr_auto]"
      >
        <div>
          <div class="flex items-center gap-2 text-sm font-semibold text-amber-700 dark:text-amber-300">
            <Coffee class="size-4" />
            Kantin Kojaya
          </div>
          <h1 class="mt-2 text-2xl font-bold tracking-tight text-zinc-950 sm:text-3xl dark:text-zinc-100">
            Pesanan Kopi
          </h1>
          <p class="mt-1 max-w-2xl text-sm text-zinc-500">
            Pantau pesanan dari aplikasi anggota dan update status yang akan dibaca langsung oleh tracker Flutter.
          </p>
        </div>
        <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
          <div class="rounded-md border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500">Total tampil</div>
            <div class="text-xl font-bold">{{ orders.total ?? orders.data.length }}</div>
          </div>
          <div class="rounded-md border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500">Aktif</div>
            <div class="text-xl font-bold">
              {{ orders.data.filter((o) => ['RECEIVED', 'BREWING', 'READY'].includes(o.status)).length }}
            </div>
          </div>
          <div class="rounded-md border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500">Siap</div>
            <div class="text-xl font-bold">
              {{ orders.data.filter((o) => o.status === 'READY').length }}
            </div>
          </div>
        </div>
      </section>

      <div
        class="grid gap-3 rounded-md border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-[220px_1fr_auto]"
      >
        <select
          v-model="status"
          class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-800 dark:bg-zinc-950"
        >
          <option value="">Status aktif</option>
          <option v-for="s in statuses" :key="s" :value="s">
            {{ statusLabels[s] ?? s }}
          </option>
        </select>
        <div class="relative">
          <Search class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
          <Input
            v-model="search"
            class="pl-9"
            placeholder="Cari kode transaksi, anggota, atau menu"
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

      <div
        v-if="orders.data.length === 0"
        class="rounded-md border border-dashed border-zinc-200 bg-white/60 p-10 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/40"
      >
        <Coffee class="mx-auto mb-2 size-8 text-zinc-300" />
        Belum ada pesanan kopi pada filter ini.
      </div>

      <div v-else class="grid gap-3 xl:grid-cols-2">
        <Card v-for="order in orders.data" :key="order.id" class="overflow-hidden">
          <CardHeader class="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle class="text-base">
                {{ order.transaction?.transaction_no }}
              </CardTitle>
              <p class="mt-1 text-xs text-zinc-500">
                {{ formatTime(order.received_at) }} · {{ order.member?.member_no || '-' }} {{ order.member?.name || '-' }}
              </p>
            </div>
            <Badge variant="outline" :class="statusTone[order.status]">
              {{ statusLabels[order.status] ?? order.status }}
            </Badge>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid gap-3 text-sm sm:grid-cols-3">
              <div>
                <div class="text-xs text-zinc-500">Menu</div>
                <div class="font-semibold">{{ order.product?.name || '-' }}</div>
              </div>
              <div>
                <div class="text-xs text-zinc-500">Qty</div>
                <div class="font-semibold">{{ order.quantity }} cup</div>
              </div>
              <div>
                <div class="text-xs text-zinc-500">Total</div>
                <div class="font-semibold">{{ formatCurrency(order.transaction?.total_amount) }}</div>
              </div>
            </div>

            <div class="grid gap-2 rounded-md bg-zinc-50 p-3 text-xs dark:bg-zinc-950 sm:grid-cols-3">
              <div>
                <div class="text-zinc-500">Gula</div>
                <div class="font-medium">{{ order.customization?.sugar_level || 'Normal' }}</div>
              </div>
              <div>
                <div class="text-zinc-500">Es/Suhu</div>
                <div class="font-medium">{{ order.customization?.ice_level || 'Normal' }}</div>
              </div>
              <div>
                <div class="text-zinc-500">Cup</div>
                <div class="font-medium">{{ order.customization?.cup_size || 'Reguler' }}</div>
              </div>
            </div>

            <div class="grid gap-2 text-xs sm:grid-cols-3">
              <div class="flex items-center gap-2">
                <Timer class="size-4 text-amber-500" />
                <span>Diterima {{ formatTime(order.received_at) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <Coffee class="size-4 text-sky-500" />
                <span>Diseduh {{ formatTime(order.brewing_at) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <PackageCheck class="size-4 text-emerald-500" />
                <span>Siap {{ formatTime(order.ready_at) }}</span>
              </div>
            </div>

            <div v-if="nextActions(order).length > 0" class="flex flex-wrap gap-2">
              <Button
                v-for="next in nextActions(order)"
                :key="next"
                type="button"
                :variant="next === 'CANCELLED' ? 'destructive' : 'default'"
                :disabled="form.processing && activeId === order.id"
                @click="update(order, next)"
              >
                <Check v-if="next !== 'CANCELLED'" class="mr-1.5 size-4" />
                {{ next === 'BREWING' ? 'Mulai Seduh' : next === 'READY' ? 'Siap Diambil' : next === 'PICKED_UP' ? 'Selesai Diambil' : 'Batalkan' }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </PageContainer>
  </AppLayout>
</template>
