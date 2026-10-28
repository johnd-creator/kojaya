<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { Calculator, ArrowLeft } from "lucide-vue-next";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import InputError from "@/components/InputError.vue";
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
  errors?: Partial<
    Record<
      "loan_type_id" | "principal_amount" | "term_months" | "first_due_date",
      string
    >
  >;
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
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Kalkulator Pinjaman"
        description="Simulasikan angsuran, bunga, biaya admin, dan total kewajiban sebelum mengajukan pinjaman."
      >
        <Button as-child variant="outline" size="sm"
          ><Link :href="index().url"
            ><ArrowLeft class="size-4" /> Data Pinjaman</Link
          ></Button
        >
      </WorkspaceHeader>
      <Card
        class="gap-0 rounded-xl py-0 overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <CardContent class="p-4">
          <form
            class="grid items-end gap-3 sm:grid-cols-2 xl:grid-cols-[1.4fr_1fr_.7fr_1fr_auto]"
            @submit.prevent="submit"
          >
            <div class="space-y-1.5">
              <label
                class="text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >Tipe Pinjaman</label
              >
              <select
                aria-label="Tipe Pinjaman"
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
              <InputError :message="errors?.loan_type_id" />
            </div>
            <div class="space-y-1.5">
              <label
                class="text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >Nominal Pokok</label
              >
              <Input
                aria-label="Nominal Pokok"
                v-model="form.principal_amount"
                type="number"
                min="1"
                step="1"
                required
              />
              <InputError :message="errors?.principal_amount" />
            </div>
            <div class="space-y-1.5">
              <label
                class="text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >Tenor (bulan)</label
              >
              <Input
                aria-label="Tenor (bulan)"
                v-model="form.term_months"
                type="number"
                min="1"
                required
              />
              <InputError :message="errors?.term_months" />
            </div>
            <div class="space-y-1.5">
              <label
                class="text-sm font-medium text-zinc-700 dark:text-zinc-300"
                >Jatuh Tempo Pertama</label
              >
              <Input
                aria-label="Jatuh Tempo Pertama"
                v-model="form.first_due_date"
                type="date"
                required
              />
              <InputError :message="errors?.first_due_date" />
            </div>
            <div class="flex justify-end sm:col-span-2 xl:col-span-1">
              <Button type="submit" class="shadow-sm">Hitung Simulasi</Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <WorkspaceSummary
        v-if="preview"
        :items="[
          {
            label: 'Angsuran / Bulan',
            value: formatCurrency(preview.installment_amount),
            tone: 'emerald',
          },
          {
            label: 'Total Bunga',
            value: formatCurrency(preview.total_interest_amount),
          },
          { label: 'Biaya Admin', value: formatCurrency(preview.admin_fee) },
          {
            label: 'Total Kewajiban',
            value: formatCurrency(preview.total_amount),
          },
        ]"
      />
      <div
        v-else
        class="flex items-start gap-3 rounded-xl border bg-zinc-50/50 p-5 dark:bg-zinc-900/50"
        data-testid="calculator-placeholder"
      >
        <Calculator class="mt-0.5 size-5 shrink-0 text-emerald-600" />
        <div>
          <h2 class="text-sm font-semibold">Rencanakan angsuran anggota</h2>
          <p class="mt-1 text-sm text-zinc-500">
            Pilih tipe pinjaman dan isi parameter, lalu hitung simulasi untuk
            melihat rincian biaya dan jadwal angsuran.
          </p>
          <p class="mt-2 text-xs text-zinc-500">
            Simulasi tidak membuat pengajuan pinjaman.
          </p>
        </div>
      </div>
      <Card
        v-if="preview"
        class="gap-0 rounded-xl py-0 overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <div class="flex items-center justify-between border-b px-4 py-3">
          <h2 class="text-sm font-semibold">Jadwal Simulasi</h2>
          <span class="text-xs text-zinc-500"
            >{{ preview.schedule?.length ?? 0 }} angsuran</span
          >
        </div>
        <CardContent class="px-0 pb-0">
          <div
            class="overflow-x-auto"
            role="region"
            aria-label="Jadwal simulasi, geser untuk melihat kolom lainnya"
            tabindex="0"
          >
            <table
              class="w-full min-w-[720px] text-left text-sm [&_th]:px-4 [&_td]:px-4 [&_td]:whitespace-nowrap"
              role="table"
            >
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
