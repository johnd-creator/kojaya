<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { approve, index, store } from "@/routes/cooperative/payments";

defineProps<{ payments: any; members: any[]; invoices: any[]; filters: any }>();

const form = useForm({
  cooperative_member_id: "",
  cooperative_dues_invoice_id: "",
  amount: 0,
  payment_method: "CASH",
  paid_at: new Date().toISOString().slice(0, 10),
  reference_no: "",
  notes: "",
  status: "PENDING",
});

const submit = () =>
  form.post(store().url, {
    preserveScroll: true,
    onSuccess: () => form.reset("amount", "reference_no", "notes"),
  });
</script>

<template>
  <Head title="Pembayaran Koperasi" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Pembayaran', href: index().url },
    ]"
  >
    <div class="grid w-full gap-6 p-6 lg:grid-cols-[360px_1fr]">
      <form
        class="rounded-lg border bg-white p-4 dark:bg-zinc-900"
        @submit.prevent="submit"
      >
        <h1 class="text-xl font-semibold">Catat Pembayaran</h1>
        <div class="mt-4 space-y-3">
          <select
            v-model="form.cooperative_member_id"
            required
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option value="">Pilih anggota</option>
            <option
              v-for="member in members"
              :key="member.id"
              :value="member.id"
            >
              {{ member.member_no }} - {{ member.name }}
            </option>
          </select>
          <select
            v-model="form.cooperative_dues_invoice_id"
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option value="">Tanpa tagihan spesifik</option>
            <option
              v-for="invoice in invoices"
              :key="invoice.id"
              :value="invoice.id"
            >
              {{ invoice.member?.name }} - {{ invoice.period }} -
              {{ invoice.contribution_type?.name }}
            </option>
          </select>
          <Input
            v-model="form.amount"
            type="number"
            min="1"
            placeholder="Nominal"
            required
          />
          <select
            v-model="form.payment_method"
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option>CASH</option>
            <option>TRANSFER</option>
            <option>QRIS</option>
          </select>
          <Input v-model="form.paid_at" type="date" required />
          <Input v-model="form.reference_no" placeholder="Referensi" />
          <select
            v-model="form.status"
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option>PENDING</option>
            <option>APPROVED</option>
          </select>
          <Button class="w-full" type="submit" :disabled="form.processing"
            >Simpan Pembayaran</Button
          >
        </div>
      </form>
      <div class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
        <table class="w-full text-left text-sm">
          <thead
            class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"
          >
            <tr>
              <th class="px-4 py-3">Anggota</th>
              <th>Tanggal</th>
              <th>Metode</th>
              <th>Status</th>
              <th class="text-right">Nominal</th>
              <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="payment in payments.data" :key="payment.id">
              <td class="px-4 py-3">{{ payment.member?.name }}</td>
              <td>{{ formatDate(payment.paid_at) }}</td>
              <td>{{ payment.payment_method }}</td>
              <td>{{ payment.status }}</td>
              <td class="text-right">{{ formatCurrency(payment.amount) }}</td>
              <td class="px-4 py-3 text-right">
                <Button
                  v-if="payment.status !== 'APPROVED'"
                  size="sm"
                  variant="outline"
                  @click="router.post(approve(payment.id).url)"
                  >Approve</Button
                >
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
