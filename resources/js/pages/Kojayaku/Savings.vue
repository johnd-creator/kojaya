<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import {
  BadgeCheck,
  CalendarDays,
  Check,
  ChevronRight,
  CircleDollarSign,
  Coins,
  FileText,
  Heart,
  ReceiptText,
  ShieldCheck,
  UserCheck,
  WalletCards
} from "lucide-vue-next";
import type {LucideIcon} from "lucide-vue-next";
import { computed, ref } from "vue";
import MidtransPaymentDialog from "@/components/Kojayaku/MidtransPaymentDialog.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate, formatDateTime } from "@/lib/formatters";

const props = defineProps<{
  summary: {
    savings_balance: number;
    by_category: Record<string, number>;
    uncategorized: number;
    total_paid: number;
    pending_invoices: number;
  };
  entries: {
    data: Array<{
      id: number;
      entry_type: string;
      ledger_scope?: string | null;
      category_snapshot?: string | null;
      contribution_type?: { name: string; category: string } | null;
      description?: string | null;
      posted_at: string;
      debit: number | string;
      credit: number | string;
    }>;
  };
  invoices: {
    data: Array<{
      id: number;
      period: string;
      amount: number | string;
      paid_amount: number | string;
      status: string;
      due_date: string;
      contribution_type?: { name: string } | null;
    }>;
  };
  payments: {
    data: Array<{
      id: number;
      paid_at: string;
      amount: number | string;
      status: string;
      payment_method: string;
      invoice?: { period: string } | null;
    }>;
  };
  wajibSummary: {
    total_invoices: number;
    paid_invoices: number;
    open_invoices: number;
    total_amount: number;
    paid_amount: number;
    outstanding_amount: number;
  };
  wajibInvoices: Array<{
    id: number;
    period: string;
    period_label: string;
    amount: number;
    paid_amount: number;
    remaining_amount: number;
    due_date: string | null;
    status: string;
    status_label: string;
    is_paid: boolean;
  }>;
  journey: {
    title: string;
    current_status: string;
    reference?: string | null;
    amount?: number | string | null;
    steps: Array<{
      label: string;
      completed: boolean;
      completed_at?: string | null;
    }>;
  } | null;
}>();

const topCards = computed(() => [
  {
    label: "Saldo Simpanan",
    value: formatCurrency(props.summary.savings_balance),
    caption: "Total saldo Anda",
    icon: CircleDollarSign,
    iconClass: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400",
  },
  {
    label: "Total Pembayaran",
    value: formatCurrency(props.summary.total_paid),
    caption: "Total yang telah dibayarkan",
    icon: Coins,
    iconClass: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400",
  },
  {
    label: "Tagihan Pending",
    value: props.summary.pending_invoices,
    caption:
      props.summary.pending_invoices > 0
        ? "Tagihan perlu dibayar"
        : "Belum ada tagihan tertunda",
    icon: FileText,
    iconClass: "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400",
  },
]);

const categoryCards = computed<
  Array<{
    label: string;
    value: string;
    caption: string;
    icon: LucideIcon;
  }>
>(() => [
  {
    label: "Simpanan Pokok",
    value: formatCurrency(props.summary.by_category?.POKOK ?? 0),
    caption: "Setoran pokok anggota",
    icon: UserCheck,
  },
  {
    label: "Simpanan Wajib",
    value: formatCurrency(props.summary.by_category?.WAJIB ?? 0),
    caption: "Setoran wajib berkala",
    icon: CalendarDays,
  },
  {
    label: "Simpanan Sukarela",
    value: formatCurrency(props.summary.by_category?.SUKARELA ?? 0),
    caption: "Simpanan sukarela",
    icon: Heart,
  },
  {
    label: "Simpanan Khusus",
    value: formatCurrency(props.summary.by_category?.KHUSUS ?? 0),
    caption: "Simpanan khusus",
    icon: BadgeCheck,
  },
]);

const latestLedger = computed(() => props.entries.data.slice(0, 5));
const latestInvoices = computed(() => props.invoices.data.slice(0, 4));
const latestPayments = computed(() => props.payments.data.slice(0, 4));
const wajibInvoices = computed(() => props.wajibInvoices ?? []);

