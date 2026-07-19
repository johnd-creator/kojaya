<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { ImageOff, Plus, Search, ShoppingCart, Sparkles, Store, Trash2 } from "lucide-vue-next";
import { computed, ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index } from "@/routes/cooperative/pos";
import { store as storeTransaction } from "@/routes/cooperative/pos/transactions";

type Product = {
  id: number;
  name: string;
  sku: string;
  barcode: string | null;
  sale_price: number | string;
  stock: number;
  image_url: string | null;
  pos_category_id: number | null;
};

type CartItem = Product & { quantity: number };

const props = defineProps<{
  products: Product[];
  categories: any[];
  members: any[];
}>();

const search = ref("");
const selectedCategory = ref("");
const cart = ref<CartItem[]>([]);
const paymentMethod = ref("CASH");
const memberId = ref("");
const discountAmount = ref(0);
const cashReceived = ref(0);
const storeDelegateId = ref("");
const storeDelegatePin = ref("");
const stockError = ref<string | null>(null);

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

const discountSafe = computed(() =>
  Math.max(0, Math.min(Number(discountAmount.value) || 0, subtotal.value)),
);

const total = computed(() => Math.max(subtotal.value - discountSafe.value, 0));

const cashChange = computed(() => {
  if (paymentMethod.value !== "CASH") {
    return 0;
  }
  return Math.max(Number(cashReceived.value) - total.value, 0);
});

const canSubmit = computed(() => {
  if (cart.value.length === 0 || form.processing) {
    return false;
  }
  if (paymentMethod.value === "MEMBER_CREDIT" && !memberId.value) {
    return false;
  }
  if (paymentMethod.value === "CASH" && Number(cashReceived.value) < total.value) {
    return false;
  }
  return cart.value.every((item) => item.quantity <= item.stock && item.quantity > 0);
});

const cartStockIssues = computed(() =>
  cart.value.filter((item) => item.quantity > item.stock || item.quantity <= 0),
);

const form = useForm({
  client_reference: "",
  cooperative_member_id: "" as string | number,
  payment_method: "CASH",
  discount_amount: 0,
  cash_received: 0 as number | null,
  store_delegate_id: "" as string | number,
  store_delegate_pin: "",
  items: [] as { pos_product_id: number; quantity: number }[],
});

const addProduct = (product: Product) => {
  const existing = cart.value.find((item) => item.id === product.id);
  if (existing) {
    if (existing.quantity >= product.stock) {
      stockError.value = `Stok ${product.name} tinggal ${product.stock}.`;
      return;
    }
    existing.quantity += 1;
    stockError.value = null;
    return;
  }
  if (product.stock <= 0) {
    stockError.value = `${product.name} habis.`;
    return;
  }
  cart.value.push({ ...product, quantity: 1 });
  stockError.value = null;
};

const removeItem = (productId: number) => {
  cart.value = cart.value.filter((item) => item.id !== productId);
};

const updateQuantity = (item: CartItem) => {
  if (item.quantity > item.stock) {
    item.quantity = item.stock;
    stockError.value = `Stok ${item.name} maksimal ${item.stock}.`;
  } else if (item.quantity <= 0) {
    item.quantity = 1;
  } else {
    stockError.value = null;
  }
};

const quickCash = (value: number) => {
  cashReceived.value = value;
};

