<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { Search, ShoppingCart, Sparkles, Store } from "lucide-vue-next";
import { computed, ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index } from "@/routes/cooperative/pos";
import { store as storeTransaction } from "@/routes/cooperative/pos/transactions";

const props = defineProps<{
  products: any[];
  categories: any[];
  members: any[];
}>();
const search = ref("");
const selectedCategory = ref("");
const cart = ref<any[]>([]);
const paymentMethod = ref("CASH");
const memberId = ref("");

const products = computed(() =>
  props.products.filter((product) => {
    const matchesSearch =
      !search.value ||
      product.name.toLowerCase().includes(search.value.toLowerCase()) ||
      product.sku.toLowerCase().includes(search.value.toLowerCase()) ||
      (product.barcode ?? "").includes(search.value);
    const matchesCategory =
      !selectedCategory.value ||
      String(product.pos_category_id ?? "") === selectedCategory.value;
    return matchesSearch && matchesCategory;
  }),
);

const subtotal = computed(() =>
  cart.value.reduce(
    (sum, item) => sum + Number(item.sale_price) * item.quantity,
    0,
  ),
);

const form = useForm({
  client_reference: "",
  cooperative_member_id: "",
  payment_method: "CASH",
  items: [] as any[],
});

const addProduct = (product: any) => {
  const existing = cart.value.find((item) => item.id === product.id);
  if (existing) {
    existing.quantity += 1;
    return;
  }
  cart.value.push({ ...product, quantity: 1 });
};

const submit = () => {
  form.client_reference = `WEB-${Date.now()}`;
  form.cooperative_member_id = memberId.value;
  form.payment_method = paymentMethod.value;
  form.items = cart.value.map((item) => ({
    pos_product_id: item.id,
    quantity: item.quantity,
  }));
  form.post(storeTransaction().url, {
    preserveScroll: true,
    onSuccess: () => {
      cart.value = [];
      memberId.value = "";
    },
  });
};
</script>

<template>
  <Head title="POS Toko Koperasi" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'POS Toko', href: index().url },
    ]"
  >
    <PageContainer class="max-w-none">
      <section
        class="relative overflow-hidden rounded-2xl border border-emerald-200/60 bg-gradient-to-br from-white via-emerald-50/60 to-sky-50/40 p-5 shadow-sm shadow-emerald-950/5 sm:p-6 dark:border-emerald-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      >
        <div
          class="pointer-events-none absolute -right-16 -top-16 size-48 rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"
          aria-hidden="true"
        />
        <div
          class="relative flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
        >
          <div class="space-y-1.5">
            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-800/60"
            >
              <Sparkles class="size-3" />
              Kasir
            </span>
            <h1
              class="text-2xl font-bold tracking-tight text-zinc-950 sm:text-3xl dark:text-white"
            >
              POS Toko Koperasi
            </h1>
          </div>
          <div class="flex flex-col gap-2 sm:flex-row">
            <div class="relative">
              <Search
                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
              />
              <Input
                v-model="search"
                class="pl-9"
                placeholder="Scan barcode atau cari produk"
              />
            </div>
            <select
              v-model="selectedCategory"
              class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300 dark:border-zinc-800 dark:bg-zinc-950"
            >
              <option value="">Semua kategori</option>
              <option
                v-for="category in categories"
                :key="category.id"
                :value="String(category.id)"
              >
                {{ category.name }}
              </option>
            </select>
          </div>
        </div>
      </section>

      <div class="grid gap-4 lg:grid-cols-[1fr_360px]">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <button
            v-for="product in products"
            :key="product.id"
            type="button"
            class="rounded-xl border border-zinc-200/80 bg-white/95 p-4 text-left shadow-sm shadow-zinc-950/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300/80 hover:shadow-md hover:shadow-emerald-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
            @click="addProduct(product)"
          >
            <div class="font-semibold text-zinc-950 dark:text-white">
              {{ product.name }}
            </div>
            <div class="mt-1 text-xs text-zinc-500">
              {{ product.sku }} · Stok {{ product.stock }}
            </div>
            <div
              class="mt-3 text-lg font-bold text-emerald-700 dark:text-emerald-300"
            >
              {{ formatCurrency(product.sale_price) }}
            </div>
          </button>
          <div
            v-if="products.length === 0"
            class="col-span-full rounded-xl border border-zinc-200/80 bg-white/95 p-8 text-center text-sm text-zinc-500 dark:border-zinc-800/80 dark:bg-zinc-900"
          >
            <Store
              class="mx-auto mb-2 size-8 text-zinc-300 dark:text-zinc-700"
            />
            Produk tidak ditemukan.
          </div>
        </div>

        <aside
          class="flex flex-col rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
        >
          <div
            class="flex items-center gap-2 border-b border-zinc-200/70 px-4 py-3 dark:border-zinc-800/70"
          >
            <ShoppingCart class="h-5 w-5 text-emerald-600" />
            <h2 class="font-semibold text-zinc-950 dark:text-white">
              Keranjang
            </h2>
          </div>
          <div
            class="flex-1 divide-y divide-zinc-200/70 overflow-auto dark:divide-zinc-800/70"
          >
            <div
              v-for="item in cart"
              :key="item.id"
              class="flex items-center justify-between gap-3 px-4 py-3"
            >
              <div class="min-w-0 flex-1">
                <div class="truncate font-medium text-zinc-950 dark:text-white">
                  {{ item.name }}
                </div>
                <div class="text-xs text-zinc-500">
                  {{ formatCurrency(item.sale_price) }}
                </div>
              </div>
              <Input
                v-model="item.quantity"
                type="number"
                min="1"
                class="w-16"
              />
            </div>
            <div
              v-if="cart.length === 0"
              class="flex flex-col items-center py-12 text-sm text-zinc-500"
            >
              <ShoppingCart
                class="mb-2 size-8 text-zinc-300 dark:text-zinc-700"
              />
              Belum ada item.
            </div>
          </div>
          <div
            class="space-y-3 border-t border-zinc-200/70 p-4 dark:border-zinc-800/70"
          >
            <select
              v-model="paymentMethod"
              class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300 dark:border-zinc-800 dark:bg-zinc-950"
            >
              <option>CASH</option>
              <option>TRANSFER</option>
              <option>QRIS</option>
              <option>MEMBER_CREDIT</option>
            </select>
            <select
              v-model="memberId"
              class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300 dark:border-zinc-800 dark:bg-zinc-950"
            >
              <option value="">
                {{
                  paymentMethod === "MEMBER_CREDIT"
                    ? "Pilih anggota aktif"
                    : "Pilih anggota untuk poin POS"
                }}
              </option>
              <option
                v-for="member in members"
                :key="member.id"
                :value="member.id"
              >
                {{ member.member_no }} - {{ member.name }}
              </option>
            </select>
            <div class="flex justify-between text-lg font-bold">
              <span>Total</span
              ><span class="text-emerald-700 dark:text-emerald-300">{{
                formatCurrency(subtotal)
              }}</span>
            </div>
            <Button
              class="w-full shadow-sm"
              :disabled="
                cart.length === 0 ||
                form.processing ||
                (paymentMethod === 'MEMBER_CREDIT' && !memberId)
              "
              @click="submit"
              >{{ form.processing ? "Memproses…" : "Bayar" }}</Button
            >
          </div>
        </aside>
      </div>
    </PageContainer>
  </AppLayout>
</template>
