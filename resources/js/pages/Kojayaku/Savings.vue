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
      <div class="flex flex-col gap-5">
        <header class="flex items-center gap-5">
          <div
            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700"
          >
            <WalletCards class="h-8 w-8" />
          </div>
          <div>
            <h1 class="text-3xl font-bold text-zinc-950">Simpanan</h1>
            <p class="mt-2 text-sm text-zinc-600">
              Lacak saldo simpanan dan status pembayaran Anda dengan mudah.
            </p>
          </div>
        </header>

        <section class="grid gap-5 lg:grid-cols-3">
          <div
            v-for="card in topCards"
            :key="card.label"
            class="flex items-center gap-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm shadow-zinc-950/5"
          >
            <div
              class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full"
              :class="card.iconClass"
            >
              <component :is="card.icon" class="h-8 w-8" />
            </div>
            <div class="min-w-0">
              <p class="text-sm text-zinc-500">{{ card.label }}</p>
              <p class="mt-1 text-2xl font-bold text-zinc-950">
                {{ card.value }}
              </p>
              <p class="mt-1 text-sm text-zinc-500">{{ card.caption }}</p>
            </div>
          </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div
            v-for="card in categoryCards"
            :key="card.label"
            class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm shadow-zinc-950/5"
          >
            <div
              class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700"
            >
              <component :is="card.icon" class="h-7 w-7" />
            </div>
            <div class="min-w-0">
              <p class="text-sm text-zinc-500">{{ card.label }}</p>
              <p class="mt-1 text-xl font-bold text-zinc-950">
                {{ card.value }}
              </p>
              <p class="mt-1 text-xs text-zinc-500">{{ card.caption }}</p>
            </div>
          </div>
        </section>

        <section
          class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm shadow-zinc-950/5"
        >
          <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
          >
            <div>
              <h2 class="text-lg font-bold text-zinc-950">
                Pembayaran Simpanan
              </h2>
              <p class="mt-1 text-sm text-zinc-500">
                {{ journey.reference || "Belum ada aktivitas terbaru" }}
              </p>
            </div>
            <div class="flex items-center gap-5">
              <span
                class="rounded-full px-4 py-1 text-xs font-bold"
                :class="statusClass(journey.current_status)"
              >
                {{ journey.current_status }}
              </span>
              <div class="text-right">
                <p class="text-xl font-bold text-zinc-950">
                  {{ formatCurrency(journey.amount) }}
                </p>
                <p class="text-xs text-zinc-500">
                  Total pembayaran periode ini
                </p>
              </div>
            </div>
          </div>

          <div class="mt-6 grid gap-4 md:grid-cols-4">
            <div
              v-for="(step, index) in journey.steps"
              :key="step.label"
              class="relative flex flex-col items-center text-center"
            >
              <div
                v-if="index !== journey.steps.length - 1"
                class="absolute left-1/2 top-5 hidden h-0.5 w-full bg-emerald-600 md:block"
              />
              <div
                class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full text-white"
                :class="step.completed ? 'bg-emerald-600' : 'bg-zinc-300'"
              >
                <Check v-if="step.completed" class="h-5 w-5" />
                <span v-else class="h-2 w-2 rounded-full bg-white" />
              </div>
              <p class="mt-3 text-sm font-medium text-zinc-900">
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
          <div
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm shadow-zinc-950/5"
          >
            <div class="flex items-center gap-4 border-b border-zinc-100 p-5">
              <div
                class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-700"
              >
                <ReceiptText class="h-5 w-5" />
              </div>
              <div>
                <h2 class="font-bold text-zinc-950">Riwayat Ledger</h2>
                <p class="text-sm text-zinc-500">
                  Catatan transaksi simpanan Anda.
                </p>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500">
                  <tr>
                    <th class="px-5 py-3">Tanggal</th>
                    <th class="px-5 py-3">Tipe</th>
                    <th class="px-5 py-3">Kategori</th>
                    <th class="px-5 py-3 text-right">Debit</th>
                    <th class="px-5 py-3 text-right">Credit</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="entry in latestLedger"
                    :key="entry.id"
                    class="border-t border-zinc-100"
                  >
                    <td class="px-5 py-4">{{ formatDate(entry.posted_at) }}</td>
                    <td class="px-5 py-4">{{ entry.entry_type }}</td>
                    <td class="px-5 py-4">
                      {{
                        entry.contribution_type?.category ||
                        entry.category_snapshot ||
                        "-"
                      }}
                    </td>
                    <td class="px-5 py-4 text-right">
                      {{ formatCurrency(entry.debit) }}
                    </td>
                    <td class="px-5 py-4 text-right font-medium">
                      {{ formatCurrency(entry.credit) }}
                    </td>
                  </tr>
                  <tr v-if="latestLedger.length === 0">
                    <td colspan="5" class="px-5 py-8 text-center text-zinc-500">
                      Belum ada riwayat ledger.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <Link
              href="/member/savings"
              class="flex items-center justify-center gap-2 border-t border-zinc-100 py-4 text-sm font-semibold text-emerald-700"
            >
              Lihat semua riwayat ledger
              <ChevronRight class="h-4 w-4" />
            </Link>
          </div>

          <div class="space-y-4">
            <div
              class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm shadow-zinc-950/5"
            >
              <div class="flex items-center gap-4 border-b border-zinc-100 p-4">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-700"
                >
                  <FileText class="h-5 w-5" />
                </div>
                <div>
                  <h2 class="font-bold text-zinc-950">Tagihan</h2>
                  <p class="text-sm text-zinc-500">
                    Daftar tagihan simpanan Anda.
                  </p>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                  <thead class="bg-zinc-50 text-xs uppercase text-zinc-500">
                    <tr>
                      <th class="px-5 py-3">Periode</th>
                      <th class="px-5 py-3">Jenis</th>
                      <th class="px-5 py-3">Status</th>
                      <th class="px-5 py-3 text-right">Nominal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="invoice in latestInvoices"
                      :key="invoice.id"
                      class="border-t border-zinc-100"
                    >
                      <td class="px-5 py-3">{{ invoice.period }}</td>
                      <td class="px-5 py-3">
                        {{ invoice.contribution_type?.name || "-" }}
                      </td>
                      <td class="px-5 py-3">
                        <span
                          class="rounded-full px-3 py-1 text-xs font-bold"
                          :class="statusClass(invoice.status)"
                        >
                          {{ invoice.status }}
                        </span>
                      </td>
                      <td class="px-5 py-3 text-right">
                        {{ formatCurrency(invoice.amount) }}
                      </td>
                    </tr>
                    <tr v-if="latestInvoices.length === 0">
                      <td
                        colspan="4"
                        class="px-5 py-7 text-center text-zinc-500"
                      >
                        Belum ada tagihan.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div
              class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm shadow-zinc-950/5"
            >
              <div class="flex items-center gap-4 border-b border-zinc-100 p-4">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-700"
                >
                  <WalletCards class="h-5 w-5" />
                </div>
                <div>
                  <h2 class="font-bold text-zinc-950">Pembayaran</h2>
                  <p class="text-sm text-zinc-500">
                    Riwayat pembayaran Anda.
                  </p>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                  <thead class="bg-zinc-50 text-xs uppercase text-zinc-500">
                    <tr>
                      <th class="px-5 py-3">Tanggal</th>
                      <th class="px-5 py-3">Metode</th>
                      <th class="px-5 py-3">Status</th>
                      <th class="px-5 py-3 text-right">Nominal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="payment in latestPayments"
                      :key="payment.id"
                      class="border-t border-zinc-100"
                    >
                      <td class="px-5 py-3">{{ formatDate(payment.paid_at) }}</td>
                      <td class="px-5 py-3">{{ payment.payment_method }}</td>
                      <td class="px-5 py-3">
                        <span
                          class="rounded-full px-3 py-1 text-xs font-bold"
                          :class="statusClass(payment.status)"
                        >
                          {{ payment.status }}
                        </span>
                      </td>
                      <td class="px-5 py-3 text-right">
                        {{ formatCurrency(payment.amount) }}
                      </td>
                    </tr>
                    <tr v-if="latestPayments.length === 0">
                      <td
                        colspan="4"
                        class="px-5 py-7 text-center text-zinc-500"
                      >
                        Belum ada pembayaran.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <Link
                href="/member/savings"
                class="flex items-center justify-center gap-2 border-t border-zinc-100 py-4 text-sm font-semibold text-emerald-700"
              >
                Lihat semua pembayaran
                <ChevronRight class="h-4 w-4" />
              </Link>
            </div>
          </div>
        </section>

        <section
          class="flex flex-col gap-4 rounded-xl border border-emerald-100 bg-emerald-50/70 p-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex items-center gap-4">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-emerald-700"
            >
              <ShieldCheck class="h-5 w-5" />
            </div>
            <div>
              <p class="font-bold text-emerald-800">
                Transaksi aman & terpercaya
              </p>
              <p class="text-sm text-zinc-600">
                Seluruh data simpanan dan pembayaran Anda dilindungi dengan
                sistem keamanan berlapis.
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2 text-sm text-zinc-600">
            <ShieldCheck class="h-5 w-5 text-emerald-700" />
            Data Anda aman bersama KojayaPro
          </div>
        </section>
      </div>
    </PageContainer>
  </AppLayout>
</template>