const submit = () => {
  if (!canSubmit.value) {
    return;
  }

  form.client_reference = `WEB-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  form.cooperative_member_id = memberId.value || "";
  form.payment_method = paymentMethod.value;
  form.discount_amount = discountSafe.value;
  form.cash_received = paymentMethod.value === "CASH" ? Number(cashReceived.value) : null;
  form.store_delegate_id = paymentMethod.value === "MEMBER_STORE_ACCOUNT" ? storeDelegateId.value : "";
  form.store_delegate_pin = paymentMethod.value === "MEMBER_STORE_ACCOUNT" ? storeDelegatePin.value : "";
  form.items = cart.value.map((item) => ({
    pos_product_id: item.id,
    quantity: item.quantity,
  }));
  form.post(storeTransaction().url, {
    preserveScroll: true,
    onSuccess: () => {
      cart.value = [];
      memberId.value = "";
      discountAmount.value = 0;
      cashReceived.value = 0;
      stockError.value = null;
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
            :disabled="product.stock <= 0"
            class="group flex flex-col overflow-hidden rounded-xl border border-zinc-200/80 bg-white/95 text-left shadow-sm shadow-zinc-950/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300/80 hover:shadow-md hover:shadow-emerald-950/5 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0 dark:border-zinc-800/80 dark:bg-zinc-900"
            @click="addProduct(product)"
          >
            <div
              class="relative aspect-[4/3] w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800"
            >
              <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                loading="lazy"
              />
              <div
                v-else
                class="flex h-full w-full items-center justify-center text-zinc-300 dark:text-zinc-700"
                aria-hidden="true"
              >
                <ImageOff class="size-10" />
              </div>
              <span
                v-if="product.stock <= 0"
                class="absolute right-2 top-2 rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold uppercase text-white"
              >
                Habis
              </span>
            </div>
            <div class="flex-1 p-3">
              <div class="line-clamp-2 font-semibold text-zinc-950 dark:text-white">
                {{ product.name }}
              </div>
              <div class="mt-1 text-[11px] text-zinc-500">
                {{ product.sku }} · Stok {{ product.stock }}
              </div>
              <div
                class="mt-2 text-base font-bold text-emerald-700 dark:text-emerald-300"
              >
                {{ formatCurrency(product.sale_price) }}
              </div>
            </div>
          </button>
          <div
            v-if="products.length === 0"
            class="col-span-full rounded-xl border border-dashed border-zinc-200/80 bg-white/60 p-8 text-center text-sm text-zinc-500 dark:border-zinc-800/80 dark:bg-zinc-900/40"
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
            <span
              v-if="cart.length > 0"
              class="ml-auto rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200"
            >
              {{ cart.length }} item
            </span>
          </div>
          <div
            v-if="stockError"
            class="border-b border-rose-200 bg-rose-50 px-4 py-2 text-xs font-medium text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300"
            role="alert"
          >
            {{ stockError }}
          </div>
          <div
            class="flex-1 divide-y divide-zinc-200/70 overflow-auto dark:divide-zinc-800/70"
          >
            <div
              v-for="item in cart"
              :key="item.id"
              class="flex items-center gap-3 px-4 py-3"
            >
              <div
                class="size-12 shrink-0 overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-800"
              >
                <img
                  v-if="item.image_url"
                  :src="item.image_url"
                  :alt="item.name"
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
              <div class="min-w-0 flex-1">
                <div class="truncate font-medium text-zinc-950 dark:text-white">
                  {{ item.name }}
                </div>
                <div class="text-xs text-zinc-500">
                  {{ formatCurrency(item.sale_price) }} · stok {{ item.stock }}
                </div>
              </div>
              <Input
                v-model.number="item.quantity"
                type="number"
                min="1"
                :max="item.stock"
                class="w-16"
                @input="updateQuantity(item)"
              />
              <Button
                variant="ghost"
                size="icon"
                type="button"
                class="text-zinc-500 hover:text-rose-600"
                @click="removeItem(item.id)"
              >
                <Trash2 class="size-4" />
                <span class="sr-only">Hapus</span>
              </Button>
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
              <option value="CASH">Tunai (CASH)</option>
              <option value="TRANSFER">Transfer Bank</option>
              <option value="QRIS">QRIS</option>
              <option value="MEMBER_CREDIT">Kredit Anggota</option>
              <option value="MEMBER_STORE_ACCOUNT">Saldo Toko Anggota</option>
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
            <div
              v-if="paymentMethod === 'MEMBER_STORE_ACCOUNT' && memberId"
              class="space-y-1.5"
            >
              <label class="text-xs font-medium text-zinc-600 dark:text-zinc-300">
                Delegate / Staff (opsional)
              </label>
              <Input
                v-model.number="storeDelegateId"
                type="number"
                placeholder="ID Delegate"
              />
              <Input
                v-model="storeDelegatePin"
                type="password"
                placeholder="PIN Delegate"
              />
              <p class="text-[11px] text-zinc-500">
                Kosongkan jika pembeli adalah pemilik akun.
              </p>
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-zinc-600 dark:text-zinc-300">
                Diskon (Rp)
              </label>
              <Input
                v-model.number="discountAmount"
                type="number"
                min="0"
                :max="subtotal"
                step="500"
                placeholder="0"
              />
              <p
                v-if="Number(discountAmount) > subtotal && subtotal > 0"
                class="text-[11px] text-rose-600"
              >
                Diskon melebihi subtotal, akan dipotong otomatis.
              </p>
            </div>
            <div
              v-if="paymentMethod === 'CASH'"
              class="space-y-1.5"
            >
              <label class="text-xs font-medium text-zinc-600 dark:text-zinc-300">
                Tunai diterima (Rp)
              </label>
              <Input
                v-model.number="cashReceived"
                type="number"
                min="0"
                step="500"
                placeholder="0"
              />
              <div class="flex flex-wrap gap-1.5">
                <Button
                  v-for="preset in [total, Math.ceil(total / 50000) * 50000, Math.ceil(total / 100000) * 100000].filter((v, i, a) => v > 0 && a.indexOf(v) === i)"
                  :key="preset"
                  type="button"
                  variant="outline"
                  size="sm"
                  class="h-7 px-2 text-[11px]"
                  @click="quickCash(preset)"
                >
                  {{ formatCurrency(preset) }}
                </Button>
              </div>
              <div
                v-if="Number(cashReceived) > 0"
                class="flex justify-between rounded-md bg-zinc-100 px-3 py-1.5 text-xs dark:bg-zinc-800"
              >
                <span class="text-zinc-500">Kembalian</span>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                  {{ formatCurrency(cashChange) }}
                </span>
              </div>
            </div>
            <div
              class="space-y-1.5 rounded-md bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-800/60"
            >
              <div class="flex justify-between text-zinc-500">
                <span>Subtotal</span>
                <span>{{ formatCurrency(subtotal) }}</span>
              </div>
              <div
                v-if="discountSafe > 0"
                class="flex justify-between text-rose-600"
              >
                <span>Diskon</span>
                <span>-{{ formatCurrency(discountSafe) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold">
                <span>Total</span>
                <span class="text-emerald-700 dark:text-emerald-300">{{
                  formatCurrency(total)
                }}</span>
              </div>
            </div>
            <p
              v-if="cartStockIssues.length > 0"
              class="text-[11px] font-medium text-rose-600"
              role="alert"
            >
              Periksa kuantitas item bertanda merah sebelum membayar.
            </p>
            <Button
              class="w-full shadow-sm"
              :disabled="!canSubmit"
              @click="submit"
            >
              <Plus v-if="!form.processing" class="mr-1.5 size-4" />
              {{
                form.processing
                  ? "Memproses…"
                  : `Bayar ${total > 0 ? formatCurrency(total) : ""}`
              }}
            </Button>
          </div>
        </aside>
      </div>
    </PageContainer>
  </AppLayout>
</template>
