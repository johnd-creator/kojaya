<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import {
  CalendarDays,
  ChevronDown,
  ChevronUp,
  ReceiptText,
  ShoppingBag,
  Wallet,
  WalletCards,
} from "lucide-vue-next";
import { ref } from "vue";
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
      id: string;
      source: "pos" | "payment";
      title: string;
      subtitle: string;
      occurred_at: string | null;
      amount: number | string;
      status: string;
      line_items: Array<{
        name: string;
        quantity: number;
        amount: number | string;
      }>;
      payment_methods: string[];
    }>;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
  };
  summary: {
    total_activities: number;
    pos_count: number;
    payment_count: number;
    total_amount: number;
    last_activity_at: string | null;
  };
  filters: {
    date_from: string | null;
    date_to: string | null;
  };
}>();

const dateFrom = ref(props.filters.date_from ?? "");
const dateTo = ref(props.filters.date_to ?? "");
const expandedIds = ref<Set<string>>(new Set());

function toggleExpand(id: string): void {
  if (expandedIds.value.has(id)) {
    expandedIds.value.delete(id);
  } else {
    expandedIds.value.add(id);
  }
}

const panelId = (id: string): string => `activity-panel-${id.replace(/[^a-zA-Z0-9_-]/g, "-")}`;
const headerId = (id: string): string => `activity-header-${id.replace(/[^a-zA-Z0-9_-]/g, "-")}`;

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
  router.get(
    "/member/transactions",
    {},
    { preserveState: true, replace: true },
  );
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
  if (status === "COMPLETED" || status === "PAID" || status === "APPROVED")
    return { label: "Selesai", variant: "default" as const };
  if (status === "REFUNDED" || status === "RETURNED")
    return { label: "Dikembalikan", variant: "destructive" as const };
  if (status === "PENDING")
    return { label: "Pending", variant: "secondary" as const };
  return { label: status, variant: "outline" as const };
};
</script>

