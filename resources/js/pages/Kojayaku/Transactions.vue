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
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Transaksi Saya</h1>
        <p class="mt-1 text-sm text-muted-foreground">
          Riwayat pembelian di toko koperasi.
        </p>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Total Transaksi"
          :value="summary.total_transactions"
          :icon="ReceiptText"
        />
        <StatsCard
          label="Total Belanja"
          :value="formatCurrency(summary.total_amount)"
          :icon="Wallet"
          value-class="text-emerald-600 dark:text-emerald-400"
          icon-container-class="bg-emerald-50 dark:bg-emerald-900/30"
          icon-class="text-emerald-600 dark:text-emerald-400"
        />
        <StatsCard
          label="Total Item"
          :value="summary.total_items"
          :icon="Package"
        />
        <StatsCard
          label="Transaksi Terakhir"
          :value="summary.last_transaction_at ? formatDateTime(summary.last_transaction_at) : '-'"
          :icon="CalendarDays"
          value-class="text-lg"
        />
      </div>

      <div
        class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center"
      >
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <CalendarDays class="h-4 w-4" />
          <span>Filter:</span>
        </div>
        <Input v-model="dateFrom" type="date" class="sm:w-44" />
        <span class="text-sm text-muted-foreground">sampai</span>
        <Input v-model="dateTo" type="date" class="sm:w-44" />
        <Button variant="outline" size="sm" @click="applyFilters">
          Terapkan
        </Button>
        <Button
          v-if="dateFrom || dateTo"
          variant="ghost"
          size="sm"
          @click="resetFilters"
        >
          Reset
        </Button>
      </div>

      <div v-if="transactions.data.length === 0" class="rounded-xl border p-12 text-center">
        <ShoppingBag class="mx-auto h-12 w-12 text-muted-foreground/40" />
        <p class="mt-4 text-lg font-semibold text-muted-foreground">
          Belum ada transaksi
        </p>
        <p class="mt-1 text-sm text-muted-foreground">
          Transaksi pembelian di toko koperasi akan muncul di sini.
        </p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="transaction in transactions.data"
          :key="transaction.id"
          class="overflow-hidden rounded-xl border transition-shadow hover:shadow-md"
        >
          <div
            class="flex cursor-pointer items-center gap-4 p-4"
            @click="toggleExpand(transaction.id)"
          >
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10"
            >
              <ReceiptText class="h-5 w-5 text-primary" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <span class="font-semibold">{{ transaction.transaction_no }}</span>
                <Badge
                  :variant="statusBadge(transaction.status).variant"
                  class="text-[10px]"
                >
                  {{ statusBadge(transaction.status).label }}
                </Badge>
              </div>
              <div class="mt-0.5 text-sm text-muted-foreground">
                {{ formatDateTime(transaction.sold_at) }}
                <span v-if="transaction.cashier">
                  &middot; Kasir: {{ transaction.cashier.name }}
                </span>
              </div>
            </div>
            <div class="text-right">
              <div class="font-semibold">
                {{ formatCurrency(transaction.total_amount) }}
              </div>
              <div class="text-xs text-muted-foreground">
                {{ transaction.items.reduce((s, i) => s + i.quantity, 0) }} item
              </div>
            </div>
            <component
              :is="expandedIds.has(transaction.id) ? ChevronUp : ChevronDown"
              class="h-5 w-5 shrink-0 text-muted-foreground"
            />
          </div>

          <div
            v-if="expandedIds.has(transaction.id)"
            class="border-t bg-muted/20 px-4 py-3"
          >
            <div class="mb-3 grid gap-3 text-sm sm:grid-cols-3">
              <div>
                <span class="text-muted-foreground">Subtotal</span>
                <div class="font-medium">{{ formatCurrency(transaction.subtotal) }}</div>
              </div>
              <div>
                <span class="text-muted-foreground">Diskon</span>
                <div class="font-medium">{{ formatCurrency(transaction.discount_amount) }}</div>
              </div>
              <div>
                <span class="text-muted-foreground">Pembayaran</span>
                <div class="font-medium">
                  {{ transaction.payments.map((p) => paymentMethodLabel(p.payment_method)).join(', ') || '-' }}
                </div>
              </div>
            </div>

            <table class="w-full text-left text-sm">
              <thead class="text-xs uppercase text-muted-foreground">
                <tr>
                  <th class="pb-2 pr-4">Produk</th>
                  <th class="pb-2 pr-4 text-right">Qty</th>
                  <th class="pb-2 pr-4 text-right">Harga</th>
                  <th class="pb-2 text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in transaction.items"
                  :key="item.id"
                  class="border-t border-border/50"
                >
                  <td class="py-2 pr-4">{{ item.product?.name || 'Produk' }}</td>
                  <td class="py-2 pr-4 text-right">{{ item.quantity }}</td>
                  <td class="py-2 pr-4 text-right">{{ formatCurrency(item.unit_price) }}</td>
                  <td class="py-2 text-right font-medium">{{ formatCurrency(item.line_total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div
        v-if="transactions.last_page > 1"
        class="flex items-center justify-center gap-2"
      >
        <Button
          variant="outline"
          size="sm"
          :disabled="!transactions.prev_page_url"
          @click="router.get(transactions.prev_page_url ?? '')"
        >
          Sebelumnya
        </Button>
        <span class="text-sm text-muted-foreground">
          {{ transactions.current_page }} / {{ transactions.last_page }}
        </span>
        <Button
          variant="outline"
          size="sm"
          :disabled="!transactions.next_page_url"
          @click="router.get(transactions.next_page_url ?? '')"
        >
          Selanjutnya
        </Button>
      </div>
    </PageContainer>
  </AppLayout>
</template>
