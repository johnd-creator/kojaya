<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import { index } from "@/routes/cooperative/pos/transactions";

defineProps<{ transaction: any }>();
</script>

<template>
  <Head title="Detail Transaksi POS" />
  <AppLayout
    :breadcrumbs="[
      { title: 'POS Toko', href: '#' },
      { title: 'Riwayat Transaksi', href: index().url },
      { title: transaction.transaction_no, href: '#' },
    ]"
  >
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-6">
      <div>
        <Link :href="index().url" class="text-sm text-indigo-600">Kembali</Link>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">
          {{ transaction.transaction_no }}
        </h1>
        <p class="text-sm text-zinc-500">
          {{ new Date(transaction.sold_at).toLocaleString("id-ID") }}
        </p>
      </div>
      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Kasir</div>
          <div class="font-medium">{{ transaction.cashier?.name || "-" }}</div>
        </div>
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Anggota</div>
          <div class="font-medium">{{ transaction.member?.name || "-" }}</div>
        </div>
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Total</div>
          <div class="font-medium">
            {{ formatCurrency(transaction.total_amount) }}
          </div>
        </div>
      </div>
      <div class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
        <table class="w-full text-left text-sm">
          <thead
            class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"
          >
            <tr>
              <th class="px-4 py-3">Produk</th>
              <th class="text-right">Qty</th>
              <th class="text-right">Harga</th>
              <th class="px-4 py-3 text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="item in transaction.items" :key="item.id">
              <td class="px-4 py-3">{{ item.product?.name }}</td>
              <td class="text-right">{{ item.quantity }}</td>
              <td class="text-right">{{ formatCurrency(item.unit_price) }}</td>
              <td class="px-4 py-3 text-right">
                {{ formatCurrency(item.line_total) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
