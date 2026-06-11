<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { Boxes, Package, Search, Sparkles } from "lucide-vue-next";
import { ref } from "vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import {
  destroy,
  index,
  show,
  store,
  update,
} from "@/routes/cooperative/pos-products";

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
            Inventory POS
          </span>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
          >
            Produk POS
          </h1>
          <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Kelola katalog produk, harga, stok, dan minimum stok toko koperasi.
          </p>
        </div>
      </section>

      <div class="grid gap-6 xl:grid-cols-[380px_1fr]">
        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <SectionHeader
            :title="editingId ? 'Edit Produk' : 'Produk Baru'"
            :icon="Package"
            tone="sky"
          />
          <CardContent class="space-y-4 p-5">
            <form class="space-y-4" @submit.prevent="submit">
              <select
                v-model="form.pos_category_id"
                class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 dark:border-zinc-800 dark:bg-zinc-950"
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
              <div class="grid grid-cols-2 gap-3">
                <Input v-model="form.sku" placeholder="SKU" required />
                <Input v-model="form.barcode" placeholder="Barcode" />
              </div>
              <div class="grid grid-cols-2 gap-3">
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
              <div class="grid grid-cols-2 gap-3">
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
                ><input
                  v-model="form.is_active"
                  type="checkbox"
                  class="rounded border"
                />
                Aktif</label
              >
              <div class="flex gap-2">
                <Button
                  class="flex-1 shadow-sm"
                  type="submit"
                  :disabled="form.processing"
                  >Simpan</Button
                >
                <Button type="button" variant="outline" @click="reset"
                  >Reset</Button
                >
              </div>
            </form>
          </CardContent>
        </Card>

        <div class="flex flex-col gap-4">
          <Card
            class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
          >
            <CardContent class="p-4">
              <div class="flex flex-col gap-3 md:flex-row">
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
                  class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 dark:border-zinc-800 dark:bg-zinc-950"
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
                <label class="flex items-center gap-2 text-sm whitespace-nowrap"
                  ><input
                    v-model="lowStock"
                    type="checkbox"
                    class="rounded border"
                  />
                  Low stock</label
                >
                <Button variant="outline" @click="applyFilters">Filter</Button>
              </div>
            </CardContent>
          </Card>

          <Card
            class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
          >
            <SectionHeader
              title="Katalog"
              :description="`${products.total ?? 0} produk`"
              :icon="Boxes"
              tone="sky"
            />
            <CardContent class="px-0 pb-0">
              <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" role="table">
                  <thead
                    class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-950"
                  >
                    <tr>
                      <th class="px-4 py-3">Produk</th>
                      <th>Kategori</th>
                      <th class="text-right">Harga</th>
                      <th class="text-right">Stok</th>
                      <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody
                    class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70"
                  >
                    <tr
                      v-for="product in products.data"
                      :key="product.id"
                      class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-900/50"
                    >
                      <td class="px-4 py-3">
                        <Link
                          class="font-semibold text-zinc-950 hover:text-indigo-600 dark:text-white"
                          :href="show(product.id).url"
                          >{{ product.name }}</Link
                        >
                        <div class="text-xs text-zinc-500">
                          {{ product.sku }} · {{ product.barcode || "-" }}
                        </div>
                      </td>
                      <td>{{ product.category?.name || "-" }}</td>
                      <td class="text-right font-medium tabular-nums">
                        {{ formatCurrency(product.sale_price) }}
                      </td>
                      <td class="text-right">
                        <span
                          :class="
                            product.stock <= product.minimum_stock
                              ? 'font-bold text-rose-600 dark:text-rose-400'
                              : 'tabular-nums'
                          "
                        >
                          {{ product.stock }}
                          <span
                            v-if="product.stock <= product.minimum_stock"
                            class="ml-1 text-xs"
                            >(min {{ product.minimum_stock }})</span
                          >
                        </span>
                      </td>
                      <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2">
                          <Button
                            size="sm"
                            variant="outline"
                            @click="edit(product)"
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
                    <tr v-if="products.data.length === 0">
                      <td
                        colspan="5"
                        class="px-4 py-16 text-center text-zinc-500"
                      >
                        <div class="flex flex-col items-center gap-2">
                          <Boxes
                            class="size-8 text-zinc-300 dark:text-zinc-700"
                          />
                          <p class="text-sm">Belum ada produk.</p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
