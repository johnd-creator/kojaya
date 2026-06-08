<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { formatCurrency, formatDate, formatDateTime } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import { approve, disburse, index, pay, reject } from "@/routes/cooperative/loans";

const props = defineProps<{ loan: any; approvalLogs: any[] }>();

const approveForm = useForm({ notes: "" });
const rejectForm = useForm({ rejection_reason: "" });
const disburseForm = useForm({ reference_no: props.loan.reference_no ?? "" });
const paymentForm = useForm({
  amount: Number(props.loan.installment_amount ?? 0),
  payment_method: "CASH",
  paid_at: new Date().toISOString().slice(0, 10),
  reference_no: "",
  notes: "",
});

const submitApprove = () => approveForm.post(approve(props.loan.id).url);
const submitReject = () => rejectForm.post(reject(props.loan.id).url);
const submitDisburse = () => disburseForm.post(disburse(props.loan.id).url);
const submitPayment = () => paymentForm.post(pay(props.loan.id).url);
</script>

<template>
  <Head :title="`Pinjaman ${loan.member?.name}`" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Pinjaman', href: index().url },
      { title: loan.member?.name ?? 'Detail', href: '#' },
    ]"
  >
    <PageContainer variant="detail" class="max-w-none">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">{{ loan.member?.name }}</h1>
          <p class="mt-1 text-sm text-zinc-500">
            {{ loan.member?.member_no }} • {{ loan.loan_type?.name }}
          </p>
        </div>
        <StatusBadge :status="loan.status" />
      </div>

      <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Pokok</div>
          <div class="mt-1 text-lg font-semibold">{{ formatCurrency(loan.principal_amount) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Total Pinjaman</div>
          <div class="mt-1 text-lg font-semibold">{{ formatCurrency(loan.total_amount) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Sisa Outstanding</div>
          <div class="mt-1 text-lg font-semibold">{{ formatCurrency(loan.outstanding_amount) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Angsuran / Bulan</div>
          <div class="mt-1 text-lg font-semibold">{{ formatCurrency(loan.installment_amount) }}</div>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="space-y-6">
          <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Informasi Pinjaman</h2>
            <dl class="mt-4 grid gap-4 text-sm md:grid-cols-2">
              <div><dt class="text-zinc-500">Tanggal Pengajuan</dt><dd>{{ formatDate(loan.applied_at) }}</dd></div>
              <div><dt class="text-zinc-500">Jatuh Tempo Pertama</dt><dd>{{ formatDate(loan.first_due_date) }}</dd></div>
              <div><dt class="text-zinc-500">Bunga</dt><dd>{{ loan.interest_rate }}% / bulan</dd></div>
              <div><dt class="text-zinc-500">Biaya Admin</dt><dd>{{ formatCurrency(loan.admin_fee) }}</dd></div>
              <div><dt class="text-zinc-500">Denda Telat</dt><dd>{{ formatCurrency(loan.late_fee_per_day) }} / hari</dd></div>
              <div><dt class="text-zinc-500">Tenor</dt><dd>{{ loan.term_months }} bulan</dd></div>
              <div class="md:col-span-2"><dt class="text-zinc-500">Tujuan</dt><dd>{{ loan.purpose || "-" }}</dd></div>
              <div class="md:col-span-2"><dt class="text-zinc-500">Catatan</dt><dd>{{ loan.notes || "-" }}</dd></div>
            </dl>
          </div>

          <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Jadwal Angsuran</h2>
            <div class="mt-4 overflow-x-auto">
              <table class="w-full text-left text-sm">
                <thead class="border-b text-xs uppercase text-zinc-500">
                  <tr>
                    <th class="py-2">#</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th class="text-right">Tagihan</th>
                    <th class="text-right">Terbayar</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="installment in loan.installments" :key="installment.id">
                    <td class="py-2">{{ installment.installment_no }}</td>
                    <td>{{ formatDate(installment.due_date) }}</td>
                    <td><StatusBadge :status="installment.status" /></td>
                    <td class="text-right">{{ formatCurrency(installment.amount_due) }}</td>
                    <td class="text-right">{{ formatCurrency(installment.amount_paid) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Riwayat Approval</h2>
            <div class="mt-4 space-y-3">
              <div
                v-for="log in approvalLogs"
                :key="log.id"
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/90 p-3 text-sm dark:border-zinc-800/70 dark:bg-zinc-950/40"
              >
                <div class="flex items-center justify-between gap-4">
                  <div class="font-medium">
                    {{ log.from_status || "NEW" }} → {{ log.to_status }}
                  </div>
                  <div class="text-xs text-zinc-500">{{ formatDateTime(log.created_at) }}</div>
                </div>
                <div class="mt-1 text-zinc-600 dark:text-zinc-300">{{ log.note || "-" }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div
            v-if="loan.status === 'APPLIED'"
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900"
          >
            <h2 class="text-lg font-semibold">Approval</h2>
            <form class="mt-4 space-y-3" @submit.prevent="submitApprove">
              <Input v-model="approveForm.notes" placeholder="Catatan approval" />
              <Button v-can="'approve_cooperative_loan'" type="submit" :disabled="approveForm.processing" class="w-full">Setujui</Button>
            </form>
            <form class="mt-4 space-y-3" @submit.prevent="submitReject">
              <textarea v-model="rejectForm.rejection_reason" required class="min-h-24 w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950" placeholder="Alasan penolakan" />
              <Button v-can="'approve_cooperative_loan'" type="submit" variant="destructive" :disabled="rejectForm.processing" class="w-full">Tolak</Button>
            </form>
          </div>

          <div
            v-if="loan.status === 'APPROVED'"
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900"
          >
            <h2 class="text-lg font-semibold">Pencairan</h2>
            <form class="mt-4 space-y-3" @submit.prevent="submitDisburse">
              <Input v-model="disburseForm.reference_no" placeholder="Referensi pencairan" />
              <Button v-can="'manage_cooperative_loan'" type="submit" :disabled="disburseForm.processing" class="w-full">Cairkan Pinjaman</Button>
            </form>
          </div>

          <div
            v-if="loan.status === 'ACTIVE'"
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900"
          >
            <h2 class="text-lg font-semibold">Catat Angsuran</h2>
            <form class="mt-4 space-y-3" @submit.prevent="submitPayment">
              <Input v-model="paymentForm.amount" type="number" min="1" step="1000" required />
              <select v-model="paymentForm.payment_method" class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950">
                <option>CASH</option>
                <option>TRANSFER</option>
                <option>QRIS</option>
              </select>
              <Input v-model="paymentForm.paid_at" type="date" required />
              <Input v-model="paymentForm.reference_no" placeholder="Referensi pembayaran" />
              <textarea v-model="paymentForm.notes" class="min-h-20 w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950" placeholder="Catatan pembayaran" />
              <Button v-can="'manage_cooperative_loan'" type="submit" :disabled="paymentForm.processing" class="w-full">Simpan Pembayaran</Button>
            </form>
          </div>

          <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Pembayaran Tercatat</h2>
            <div class="mt-4 space-y-3">
              <div
                v-for="payment in loan.payments"
                :key="payment.id"
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/90 p-3 text-sm dark:border-zinc-800/70 dark:bg-zinc-950/40"
              >
                <div class="flex items-center justify-between gap-4">
                  <div class="font-medium">{{ formatCurrency(payment.amount) }}</div>
                  <div class="text-xs text-zinc-500">{{ formatDate(payment.paid_at) }}</div>
                </div>
                <div class="mt-1 text-zinc-600 dark:text-zinc-300">
                  {{ payment.payment_method }} • {{ payment.reference_no || "Tanpa referensi" }}
                </div>
              </div>
              <div v-if="loan.payments.length === 0" class="text-sm text-zinc-500">Belum ada pembayaran angsuran.</div>
            </div>
          </div>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
