<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { BarChart3, Sparkles, TrendingUp } from "lucide-vue-next";
import { reactive } from "vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index } from "@/routes/cooperative/pos/reports";

const props = defineProps<{
  summary: any;
  productSales: any[];
  filters: any;
}>();

const filter = reactive({
  year: Number(props.filters?.year ?? new Date().getFullYear()),
});

const refreshReport = () => {
  router.get(
    index().url,
    { year: filter.year },
    { preserveState: true, preserveScroll: true },
  );
};
</script>

<template>
  <Head title="Report Penjualan POS" />
  <AppLayout
    :breadcrumbs="[
      { title: 'POS Toko', href: '#' },
      { title: 'Report Penjualan', href: index().url },
    ]"
  >
    <PageContainer class="max-w-none">
      <section
        class="relative overflow-hidden rounded-2xl border border-emerald-200/60 bg-gradient-to-br from-white via-emerald-50/60 to-sky-50/40 p-6 shadow-sm shadow-emerald-950/5 sm:p-7 dark:border-emerald-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      >
        <div
          class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"
          aria-hidden="true"
        />
        <div
          class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
        >
          <div class="space-y-3">
            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-800/60"
            >
              <Sparkles class="size-3.5" />
              Laporan Tahunan
            </span>
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
            >
              Report Penjualan POS
            </h1>
            <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
              Ringkasan omzet, profit kotor, dan performa produk toko koperasi
              per tahun.
            </p>
          </div>
          <div
            class="flex items-end gap-3 rounded-xl border border-white/70 bg-white/70 p-3 shadow-sm backdrop-blur dark:border-zinc-800/80 dark:bg-zinc-950/40"
          >
            <div class="space-y-1">
              <label
                class="text-[11px] font-medium uppercase text-zinc-500"
                for="rpt-year"
                >Tahun</label
              >
              <Input
                id="rpt-year"
                v-model.number="filter.year"
                type="number"
                min="2020"
                class="w-28"
              />
            </div>
            <Button
              type="button"
              variant="outline"
              size="sm"
              class="mb-0.5"
              @click="refreshReport"
              >Refresh</Button
            >
          </div>
        </div>
      </section>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div
          class="rounded-xl border border-zinc-200/80 bg-white/95 p-4 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
        >
          <div
            class="text-xs font-medium uppercase tracking-wide text-zinc-500"
          >
            Transaksi
          </div>
          <div
            class="mt-1 text-2xl font-bold tabular-nums text-zinc-950 dark:text-white"
          >
            {{ summary.transactions }}
          </div>
        </div>
        <div
          class="rounded-xl border border-emerald-200/60 bg-gradient-to-br from-emerald-50/60 to-white p-4 shadow-sm shadow-emerald-950/5 dark:border-emerald-900/40 dark:from-emerald-950/20 dark:to-zinc-900"
        >
          <div
            class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300"
          >
            Omzet
          </div>
          <div
            class="mt-1 text-2xl font-bold tabular-nums text-emerald-700 dark:text-emerald-300"
          >
            {{ formatCurrency(summary.revenue) }}
          </div>
        </div>
        <div
          class="rounded-xl border border-sky-200/60 bg-gradient-to-br from-sky-50/60 to-white p-4 shadow-sm shadow-sky-950/5 dark:border-sky-900/40 dark:from-sky-950/20 dark:to-zinc-900"
        >
          <div
            class="text-xs font-medium uppercase tracking-wide text-sky-700 dark:text-sky-300"
          >
            Profit Kotor
          </div>
          <div
            class="mt-1 text-2xl font-bold tabular-nums text-sky-700 dark:text-sky-300"
          >
            {{ formatCurrency(summary.gross_profit) }}
          </div>
        </div>
        <div
          class="rounded-xl border border-zinc-200/80 bg-white/95 p-4 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
        >
          <div
            class="text-xs font-medium uppercase tracking-wide text-zinc-500"
          >
            Transaksi Anggota
          </div>
          <div
            class="mt-1 text-2xl font-bold tabular-nums text-zinc-950 dark:text-white"
          >
            {{ summary.member_transactions }}
          </div>
        </div>
      </div>

      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Penjualan per Produk"
          :description="`${productSales.length} produk`"
          :icon="BarChart3"
          tone="emerald"
        />
        <CardContent class="px-0 pb-0">
          <div class="overflow-x-auto">
            <table class="w-full text-sm" role="table">
              <thead
                class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-950"
              >
                <tr>
                  <th class="px-4 py-3">Produk</th>
                  <th class="px-4 py-3 text-right">Qty</th>
                  <th class="px-4 py-3 text-right">Omzet</th>
                  <th class="px-4 py-3 text-right">Profit Kotor</th>
                </tr>
              </thead>
              <tbody
                class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70"
              >
                <tr
                  v-for="product in productSales"
                  :key="product.pos_product_id ?? product.product_name"
                  class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-900/50"
                >
                  <td
                    class="px-4 py-3 font-semibold text-zinc-950 dark:text-white"
                  >
                    {{ product.product_name }}
                  </td>
                  <td class="px-4 py-3 text-right tabular-nums">
                    {{ product.quantity }}
                  </td>
                  <td class="px-4 py-3 text-right tabular-nums">
                    {{ formatCurrency(product.revenue) }}
                  </td>
                  <td
                    class="px-4 py-3 text-right font-bold tabular-nums text-emerald-700 dark:text-emerald-300"
                  >
                    {{ formatCurrency(product.gross_profit) }}
                  </td>
                </tr>
                <tr v-if="productSales.length === 0">
                  <td colspan="4" class="px-4 py-16 text-center text-zinc-500">
                    <div class="flex flex-col items-center gap-2">
                      <TrendingUp
                        class="size-8 text-zinc-300 dark:text-zinc-700"
                      />
                      <p class="text-sm">
                        Belum ada penjualan POS untuk tahun ini.
                      </p>
                    </div>
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
