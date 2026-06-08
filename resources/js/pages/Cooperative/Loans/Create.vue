<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import { calculator, index, store } from "@/routes/cooperative/loans";

const props = defineProps<{ members: any[]; loanTypes: any[] }>();

const form = useForm({
  cooperative_member_id: "",
  loan_type_id: "",
  principal_amount: 1000000,
  term_months: 12,
  first_due_date: new Date(new Date().setMonth(new Date().getMonth() + 1))
    .toISOString()
    .slice(0, 10),
  purpose: "",
  notes: "",
});

const selectedLoanType = () =>
  props.loanTypes.find((loanType) => String(loanType.id) === String(form.loan_type_id));

const submit = () => form.post(store().url);
</script>

<template>
  <Head title="Pengajuan Pinjaman" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Pinjaman', href: index().url },
      { title: 'Pengajuan Baru', href: '#' },
    ]"
  >
    <PageContainer variant="form" class="max-w-none">
      <form class="grid gap-6" @submit.prevent="submit">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Pengajuan Pinjaman</h1>
            <p class="mt-1 text-sm text-zinc-500">
              Buat pengajuan pinjaman anggota dan hitung simulasi angsuran sebelum approval.
            </p>
          </div>
          <Link :href="calculator().url">
            <Button type="button" variant="outline">Buka Kalkulator</Button>
          </Link>
        </div>

        <div class="grid gap-4 rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900 md:grid-cols-2">
          <label class="space-y-1">
            <span class="text-sm">Anggota</span>
            <select v-model="form.cooperative_member_id" required class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950">
              <option value="">Pilih anggota</option>
              <option v-for="member in members" :key="member.id" :value="member.id">
                {{ member.member_no }} - {{ member.name }}
              </option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm">Tipe Pinjaman</span>
            <select v-model="form.loan_type_id" required class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950">
              <option value="">Pilih tipe</option>
              <option v-for="loanType in loanTypes" :key="loanType.id" :value="loanType.id">
                {{ loanType.name }}
              </option>
            </select>
          </label>

          <label class="space-y-1">
            <span class="text-sm">Nominal Pokok</span>
            <Input v-model="form.principal_amount" type="number" min="1" step="1000" required />
          </label>

          <label class="space-y-1">
            <span class="text-sm">Tenor (bulan)</span>
            <Input v-model="form.term_months" type="number" min="1" required />
          </label>

          <label class="space-y-1">
            <span class="text-sm">Jatuh Tempo Pertama</span>
            <Input v-model="form.first_due_date" type="date" required />
          </label>

          <div class="rounded-md border bg-zinc-50 p-4 text-sm dark:bg-zinc-950/40">
            <div class="font-medium">Ringkasan Tipe</div>
            <div class="mt-2 space-y-1 text-zinc-600 dark:text-zinc-300" v-if="selectedLoanType()">
              <div>Bunga: {{ selectedLoanType()?.interest_rate }}% / bulan</div>
              <div>Biaya admin: {{ formatCurrency(selectedLoanType()?.admin_fee) }}</div>
              <div>Denda telat: {{ formatCurrency(selectedLoanType()?.late_fee_per_day) }} / hari</div>
            </div>
            <div v-else class="mt-2 text-zinc-500">Pilih tipe pinjaman untuk melihat parameternya.</div>
          </div>

          <label class="space-y-1 md:col-span-2">
            <span class="text-sm">Tujuan Pinjaman</span>
            <textarea v-model="form.purpose" class="min-h-24 w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950" />
          </label>

          <label class="space-y-1 md:col-span-2">
            <span class="text-sm">Catatan Internal</span>
            <textarea v-model="form.notes" class="min-h-20 w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950" />
          </label>
        </div>

        <div class="flex justify-end gap-2">
          <Link :href="index().url">
            <Button type="button" variant="outline">Batal</Button>
          </Link>
          <Button type="submit" :disabled="form.processing">Simpan Pengajuan</Button>
        </div>
      </form>
    </PageContainer>
  </AppLayout>
</template>
