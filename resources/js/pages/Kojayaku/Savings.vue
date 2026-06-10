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
  WalletCards,
  type LucideIcon,
} from "lucide-vue-next";
import { computed } from "vue";
import PageContainer from "@/components/PageContainer.vue";
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
  };
}>();

const topCards = computed(() => [
  {
    label: "Saldo Simpanan",
    value: formatCurrency(props.summary.savings_balance),
    caption: "Total saldo Anda",
    icon: CircleDollarSign,
    iconClass: "bg-emerald-50 text-emerald-700",
  },
  {
    label: "Total Pembayaran",
    value: formatCurrency(props.summary.total_paid),
    caption: "Total yang telah dibayarkan",
    icon: Coins,
    iconClass: "bg-emerald-50 text-emerald-700",
  },
  {
    label: "Tagihan Pending",
    value: props.summary.pending_invoices,
    caption:
      props.summary.pending_invoices > 0
        ? "Tagihan perlu dibayar"
        : "Belum ada tagihan tertunda",
    icon: FileText,
    iconClass: "bg-amber-50 text-amber-600",
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

const statusClass = (status: string): string => {
  if (["PAID", "APPROVED", "ACTIVE", "COMPLETED"].includes(status)) {
    return "bg-emerald-100 text-emerald-700";
  }

  if (["UNPAID", "PENDING", "PARTIAL"].includes(status)) {
    return "bg-amber-100 text-amber-700";
  }

  return "bg-zinc-100 text-zinc-700";
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
            <h1 class="text-3xl font-extrabold text-zinc-900 tracking-tight">Simpanan Saya</h1>
            <p class="mt-1 text-sm text-zinc-500">
              Lacak saldo simpanan dan status pembayaran iuran wajib Anda dengan mudah.
            </p>
          </div>
        </header>

        <section class="grid gap-6 lg:grid-cols-3">
          <div
            v-for="card in topCards"
            :key="card.label"
            class="group flex items-center gap-5 rounded-3xl border border-zinc-100 bg-white p-6 shadow-sm hover:shadow-md transition-all duration-300"
          >
            <div
              class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl shadow-sm group-hover:scale-110 transition-transform duration-300"
              :class="card.iconClass"
            >
              <component :is="card.icon" class="h-8 w-8" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ card.label }}</p>
              <p class="mt-1.5 text-2xl font-extrabold text-zinc-900 tracking-tight">
                {{ card.value }}
              </p>
              <p class="mt-1 text-xs text-zinc-500 leading-normal">{{ card.caption }}</p>
            </div>
          </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div
            v-for="card in categoryCards"
            :key="card.label"
            class="group flex items-center gap-4 rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm hover:shadow-md hover:border-emerald-300 transition-all duration-300"
          >
            <div
              class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 shadow-sm group-hover:scale-105 transition-transform"
            >
              <component :is="card.icon" class="h-6 w-6" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ card.label }}</p>
              <p class="mt-1 text-lg font-extrabold text-zinc-900 tracking-tight">
                {{ card.value }}
              </p>
              <p class="mt-0.5 text-[10px] text-zinc-500 leading-normal">{{ card.caption }}</p>
            </div>
          </div>
        </section>

        <section
          class="rounded-3xl border border-zinc-100 bg-white p-6 shadow-sm"
        >
          <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
          >
            <div>
              <h2 class="text-lg font-bold text-zinc-900 tracking-tight">
                Status Pembayaran Iuran
              </h2>
              <p class="mt-1 text-sm text-zinc-500 font-medium">
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
                <p class="text-2xl font-extrabold text-zinc-900 tracking-tight">
                  {{ formatCurrency(journey.amount) }}
                </p>
                <p class="text-[10px] font-semibold text-zinc-400 uppercase tracking-wider mt-0.5">
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
                :class="step.completed ? 'bg-emerald-600' : 'bg-zinc-200'"
              />
              <div
                class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full text-white shadow-md transition-transform duration-300 group-hover:scale-110"
                :class="step.completed ? 'bg-emerald-600 shadow-emerald-600/20' : 'bg-zinc-300'"
              >
                <Check v-if="step.completed" class="h-5 w-5" />
                <span v-else class="h-2 w-2 rounded-full bg-white" />
              </div>
              <p class="mt-3 text-sm font-bold text-zinc-800 leading-tight">
                {{ step.label }}
              </p>
              <p class="mt-1 text-xs text-zinc-500">
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
            class="overflow-hidden rounded-3xl border border-zinc-100 bg-white shadow-sm flex flex-col justify-between"
          >
            <div>
              <div class="flex items-center gap-4 border-b border-zinc-50 p-6">
                <div
                  class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 shadow-sm"
                >
                  <ReceiptText class="h-5 w-5" />
                </div>
                <div>
                  <h2 class="font-bold text-zinc-900 tracking-tight">Riwayat Ledger</h2>
                  <p class="text-xs text-zinc-400 mt-0.5">
                    Catatan seluruh transaksi simpanan Anda.
                  </p>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                  <thead class="bg-zinc-50/50 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                    <tr>
                      <th class="px-6 py-4">Tanggal</th>
                      <th class="px-6 py-4">Tipe</th>
                      <th class="px-6 py-4">Kategori</th>
                      <th class="px-6 py-4 text-right">Debit</th>
                      <th class="px-6 py-4 text-right">Kredit</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="entry in latestLedger"
                      :key="entry.id"
                      class="border-t border-zinc-50 transition-colors hover:bg-zinc-50/50"
                    >
                      <td class="px-6 py-4 font-medium text-zinc-500">{{ formatDate(entry.posted_at) }}</td>
                      <td class="px-6 py-4 font-semibold text-zinc-800">{{ entry.entry_type }}</td>
                      <td class="px-6 py-4 text-zinc-600">
                        {{
                          entry.contribution_type?.category ||
                          entry.category_snapshot ||
                          "-"
                        }}
                      </td>
                      <td class="px-6 py-4 text-right text-zinc-500">
                        {{ entry.debit > 0 ? formatCurrency(entry.debit) : '-' }}
                      </td>
                      <td class="px-6 py-4 text-right font-extrabold text-emerald-800">
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
              class="flex items-center justify-center gap-2 border-t border-zinc-50 py-4 text-xs font-bold uppercase tracking-wider text-emerald-800 hover:text-emerald-955 bg-zinc-50/20 transition-colors"
            >
              Lihat semua riwayat ledger
              <ChevronRight class="h-4 w-4" />
            </Link>
          </div>

          <div class="space-y-6">
            <!-- Tagihan Card -->
            <div
              class="overflow-hidden rounded-3xl border border-zinc-100 bg-white shadow-sm"
            >
              <div class="flex items-center gap-4 border-b border-zinc-50 p-6">
                <div
                  class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 shadow-sm"
                >
                  <FileText class="h-5 w-5" />
                </div>
                <div>
                  <h2 class="font-bold text-zinc-900 tracking-tight">Tagihan</h2>
                  <p class="text-xs text-zinc-400 mt-0.5">
                    Daftar tagihan iuran yang perlu diselesaikan.
                  </p>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                  <thead class="bg-zinc-50/50 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                    <tr>
                      <th class="px-6 py-4">Periode</th>
                      <th class="px-6 py-4">Jenis</th>
                      <th class="px-6 py-4">Status</th>
                      <th class="px-6 py-4 text-right">Nominal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="invoice in latestInvoices"
                      :key="invoice.id"
                      class="border-t border-zinc-50 transition-colors hover:bg-zinc-50/50"
                    >
                      <td class="px-6 py-4 font-semibold text-zinc-800">{{ invoice.period }}</td>
                      <td class="px-6 py-4 text-zinc-600">
                        {{ invoice.contribution_type?.name || "-" }}
                      </td>
                      <td class="px-6 py-4">
                        <span
                          class="rounded-full px-2.5 py-0.5 text-xs font-extrabold tracking-wide uppercase"
                          :class="statusClass(invoice.status)"
                        >
                          {{ invoice.status }}
                        </span>
                      </td>
                      <td class="px-6 py-4 text-right font-extrabold text-zinc-800">
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
              class="overflow-hidden rounded-3xl border border-zinc-100 bg-white shadow-sm flex flex-col justify-between"
            >
              <div>
                <div class="flex items-center gap-4 border-b border-zinc-50 p-6">
                  <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 shadow-sm"
                  >
                    <WalletCards class="h-5 w-5" />
                  </div>
                  <div>
                    <h2 class="font-bold text-zinc-900 tracking-tight">Pembayaran</h2>
                    <p class="text-xs text-zinc-400 mt-0.5">
                      Catatan riwayat pembayaran iuran Anda.
                    </p>
                  </div>
                </div>
                <div class="overflow-x-auto">
                  <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50/50 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                      <tr>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Metode</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Nominal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="payment in latestPayments"
                        :key="payment.id"
                        class="border-t border-zinc-50 transition-colors hover:bg-zinc-50/50"
                      >
                        <td class="px-6 py-4 font-medium text-zinc-500">{{ formatDate(payment.paid_at) }}</td>
                        <td class="px-6 py-4 font-semibold text-zinc-800">{{ payment.payment_method }}</td>
                        <td class="px-6 py-4">
                          <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-extrabold tracking-wide uppercase"
                            :class="statusClass(payment.status)"
                          >
                            {{ payment.status }}
                          </span>
                        </td>
                        <td class="px-6 py-4 text-right font-extrabold text-zinc-800">
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
                class="flex items-center justify-center gap-2 border-t border-zinc-50 py-4 text-xs font-bold uppercase tracking-wider text-emerald-800 hover:text-emerald-955 bg-zinc-50/20 transition-colors"
              >
                Lihat semua pembayaran
                <ChevronRight class="h-4 w-4" />
              </Link>
            </div>
          </div>
        </section>

        <section
          class="flex flex-col gap-4 rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50/60 to-teal-50/30 p-6 sm:flex-row sm:items-center sm:justify-between shadow-sm"
        >
          <div class="flex items-center gap-4">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-800 shadow-sm border border-emerald-50"
            >
              <ShieldCheck class="h-6 w-6" />
            </div>
            <div>
              <p class="font-bold text-emerald-950">
                Transaksi aman & terpercaya
              </p>
              <p class="text-sm text-emerald-900/70 mt-1 max-w-xl">
                Seluruh data simpanan dan pembayaran iuran Anda dilindungi dengan sistem keamanan berlapis demi kenyamanan bertransaksi.
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2 text-sm font-semibold text-emerald-800">
            <ShieldCheck class="h-5 w-5 text-emerald-700 animate-pulse" />
            Data Anda aman bersama Kojayaku
          </div>
        </section>
      </div>
    </PageContainer>
  </AppLayout>
</template>
