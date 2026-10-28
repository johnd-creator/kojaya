<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { Boxes, ImageOff, Search } from "lucide-vue-next";
import { ref } from "vue";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspacePagination from "@/components/dashboard/WorkspacePagination.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import InputError from "@/components/InputError.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import {
  destroy,
  index,
  show,
  store,
  update,
} from "@/routes/cooperative/pos-products";
import type { PaginationMeta } from "@/types/pagination";

type ProductRow = {
  id: number;
  name: string;
  sku: string;
  barcode: string | null;
  brand?: string | null;
  variant?: string | null;
  image_url?: string | null;
  category?: { name: string } | null;
  stock: number;
  minimum_stock: number;
  sale_price: number | string;
  is_active: boolean;
  is_discontinued: boolean;
};
const props = defineProps<{
  products: PaginationMeta & { data: ProductRow[] };
  categories: any[];
  filters: any;
}>();
const search = ref(props.filters.search ?? "");
const categoryId = ref(props.filters.category_id ?? "");
const lowStock = ref(Boolean(props.filters.low_stock));
const discontinued = ref(Boolean(props.filters.discontinued));
const editorOpen = ref(false);
const editingId = ref<number | null>(null);
const imagePreview = ref<string | null>(null);

const form = useForm({
  pos_category_id: "",
  sku: "",
  barcode: "",
  name: "",
  brand: "",
  variant: "",
  unit: "",
  rack_location: "",
  cost_price: 0,
  sale_price: 0,
  stock: 0,
  minimum_stock: 0,
  is_active: true,
  is_discontinued: false,
  remove_image: false as boolean,
  image: null as File | null,
});

const applyFilters = () =>
  router.get(
    index().url,
    {
      search: search.value,
      category_id: categoryId.value,
      low_stock: lowStock.value ? 1 : undefined,
      discontinued: discontinued.value ? 1 : undefined,
    },
    { preserveState: true, replace: true },
  );

const reset = () => {
  form.clearErrors();
  form.transform((data) => data);
  editingId.value = null;
  form.reset();
  form.is_active = true;
  form.is_discontinued = false;
  form.remove_image = false;
  form.image = null;
  imagePreview.value = null;
};

const onImageChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0] ?? null;
  form.image = file;
  form.remove_image = false;
  if (file) {
    imagePreview.value = URL.createObjectURL(file);
  } else {
    imagePreview.value = null;
  }
};

const edit = (product: any) => {
  editorOpen.value = true;
  form.clearErrors();
  editingId.value = product.id;
  form.pos_category_id = product.pos_category_id ?? "";
  form.sku = product.sku;
  form.barcode = product.barcode ?? "";
  form.name = product.name;
  form.brand = product.brand ?? "";
  form.variant = product.variant ?? "";
  form.unit = product.unit ?? "";
  form.rack_location = product.rack_location ?? "";
  form.cost_price = product.cost_price;
  form.sale_price = product.sale_price;
  form.stock = product.stock;
  form.minimum_stock = product.minimum_stock;
  form.is_active = product.is_active;
  form.is_discontinued = !!product.is_discontinued;
  form.remove_image = false;
  form.image = null;
  imagePreview.value = product.image_url ?? null;
};