type InvoiceForDialog = {
  id: number;
  amount: number;
  paid_amount: number;
  due_date: string | null;
};

const selectedInvoice = ref<InvoiceForDialog | null>(null);

function openPaymentDialog(invoice: InvoiceForDialog): void {
  selectedInvoice.value = invoice;
}

function closePaymentDialog(): void {
  selectedInvoice.value = null;
}

const statusClass = (status: string): string => {
  if (["PAID", "APPROVED", "ACTIVE", "COMPLETED"].includes(status)) {
    return "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400";
  }

  if (["UNPAID", "PENDING", "PARTIAL"].includes(status)) {
    return "bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400";
  }

  return "bg-zinc-100 text-zinc-700 dark:bg-zinc-700/40 dark:text-zinc-300";
};
</script>

<template>
  <Head title="Simpanan Saya" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Kojayaku', href: '/member' },
      { title: 'Simpanan', href: '/member/savings' },
    ]"
  >
    <PageContainer>
      <div class="flex flex-col gap-6">
        <header class="flex items-center gap-5">
          <div
            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 text-white shadow-lg shadow-emerald-800/20"
          >
            <WalletCards class="h-8 w-8" />
          </div>
          <div>
            <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white tracking-tight">Simpanan Saya</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
              Lacak saldo simpanan dan status pembayaran iuran wajib Anda dengan mudah.
            </p>
          </div>
        </header>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
          <div
            v-for="card in topCards"
            :key="card.label"
            class="group flex items-center gap-4 rounded-3xl border border-zinc-100 bg-white p-4 shadow-sm hover:shadow-md transition-all duration-300 dark:bg-zinc-900 dark:border-zinc-800 sm:gap-5 sm:p-6"
          >
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-sm group-hover:scale-110 transition-transform duration-300 sm:h-16 sm:w-16"
              :class="card.iconClass"
            >
              <component :is="card.icon" class="h-6 w-6 sm:h-8 sm:w-8" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ card.label }}</p>
              <p class="mt-1.5 text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight sm:text-2xl">
                {{ card.value }}
              </p>
              <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 leading-normal">{{ card.caption }}</p>
            </div>
          </div>
        </section>

        <section class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
          <div
            v-for="card in categoryCards"
            :key="card.label"
            class="group flex items-center gap-3 rounded-2xl border border-zinc-100 bg-white p-4 shadow-sm hover:shadow-md hover:border-emerald-300 transition-all duration-300 dark:bg-zinc-900 dark:border-zinc-800 dark:hover:border-emerald-700/50"
          >
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 shadow-sm group-hover:scale-105 transition-transform dark:bg-emerald-500/10 dark:text-emerald-400 sm:h-14 sm:w-14"
            >
              <component :is="card.icon" class="h-5 w-5 sm:h-6 sm:w-6" />
            </div>
            <div class="min-w-0">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 sm:text-xs">{{ card.label }}</p>
              <p class="mt-1 text-base font-extrabold text-zinc-900 dark:text-white tracking-tight sm:text-lg">
                {{ card.value }}
              </p>
              <p class="mt-0.5 text-[10px] text-zinc-500 dark:text-zinc-400 leading-normal">{{ card.caption }}</p>
            </div>
          </div>
        </section>

        <section
          class="overflow-hidden rounded-3xl border border-zinc-100 bg-white shadow-sm dark:bg-zinc-900 dark:border-zinc-800"
        >
          <div
            class="flex flex-col gap-4 border-b border-zinc-50 dark:border-zinc-800 p-4 sm:p-6 lg:flex-row lg:items-center lg:justify-between"
          >
            <div class="flex items-start gap-4">
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 shadow-sm dark:bg-emerald-500/10 dark:text-emerald-400"
              >
                <CalendarDays class="h-5 w-5" />
              </div>
              <div>
                <h2 class="font-bold text-zinc-900 dark:text-white tracking-tight">
                  Simpanan Wajib Bulanan
                </h2>
                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                  Pantau bulan apa saja yang sudah dibayar, belum dibayar, atau masih sebagian.
                </p>
              </div>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center sm:min-w-[360px]">
              <div class="rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 px-3 py-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Total</p>
                <p class="mt-1 text-sm font-extrabold text-zinc-900 dark:text-white">
                  {{ wajibSummary.total_invoices }}
                </p>
              </div>
              <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/20 px-3 py-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Lunas</p>
                <p class="mt-1 text-sm font-extrabold text-emerald-800 dark:text-emerald-400">
                  {{ wajibSummary.paid_invoices }}
                </p>
              </div>
              <div class="rounded-2xl bg-amber-50 dark:bg-amber-950/20 px-3 py-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Belum</p>
                <p class="mt-1 text-sm font-extrabold text-amber-800 dark:text-amber-400">
                  {{ wajibSummary.open_invoices }}
                </p>
              </div>
            </div>
          </div>

          <div class="grid gap-4 p-4 sm:p-6 lg:grid-cols-[0.85fr_1.15fr]">
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 dark:border-emerald-900/20 dark:bg-emerald-950/10 p-4 sm:p-5">
              <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                Ringkasan Simpanan Wajib
              </p>
              <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between gap-4">
                  <span class="text-sm text-emerald-900/70 dark:text-emerald-300/70">Total tertagih</span>
                  <span class="font-extrabold text-emerald-950 dark:text-emerald-100">
                    {{ formatCurrency(wajibSummary.total_amount) }}
                  </span>
                </div>
                <div class="flex items-center justify-between gap-4">
                  <span class="text-sm text-emerald-900/70 dark:text-emerald-300/70">Sudah dibayar</span>
                  <span class="font-extrabold text-emerald-950 dark:text-emerald-100">
                    {{ formatCurrency(wajibSummary.paid_amount) }}
                  </span>
                </div>
                <div class="flex items-center justify-between gap-4">
                  <span class="text-sm text-emerald-900/70 dark:text-emerald-300/70">Sisa tagihan</span>
                  <span class="font-extrabold text-amber-700 dark:text-amber-400">
                    {{ formatCurrency(wajibSummary.outstanding_amount) }}
                  </span>
                </div>
              </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-zinc-100 dark:border-zinc-800">
              <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50/70 dark:bg-zinc-800/50 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                  <tr>
                    <th class="px-4 py-3">Bulan Iuran</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Tagihan</th>
                    <th class="px-4 py-3 text-right">Dibayar</th>
                    <th class="px-4 py-3 text-right">Sisa</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="invoice in wajibInvoices"
                    :key="invoice.id"
                    class="border-t border-zinc-50 dark:border-zinc-800 transition-colors hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30"
                  >
                    <td class="px-4 py-3">
                      <p class="font-bold text-zinc-900 dark:text-zinc-100">{{ invoice.period_label }}</p>
                      <p class="mt-0.5 text-xs text-zinc-400">
                        Jatuh tempo {{ invoice.due_date ? formatDate(invoice.due_date) : "-" }}
                      </p>
                    </td>
                    <td class="px-4 py-3">
                      <span
                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-extrabold tracking-wide"
                        :class="statusClass(invoice.status)"
                      >
                        {{ invoice.status_label }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">
                      {{ formatCurrency(invoice.amount) }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-emerald-800 dark:text-emerald-400">
                      {{ formatCurrency(invoice.paid_amount) }}
                    </td>
                    <td class="px-4 py-3 text-right font-extrabold" :class="invoice.remaining_amount > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-emerald-800 dark:text-emerald-400'">
                      {{ formatCurrency(invoice.remaining_amount) }}
                    </td>
                    <td class="px-4 py-3 text-center">
                      <Button
                        v-if="invoice.remaining_amount > 0"
                        type="button"
                        size="sm"
                        class="bg-emerald-600 hover:bg-emerald-700"
                        @click="
                          openPaymentDialog({
                            id: invoice.id,
                            amount: invoice.amount,
                            paid_amount: invoice.paid_amount,
                            due_date: invoice.due_date,
                          })
                        "
                      >
                        Bayar
                      </Button>
                      <span v-else class="text-xs text-emerald-600 dark:text-emerald-400">Lunas</span>
                    </td>
                  </tr>
                  <tr v-if="wajibInvoices.length === 0">
                    <td colspan="6" class="px-4 py-10 text-center text-zinc-400">
                      Belum ada tagihan Simpanan Wajib bulanan.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section
          v-if="journey"
          class="rounded-3xl border border-zinc-100 bg-white p-4 shadow-sm sm:p-6 dark:bg-zinc-900 dark:border-zinc-800"
        >
          <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
          >
            <div>
              <h2 class="text-lg font-bold text-zinc-900 dark:text-white tracking-tight">
                Status Pembayaran Iuran
              </h2>
              <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 font-medium">
                {{ journey.reference || "Belum ada aktivitas terbaru" }}
              </p>
            </div>
            <div class="flex items-center gap-5">
              <span
                class="rounded-full px-3.5 py-1 text-xs font-bold shadow-sm"
                :class="statusClass(journey.current_status)"
              >
                {{ journey.current_status }}
              </span>
              <div class="text-right">
                <p class="text-2xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                  {{ formatCurrency(journey.amount) }}
                </p>
                <p class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mt-0.5">
                  Total pembayaran periode ini
                </p>
              </div>
            </div>
          </div>

          <div class="mt-8 grid gap-4 md:grid-cols-4 relative">
            <div
              v-for="(step, index) in journey.steps"
              :key="step.label"
              class="relative flex flex-col items-center text-center group"
            >
              <div
                v-if="index !== journey.steps.length - 1"
                class="absolute left-1/2 top-5 hidden h-0.5 w-full md:block"
                :class="step.completed ? 'bg-emerald-600' : 'bg-zinc-200 dark:bg-zinc-800'"
              />
              <div
                class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full text-white shadow-md transition-transform duration-300 group-hover:scale-110"
                :class="step.completed ? 'bg-emerald-600 shadow-emerald-600/20' : 'bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-500'"
              >
                <Check v-if="step.completed" class="h-5 w-5" />
                <span v-else class="h-2 w-2 rounded-full bg-white dark:bg-zinc-700" />
              </div>
              <p class="mt-3 text-sm font-bold text-zinc-800 dark:text-zinc-200 leading-tight">
                {{ step.label }}
              </p>
              <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                {{
                  step.completed_at
                    ? formatDateTime(step.completed_at)
                    : step.completed
                      ? "Selesai"
                      : "Menunggu"
                }}
              </p>
            </div>
          </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1fr_1.05fr]">
          <!-- Riwayat Ledger Card -->
          <div
            class="overflow-hidden rounded-3xl border border-zinc-100 bg-white shadow-sm flex flex-col justify-between dark:bg-zinc-900 dark:border-zinc-800"
          >
            <div>
              <div class="flex items-center gap-4 border-b border-zinc-50 dark:border-zinc-800 p-4 sm:p-6">
                <div
                  class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 shadow-sm dark:bg-emerald-500/10 dark:text-emerald-400"
                >
                  <ReceiptText class="h-5 w-5" />
                </div>
                <div>
                  <h2 class="font-bold text-zinc-900 dark:text-white tracking-tight">Riwayat Ledger</h2>
                  <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                    Catatan seluruh transaksi simpanan Anda.
                  </p>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                  <thead class="bg-zinc-50/50 dark:bg-zinc-800/50 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                    <tr>
                      <th class="px-4 py-3 sm:px-6 sm:py-4">Tanggal</th>
                      <th class="px-4 py-3 sm:px-6 sm:py-4">Tipe</th>
                      <th class="px-4 py-3 sm:px-6 sm:py-4">Kategori</th>
                      <th class="px-4 py-3 sm:px-6 sm:py-4 text-right">Debit</th>
                      <th class="px-4 py-3 sm:px-6 sm:py-4 text-right">Kredit</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800/50">
                    <tr
                      v-for="entry in latestLedger"
                      :key="entry.id"
                      class="border-t border-zinc-50 dark:border-zinc-800 transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30"
                    >
                      <td class="px-4 py-3 sm:px-6 sm:py-4 font-medium text-zinc-500 dark:text-zinc-400">{{ formatDate(entry.posted_at) }}</td>
                      <td class="px-4 py-3 sm:px-6 sm:py-4 font-semibold text-zinc-800 dark:text-zinc-200">{{ entry.entry_type }}</td>
                      <td class="px-4 py-3 sm:px-6 sm:py-4 text-zinc-600 dark:text-zinc-300">
                        {{
                          entry.contribution_type?.category ||
                          entry.category_snapshot ||
                          "-"
                        }}
                      </td>
                      <td class="px-4 py-3 sm:px-6 sm:py-4 text-right text-zinc-500 dark:text-zinc-400">
                        {{ entry.debit > 0 ? formatCurrency(entry.debit) : '-' }}
                      </td>
                      <td class="px-4 py-3 sm:px-6 sm:py-4 text-right font-extrabold text-emerald-800 dark:text-emerald-400">
                        {{ entry.credit > 0 ? formatCurrency(entry.credit) : '-' }}
                      </td>
                    </tr>
                    <tr v-if="latestLedger.length === 0">
                      <td colspan="5" class="px-6 py-12 text-center text-zinc-400">
                        Belum ada riwayat ledger.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <Link
              href="/member/savings"
              class="flex items-center justify-center gap-2 border-t border-zinc-50 dark:border-zinc-800 py-4 text-xs font-bold uppercase tracking-wider text-emerald-800 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300 bg-zinc-50/20 dark:bg-zinc-800/20 transition-colors"
            >
              Lihat semua riwayat ledger
              <ChevronRight class="h-4 w-4" />
            </Link>
          </div>

          <div class="space-y-6">
            <!-- Tagihan Card -->
            <div
              class="overflow-hidden rounded-3xl border border-zinc-100 bg-white shadow-sm dark:bg-zinc-900 dark:border-zinc-800"
            >
              <div class="flex items-center gap-4 border-b border-zinc-50 dark:border-zinc-800 p-6">
                <div
                  class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 shadow-sm dark:bg-amber-500/10 dark:text-amber-400"
                >
                  <FileText class="h-5 w-5" />
                </div>
                <div>
                  <h2 class="font-bold text-zinc-900 dark:text-white tracking-tight">Tagihan</h2>
                  <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                    Daftar tagihan iuran yang perlu diselesaikan.
                  </p>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                  <thead class="bg-zinc-50/50 dark:bg-zinc-800/50 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                    <tr>
                      <th class="px-4 py-3 sm:px-6 sm:py-4">Periode</th>
                      <th class="px-4 py-3 sm:px-6 sm:py-4">Jenis</th>
                      <th class="px-4 py-3 sm:px-6 sm:py-4">Status</th>
                      <th class="px-4 py-3 sm:px-6 sm:py-4 text-right">Nominal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="invoice in latestInvoices"
                      :key="invoice.id"
                      class="border-t border-zinc-50 dark:border-zinc-800 transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30"
                    >
                      <td class="px-4 py-3 sm:px-6 sm:py-4 font-semibold text-zinc-800 dark:text-zinc-200">{{ invoice.period }}</td>
                      <td class="px-4 py-3 sm:px-6 sm:py-4 text-zinc-600 dark:text-zinc-300">
                        {{ invoice.contribution_type?.name || "-" }}
                      </td>
                      <td class="px-4 py-3 sm:px-6 sm:py-4">
                        <span
                          class="rounded-full px-2.5 py-0.5 text-xs font-extrabold tracking-wide uppercase"
                          :class="statusClass(invoice.status)"
                        >
                          {{ invoice.status }}
                        </span>
                      </td>
                      <td class="px-4 py-3 sm:px-6 sm:py-4 text-right font-extrabold text-zinc-800 dark:text-zinc-300">
                        {{ formatCurrency(invoice.amount) }}
                      </td>
                    </tr>
                    <tr v-if="latestInvoices.length === 0">
                      <td
                        colspan="4"
                        class="px-6 py-10 text-center text-zinc-400"
                      >
                        Belum ada tagihan.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Pembayaran Card -->
            <div
              class="overflow-hidden rounded-3xl border border-zinc-100 bg-white shadow-sm flex flex-col justify-between dark:bg-zinc-900 dark:border-zinc-800"
            >
              <div>
                <div class="flex items-center gap-4 border-b border-zinc-50 dark:border-zinc-800 p-4 sm:p-6">
                  <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 shadow-sm dark:bg-blue-500/10 dark:text-blue-400"
                  >
                    <WalletCards class="h-5 w-5" />
                  </div>
                  <div>
                    <h2 class="font-bold text-zinc-900 dark:text-white tracking-tight">Pembayaran</h2>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                      Catatan riwayat pembayaran iuran Anda.
                    </p>
                  </div>
                </div>
                <div class="overflow-x-auto">
                  <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50/50 dark:bg-zinc-800/50 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                      <tr>
                        <th class="px-4 py-3 sm:px-6 sm:py-4">Tanggal</th>
                        <th class="px-4 py-3 sm:px-6 sm:py-4">Metode</th>
                        <th class="px-4 py-3 sm:px-6 sm:py-4">Status</th>
                        <th class="px-4 py-3 sm:px-6 sm:py-4 text-right">Nominal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="payment in latestPayments"
                        :key="payment.id"
                        class="border-t border-zinc-50 dark:border-zinc-800 transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30"
                      >
                        <td class="px-4 py-3 sm:px-6 sm:py-4 font-medium text-zinc-500 dark:text-zinc-400">{{ formatDate(payment.paid_at) }}</td>
                        <td class="px-4 py-3 sm:px-6 sm:py-4 font-semibold text-zinc-800 dark:text-zinc-200">{{ payment.payment_method }}</td>
                        <td class="px-4 py-3 sm:px-6 sm:py-4">
                          <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-extrabold tracking-wide uppercase"
                            :class="statusClass(payment.status)"
                          >
                            {{ payment.status }}
                          </span>
                        </td>
                        <td class="px-4 py-3 sm:px-6 sm:py-4 text-right font-extrabold text-zinc-800 dark:text-zinc-300">
                          {{ formatCurrency(payment.amount) }}
                        </td>
                      </tr>
                      <tr v-if="latestPayments.length === 0">
                        <td
                          colspan="4"
                          class="px-6 py-10 text-center text-zinc-400"
                        >
                          Belum ada pembayaran.
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <Link
                href="/member/savings"
                class="flex items-center justify-center gap-2 border-t border-zinc-50 dark:border-zinc-800 py-4 text-xs font-bold uppercase tracking-wider text-emerald-800 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300 bg-zinc-50/20 dark:bg-zinc-800/20 transition-colors"
              >
                Lihat semua pembayaran
                <ChevronRight class="h-4 w-4" />
              </Link>
            </div>
          </div>
        </section>

        <section
          class="flex flex-col gap-4 rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50/60 to-teal-50/30 p-4 sm:p-6 sm:flex-row sm:items-center sm:justify-between shadow-sm dark:border-emerald-950/20 dark:from-emerald-950/10 dark:to-teal-950/5"
        >
          <div class="flex items-center gap-4">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-800 shadow-sm border border-emerald-50 dark:bg-zinc-900 dark:text-emerald-400 dark:border-zinc-800"
            >
              <ShieldCheck class="h-6 w-6" />
            </div>
            <div>
              <p class="font-bold text-emerald-950 dark:text-emerald-300">
                Transaksi aman & terpercaya
              </p>
              <p class="text-sm text-emerald-900/70 dark:text-zinc-400 mt-1 max-w-xl">
                Seluruh data simpanan dan pembayaran iuran Anda dilindungi dengan sistem keamanan berlapis demi kenyamanan bertransaksi.
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2 text-sm font-semibold text-emerald-800 dark:text-emerald-400">
            <ShieldCheck class="h-5 w-5 text-emerald-700 dark:text-emerald-400 animate-pulse" />
            Data Anda aman bersama Kojayaku
          </div>
        </section>
      </div>

      <MidtransPaymentDialog
        :open="selectedInvoice !== null"
        :invoice="selectedInvoice"
        @update:open="closePaymentDialog"
      />
    </PageContainer>
  </AppLayout>
</template>
