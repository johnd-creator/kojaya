<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Calculator, Sparkles } from "lucide-vue-next";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
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
    <PageContainer class="max-w-none">
      <section
        class="relative overflow-hidden rounded-2xl border border-sky-200/60 bg-gradient-to-br from-white via-sky-50/60 to-emerald-50/40 p-6 shadow-sm shadow-sky-950/5 sm:p-7 dark:border-sky-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      >
        <div
          class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10"
          aria-hidden="true"
        />
        <div class="relative space-y-3">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-900/40 dark:text-sky-200 dark:ring-sky-800/60"
          >
            <Sparkles class="size-3.5" />
            Simulasi
          </span>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
          >
            Kalkulator Pinjaman
          </h1>
          <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Simulasikan angsuran, bunga, biaya admin, dan total pinjaman.
          </p>
        </div>
      </section>

      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Parameter Simulasi"
          :icon="Calculator"
          tone="sky"
        />
        <CardContent class="p-5">
          <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
            <div class="space-y-1.5">
              <label
                class="text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >Tipe Pinjaman</label
              >
              <select
                v-model="form.loan_type_id"
                required
                class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 dark:border-zinc-800 dark:bg-zinc-950"
              >
                <option value="">Pilih tipe</option>
                <option
                  v-for="loanType in loanTypes"
                  :key="loanType.id"
                  :value="loanType.id"
                >
                  {{ loanType.name }}
                </option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label
                class="text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >Nominal Pokok</label
              >
              <Input
                v-model="form.principal_amount"
                type="number"
                min="1"
                step="1000"
                required
              />
            </div>
            <div class="space-y-1.5">
              <label
                class="text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >Tenor (bulan)</label
              >
              <Input
                v-model="form.term_months"
                type="number"
                min="1"
                required
              />
            </div>
            <div class="space-y-1.5">
              <label
                class="text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >Jatuh Tempo Pertama</label
              >
              <Input v-model="form.first_due_date" type="date" required />
            </div>
            <div class="md:col-span-2 flex justify-end">
              <Button type="submit" class="shadow-sm">Hitung Simulasi</Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <div v-if="preview" class="grid gap-4 md:grid-cols-4">
        <div
          class="rounded-xl border border-zinc-200/80 bg-white/95 p-4 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
        >
          <div
            class="text-xs font-medium uppercase tracking-wide text-zinc-500"
          >
            Angsuran / Bulan
          </div>
          <div
            class="mt-1 text-xl font-bold tabular-nums text-zinc-950 dark:text-white"
          >
            {{ formatCurrency(preview.installment_amount) }}
          </div>
        </div>
        <div
          class="rounded-xl border border-zinc-200/80 bg-white/95 p-4 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
        >
          <div
            class="text-xs font-medium uppercase tracking-wide text-zinc-500"
          >
            Total Bunga
          </div>
          <div
            class="mt-1 text-xl font-bold tabular-nums text-zinc-950 dark:text-white"
          >
            {{ formatCurrency(preview.total_interest_amount) }}
          </div>
        </div>
        <div
          class="rounded-xl border border-zinc-200/80 bg-white/95 p-4 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
        >
          <div
            class="text-xs font-medium uppercase tracking-wide text-zinc-500"
          >
            Biaya Admin
          </div>
          <div
            class="mt-1 text-xl font-bold tabular-nums text-zinc-950 dark:text-white"
          >
            {{ formatCurrency(preview.admin_fee) }}
          </div>
        </div>
        <div
          class="rounded-xl border border-zinc-200/80 bg-white/95 p-4 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
        >
          <div
            class="text-xs font-medium uppercase tracking-wide text-zinc-500"
          >
            Total Kewajiban
          </div>
          <div
            class="mt-1 text-xl font-bold tabular-nums text-zinc-950 dark:text-white"
          >
            {{ formatCurrency(preview.total_amount) }}
          </div>
        </div>
      </div>

      <Card
        v-if="preview"
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Jadwal Simulasi"
          :description="`${preview.schedule?.length ?? 0} angsuran`"
          :icon="Calculator"
          tone="sky"
        />
        <CardContent class="px-0 pb-0">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" role="table">
              <thead
                class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-950"
              >
                <tr>
                  <th class="px-4 py-3">#</th>
                  <th>Jatuh Tempo</th>
                  <th class="text-right">Pokok</th>
                  <th class="text-right">Bunga</th>
                  <th class="text-right">Biaya</th>
                  <th class="text-right">Tagihan</th>
                </tr>
              </thead>
              <tbody
                class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70"
              >
                <tr
                  v-for="row in preview.schedule"
                  :key="row.installment_no"
                  class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-900/50"
                >
                  <td class="px-4 py-2.5 tabular-nums">
                    {{ row.installment_no }}
                  </td>
                  <td class="tabular-nums">{{ formatDate(row.due_date) }}</td>
                  <td class="text-right tabular-nums">
                    {{ formatCurrency(row.principal_amount) }}
                  </td>
                  <td class="text-right tabular-nums">
                    {{ formatCurrency(row.interest_amount) }}
                  </td>
                  <td class="text-right tabular-nums">
                    {{ formatCurrency(row.fee_amount) }}
                  </td>
                  <td
                    class="text-right font-bold tabular-nums text-zinc-950 dark:text-white"
                  >
                    {{ formatCurrency(row.amount_due) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </PageContainer>
  </AppLayout>
</template>
