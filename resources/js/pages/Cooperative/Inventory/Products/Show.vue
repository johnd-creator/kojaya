<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { adjustStock, index } from "@/routes/cooperative/pos-products";

const props = defineProps<{ product: any }>();
const form = useForm({
  movement_type: "ADJUSTMENT_IN",
  quantity: 1,
  notes: "",
});
const submit = () =>
  form.post(adjustStock(props.product.id).url, {
    preserveScroll: true,
    onSuccess: () => form.reset("quantity", "notes"),
  });
</script>

<template>
  <Head title="Detail Produk POS" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Inventory POS', href: '#' },
      { title: 'Produk', href: index().url },
      { title: product.sku, href: '#' },
    ]"
  >
    <div
      class="grid w-full gap-6 p-6 lg:grid-cols-[360px_1fr]"
    >
      <div class="space-y-4">
        <Link
          :href="index().url"
          class="inline-flex items-center gap-2 text-sm text-indigo-600"
          ><ArrowLeft class="h-4 w-4" />Kembali</Link
        >
        <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900">
          <h1 class="text-2xl font-semibold">{{ product.name }}</h1>
          <p class="text-sm text-zinc-500">
            {{ product.sku }} · {{ product.category?.name || "-" }}
          </p>
          <dl class="mt-4 grid gap-3 text-sm">
            <div class="flex justify-between">
              <dt>Harga jual</dt>
              <dd class="font-medium">
                {{ formatCurrency(product.sale_price) }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt>Harga beli</dt>
              <dd class="font-medium">
                {{ formatCurrency(product.cost_price) }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt>Stok</dt>
              <dd class="font-medium">{{ product.stock }}</dd>
            </div>
            <div class="flex justify-between">
              <dt>Minimum stok</dt>
              <dd class="font-medium">{{ product.minimum_stock }}</dd>
            </div>
          </dl>
        </div>
        <form
          class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900"
          @submit.prevent="submit"
        >
          <h2 class="text-lg font-semibold">Adjustment Stok</h2>
          <div class="mt-4 space-y-3">
            <select
              v-model="form.movement_type"
              class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
            >
              <option value="ADJUSTMENT_IN">Stok Masuk</option>
              <option value="ADJUSTMENT_OUT">Stok Keluar</option>
            </select>
            <Input v-model="form.quantity" type="number" min="1" required />
            <textarea
              v-model="form.notes"
              class="min-h-20 w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950"
              placeholder="Catatan"
            />
            <Button class="w-full" type="submit" :disabled="form.processing"
              >Simpan Adjustment</Button
            >
          </div>
        </form>
      </div>

      <div class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900">
        <div class="border-b p-4">
          <h2 class="text-lg font-semibold">Riwayat Stock Movement</h2>
        </div>
        <table class="w-full text-left text-sm">
          <thead
            class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"
          >
            <tr>
              <th class="px-4 py-3">Tanggal</th>
              <th>Tipe</th>
              <th class="text-right">Qty</th>
              <th class="text-right">Sebelum</th>
              <th class="text-right">Sesudah</th>
              <th class="px-4 py-3">Catatan</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="movement in product.stock_movements" :key="movement.id">
              <td class="px-4 py-3">
                {{ new Date(movement.created_at).toLocaleString("id-ID") }}
              </td>
              <td>{{ movement.movement_type }}</td>
              <td class="text-right">{{ movement.quantity }}</td>
              <td class="text-right">{{ movement.stock_before }}</td>
              <td class="text-right">{{ movement.stock_after }}</td>
              <td class="px-4 py-3">{{ movement.notes || "-" }}</td>
            </tr>
            <tr v-if="product.stock_movements.length === 0">
              <td colspan="6" class="px-4 py-10 text-center text-zinc-500">
                Belum ada movement.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
