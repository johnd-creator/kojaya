<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { index, show } from "@/routes/cooperative/pos/transactions";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";

const props = defineProps<{ transactions: any; filters: any }>();
const dateFrom = ref(props.filters.date_from ?? "");
const dateTo = ref(props.filters.date_to ?? "");
const applyFilters = () =>
  router.get(
    index().url,
    { date_from: dateFrom.value, date_to: dateTo.value },
    { preserveState: true, replace: true },
  );
</script>

<template>
  <Head title="Riwayat POS" />
  <AppLayout
    :breadcrumbs="[
      { title: 'POS Toko', href: '#' },
      { title: 'Riwayat Transaksi', href: index().url },
    ]"
  >
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Riwayat Transaksi POS</h1>
        <p class="mt-1 text-sm text-zinc-500">Transaksi kasir toko koperasi.</p>
      </div>
      <div
        class="flex flex-col gap-3 rounded-lg border bg-white p-4 dark:bg-zinc-900 md:flex-row"
      >
        <Input v-model="dateFrom" type="date" class="md:w-48" />
        <Input v-model="dateTo" type="date" class="md:w-48" />
        <Button variant="outline" @click="applyFilters">Filter</Button>
      </div>
      <div class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
        <table class="w-full text-left text-sm">
          <thead
            class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"
          >
            <tr>
              <th class="px-4 py-3">Transaksi</th>
              <th>Kasir</th>
              <th>Anggota</th>
              <th>Payment</th>
              <th class="text-right">Items</th>
              <th class="px-4 py-3 text-right">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="transaction in transactions.data" :key="transaction.id">
              <td class="px-4 py-3">
                <Link
                  class="font-medium hover:text-indigo-600"
                  :href="show(transaction.id).url"
                  >{{ transaction.transaction_no }}</Link
                >
                <div class="text-xs text-zinc-500">
                  {{ new Date(transaction.sold_at).toLocaleString("id-ID") }}
                </div>
              </td>
              <td>{{ transaction.cashier?.name || "-" }}</td>
              <td>{{ transaction.member?.name || "-" }}</td>
              <td>{{ transaction.payments?.[0]?.payment_method || "-" }}</td>
              <td class="text-right">{{ transaction.items_count }}</td>
              <td class="px-4 py-3 text-right">
                {{ formatCurrency(transaction.total_amount) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