<template>
  <Head title="Aktivitas Keuangan" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Kojayaku', href: '/member' },
      { title: 'Aktivitas Keuangan', href: '/member/transactions' },
    ]"
  >
    <PageContainer>
      <div class="flex flex-col gap-6">
        <header class="flex items-center gap-3 sm:gap-5">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 text-white shadow-lg shadow-emerald-800/20 sm:h-16 sm:w-16"
          >
            <WalletCards class="h-6 w-6 sm:h-8 sm:w-8" />
          </div>
          <div>
            <h1
              class="text-2xl font-extrabold text-zinc-900 dark:text-white tracking-tight sm:text-3xl"
            >
              Aktivitas Keuangan
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
              Satu tempat untuk melihat transaksi toko dan pembayaran simpanan
              Anda.
            </p>
          </div>
        </header>

        <div
          class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-4"
        >
          <StatsCard
            label="Total Aktivitas"
            :value="summary.total_activities"
            :icon="ReceiptText"
            class="rounded-3xl border border-zinc-100 dark:border-zinc-800 shadow-sm"
          />
          <StatsCard
            label="Total Nilai"
            :value="formatCurrency(summary.total_amount)"
            :icon="Wallet"
            value-class="text-emerald-700 dark:text-emerald-400 font-extrabold text-2xl"
            icon-container-class="bg-emerald-50 dark:bg-emerald-900/30"
            icon-class="text-emerald-700 dark:text-emerald-400"
            class="rounded-3xl border border-zinc-100 dark:border-zinc-800 shadow-sm"
          />
          <StatsCard
            label="Transaksi POS"
            :value="summary.pos_count"
            :icon="ShoppingBag"
            class="rounded-3xl border border-zinc-100 dark:border-zinc-800 shadow-sm"
          />
          <StatsCard
            label="Pembayaran Simpanan"
            :value="summary.payment_count"
            :icon="WalletCards"
            class="rounded-3xl border border-zinc-100 dark:border-zinc-800 shadow-sm"
          />
        </div>

        <div
          class="flex flex-col gap-4 rounded-2xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 sm:flex-row sm:items-center shadow-sm sm:p-5"
        >
          <div
            class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-zinc-400"
          >
            <CalendarDays class="h-4.5 w-4.5 text-zinc-400" />
            <span>Filter tanggal:</span>
          </div>
          <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <Input
              v-model="dateFrom"
              type="date"
              class="sm:w-44 rounded-xl dark:bg-zinc-900 dark:border-zinc-800"
            />
            <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500"
              >sampai</span
            >
            <Input
              v-model="dateTo"
              type="date"
              class="sm:w-44 rounded-xl dark:bg-zinc-900 dark:border-zinc-800"
            />
          </div>
          <div class="flex gap-2.5 sm:ml-auto">
            <Button
              variant="outline"
              size="sm"
              class="rounded-xl px-4 py-2 font-bold text-xs uppercase"
              @click="applyFilters"
            >
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

        <div
          v-if="transactions.data.length === 0"
          class="rounded-3xl border border-dashed border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-8 text-center sm:p-12"
        >
          <WalletCards
            class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700"
          />
          <p class="mt-4 text-lg font-bold text-zinc-800 dark:text-zinc-200">
            Belum ada aktivitas
          </p>
          <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Transaksi POS dan pembayaran simpanan akan muncul di sini.
          </p>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="transaction in transactions.data"
            :key="transaction.id"
            class="overflow-hidden rounded-2xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 transition-all duration-300 hover:shadow-md hover:border-zinc-200 dark:hover:border-zinc-700"
          >
            <button
              type="button"
              class="flex w-full cursor-pointer items-center gap-3 p-4 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-600 sm:gap-4 sm:p-5"
              @click="toggleExpand(transaction.id)"
              :id="headerId(transaction.id)"
              :aria-expanded="expandedIds.has(transaction.id)"
              :aria-controls="panelId(transaction.id)"
            >
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 shadow-sm dark:bg-emerald-500/10 dark:text-emerald-400"
              >
                <component
                  :is="transaction.source === 'pos' ? ShoppingBag : WalletCards"
                  class="h-5 w-5"
                  aria-hidden="true"
                />
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-zinc-800 dark:text-zinc-200">{{
                    transaction.title
                  }}</span>
                  <Badge
                    :variant="statusBadge(transaction.status).variant"
                    class="text-[9px] font-extrabold uppercase tracking-wider rounded-md"
                  >
                    {{ statusBadge(transaction.status).label }}
                  </Badge>
                </div>
                <div class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                  {{ transaction.subtitle }} ·
                  {{
                    transaction.occurred_at
                      ? formatDateTime(transaction.occurred_at)
                      : "-"
                  }}
                </div>
              </div>
              <div class="text-right mr-2">
                <div
                  class="font-extrabold text-zinc-900 dark:text-white text-base"
                >
                  {{ formatCurrency(transaction.amount) }}
                </div>
                <div
                  class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 mt-0.5"
                >
                  {{
                    transaction.source === "pos"
                      ? `${transaction.line_items.length} produk`
                      : "Simpanan"
                  }}
                </div>
              </div>
              <component
                :is="expandedIds.has(transaction.id) ? ChevronUp : ChevronDown"
                class="h-5 w-5 shrink-0 text-zinc-400 dark:text-zinc-500"
                aria-hidden="true"
              />
            </button>

            <div
              v-if="expandedIds.has(transaction.id)"
              class="border-t border-zinc-50 dark:border-zinc-800 bg-zinc-50/20 dark:bg-zinc-950/20 px-4 py-4 sm:px-6 sm:py-4"
              role="region"
              :id="panelId(transaction.id)"
              :aria-labelledby="headerId(transaction.id)"
            >
              <div class="mb-4 grid gap-4 text-xs sm:grid-cols-3">
                <div>
                  <span
                    class="text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider"
                    >Jenis aktivitas</span
                  >
                  <div
                    class="font-extrabold text-zinc-800 dark:text-zinc-200 text-sm mt-1"
                  >
                    {{
                      transaction.source === "pos"
                        ? "Transaksi POS"
                        : "Pembayaran Simpanan"
                    }}
                  </div>
                </div>
                <div>
                  <span
                    class="text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider"
                    >Total</span
                  >
                  <div
                    class="font-extrabold text-zinc-800 dark:text-zinc-200 text-sm mt-1"
                  >
                    {{ formatCurrency(transaction.amount) }}
                  </div>
                </div>
                <div>
                  <span
                    class="text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider"
                    >Metode Pembayaran</span
                  >
                  <div
                    class="font-extrabold text-zinc-800 dark:text-zinc-200 text-sm mt-1"
                  >
                    {{
                      transaction.payment_methods
                        .map(paymentMethodLabel)
                        .join(", ") || "-"
                    }}
                  </div>
                </div>
              </div>

              <table
                v-if="transaction.source === 'pos'"
                class="w-full text-left text-xs"
              >
                <thead
                  class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                >
                  <tr>
                    <th class="pb-3 pr-4">Produk</th>
                    <th class="pb-3 pr-4 text-right">Quantity</th>
                    <th class="pb-3 text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800/50">
                  <tr
                    v-for="item in transaction.line_items"
                    :key="item.name"
                    class="border-t border-zinc-100 dark:border-zinc-800"
                  >
                    <td
                      class="py-3 pr-4 font-semibold text-zinc-700 dark:text-zinc-300"
                    >
                      {{ item.name }}
                    </td>
                    <td
                      class="py-3 pr-4 text-right font-medium text-zinc-500 dark:text-zinc-400"
                    >
                      {{ item.quantity }}
                    </td>
                    <td
                      class="py-3 text-right font-bold text-zinc-900 dark:text-white"
                    >
                      {{ formatCurrency(item.amount) }}
                    </td>
                  </tr>
                </tbody>
              </table>
              <p
                v-else
                class="rounded-xl bg-white p-3 text-xs text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400"
              >
                Pembayaran ini tercatat pada aktivitas simpanan anggota Anda.
              </p>
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
          <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">
            Halaman {{ transactions.current_page }} dari
            {{ transactions.last_page }}
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
