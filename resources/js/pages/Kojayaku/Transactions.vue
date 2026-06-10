<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import {
  CalendarDays,
  ChevronDown,
  ChevronUp,
  CreditCard,
  Package,
  ReceiptText,
  ShoppingBag,
  Wallet,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import StatsCard from "@/components/StatsCard.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDateTime } from "@/lib/formatters";

const props = defineProps<{
  transactions: {
    data: Array<{
      id: number;
      transaction_no: string;
      sold_at: string;
      subtotal: number | string;
      discount_amount: number | string;
      total_amount: number | string;
      status: string;
      items: Array<{
        id: number;
        quantity: number;
        unit_price: number | string;
        line_total: number | string;
        product?: { name: string } | null;
      }>;
      payments: Array<{
        id: number;
        payment_method: string;
        amount: number | string;
        reference_no?: string | null;
      }>;
      cashier?: { name: string } | null;
    }>;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
  };
  summary: {
    total_transactions: number;
    total_amount: number;
    total_items: number;
    last_transaction_at: string | null;
  };
  filters: {
    date_from: string | null;
    date_to: string | null;
  };
}>();

const dateFrom = ref(props.filters.date_from ?? "");
const dateTo = ref(props.filters.date_to ?? "");
const expandedIds = ref<Set<number>>(new Set());

function toggleExpand(id: number): void {
  if (expandedIds.value.has(id)) {
    expandedIds.value.delete(id);
  } else {
    expandedIds.value.add(id);
  }
}

function applyFilters(): void {
  router.get(
    "/member/transactions",
    {
      ...(dateFrom.value ? { date_from: dateFrom.value } : {}),
      ...(dateTo.value ? { date_to: dateTo.value } : {}),
    },
    { preserveState: true, replace: true },
  );
}

function resetFilters(): void {
  dateFrom.value = "";
  dateTo.value = "";
  router.get("/member/transactions", {}, { preserveState: true, replace: true });
}

const paymentMethodLabel = (method: string): string => {
  const map: Record<string, string> = {
    CASH: "Tunai",
    CASHLESS: "Non-tunai",
    QRIS: "QRIS",
    TRANSFER: "Transfer",
    DEBIT: "Debit",
    CREDIT: "Kredit",
  };
  return map[method] ?? method;
};

const statusBadge = (status: string) => {
  if (status === "COMPLETED" || status === "PAID")
    return { label: "Selesai", variant: "default" as const };
  if (status === "REFUNDED" || status === "RETURNED")
    return { label: "Dikembalikan", variant: "destructive" as const };
  if (status === "PENDING")
    return { label: "Pending", variant: "secondary" as const };
  return { label: status, variant: "outline" as const };
};

const totalItems = computed(() =>
  props.transactions.data.reduce(
    (sum, t) => sum + t.items.reduce((s, i) => s + i.quantity, 0),
    0,
  ),
);
</script>

<template>
  <Head title="Transaksi Saya" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Kojayaku', href: '/member' },
      { title: 'Transaksi', href: '/member/transactions' },
    ]"
  >
    <PageContainer>
      <div class="flex flex-col gap-6">
        <header class="flex items-center gap-5">
          <div
            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 text-white shadow-lg shadow-emerald-800/20"
          >
            <ShoppingBag class="h-8 w-8" />
          </div>
          <div>
            <h1 class="text-3xl font-extrabold text-zinc-900 tracking-tight">Transaksi Saya</h1>
            <p class="mt-1 text-sm text-zinc-500">
              Riwayat transaksi pembelian produk Anda di toko koperasi.
            </p>
          </div>
        </header>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <StatsCard
            label="Total Transaksi"
            :value="summary.total_transactions"
            :icon="ReceiptText"
            class="rounded-3xl border border-zinc-150 shadow-sm"
          />
          <StatsCard
            label="Total Belanja"
            :value="formatCurrency(summary.total_amount)"
            :icon="Wallet"
            value-class="text-emerald-855 dark:text-emerald-400 font-extrabold text-2xl"
            icon-container-class="bg-emerald-50 dark:bg-emerald-900/30"
            icon-class="text-emerald-700 dark:text-emerald-400"
            class="rounded-3xl border border-zinc-150 shadow-sm"
          />
          <StatsCard
            label="Total Item"
            :value="summary.total_items"
            :icon="Package"
            class="rounded-3xl border border-zinc-150 shadow-sm"
          />
          <StatsCard
            label="Transaksi Terakhir"
            :value="summary.last_transaction_at ? formatDateTime(summary.last_transaction_at) : '-'"
            :icon="CalendarDays"
            value-class="text-sm font-bold text-zinc-800"
            class="rounded-3xl border border-zinc-150 shadow-sm"
          />
        </div>

        <div
          class="flex flex-col gap-4 rounded-2xl border border-zinc-100 bg-white p-5 sm:flex-row sm:items-center shadow-sm"
        >
          <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-zinc-400">
            <CalendarDays class="h-4.5 w-4.5 text-zinc-400" />
            <span>Filter Periode:</span>
          </div>
          <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
              <Input v-model="dateFrom" type="date" class="sm:w-44 rounded-xl" />
              <span class="text-xs font-semibold text-zinc-400">sampai</span>
              <Input v-model="dateTo" type="date" class="sm:w-44 rounded-xl" />
          </div>
          <div class="flex gap-2.5 sm:ml-auto">
              <Button variant="outline" size="sm" class="rounded-xl px-4 py-2 font-bold text-xs uppercase" @click="applyFilters">
                Terapkan
              </Button>
              <Button
                v-if="dateFrom || dateTo"
                variant="ghost"
                size="sm"
                class="rounded-xl px-4 py-2 font-bold text-xs uppercase"
                @click="resetFilters"
              >
                Reset
              </Button>
          </div>
        </div>

        <div v-if="transactions.data.length === 0" class="rounded-3xl border border-dashed border-zinc-200 bg-white p-12 text-center">
          <ShoppingBag class="mx-auto h-12 w-12 text-zinc-300" />
          <p class="mt-4 text-lg font-bold text-zinc-850">
            Belum ada transaksi
          </p>
          <p class="mt-1 text-sm text-zinc-500">
            Transaksi pembelian di toko koperasi akan muncul di sini.
          </p>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="transaction in transactions.data"
            :key="transaction.id"
            class="overflow-hidden rounded-2xl border border-zinc-100 bg-white transition-all duration-300 hover:shadow-md hover:border-zinc-200"
          >
            <div
              class="flex cursor-pointer items-center gap-4 p-5"
              @click="toggleExpand(transaction.id)"
            >
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 shadow-sm"
              >
                <ReceiptText class="h-5 w-5" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-zinc-800">{{ transaction.transaction_no }}</span>
                  <Badge
                    :variant="statusBadge(transaction.status).variant"
                    class="text-[9px] font-extrabold uppercase tracking-wider rounded-md"
                  >
                    {{ statusBadge(transaction.status).label }}
                  </Badge>
                </div>
                <div class="mt-1 text-xs text-zinc-400">
                  {{ formatDateTime(transaction.sold_at) }}
                  <span v-if="transaction.cashier" class="ml-1 text-zinc-400">
                    &middot; Kasir: {{ transaction.cashier.name }}
                  </span>
                </div>
              </div>
              <div class="text-right mr-2">
                <div class="font-extrabold text-zinc-900 text-base">
                  {{ formatCurrency(transaction.total_amount) }}
                </div>
                <div class="text-[10px] font-semibold text-zinc-400 mt-0.5">
                  {{ transaction.items.reduce((s, i) => s + i.quantity, 0) }} item
                </div>
              </div>
              <component
                :is="expandedIds.has(transaction.id) ? ChevronUp : ChevronDown"
                class="h-5 w-5 shrink-0 text-zinc-400"
              />
            </div>

            <div
              v-if="expandedIds.has(transaction.id)"
              class="border-t border-zinc-50 bg-zinc-50/20 px-6 py-4"
            >
              <div class="mb-4 grid gap-4 text-xs sm:grid-cols-3">
                <div>
                  <span class="text-zinc-400 font-bold uppercase tracking-wider">Subtotal</span>
                  <div class="font-extrabold text-zinc-800 text-sm mt-1">{{ formatCurrency(transaction.subtotal) }}</div>
                </div>
                <div>
                  <span class="text-zinc-400 font-bold uppercase tracking-wider">Diskon</span>
                  <div class="font-extrabold text-zinc-800 text-sm mt-1">{{ formatCurrency(transaction.discount_amount) }}</div>
                </div>
                <div>
                  <span class="text-zinc-400 font-bold uppercase tracking-wider">Metode Pembayaran</span>
                  <div class="font-extrabold text-zinc-850 text-sm mt-1">
                    {{ transaction.payments.map((p) => paymentMethodLabel(p.payment_method)).join(', ') || '-' }}
                  </div>
                </div>
              </div>

              <table class="w-full text-left text-xs">
                <thead class="text-[9px] font-bold uppercase tracking-wider text-zinc-400">
                  <tr>
                    <th class="pb-3 pr-4">Produk</th>
                    <th class="pb-3 pr-4 text-right">Quantity</th>
                    <th class="pb-3 pr-4 text-right">Harga Satuan</th>
                    <th class="pb-3 text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="item in transaction.items"
                    :key="item.id"
                    class="border-t border-zinc-100"
                  >
                    <td class="py-3 pr-4 font-semibold text-zinc-700">{{ item.product?.name || 'Produk' }}</td>
                    <td class="py-3 pr-4 text-right font-medium text-zinc-550">{{ item.quantity }}</td>
                    <td class="py-3 pr-4 text-right text-zinc-500">{{ formatCurrency(item.unit_price) }}</td>
                    <td class="py-3 text-right font-bold text-zinc-900">{{ formatCurrency(item.line_total) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div
          v-if="transactions.last_page > 1"
          class="flex items-center justify-center gap-3 mt-4"
        >
          <Button
            variant="outline"
            size="sm"
            class="rounded-xl px-4"
            :disabled="!transactions.prev_page_url"
            @click="router.get(transactions.prev_page_url ?? '')"
          >
            Sebelumnya
          </Button>
          <span class="text-xs font-semibold text-zinc-500">
            Halaman {{ transactions.current_page }} dari {{ transactions.last_page }}
          </span>
          <Button
            variant="outline"
            size="sm"
            class="rounded-xl px-4"
            :disabled="!transactions.next_page_url"
            @click="router.get(transactions.next_page_url ?? '')"
          >
            Selanjutnya
          </Button>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
