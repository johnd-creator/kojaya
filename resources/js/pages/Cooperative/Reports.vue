<script setup lang="ts">
import { Deferred, Head } from "@inertiajs/vue3";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index } from "@/routes/cooperative/reports";

type ReportSummary = {
  active_members: number;
  saving_balance: number;
  member_credit_balance: number;
  unpaid_dues: number;
  today_sales: number;
  monthly_sales: number;
  low_stock_products: number;
  annual_pos_profit: number;
  annual_pos_points: number;
  latest_shu_year: number | null;
  latest_shu_total: number;
};

defineProps<{ summary?: ReportSummary }>();
</script>

<template>
  <Head title="Laporan Koperasi" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Laporan', href: index().url },
    ]"
  >
    <div class="flex w-full flex-col gap-6 p-6">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Laporan Koperasi</h1>
        <p class="mt-1 text-sm text-zinc-500">
          Ringkasan anggota, simpanan, tunggakan, POS toko, dan SHU tahunan.
        </p>
      </div>

      <Deferred data="summary">
        <template #fallback>
          <div aria-live="polite" class="sr-only">
            Memuat ringkasan laporan koperasi.
          </div>
          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Skeleton v-for="i in 11" :key="i" class="h-[108px] rounded-xl" />
          </div>
        </template>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">Anggota Aktif</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ summary.active_members }}
            </div>
          </div>
          <div
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">Saldo Simpanan</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ formatCurrency(summary.saving_balance) }}
            </div>
          </div>
          <div
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">Kredit Anggota</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ formatCurrency(summary.member_credit_balance) }}
            </div>
          </div>
          <div
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">Tunggakan</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ formatCurrency(summary.unpaid_dues) }}
            </div>
          </div>
          <div
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">Omzet Hari Ini</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ formatCurrency(summary.today_sales) }}
            </div>
          </div>
          <div
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">Omzet Bulan Ini</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ formatCurrency(summary.monthly_sales) }}
            </div>
          </div>
          <div
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">Produk Low Stock</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ summary.low_stock_products }}
            </div>
          </div>
          <div
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">Profit POS Tahun Ini</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ formatCurrency(summary.annual_pos_profit) }}
            </div>
          </div>
          <div
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">Poin POS Tahun Ini</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ summary.annual_pos_points }}
            </div>
          </div>
          <div
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">SHU Terakhir Ditutup</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ summary.latest_shu_year ?? "-" }}
            </div>
          </div>
          <div
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">Pool SHU Terakhir</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ formatCurrency(summary.latest_shu_total) }}
            </div>
          </div>
        </div>
      </Deferred>
    </div>
  </AppLayout>
</template>
