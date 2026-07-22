<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { ArrowLeft, CheckCircle2, Wallet } from "lucide-vue-next";
import { computed } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";

const browserWindow = globalThis.window;

const props = defineProps<{
  member: any;
  outstanding_balance: number;
  available_credit: number;
}>();

const form = useForm({
  amount: 0,
  reference_no: "",
  notes: "",
  paid_at: new Date().toISOString().slice(0, 10),
});

const submit = () => {
  if (form.amount <= 0) {
    form.setError("amount", "Jumlah harus lebih dari 0.");
    return;
  }
  if (form.amount > props.outstanding_balance) {
    form.setError(
      "amount",
      `Pembayaran melebihi saldo terutang ${formatCurrency(props.outstanding_balance)}.`,
    );
    return;
  }
  form.post(browserWindow.location.pathname, { preserveScroll: true });
};

const totalAfter = computed(() =>
  Math.max(props.outstanding_balance - (Number(form.amount) || 0), 0),
);
</script>

<template>
  <Head title="Bayar Kredit Anggota" />
  <AppLayout
    :breadcrumbs="[
      { title: 'POS Toko', href: '#' },
      { title: 'Bayar Kredit', href: '#' },
    ]"
  >
    <PageContainer class="max-w-3xl">
      <div>
        <a
          href="#"
          @click.prevent="browserWindow.history.back()"
          class="inline-flex items-center text-sm text-indigo-600 hover:underline"
        >
          <ArrowLeft class="mr-1 size-4" /> Kembali
        </a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">
          Pembayaran Kredit
        </h1>
        <p class="text-sm text-zinc-500">
          {{ member.name }} · {{ member.member_no }}
        </p>
      </div>

      <div class="grid gap-3 md:grid-cols-3">
        <Card>
          <CardContent class="p-4">
            <div class="text-xs uppercase text-zinc-500">Saldo Terutang</div>
            <div class="text-2xl font-bold text-rose-600">
              {{ formatCurrency(outstanding_balance) }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-xs uppercase text-zinc-500">Limit Kredit</div>
            <div class="text-2xl font-bold">
              {{ formatCurrency(Number(member.credit_limit) || 0) }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="text-xs uppercase text-zinc-500">Sisa Limit</div>
            <div class="text-2xl font-bold text-emerald-600">
              {{ formatCurrency(available_credit) }}
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Catat Pembayaran</CardTitle>
        </CardHeader>
        <CardContent>
          <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-zinc-600" for="amount">
                Jumlah (Rp)
              </label>
              <Input
                id="amount"
                v-model.number="form.amount"
                type="number"
                min="1"
                step="1000"
                required
              />
              <p
                v-if="form.errors.amount"
                class="text-xs text-rose-600"
                role="alert"
              >
                {{ form.errors.amount }}
              </p>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1.5">
                <label class="text-xs font-medium text-zinc-600" for="paid_at">
                  Tanggal
                </label>
                <Input id="paid_at" v-model="form.paid_at" type="date" required />
              </div>
              <div class="space-y-1.5">
                <label
                  class="text-xs font-medium text-zinc-600"
                  for="reference_no"
                >
                  Referensi
                </label>
                <Input
                  id="reference_no"
                  v-model="form.reference_no"
                  placeholder="TRF-…"
                />
              </div>
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-zinc-600" for="notes">
                Catatan
              </label>
              <Input id="notes" v-model="form.notes" placeholder="Opsional" />
            </div>
            <div
              class="flex flex-col gap-2 rounded-md bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-800/40 sm:flex-row sm:items-center sm:justify-between"
            >
              <div>
                <div class="text-zinc-500">Sisa saldo setelah pembayaran</div>
                <div class="font-bold text-emerald-700">
                  {{ formatCurrency(totalAfter) }}
                </div>
              </div>
              <Button class="w-full sm:w-auto" type="submit">
                <Wallet class="mr-1.5 size-4" /> Catat Pembayaran
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <Card v-if="member.credit_payments?.length">
        <CardHeader>
          <CardTitle>Riwayat Pembayaran</CardTitle>
        </CardHeader>
        <CardContent>
          <ul class="divide-y text-sm">
            <li
              v-for="payment in member.credit_payments"
              :key="payment.id"
              class="flex items-center justify-between py-2"
            >
              <div>
                <div class="font-medium">
                  {{ new Date(payment.paid_at).toLocaleDateString("id-ID") }}
                </div>
                <div class="text-xs text-zinc-500">
                  {{ payment.reference_no || "Tunai" }} ·
                  {{ payment.receiver?.name || "—" }}
                </div>
                <div v-if="payment.notes" class="text-xs text-zinc-400">
                  {{ payment.notes }}
                </div>
              </div>
              <div class="text-right font-bold text-emerald-700">
                {{ formatCurrency(payment.amount) }}
              </div>
            </li>
          </ul>
        </CardContent>
      </Card>

      <p
        v-if="form.recentlySuccessful"
        class="flex items-center gap-2 text-sm text-emerald-700"
      >
        <CheckCircle2 class="size-4" /> Pembayaran tersimpan.
      </p>
    </PageContainer>
  </AppLayout>
</template>
