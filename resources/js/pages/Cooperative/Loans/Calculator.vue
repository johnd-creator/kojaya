<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { formatCurrency, formatDate } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import { calculator, index } from "@/routes/cooperative/loans";

const props = defineProps<{
  loanTypes: any[];
  input: {
    loan_type_id?: string;
    principal_amount?: string;
    term_months?: string;
    first_due_date?: string;
  };
  preview?: any | null;
}>();

const form = {
  loan_type_id: props.input.loan_type_id ?? "",
  principal_amount: props.input.principal_amount ?? "1000000",
  term_months: props.input.term_months ?? "12",
  first_due_date:
    props.input.first_due_date ??
    new Date(new Date().setMonth(new Date().getMonth() + 1))
      .toISOString()
      .slice(0, 10),
};

const submit = () => {
  router.get(calculator().url, form, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Kalkulator Pinjaman" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Pinjaman', href: index().url },
      { title: 'Kalkulator', href: '#' },
    ]"
  >
    <PageContainer variant="detail">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Kalkulator Pinjaman</h1>
        <p class="mt-1 text-sm text-zinc-500">
          Simulasikan angsuran, bunga, biaya admin, dan total pinjaman.
        </p>
      </div>

      <form class="grid gap-4 rounded-lg border bg-white p-6 dark:bg-zinc-900 md:grid-cols-2" @submit.prevent="submit">
        <label class="space-y-1">
          <span class="text-sm">Tipe Pinjaman</span>
          <select v-model="form.loan_type_id" required class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950">
            <option value="">Pilih tipe</option>
            <option v-for="loanType in loanTypes" :key="loanType.id" :value="loanType.id">
              {{ loanType.name }}
            </option>
          </select>
        </label>
        <label class="space-y-1">
          <span class="text-sm">Nominal Pokok</span>
          <Input v-model="form.principal_amount" type="number" min="1" step="1000" required />
        </label>
        <label class="space-y-1">
          <span class="text-sm">Tenor (bulan)</span>
          <Input v-model="form.term_months" type="number" min="1" required />
        </label>
        <label class="space-y-1">
          <span class="text-sm">Jatuh Tempo Pertama</span>
          <Input v-model="form.first_due_date" type="date" required />
        </label>
        <div class="md:col-span-2 flex justify-end">
          <Button type="submit">Hitung Simulasi</Button>
        </div>
      </form>

      <div v-if="preview" class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Angsuran / Bulan</div>
          <div class="mt-1 text-lg font-semibold">{{ formatCurrency(preview.installment_amount) }}</div>
        </div>
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Total Bunga</div>
          <div class="mt-1 text-lg font-semibold">{{ formatCurrency(preview.total_interest_amount) }}</div>
        </div>
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Biaya Admin</div>
          <div class="mt-1 text-lg font-semibold">{{ formatCurrency(preview.admin_fee) }}</div>
        </div>
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Total Kewajiban</div>
          <div class="mt-1 text-lg font-semibold">{{ formatCurrency(preview.total_amount) }}</div>
        </div>
      </div>

      <div v-if="preview" class="rounded-lg border bg-white p-6 dark:bg-zinc-900">
        <h2 class="text-lg font-semibold">Jadwal Simulasi</h2>
        <div class="mt-4 overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-b text-xs uppercase text-zinc-500">
              <tr>
                <th class="py-2">#</th>
                <th>Jatuh Tempo</th>
                <th class="text-right">Pokok</th>
                <th class="text-right">Bunga</th>
                <th class="text-right">Biaya</th>
                <th class="text-right">Tagihan</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="row in preview.schedule" :key="row.installment_no">
                <td class="py-2">{{ row.installment_no }}</td>
                <td>{{ formatDate(row.due_date) }}</td>
                <td class="text-right">{{ formatCurrency(row.principal_amount) }}</td>
                <td class="text-right">{{ formatCurrency(row.interest_amount) }}</td>
                <td class="text-right">{{ formatCurrency(row.fee_amount) }}</td>
                <td class="text-right font-medium">{{ formatCurrency(row.amount_due) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
