<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import StatusJourney from "@/components/Kojayaku/StatusJourney.vue";
import PageContainer from "@/components/PageContainer.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";

defineProps<{
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
      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border p-4">
          <div class="text-sm text-muted-foreground">Saldo Simpanan</div>
          <div class="mt-2 text-2xl font-semibold">
            {{ formatCurrency(summary.savings_balance) }}
          </div>
        </div>
        <div class="rounded-lg border p-4">
          <div class="text-sm text-muted-foreground">Total Pembayaran</div>
          <div class="mt-2 text-2xl font-semibold">
            {{ formatCurrency(summary.total_paid) }}
          </div>
        </div>
        <div class="rounded-lg border p-4">
          <div class="text-sm text-muted-foreground">Tagihan Pending</div>
          <div class="mt-2 text-2xl font-semibold">
            {{ summary.pending_invoices }}
          </div>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-4">
        <div
          v-for="key in ['POKOK', 'WAJIB', 'SUKARELA', 'KHUSUS']"
          :key="key"
          class="rounded-lg border p-4"
        >
          <div class="text-sm text-muted-foreground">Simpanan {{ key }}</div>
          <div class="mt-2 text-xl font-semibold">
            {{ formatCurrency(summary.by_category?.[key] ?? 0) }}
          </div>
        </div>
      </div>

      <StatusJourney
        :title="journey.title"
        :current-status="journey.current_status"
        :reference="journey.reference"
        :amount="journey.amount"
        :steps="journey.steps"
      />

      <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-lg border overflow-x-auto">
          <div class="border-b px-4 py-3 font-semibold">Riwayat Ledger</div>
          <table class="w-full text-left text-sm">
            <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
              <tr>
                <th class="px-4 py-3">Tanggal</th>
                <th class="px-4 py-3">Tipe</th>
                <th class="px-4 py-3">Kategori</th>
                <th class="px-4 py-3 text-right">Debit</th>
                <th class="px-4 py-3 text-right">Credit</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="entry in entries.data"
                :key="entry.id"
                class="border-t"
              >
                <td class="px-4 py-3">{{ formatDate(entry.posted_at) }}</td>
                <td class="px-4 py-3">{{ entry.entry_type }}</td>
                <td class="px-4 py-3">
                  {{
                    entry.contribution_type?.category ||
                    entry.category_snapshot ||
                    "-"
                  }}
                </td>
                <td class="px-4 py-3 text-right">
                  {{ formatCurrency(entry.debit) }}
                </td>
                <td class="px-4 py-3 text-right">
                  {{ formatCurrency(entry.credit) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="space-y-6">
          <div class="rounded-lg border overflow-x-auto">
            <div class="border-b px-4 py-3 font-semibold">Tagihan</div>
            <table class="w-full text-left text-sm">
              <thead
                class="bg-muted/40 text-xs uppercase text-muted-foreground"
              >
                <tr>
                  <th class="px-4 py-3">Periode</th>
                  <th class="px-4 py-3">Jenis</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3 text-right">Nominal</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="invoice in invoices.data"
                  :key="invoice.id"
                  class="border-t"
                >
                  <td class="px-4 py-3">{{ invoice.period }}</td>
                  <td class="px-4 py-3">
                    {{ invoice.contribution_type?.name || "-" }}
                  </td>
                  <td class="px-4 py-3">{{ invoice.status }}</td>
                  <td class="px-4 py-3 text-right">
                    {{ formatCurrency(invoice.amount) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="rounded-lg border overflow-x-auto">
            <div class="border-b px-4 py-3 font-semibold">Pembayaran</div>
            <table class="w-full text-left text-sm">
              <thead
                class="bg-muted/40 text-xs uppercase text-muted-foreground"
              >
                <tr>
                  <th class="px-4 py-3">Tanggal</th>
                  <th class="px-4 py-3">Metode</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3 text-right">Nominal</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="payment in payments.data"
                  :key="payment.id"
                  class="border-t"
                >
                  <td class="px-4 py-3">{{ formatDate(payment.paid_at) }}</td>
                  <td class="px-4 py-3">{{ payment.payment_method }}</td>
                  <td class="px-4 py-3">{{ payment.status }}</td>
                  <td class="px-4 py-3 text-right">
                    {{ formatCurrency(payment.amount) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