const submit = () => {
  if (editingId.value) {
    form
      .transform((data) => ({ ...data, _method: "PUT" }))
      .post(update(editingId.value).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
          reset();
          editorOpen.value = false;
        },
      });
    return;
  }
  form.post(store().url, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      reset();
      editorOpen.value = false;
    },
  });
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
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Produk POS"
        description="Kelola katalog, harga, status, dan batas minimum stok produk toko."
        ><Button
          size="sm"
          @click="
            reset();
            editorOpen = true;
          "
          >Tambah Produk</Button
        ></WorkspaceHeader
      >
      <WorkspaceSummary
        :items="[
          {
            label: 'Produk pada Filter',
            value: products.total ?? products.data.length,
          },
          { label: 'Ditampilkan', value: products.data.length },
          {
            label: 'Stok Rendah di Halaman Ini',
            value: products.data.filter(
              (product) => product.stock <= product.minimum_stock,
            ).length,
            tone: 'rose',
          },
          {
            label: 'Aktif di Halaman Ini',
            value: products.data.filter(
              (product) => product.is_active && !product.is_discontinued,
            ).length,
            tone: 'emerald',
          },
        ]"
      />
      <Dialog v-model:open="editorOpen"
        ><DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-2xl"
          ><DialogHeader
            ><DialogTitle>{{
              editingId ? "Edit Produk" : "Tambah Produk"
            }}</DialogTitle
            ><DialogDescription
              >Isi identitas, harga, dan pengaturan katalog
              produk.</DialogDescription
            ></DialogHeader
          >
          <form class="space-y-4" @submit.prevent="submit">
            <Label for="product-category">Kategori Produk</Label>
            <select
              id="product-category"
              aria-label="Kategori produk"
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
            <div class="space-y-1">
              <Label for="product-name" class="text-xs">Nama Produk</Label
              ><Input
                id="product-name"
                v-model="form.name"
                placeholder="Nama produk"
                required
              />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <Label for="product-sku" class="text-xs">SKU</Label
                ><Input
                  id="product-sku"
                  v-model="form.sku"
                  placeholder="SKU"
                  required
                />
              </div>
              <div class="space-y-1">
                <Label for="product-barcode" class="text-xs">Barcode</Label
                ><Input
                  id="product-barcode"
                  v-model="form.barcode"
                  placeholder="Barcode"
                />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <Label for="product-brand" class="text-xs">Brand</Label
                ><Input
                  id="product-brand"
                  v-model="form.brand"
                  placeholder="Brand"
                />
              </div>
              <div class="space-y-1">
                <Label for="product-variant" class="text-xs">Varian</Label
                ><Input
                  id="product-variant"
                  v-model="form.variant"
                  placeholder="Varian"
                />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <Label for="product-unit" class="text-xs">Satuan</Label
                ><Input
                  id="product-unit"
                  v-model="form.unit"
                  placeholder="Satuan (pcs, box…)"
                />
              </div>
              <div class="space-y-1">
                <Label for="product-rack_location" class="text-xs"
                  >Lokasi Rak</Label
                ><Input
                  id="product-rack_location"
                  v-model="form.rack_location"
                  placeholder="Lokasi rak"
                />
              </div>
            </div>
            <div class="space-y-1.5">
              <label
                class="text-xs font-medium text-zinc-600 dark:text-zinc-300"
              >
                Foto produk
              </label>
              <div
                class="flex items-center gap-3 rounded-md border border-dashed border-zinc-200 p-2 dark:border-zinc-800"
              >
                <div
                  class="size-16 shrink-0 overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-800"
                >
                  <img
                    v-if="imagePreview"
                    :src="imagePreview"
                    class="h-full w-full object-cover"
                    alt="Pratinjau"
                  />
                  <div
                    v-else
                    class="flex h-full w-full items-center justify-center text-zinc-300 dark:text-zinc-700"
                    aria-hidden="true"
                  >
                    <ImageOff class="size-5" />
                  </div>
                </div>
                <input
                  aria-label="Foto produk"
                  type="file"
                  accept="image/png,image/jpeg,image/webp"
                  class="block w-full text-xs file:mr-3 file:rounded-md file:border-0 file:bg-sky-50 file:px-2 file:py-1 file:text-sky-700 hover:file:bg-sky-100 dark:file:bg-sky-900/40 dark:file:text-sky-200"
                  @change="onImageChange"
                />
              </div>
              <button
                v-if="editingId && imagePreview"
                type="button"
                class="text-xs text-rose-600 hover:underline"
                @click="
                  imagePreview = null;
                  form.remove_image = true;
                  form.image = null;
                "
              >
                Hapus foto
              </button>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <Label for="product-cost_price" class="text-xs"
                  >Harga Beli</Label
                ><Input
                  id="product-cost_price"
                  v-model="form.cost_price"
                  type="number"
                  min="0"
                  placeholder="Harga beli"
                />
              </div>
              <div class="space-y-1">
                <Label for="product-sale_price" class="text-xs"
                  >Harga Jual</Label
                ><Input
                  id="product-sale_price"
                  v-model="form.sale_price"
                  type="number"
                  min="0"
                  placeholder="Harga jual"
                  required
                />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <Label for="product-stock" class="text-xs">Stok Awal</Label
                ><Input
                  id="product-stock"
                  v-model="form.stock"
                  type="number"
                  min="0"
                  placeholder="Stok awal"
                  :disabled="Boolean(editingId)"
                />
              </div>
              <div class="space-y-1">
                <Label for="product-minimum_stock" class="text-xs"
                  >Minimum Stok</Label
                ><Input
                  id="product-minimum_stock"
                  v-model="form.minimum_stock"
                  type="number"
                  min="0"
                  placeholder="Minimum stok"
                />
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-sm">
              <label class="flex items-center gap-2"
                ><input
                  v-model="form.is_active"
                  type="checkbox"
                  class="rounded border"
                />
                Aktif</label
              >
              <label class="flex items-center gap-2"
                ><input
                  v-model="form.is_discontinued"
                  type="checkbox"
                  class="rounded border"
                />
                Discontinue</label
              >
            </div>
            <InputError
              v-for="(message, field) in form.errors"
              :key="field"
              :message="message"
              role="alert"
            />
            <div class="flex gap-2">
              <Button
                class="flex-1 shadow-sm"
                type="submit"
                :disabled="form.processing"
                >Simpan</Button
              >
              <Button
                type="button"
                variant="outline"
                @click="editorOpen = false"
                >Batal</Button
              >
            </div>
          </form>
        </DialogContent></Dialog
      >
      <div class="flex flex-col gap-4">
        <Card
          class="gap-0 rounded-xl py-0 overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <CardContent class="p-3">
            <div class="flex flex-wrap items-center gap-3">
              <div class="relative min-w-0 basis-full sm:basis-auto sm:flex-1">
                <Search
                  class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
                />
                <Input
                  v-model="search"
                  class="pl-9"
                  placeholder="Cari produk, SKU, barcode, brand"
                  @keyup.enter="applyFilters"
                />
              </div>
              <select
                aria-label="Filter kategori produk"
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
              <label class="flex items-center gap-2 text-sm whitespace-nowrap"
                ><input
                  v-model="discontinued"
                  type="checkbox"
                  class="rounded border"
                />
                Discontinue</label
              >
              <Button variant="outline" @click="applyFilters">Filter</Button>
            </div>
          </CardContent>
        </Card>

        <Card
          class="gap-0 rounded-xl py-0 overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <div class="flex items-center justify-between border-b px-4 py-3">
            <h2 class="text-sm font-semibold">Katalog Produk</h2>
            <span class="text-xs text-zinc-500"
              >{{ products.total ?? products.data.length }} produk pada
              filter</span
            >
          </div>
          <CardContent class="px-0 pb-0">
            <div
              class="overflow-x-auto"
              role="region"
              aria-label="Katalog produk, geser untuk melihat kolom lainnya"
              tabindex="0"
            >
              <table
                class="w-full min-w-[950px] text-left text-sm [&_th]:px-4 [&_td]:px-4"
                role="table"
              >
                <thead
                  class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-950"
                >
                  <tr>
                    <th class="px-4 py-3">Produk</th>
                    <th>Kategori</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Stok</th>
                    <th>Status</th>
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
                      <div class="flex items-center gap-3">
                        <div
                          class="size-12 shrink-0 overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-800"
                        >
                          <img
                            v-if="product.image_url"
                            :src="product.image_url"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                            loading="lazy"
                          />
                          <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-zinc-300 dark:text-zinc-700"
                            aria-hidden="true"
                          >
                            <ImageOff class="size-5" />
                          </div>
                        </div>
                        <div>
                          <Link
                            class="font-semibold text-zinc-950 hover:text-indigo-600 dark:text-white"
                            :href="show(product.id).url"
                            >{{ product.name }}</Link
                          >
                          <div class="text-xs text-zinc-500">
                            {{ product.sku }} · {{ product.barcode || "-" }}
                          </div>
                          <div
                            v-if="product.brand"
                            class="text-[11px] text-zinc-500"
                          >
                            {{ product.brand
                            }}<span v-if="product.variant">
                              · {{ product.variant }}</span
                            >
                          </div>
                        </div>
                      </div>
                    </td>
                    <td>{{ product.category?.name || "-" }}</td>
                    <td
                      class="whitespace-nowrap text-right font-medium tabular-nums"
                    >
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
                    <td>
                      <span
                        v-if="product.is_discontinued"
                        class="inline-flex items-center rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100"
                      >
                        Discontinue
                      </span>
                      <span
                        v-else-if="product.is_active"
                        class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200"
                      >
                        Aktif
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                      >
                        Non-aktif
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
                          >Hapus</Button
                        >
                      </div>
                    </td>
                  </tr>
                  <tr v-if="products.data.length === 0">
                    <td
                      colspan="6"
                      class="px-4 py-10 text-center text-zinc-500"
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
      <WorkspacePagination
        :pagination="products"
        label="Halaman katalog produk"
      />
    </PageContainer>
  </AppLayout>
</template>
