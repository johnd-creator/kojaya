<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Ban, FileDown, Printer, RefreshCcw } from "lucide-vue-next";
import { computed, ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import {
  index as voidIndex,
  store as storeVoidRequest,
} from "@/routes/cooperative/pos/void-requests";
import { create as createReturn } from "@/routes/cooperative/pos/returns";
import {
  show as showTransaction,
} from "@/routes/cooperative/pos/transactions";

const browserWindow = globalThis.window;

const props = defineProps<{ transaction: any }>();

const voidForm = useForm({ reason: "" });
const showVoidForm = ref(false);
const showReturnHint = ref(false);

const submitVoid = () => {
  voidForm.post(storeVoidRequest(props.transaction.id).url, {
    preserveScroll: true,
    onSuccess: () => {
      voidForm.reset();
      showVoidForm.value = false;
    },
  });
};

const transactionStatus = computed(() => props.transaction.status);
const isVoided = computed(
  () => transactionStatus.value === "VOIDED" || transactionStatus.value === "VOID_PENDING",
);
</script>

<template>
  <Head :title="`Transaksi ${transaction.transaction_no}`" />
  <AppLayout
    :breadcrumbs="[
      { title: 'POS Toko', href: '#' },
      { title: 'Riwayat Transaksi', href: '#' },
      { title: transaction.transaction_no, href: '#' },
    ]"
  >
    <PageContainer class="max-w-none">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <Link
            href="#"
            class="inline-flex items-center text-sm text-indigo-600 hover:underline"
          >
            <ArrowLeft class="mr-1 size-4" /> Kembali
          </Link>
          <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">
            {{ transaction.transaction_no }}
          </h1>
          <p class="text-sm text-zinc-500">
            {{ new Date(transaction.sold_at).toLocaleString("id-ID") }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button
            variant="outline"
            @click="
              browserWindow.open(showTransaction(transaction.id, { query: {} }).url, '_blank')
            "
          >
            <Printer class="mr-1.5 size-4" /> Cetak
          </Button>
          <Button
            variant="outline"
            @click="
              browserWindow.open(
                showTransaction(transaction.id, { query: { autoprint: 1 } }).url,
                '_blank',
              )
            "
          >
            <FileDown class="mr-1.5 size-4" /> PDF/HTML
          </Button>
          <Link
            v-if="!isVoided"
            :href="createReturn(transaction.id).url" prefetch
          >
            <Button variant="outline">
              <RefreshCcw class="mr-1.5 size-4" /> Buat Retur
            </Button>
          </Link>
          <Button
            v-if="!isVoided && !showVoidForm"
            variant="destructive"
            @click="showVoidForm = true"
          >
            <Ban class="mr-1.5 size-4" /> Ajukan Void
          </Button>
        </div>
      </div>

      <div
        v-if="isVoided"
        class="rounded-md border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200"
        role="status"
      >
        <div class="font-semibold">Status: {{ transaction.status }}</div>
        <p v-if="transaction.void_reason" class="mt-1 text-xs">
          Alasan: {{ transaction.void_reason }}
        </p>
      </div>

      <Card
        v-if="showVoidForm && !isVoided"
        class="border-rose-200/80 bg-rose-50/40 shadow-sm"
      >
        <CardHeader>
          <CardTitle class="text-rose-700 dark:text-rose-300">
            Ajukan Void
          </CardTitle>
        </CardHeader>
        <CardContent>
          <form class="space-y-3" @submit.prevent="submitVoid">
            <label class="text-xs font-medium text-zinc-600" for="void-reason">
              Alasan void
            </label>
            <Input
              id="void-reason"
              v-model="voidForm.reason"
              placeholder="Mis. Input salah, pelanggan berubah pikiran"
              minlength="5"
              required
            />
            <p
              v-if="voidForm.errors.reason"
              class="text-xs text-rose-600"
              role="alert"
            >
              {{ voidForm.errors.reason }}
            </p>
            <div class="flex gap-2">
              <Button
                type="submit"
                variant="destructive"
                :disabled="voidForm.processing"
              >
                Kirim Pengajuan
              </Button>
              <Button
                type="button"
                variant="outline"
                @click="showVoidForm = false"
              >
                Batal
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardContent class="p-4">
            <div class="text-xs uppercase text-zinc-500">Kasir</div>
            <div class="font-semibold">
              {{ transaction.cashier?.name || "-" }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-xs uppercase text-zinc-500">Anggota</div>
            <div class="font-semibold">
              {{ transaction.member?.name || "-" }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-xs uppercase text-zinc-500">Item</div>
            <div class="font-semibold">{{ transaction.items?.length || 0 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-xs uppercase text-zinc-500">Total</div>
            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">
              {{ formatCurrency(transaction.total_amount) }}
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Item</CardTitle>
        </CardHeader>
        <CardContent class="px-0">
          <div class="overflow-x-auto">
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
                  <td class="text-right">
                    {{ formatCurrency(item.unit_price) }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    {{ formatCurrency(item.line_total) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Card v-if="transaction.payments?.length">
        <CardHeader>
          <CardTitle>Pembayaran</CardTitle>
        </CardHeader>
        <CardContent>
          <ul class="space-y-1.5 text-sm">
            <li
              v-for="payment in transaction.payments"
              :key="payment.id"
              class="flex items-center justify-between"
            >
              <span
                class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
              >
                {{ payment.payment_method }}
              </span>
              <span class="font-mono tabular-nums">
                {{ formatCurrency(payment.amount) }}
              </span>
            </li>
          </ul>
        </CardContent>
      </Card>

      <Card v-if="transaction.returns?.length">
        <CardHeader>
          <CardTitle>Riwayat Retur</CardTitle>
        </CardHeader>
        <CardContent>
          <ul class="space-y-2 text-sm">
            <li
              v-for="ret in transaction.returns"
              :key="ret.id"
              class="rounded-md bg-zinc-50 px-3 py-2 dark:bg-zinc-800/40"
            >
              <div class="flex items-center justify-between">
                <Link
                  class="font-semibold hover:underline"
                  :href="`#`"
                >
                  {{ ret.return_no }}
                </Link>
                <span class="font-mono tabular-nums text-rose-600">
                  -{{ formatCurrency(ret.total_amount) }}
                </span>
              </div>
              <div class="text-xs text-zinc-500">{{ ret.reason }}</div>
            </li>
          </ul>
        </CardContent>
      </Card>

      <div
        v-if="transaction.void_requests?.length"
        class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200"
      >
        <div class="font-semibold">Pengajuan Void:</div>
        <ul class="mt-1 space-y-1 text-xs">
          <li v-for="vr in transaction.void_requests" :key="vr.id">
            {{ vr.status }} • {{ vr.reason }} • {{ vr.requester?.name }}
          </li>
        </ul>
        <Link
          v-if="transaction.void_requests.some((r: any) => r.status === 'PENDING')"
          :href="voidIndex().url"
          class="mt-2 inline-flex items-center text-xs font-semibold text-amber-800 hover:underline"
        >
          Buka antrian persetujuan →
        </Link>
      </div>
    </PageContainer>
  </AppLayout>
</template>
