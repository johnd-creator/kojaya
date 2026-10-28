<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ArrowLeft, ShieldCheck } from "lucide-vue-next";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { index, show, report } from "@/routes/cooperative/store-credit";

type Summary = {
  positive_deposit_liability: number;
  negative_receivable: number;
  positive_account_count: number;
  zero_account_count: number;
  negative_account_count: number;
  suspended_account_count: number;
  utilization_threshold: number;
  high_utilization_accounts: Array<{
    id: number;
    cooperative_member_id: number;
    balance: number;
    credit_limit: number;
    utilization: number;
  }>;
  oldest_uncovered_debt_date: string | null;
};

defineProps<{ summary: Summary }>();
</script>

<template>
  <Head title="Laporan Saldo Toko" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Laporan Saldo Toko', href: report().url },
    ]"
  >
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Laporan Saldo Toko"
        description="Pantau kewajiban saldo positif, piutang anggota, dan akun yang mendekati batas kredit."
      >
        <Button as-child variant="outline" size="sm"
          ><Link :href="index().url"
            ><ArrowLeft class="size-4" /> Daftar Akun</Link
          ></Button
        >
      </WorkspaceHeader>
      <WorkspaceSummary
        :items="[
          {
            label: 'Kewajiban (Saldo +)',
            value: formatCurrency(summary.positive_deposit_liability),
            tone: 'emerald',
          },
          {
            label: 'Piutang (Saldo -)',
            value: formatCurrency(summary.negative_receivable),
            tone: 'rose',
          },
          {
            label: 'Utang Tertua',
            value: formatDate(summary.oldest_uncovered_debt_date),
            hint: 'Tanggal utang tertua yang belum tertutup.',
          },
          { label: 'Akun Positif', value: summary.positive_account_count },
          { label: 'Akun Nol', value: summary.zero_account_count },
          { label: 'Akun Negatif', value: summary.negative_account_count },
          { label: 'Ditangguhkan', value: summary.suspended_account_count },
        ]"
      />
      <Card class="gap-0 overflow-hidden rounded-xl py-0">
        <div
          class="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-3"
        >
          <h2 class="text-sm font-semibold">
            Akun Melebihi Utilisasi
            {{ Math.round(summary.utilization_threshold * 100) }}%
          </h2>
          <span class="text-xs text-zinc-500"
            >{{ summary.high_utilization_accounts.length }} akun perlu
            diperhatikan</span
          >
        </div>
        <CardContent
          class="overflow-x-auto p-0"
          role="region"
          aria-label="Akun dengan utilisasi tinggi, geser untuk melihat kolom lainnya"
          tabindex="0"
        >
          <table class="w-full min-w-[640px] text-sm [&_td]:whitespace-nowrap">
            <thead
              class="border-b bg-zinc-50/70 text-left text-xs uppercase text-zinc-500 dark:bg-zinc-950/40"
            >
              <tr>
                <th class="p-3">Anggota</th>
                <th class="p-3 text-right">Saldo</th>
                <th class="p-3 text-right">Limit</th>
                <th class="p-3 text-right">Utilisasi</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="account in summary.high_utilization_accounts"
                :key="account.id"
                class="border-b last:border-0 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50"
              >
                <td class="p-3 font-medium">
                  <Link
                    :href="show({ account: account.id }).url"
                    class="text-emerald-700 hover:underline dark:text-emerald-400"
                    >Anggota #{{ account.cooperative_member_id }}</Link
                  >
                </td>
                <td
                  class="p-3 text-right tabular-nums text-rose-600 dark:text-rose-400"
                >
                  {{ formatCurrency(account.balance) }}
                </td>
                <td class="p-3 text-right tabular-nums">
                  {{ formatCurrency(account.credit_limit) }}
                </td>
                <td class="p-3 text-right font-semibold tabular-nums">
                  {{ (account.utilization * 100).toFixed(0) }}%
                </td>
              </tr>
              <tr v-if="summary.high_utilization_accounts.length === 0">
                <td colspan="4" class="p-8 text-center">
                  <ShieldCheck class="mx-auto mb-2 size-6 text-emerald-600" />
                  <p class="text-sm font-medium">
                    Tidak ada akun melebihi ambang utilisasi.
                  </p>
                  <p class="mt-1 text-xs text-zinc-500">
                    Akun yang melampaui
                    {{ Math.round(summary.utilization_threshold * 100) }}% limit
                    kredit akan ditampilkan di sini.
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </PageContainer>
  </AppLayout>
</template>
