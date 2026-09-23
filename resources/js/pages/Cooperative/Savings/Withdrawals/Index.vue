<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
  AlertCircle,
  Ban,
  Check,
  CheckCircle2,
  Clock,
  Wallet,
  XCircle,
} from "lucide-vue-next";
import { ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDateTime } from "@/lib/formatters";
import { process as processWithdrawal } from "@/routes/cooperative/savings/withdrawals";

interface WithdrawalItem {
  id: number;
  cooperative_member_id: number;
  amount: number | string;
  status: "PENDING" | "PROCESSED" | "REJECTED" | string;
  destination_bank?: string | null;
  destination_account_no?: string | null;
  destination_account_name?: string | null;
  reason?: string | null;
  rejection_reason?: string | null;
  approved_at?: string | null;
  processed_at?: string | null;
  created_at?: string | null;
  available_voluntary_balance?: number;
  projected_remaining_balance?: number;
  member?: {
    id: number;
    name: string;
    member_no?: string;
    no_anggota?: string;
  } | null;
}

defineProps<{
  withdrawals: {
    data: WithdrawalItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
}>();

const approveDialogOpen = ref(false);
const rejectDialogOpen = ref(false);
const selectedWithdrawal = ref<WithdrawalItem | null>(null);

const approveForm = useForm({
  decision: "APPROVE",
});

const rejectForm = useForm({
  decision: "REJECT",
  rejection_reason: "",
});

const openApproveModal = (item: WithdrawalItem) => {
  selectedWithdrawal.value = item;
  approveDialogOpen.value = true;
};

const openRejectModal = (item: WithdrawalItem) => {
  selectedWithdrawal.value = item;
  rejectForm.rejection_reason = "";
  rejectForm.clearErrors();
  rejectDialogOpen.value = true;
};

const submitApprove = () => {
  if (!selectedWithdrawal.value) return;

  approveForm.post(processWithdrawal(selectedWithdrawal.value.id).url, {
    preserveScroll: true,
    onSuccess: () => {
      approveDialogOpen.value = false;
      selectedWithdrawal.value = null;
    },
  });
};

const submitReject = () => {
  if (!selectedWithdrawal.value) return;

  if (!rejectForm.rejection_reason.trim()) {
    rejectForm.setError("rejection_reason", "Alasan penolakan wajib diisi.");
    return;
  }

  rejectForm.post(processWithdrawal(selectedWithdrawal.value.id).url, {
    preserveScroll: true,
    onSuccess: () => {
      rejectDialogOpen.value = false;
      selectedWithdrawal.value = null;
      rejectForm.reset();
    },
  });
};

const getStatusBadge = (status: string) => {
  if (status === "PENDING") {
    return {
      label: "Menunggu",
      classes:
        "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 border-amber-200 dark:border-amber-800",
      icon: Clock,
    };
  }
  if (status === "PROCESSED") {
    return {
      label: "Disetujui",
      classes:
        "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200 border-emerald-200 dark:border-emerald-800",
      icon: CheckCircle2,
    };
  }
  if (status === "REJECTED") {
    return {
      label: "Ditolak",
      classes:
        "bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200 border-rose-200 dark:border-rose-800",
      icon: XCircle,
    };
  }
  return {
    label: status,
    classes:
      "bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 border-zinc-200 dark:border-zinc-700",
    icon: Clock,
  };
};
</script>

<template>
  <Head title="Antrian Penarikan Simpanan" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Iuran & Simpanan', href: '#' },
      { title: 'Penarikan Simpanan', href: '#' },
    ]"
  >
    <PageContainer class="max-w-none space-y-6">
      <!-- Header -->
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-zinc-950 sm:text-3xl dark:text-white">
            Antrian Penarikan Simpanan
          </h1>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">
            Verifikasi, setujui, atau tolak permohonan pencairan simpanan sukarela anggota koperasi.
          </p>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-if="!withdrawals.data || withdrawals.data.length === 0"
        class="rounded-xl border border-dashed border-zinc-200 bg-white/60 p-12 text-center text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/40"
      >
        <Wallet class="mx-auto mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
        <p class="text-base font-medium">Tidak ada pengajuan penarikan</p>
        <p class="mt-1 text-xs text-zinc-400">
          Semua pengajuan penarikan telah diproses atau belum ada pengajuan baru.
        </p>
      </div>

      <div v-else class="space-y-4">
        <!-- Desktop Table View (Hidden on mobile) -->
        <Card
          class="hidden overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 md:block dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" role="table">
              <thead
                class="border-b border-zinc-200 bg-zinc-50/75 text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:border-zinc-800 dark:bg-zinc-950/40 dark:text-zinc-400"
              >
                <tr>
                  <th scope="col" class="px-5 py-3.5">Anggota</th>
                  <th scope="col" class="px-5 py-3.5">Tanggal Pengajuan</th>
                  <th scope="col" class="px-5 py-3.5 text-right">Jumlah</th>
                  <th scope="col" class="px-5 py-3.5">Rekening Tujuan</th>
                  <th scope="col" class="px-5 py-3.5">Alasan</th>
                  <th scope="col" class="px-5 py-3.5 text-center">Status</th>
                  <th scope="col" class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70">
                <tr
                  v-for="item in withdrawals.data"
                  :key="item.id"
                  class="transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50"
                >
                  <!-- Anggota -->
                  <td class="px-5 py-4">
                    <div class="font-medium text-zinc-900 dark:text-white">
                      {{ item.member?.name || "-" }}
                    </div>
                    <div class="text-xs text-zinc-500">
                      {{
                        item.member?.member_no ||
                        item.member?.no_anggota ||
                        `ID #${item.cooperative_member_id}`
                      }}
                    </div>
                  </td>

                  <!-- Tanggal Pengajuan -->
                  <td class="px-5 py-4 text-xs text-zinc-600 dark:text-zinc-400">
                    {{ formatDateTime(item.created_at) }}
                  </td>

                  <!-- Jumlah -->
                  <td class="px-5 py-4 text-right font-semibold text-zinc-900 dark:text-white">
                    {{ formatCurrency(item.amount) }}
                  </td>

                  <!-- Rekening Tujuan -->
                  <td class="px-5 py-4 text-xs">
                    <div class="font-medium text-zinc-900 dark:text-white">
                      {{ item.destination_bank || "-" }}
                    </div>
                    <div class="text-zinc-500">
                      {{ item.destination_account_no || "-" }}
                    </div>
                    <div v-if="item.destination_account_name" class="text-zinc-400">
                      a.n. {{ item.destination_account_name }}
                    </div>
                  </td>

                  <!-- Alasan -->
                  <td class="max-w-[200px] px-5 py-4 text-xs text-zinc-600 dark:text-zinc-400">
                    <span class="line-clamp-2" :title="item.reason || ''">
                      {{ item.reason || "-" }}
                    </span>
                  </td>

                  <!-- Status -->
                  <td class="px-5 py-4 text-center">
                    <span
                      :class="[
                        'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                        getStatusBadge(item.status).classes,
                      ]"
                    >
                      <component :is="getStatusBadge(item.status).icon" class="size-3" />
                      {{ getStatusBadge(item.status).label }}
                    </span>
                    <div
                      v-if="item.status === 'REJECTED' && item.rejection_reason"
                      class="mt-1 max-w-[160px] truncate text-[11px] text-rose-600 dark:text-rose-400"
                      :title="item.rejection_reason"
                    >
                      Ket: {{ item.rejection_reason }}
                    </div>
                    <div
                      v-else-if="item.status === 'PROCESSED' && item.approved_at"
                      class="mt-1 text-[11px] text-zinc-400"
                    >
                      {{ formatDateTime(item.approved_at) }}
                    </div>
                  </td>

                  <!-- Aksi -->
                  <td class="px-5 py-4 text-right">
                    <div v-if="item.status === 'PENDING'" class="flex items-center justify-end gap-2">
                      <Button
                        size="sm"
                        class="h-8 gap-1 bg-emerald-600 text-white hover:bg-emerald-700"
                        @click="openApproveModal(item)"
                      >
                        <Check class="size-3.5" />
                        Setujui
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        class="h-8 gap-1 border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700 dark:border-rose-900/60 dark:text-rose-400 dark:hover:bg-rose-950/40"
                        @click="openRejectModal(item)"
                      >
                        <Ban class="size-3.5" />
                        Tolak
                      </Button>
                    </div>
                    <div v-else class="text-xs text-zinc-400">
                      -
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </Card>

        <!-- Mobile Card View (Visible on mobile only) -->
        <div class="grid gap-3 md:hidden">
          <Card
            v-for="item in withdrawals.data"
            :key="item.id"
            class="overflow-hidden border-zinc-200/80 bg-white shadow-sm dark:border-zinc-800/80 dark:bg-zinc-900"
          >
            <CardHeader class="flex flex-row items-start justify-between gap-2 pb-2">
              <div>
                <CardTitle class="text-base font-semibold">
                  {{ item.member?.name || "-" }}
                </CardTitle>
                <p class="text-xs text-zinc-500">
                  Diajukan {{ formatDateTime(item.created_at) }}
                </p>
              </div>
              <span
                :class="[
                  'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                  getStatusBadge(item.status).classes,
                ]"
              >
                <component :is="getStatusBadge(item.status).icon" class="size-3" />
                {{ getStatusBadge(item.status).label }}
              </span>
            </CardHeader>
            <CardContent class="space-y-3 text-xs">
              <div class="grid grid-cols-2 gap-2 rounded-lg bg-zinc-50 p-2.5 dark:bg-zinc-800/50">
                <div>
                  <span class="text-zinc-500">Jumlah</span>
                  <div class="font-bold text-zinc-900 dark:text-white">
                    {{ formatCurrency(item.amount) }}
                  </div>
                </div>
                <div>
                  <span class="text-zinc-500">Rekening Tujuan</span>
                  <div class="font-medium text-zinc-900 dark:text-white">
                    {{ item.destination_bank || "-" }} ({{ item.destination_account_no || "-" }})
                  </div>
                  <div v-if="item.destination_account_name" class="text-zinc-500">
                    a.n. {{ item.destination_account_name }}
                  </div>
                </div>
              </div>

              <div v-if="item.reason" class="text-zinc-600 dark:text-zinc-400">
                <span class="font-medium text-zinc-700 dark:text-zinc-300">Alasan:</span>
                {{ item.reason }}
              </div>

              <div
                v-if="item.status === 'REJECTED' && item.rejection_reason"
                class="text-rose-600 dark:text-rose-400"
              >
                <span class="font-medium">Alasan penolakan:</span> {{ item.rejection_reason }}
              </div>

              <div v-if="item.status === 'PENDING'" class="flex gap-2 pt-1">
                <Button
                  size="sm"
                  class="flex-1 gap-1.5 bg-emerald-600 text-white hover:bg-emerald-700"
                  @click="openApproveModal(item)"
                >
                  <Check class="size-3.5" />
                  Setujui
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  class="flex-1 gap-1.5 border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-rose-900/60 dark:text-rose-400"
                  @click="openRejectModal(item)"
                >
                  <Ban class="size-3.5" />
                  Tolak
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Pagination Controls -->
        <div
          v-if="withdrawals.links && withdrawals.links.length > 3"
          class="flex flex-wrap items-center justify-between gap-4 pt-2"
        >
          <p class="text-xs text-zinc-500">
            Menampilkan {{ withdrawals.from ?? 0 }} - {{ withdrawals.to ?? 0 }} dari total
            {{ withdrawals.total }} pengajuan
          </p>
          <div class="flex flex-wrap items-center gap-1 text-sm">
            <Link
              v-for="(link, i) in withdrawals.links"
              :key="i"
              :href="link.url || '#'"
              :class="[
                'rounded-md border px-3 py-1 text-xs transition',
                link.active
                  ? 'border-emerald-600 bg-emerald-600 font-medium text-white shadow-sm'
                  : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800',
                !link.url && 'pointer-events-none opacity-40',
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>

      <!-- Approve Confirmation Dialog -->
      <Dialog :open="approveDialogOpen" @update:open="approveDialogOpen = $event">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2 text-zinc-900 dark:text-white">
              <CheckCircle2 class="size-5 text-emerald-600 dark:text-emerald-400" />
              Konfirmasi Persetujuan Penarikan
            </DialogTitle>
            <DialogDescription>
              Pastikan rincian pengajuan penarikan simpanan sukarela berikut telah sesuai sebelum
              diproses.
            </DialogDescription>
          </DialogHeader>

          <div v-if="selectedWithdrawal" class="space-y-3 py-2 text-sm">
            <div
              class="space-y-2 rounded-lg border border-zinc-200 bg-zinc-50/70 p-3.5 dark:border-zinc-800 dark:bg-zinc-950/40"
            >
              <div class="flex justify-between">
                <span class="text-zinc-500">Anggota</span>
                <span class="font-semibold text-zinc-900 dark:text-white">
                  {{ selectedWithdrawal.member?.name || "-" }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-zinc-500">Jumlah Penarikan</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                  {{ formatCurrency(selectedWithdrawal.amount) }}
                </span>
              </div>
              <div class="border-t border-zinc-200/70 pt-2 dark:border-zinc-800/70">
                <div class="flex justify-between">
                  <span class="text-zinc-500">Bank Tujuan</span>
                  <span class="font-medium text-zinc-900 dark:text-white">
                    {{ selectedWithdrawal.destination_bank || "-" }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-zinc-500">No. Rekening</span>
                  <span class="font-medium text-zinc-900 dark:text-white">
                    {{ selectedWithdrawal.destination_account_no || "-" }}
                  </span>
                </div>
                <div
                  v-if="selectedWithdrawal.destination_account_name"
                  class="flex justify-between"
                >
                  <span class="text-zinc-500">Pemilik Rekening</span>
                  <span class="font-medium text-zinc-900 dark:text-white">
                    {{ selectedWithdrawal.destination_account_name }}
                  </span>
                </div>
              </div>

              <!-- Voluntary balance projections from server -->
              <div
                v-if="selectedWithdrawal.available_voluntary_balance !== undefined"
                class="space-y-1 border-t border-zinc-200/70 pt-2 text-xs dark:border-zinc-800/70"
              >
                <div class="flex justify-between">
                  <span class="text-zinc-500">Saldo Sukarela Tersedia</span>
                  <span class="font-medium text-zinc-700 dark:text-zinc-300">
                    {{ formatCurrency(selectedWithdrawal.available_voluntary_balance) }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-zinc-500">Proyeksi Sisa Saldo</span>
                  <span class="font-semibold text-zinc-900 dark:text-white">
                    {{ formatCurrency(selectedWithdrawal.projected_remaining_balance) }}
                  </span>
                </div>
              </div>
            </div>

            <div v-if="selectedWithdrawal.reason" class="text-xs text-zinc-500">
              <span class="font-medium text-zinc-700 dark:text-zinc-300">Alasan Anggota:</span>
              <p class="mt-0.5 italic">"{{ selectedWithdrawal.reason }}"</p>
            </div>
          </div>

          <DialogFooter class="flex flex-row justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              :disabled="approveForm.processing"
              @click="approveDialogOpen = false"
            >
              Batal
            </Button>
            <Button
              type="button"
              class="bg-emerald-600 text-white hover:bg-emerald-700"
              :disabled="approveForm.processing"
              @click="submitApprove"
            >
              {{ approveForm.processing ? "Memproses..." : "Setujui & Proses" }}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Reject Dialog -->
      <Dialog :open="rejectDialogOpen" @update:open="rejectDialogOpen = $event">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2 text-rose-600 dark:text-rose-400">
              <AlertCircle class="size-5" />
              Tolak Pengajuan Penarikan
            </DialogTitle>
            <DialogDescription>
              Pengajuan ini akan ditolak dan tidak akan memotong saldo simpanan anggota.
            </DialogDescription>
          </DialogHeader>

          <div v-if="selectedWithdrawal" class="space-y-4 py-2 text-sm">
            <div
              class="space-y-1 rounded-lg border border-zinc-200 bg-zinc-50/70 p-3 text-xs dark:border-zinc-800 dark:bg-zinc-950/40"
            >
              <div class="flex justify-between">
                <span class="text-zinc-500">Anggota</span>
                <span class="font-semibold text-zinc-900 dark:text-white">
                  {{ selectedWithdrawal.member?.name || "-" }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-zinc-500">Jumlah Penarikan</span>
                <span class="font-bold text-zinc-900 dark:text-white">
                  {{ formatCurrency(selectedWithdrawal.amount) }}
                </span>
              </div>
            </div>

            <div class="space-y-1.5">
              <Label for="rejection-reason" class="text-xs font-semibold">
                Alasan Penolakan <span class="text-rose-500">*</span>
              </Label>
              <Textarea
                id="rejection-reason"
                v-model="rejectForm.rejection_reason"
                rows="3"
                placeholder="Tuliskan alasan penolakan secara jelas (misal: Saldo tidak mencukupi, rekening tidak valid)..."
                :class="rejectForm.errors.rejection_reason ? 'border-rose-500' : ''"
              />
              <p v-if="rejectForm.errors.rejection_reason" class="text-xs text-rose-500">
                {{ rejectForm.errors.rejection_reason }}
              </p>
            </div>
          </div>

          <DialogFooter class="flex flex-row justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              :disabled="rejectForm.processing"
              @click="rejectDialogOpen = false"
            >
              Batal
            </Button>
            <Button
              type="button"
              variant="destructive"
              :disabled="rejectForm.processing || !rejectForm.rejection_reason.trim()"
              @click="submitReject"
            >
              {{ rejectForm.processing ? "Memproses..." : "Tolak Pengajuan" }}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </PageContainer>
  </AppLayout>
</template>
