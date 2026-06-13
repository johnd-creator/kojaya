<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Minus, Plus, RefreshCcw } from "lucide-vue-next";
import { computed, ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { store as storeReturn } from "@/routes/cooperative/pos/returns";

const props = defineProps<{ transaction: any; items: any[] }>();

const quantities = ref<Record<number, number>>({});
const reason = ref("");

const toggle = (id: number, max: number) => {
  if (quantities.value[id]) {
    delete quantities.value[id];
    return;
  }
  quantities.value[id] = Math.min(1, max);
};

const adjust = (id: number, delta: number, max: number) => {
  const current = quantities.value[id] ?? 0;
  quantities.value[id] = Math.max(0, Math.min(max, current + delta));
};

const form = useForm({ reason: "", items: [] as any[] });

const totalReturn = computed(() =>
  props.items.reduce((sum, item) => {
    const q = quantities.value[item.id] ?? 0;
    return sum + q * item.unit_price;
  }, 0),
);

const selectedCount = computed(
  () => Object.values(quantities.value).filter((q) => q > 0).length,
);

const submit = () => {
  if (selectedCount.value === 0) {
    form.setError("items", "Pilih minimal satu item yang akan diretur.");
    return;
  }
  form.reason = reason.value;
  form.items = Object.entries(quantities.value)
    .filter(([_, q]) => q > 0)
    .map(([id, quantity]) => ({
      pos_transaction_item_id: Number(id),
      quantity,
    }));
  form.post(storeReturn(props.transaction.id).url, {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Buat Retur POS" />
  <AppLayout
    :breadcrumbs="[
      { title: 'POS Toko', href: '#' },
      { title: 'Riwayat', href: '#' },
      { title: transaction.transaction_no, href: '#' },
      { title: 'Retur', href: '#' },
    ]"
  >
    <PageContainer class="max-w-none">
      <div>
        <a
          href="#"
          @click.prevent="$inertia.visit('back')"
          class="inline-flex items-center text-sm text-indigo-600 hover:underline"
        >
          <ArrowLeft class="mr-1 size-4" /> Kembali
        </a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">
          Retur {{ transaction.transaction_no }}
        </h1>
        <p class="text-sm text-zinc-500">
          Pilih item yang akan diretur dan tentukan alasannya.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Item yang akan diretur</CardTitle>
        </CardHeader>
        <CardContent class="px-0">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead
                class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"
              >
                <tr>
                  <th class="px-4 py-3">Produk</th>
                  <th class="text-right">Qty Jual</th>
                  <th class="text-right">Sisa</th>
                  <th class="text-right">Harga</th>
                  <th class="px-4 py-3 text-right">Retur</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="item in items" :key="item.id">
                  <td class="px-4 py-3">{{ item.product }}</td>
                  <td class="text-right">{{ item.quantity }}</td>
                  <td class="text-right">
                    <span
                      :class="
                        item.max_returnable === 0
                          ? 'font-semibold text-rose-600'
                          : 'text-zinc-500'
                      "
                    >
                      {{ item.max_returnable }}
                    </span>
                  </td>
                  <td class="text-right">
                    {{ formatCurrency(item.unit_price) }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div
                      v-if="item.max_returnable > 0"
                      class="flex items-center justify-end gap-2"
                    >
                      <Button
                        size="icon"
                        variant="outline"
                        type="button"
                        class="size-7"
                        :disabled="(quantities[item.id] ?? 0) === 0"
                        @click="adjust(item.id, -1, item.max_returnable)"
                      >
                        <Minus class="size-3" />
                      </Button>
                      <Input
                        v-model.number="quantities[item.id]"
                        type="number"
                        class="h-7 w-16 text-right"
                        :min="0"
                        :max="item.max_returnable"
                      />
                      <Button
                        size="icon"
                        variant="outline"
                        type="button"
                        class="size-7"
                        :disabled="(quantities[item.id] ?? 0) >= item.max_returnable"
                        @click="adjust(item.id, 1, item.max_returnable)"
                      >
                        <Plus class="size-3" />
                      </Button>
                    </div>
                    <span v-else class="text-xs text-rose-600">Habis diretur</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="space-y-3 p-5">
          <label class="text-xs font-medium text-zinc-600" for="return-reason">
            Alasan retur
          </label>
          <Input
            id="return-reason"
            v-model="reason"
            placeholder="Mis. Produk rusak, pelanggan berubah pikiran"
            minlength="5"
            required
          />
          <p
            v-if="form.errors.reason"
            class="text-xs text-rose-600"
            role="alert"
          >
            {{ form.errors.reason }}
          </p>
          <p
            v-if="form.errors.items"
            class="text-xs text-rose-600"
            role="alert"
          >
            {{ form.errors.items }}
          </p>
          <div
            class="flex flex-col gap-2 rounded-md bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-800/40 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="font-semibold">
              Total retur: {{ formatCurrency(totalReturn) }}
            </div>
            <Button
              class="w-full sm:w-auto"
              variant="destructive"
              :disabled="form.processing || selectedCount === 0"
              @click="submit"
            >
              <RefreshCcw class="mr-1.5 size-4" /> Proses Retur
            </Button>
          </div>
        </CardContent>
      </Card>
    </PageContainer>
  </AppLayout>
</template>
