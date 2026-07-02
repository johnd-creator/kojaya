<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { Ban, Check, Wallet } from "lucide-vue-next";
import { ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { process as processWithdrawal } from "@/routes/cooperative/savings/withdrawals";

defineProps<{ withdrawals: any }>();
const activeId = ref<number | null>(null);

const form = useForm({ decision: "APPROVE", rejection_reason: "" });

const process = (id: number, decision: "APPROVE" | "REJECT") => {
  activeId.value = id;
  form.decision = decision;
  form.post(processWithdrawal(id).url, {
    preserveScroll: true,
    onFinish: () => {
      activeId.value = null;
    },
  });
};
</script>

<template>
  <Head title="Penarikan Simpanan" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Iuran & Simpanan', href: '#' },
      { title: 'Penarikan Simpanan', href: '#' },
    ]"
  >
    <PageContainer class="max-w-none">
      <div>
        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
          Antrian Penarikan Simpanan
        </h1>
        <p class="text-sm text-zinc-500">
          Setujui atau tolak pengajuan penarikan simpanan sukarela anggota.
        </p>
      </div>

      <div
        v-if="withdrawals.data?.length === 0"
        class="rounded-md border border-dashed border-zinc-200 bg-white/60 p-8 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/40"
      >
        <Wallet class="mx-auto mb-2 size-8 text-zinc-300" />
        Tidak ada pengajuan penarikan.
      </div>

      <div v-else class="grid gap-3">
        <Card v-for="item in withdrawals.data" :key="item.id" class="overflow-hidden">
          <CardHeader class="flex flex-row items-start justify-between gap-2">
            <div>
              <CardTitle class="text-base">
                {{ item.member?.name || "-" }}
              </CardTitle>
              <p class="text-xs text-zinc-500">
                Diajukan {{ new Date(item.created_at).toLocaleString("id-ID") }}
              </p>
            </div>
            <span
              class="rounded-full px-2 py-0.5 text-xs font-semibold"
              :class="
                item.status === 'PENDING'
                  ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'
                  : item.status === 'PROCESSED'
                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
                    : item.status === 'REJECTED'
                      ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200'
                      : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200'
              "
            >
              {{ item.status }}
            </span>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="grid grid-cols-2 gap-3 text-xs sm:grid-cols-4">
              <div>
                <div class="text-zinc-500">Jumlah</div>
                <div class="font-semibold">{{ formatCurrency(item.amount) }}</div>
              </div>
              <div>
                <div class="text-zinc-500">Bank Tujuan</div>
                <div class="font-medium">{{ item.destination_bank || "-" }}</div>
              </div>
              <div>
                <div class="text-zinc-500">No. Rekening</div>
                <div class="font-medium">{{ item.destination_account_no || "-" }}</div>
              </div>
              <div>
                <div class="text-zinc-500">Nama Rekening</div>
                <div class="font-medium">{{ item.destination_account_name || "-" }}</div>
              </div>
            </div>

            <div v-if="item.reason" class="text-sm">
              <div class="text-zinc-500">Alasan anggota:</div>
              <p>{{ item.reason }}</p>
            </div>

            <div v-if="item.status === 'PENDING'" class="space-y-2">
              <Input
                v-model="form.rejection_reason"
                placeholder="Alasan penolakan (wajib jika menolak)"
              />
              <div class="flex flex-wrap gap-2">
                <Button
                  class="flex-1"
                  :disabled="form.processing && activeId === item.id"
                  @click="process(item.id, 'APPROVE')"
                >
                  <Check class="mr-1.5 size-4" /> Setujui & Proses
                </Button>
                <Button
                  class="flex-1"
                  variant="destructive"
                  :disabled="
                    (form.processing && activeId === item.id) ||
                    form.rejection_reason.length < 3
                  "
                  @click="process(item.id, 'REJECT')"
                >
                  <Ban class="mr-1.5 size-4" /> Tolak
                </Button>
              </div>
            </div>

            <div v-else class="text-xs text-zinc-500">
              <span v-if="item.approved_at">
                Diputuskan
                {{ new Date(item.approved_at).toLocaleString("id-ID") }}
              </span>
              <p v-if="item.rejection_reason" class="mt-1">
                Catatan: {{ item.rejection_reason }}
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </PageContainer>
  </AppLayout>
</template>
