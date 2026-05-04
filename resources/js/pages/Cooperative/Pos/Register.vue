<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { Search, ShoppingCart } from "lucide-vue-next";
import { computed, ref } from "vue";
import { index } from "@/routes/cooperative/pos";
import { store as storeTransaction } from "@/routes/cooperative/pos/transactions";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";

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
    <div
      class="grid h-full min-h-[calc(100vh-5rem)] gap-4 p-4 lg:grid-cols-[1fr_380px]"
    >
      <div class="flex flex-col gap-4">
        <div
          class="flex flex-col gap-3 rounded-lg border bg-white p-4 dark:bg-zinc-900 md:flex-row"
        >
          <div class="relative flex-1">
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
            class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
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
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <button
            v-for="product in products"
            :key="product.id"
            type="button"
            class="rounded-lg border bg-white p-4 text-left hover:border-indigo-400 dark:bg-zinc-900"
            @click="addProduct(product)"
          >
            <div class="font-medium">{{ product.name }}</div>
            <div class="mt-1 text-xs text-zinc-500">
              {{ product.sku }} · Stok {{ product.stock }}
            </div>
            <div class="mt-3 text-lg font-semibold">
              {{ formatCurrency(product.sale_price) }}
            </div>
          </button>
        </div>
      </div>
      <aside
        class="flex flex-col rounded-lg border bg-white p-4 dark:bg-zinc-900"
      >
        <div class="flex items-center gap-2 border-b pb-3">
          <ShoppingCart class="h-5 w-5" />
          <h1 class="text-lg font-semibold">Keranjang</h1>
        </div>
        <div class="flex-1 divide-y overflow-auto">
          <div
            v-for="item in cart"
            :key="item.id"
            class="flex items-center justify-between gap-3 py-3"
          >
            <div>
              <div class="font-medium">{{ item.name }}</div>
              <div class="text-xs text-zinc-500">
                {{ formatCurrency(item.sale_price) }}
              </div>
            </div>
            <Input v-model="item.quantity" type="number" min="1" class="w-20" />
          </div>
          <div
            v-if="cart.length === 0"
            class="py-10 text-center text-sm text-zinc-500"
          >
            Belum ada item.
          </div>
        </div>
        <div class="space-y-3 border-t pt-4">
          <select
            v-model="paymentMethod"
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option>CASH</option>
            <option>TRANSFER</option>
            <option>QRIS</option>
            <option>MEMBER_CREDIT</option>
          </select>
          <select
            v-model="memberId"
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
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
          <div class="flex justify-between text-lg font-semibold">
            <span>Total</span><span>{{ formatCurrency(subtotal) }}</span>
          </div>
          <Button
            class="w-full"
            :disabled="
              cart.length === 0 ||
              form.processing ||
              (paymentMethod === 'MEMBER_CREDIT' && !memberId)
            "
            @click="submit"
            >Bayar</Button
          >
        </div>
      </aside>
    </div>
  </AppLayout>
</template>
