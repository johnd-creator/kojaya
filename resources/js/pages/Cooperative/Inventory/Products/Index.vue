<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { Package, Search } from "lucide-vue-next";
import { ref } from "vue";
import {
  destroy,
  index,
  show,
  store,
  update,
} from "@/routes/cooperative/pos-products";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";

const props = defineProps<{ products: any; categories: any[]; filters: any }>();
const search = ref(props.filters.search ?? "");
const categoryId = ref(props.filters.category_id ?? "");
const lowStock = ref(Boolean(props.filters.low_stock));
const editingId = ref<number | null>(null);
const form = useForm({
  pos_category_id: "",
  sku: "",
  barcode: "",
  name: "",
  cost_price: 0,
  sale_price: 0,
  stock: 0,
  minimum_stock: 0,
  is_active: true,
});

const applyFilters = () =>
  router.get(
    index().url,
    {
      search: search.value,
      category_id: categoryId.value,
      low_stock: lowStock.value ? 1 : undefined,
    },
    { preserveState: true, replace: true },
  );
const reset = () => {
  editingId.value = null;
  form.reset();
  form.is_active = true;
};
const edit = (product: any) => {
  editingId.value = product.id;
  form.pos_category_id = product.pos_category_id ?? "";
  form.sku = product.sku;
  form.barcode = product.barcode ?? "";
  form.name = product.name;
  form.cost_price = product.cost_price;
  form.sale_price = product.sale_price;
  form.stock = product.stock;
  form.minimum_stock = product.minimum_stock;
  form.is_active = product.is_active;
};
const submit = () => {
  if (editingId.value) {
    form.put(update(editingId.value).url, {
      preserveScroll: true,
      onSuccess: reset,
    });
    return;
  }
  form.post(store().url, { preserveScroll: true, onSuccess: reset });
};
</script>

<template>
  <Head title="Produk POS" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Inventory POS', href: '#' },
      { title: 'Produk', href: index().url },
    ]"
  >
    <div
      class="grid w-full gap-6 p-6 xl:grid-cols-[380px_1fr]"
    >
      <form
        class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900"
        @submit.prevent="submit"
      >
        <div class="flex items-center gap-2">
          <Package class="h-5 w-5" />
          <h1 class="text-xl font-semibold">
            {{ editingId ? "Edit Produk" : "Produk Baru" }}
          </h1>
        </div>
        <div class="mt-4 grid gap-3">
          <select
            v-model="form.pos_category_id"
            class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option value="">Tanpa kategori</option>
            <option
              v-for="category in categories"
              :key="category.id"
              :value="category.id"
            >
              {{ category.name }}
            </option>
          </select>
          <Input v-model="form.name" placeholder="Nama produk" required />
          <div class="grid grid-cols-2 gap-2">
            <Input v-model="form.sku" placeholder="SKU" required />
            <Input v-model="form.barcode" placeholder="Barcode" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <Input
              v-model="form.cost_price"
              type="number"
              min="0"
              placeholder="Harga beli"
            />
            <Input
              v-model="form.sale_price"
              type="number"
              min="0"
              placeholder="Harga jual"
              required
            />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <Input
              v-model="form.stock"
              type="number"
              min="0"
              placeholder="Stok awal"
              :disabled="Boolean(editingId)"
            />
            <Input
              v-model="form.minimum_stock"
              type="number"
              min="0"
              placeholder="Minimum stok"
            />
          </div>
          <label class="flex items-center gap-2 text-sm"
            ><input v-model="form.is_active" type="checkbox" />Aktif</label
          >
          <div class="flex gap-2">
            <Button class="flex-1" type="submit" :disabled="form.processing"
              >Simpan</Button
            >
            <Button type="button" variant="outline" @click="reset"
              >Reset</Button
            >
          </div>
        </div>
      </form>

      <div class="flex flex-col gap-4">
        <div
          class="flex flex-col gap-3 rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900 md:flex-row"
        >
          <div class="relative flex-1">
            <Search
              class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
            />
            <Input
              v-model="search"
              class="pl-9"
              placeholder="Cari produk, SKU, barcode"
              @keyup.enter="applyFilters"
            />
          </div>
          <select
            v-model="categoryId"
            class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option value="">Semua kategori</option>
            <option
              v-for="category in categories"
              :key="category.id"
              :value="category.id"
            >
              {{ category.name }}
            </option>
          </select>
          <label class="flex items-center gap-2 text-sm"
            ><input v-model="lowStock" type="checkbox" />Low stock</label
          >
          <Button variant="outline" @click="applyFilters">Filter</Button>
        </div>

        <div
          class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
        >
          <table class="w-full text-left text-sm">
            <thead
              class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"
            >
              <tr>
                <th class="px-4 py-3">Produk</th>
                <th>Kategori</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Stok</th>
                <th class="px-4 py-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="product in products.data" :key="product.id">
                <td class="px-4 py-3">
                  <Link
                    class="font-medium hover:text-indigo-600"
                    :href="show(product.id).url"
                    >{{ product.name }}</Link
                  >
                  <div class="text-xs text-zinc-500">
                    {{ product.sku }} · {{ product.barcode || "-" }}
                  </div>
                </td>
                <td>{{ product.category?.name || "-" }}</td>
                <td class="text-right">
                  {{ formatCurrency(product.sale_price) }}
                </td>
                <td
                  class="text-right"
                  :class="
                    product.stock <= product.minimum_stock
                      ? 'font-semibold text-red-600'
                      : ''
                  "
                >
                  {{ product.stock }}
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2">
                    <Button size="sm" variant="outline" @click="edit(product)"
                      >Edit</Button
                    >
                    <Button
                      size="sm"
                      variant="outline"
                      @click="router.delete(destroy(product.id).url)"
                      >Delete</Button
                    >
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
