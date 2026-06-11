<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { RefreshCw } from "lucide-vue-next";
import { reactive } from "vue";
import { Button } from "@/components/ui/button";
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
    <div class="flex w-full flex-col gap-6 p-6">
      <div
        class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
      >
        <div>
          <h1 class="text-3xl font-bold tracking-tight">
            Report Penjualan POS
          </h1>
          <p class="mt-1 text-sm text-zinc-500">
            Ringkasan omzet, profit kotor, dan performa produk toko koperasi per
            tahun.
          </p>
        </div>
        <div class="flex gap-3 rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900">
          <Input
            v-model.number="filter.year"
            type="number"
            min="2020"
            class="w-32"
          />
          <Button type="button" variant="outline" @click="refreshReport">
            <RefreshCw class="mr-2 h-4 w-4" />
            Refresh
          </Button>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Transaksi</div>
          <div class="mt-2 text-2xl font-semibold">
            {{ summary.transactions }}
          </div>
        </div>
        <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Omzet</div>
          <div class="mt-2 text-2xl font-semibold">
            {{ formatCurrency(summary.revenue) }}
          </div>
        </div>
        <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Profit Kotor</div>
          <div class="mt-2 text-2xl font-semibold">
            {{ formatCurrency(summary.gross_profit) }}
          </div>
        </div>
        <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-5 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Transaksi Anggota</div>
          <div class="mt-2 text-2xl font-semibold">
            {{ summary.member_transactions }}
          </div>
        </div>
      </div>

      <div class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900">
        <table class="w-full text-sm">
          <thead
            class="border-b bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-zinc-950"
          >
            <tr>
              <th class="px-4 py-3">Produk</th>
              <th class="px-4 py-3 text-right">Qty</th>
              <th class="px-4 py-3 text-right">Omzet</th>
              <th class="px-4 py-3 text-right">Profit Kotor</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr
              v-for="product in productSales"
              :key="product.pos_product_id ?? product.product_name"
            >
              <td class="px-4 py-3 font-medium">{{ product.product_name }}</td>
              <td class="px-4 py-3 text-right">{{ product.quantity }}</td>
              <td class="px-4 py-3 text-right">
                {{ formatCurrency(product.revenue) }}
              </td>
              <td class="px-4 py-3 text-right font-medium">
                {{ formatCurrency(product.gross_profit) }}
              </td>
            </tr>
            <tr v-if="productSales.length === 0">
              <td colspan="4" class="px-4 py-10 text-center text-zinc-500">
                Belum ada penjualan POS untuk tahun ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
