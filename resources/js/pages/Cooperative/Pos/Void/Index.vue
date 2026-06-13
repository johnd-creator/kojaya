<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { Ban, Check, ShieldAlert } from "lucide-vue-next";
import { ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { process as processVoid } from "@/routes/cooperative/pos/void-requests";

const props = defineProps<{ requests: any }>();
const activeId = ref<number | null>(null);

const form = useForm({ decision: "APPROVE", rejection_reason: "" });

const process = (id: number, decision: "APPROVE" | "REJECT") => {
  activeId.value = id;
  form.decision = decision;
  form.post(processVoid(id).url, {
    preserveScroll: true,
    onFinish: () => {
      activeId.value = null;
    },
  });
};
</script>

<template>
  <Head title="Antrian Void" />
  <AppLayout
    :breadcrumbs="[
      { title: 'POS Toko', href: '#' },
      { title: 'Antrian Void', href: '#' },
    ]"
  >
    <PageContainer class="max-w-none">
      <div>
        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
          Antrian Persetujuan Void
        </h1>
        <p class="text-sm text-zinc-500">
          Setujui atau tolak pengajuan void transaksi.
        </p>
      </div>

      <div v-if="requests.data?.length === 0" class="rounded-md border border-dashed border-zinc-200 bg-white/60 p-8 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/40">
        <ShieldAlert class="mx-auto mb-2 size-8 text-zinc-300" />
        Tidak ada pengajuan.
      </div>

      <div v-else class="grid gap-3">
        <Card v-for="request in requests.data" :key="request.id" class="overflow-hidden">
          <CardHeader class="flex flex-row items-start justify-between gap-2">
            <div>
              <CardTitle class="text-base">
                {{ request.transaction?.transaction_no }}
              </CardTitle>
              <p class="text-xs text-zinc-500">
                Diajukan oleh {{ request.requester?.name }} ·
                {{ new Date(request.created_at).toLocaleString("id-ID") }}
              </p>
            </div>
            <span
              class="rounded-full px-2 py-0.5 text-xs font-semibold"
              :class="
                request.status === 'PENDING'
                  ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'
                  : request.status === 'APPROVED'
                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
                    : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200'
              "
            >
              {{ request.status }}
            </span>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="text-sm">
              <div class="text-zinc-500">Alasan:</div>
              <p>{{ request.reason }}</p>
            </div>
            <div class="grid grid-cols-3 gap-3 text-xs">
              <div>
                <div class="text-zinc-500">Kasir</div>
                <div class="font-medium">
                  {{ request.transaction?.cashier?.name || "-" }}
                </div>
              </div>
              <div>
                <div class="text-zinc-500">Total</div>
                <div class="font-semibold">
                  {{ formatCurrency(request.transaction?.total_amount) }}
                </div>
              </div>
              <div>
                <div class="text-zinc-500">Member</div>
                <div class="font-medium">
                  {{ request.transaction?.member?.name || "-" }}
                </div>
              </div>
            </div>

            <div v-if="request.status === 'PENDING'" class="space-y-2">
              <Input
                v-model="form.rejection_reason"
                placeholder="Alasan penolakan (wajib jika menolak)"
              />
              <div class="flex flex-wrap gap-2">
                <Button
                  class="flex-1"
                  :disabled="form.processing && activeId === request.id"
                  @click="process(request.id, 'APPROVE')"
                >
                  <Check class="mr-1.5 size-4" /> Setujui
                </Button>
                <Button
                  class="flex-1"
                  variant="destructive"
                  :disabled="
                    (form.processing && activeId === request.id) ||
                    form.rejection_reason.length < 3
                  "
                  @click="process(request.id, 'REJECT')"
                >
                  <Ban class="mr-1.5 size-4" /> Tolak
                </Button>
              </div>
            </div>

            <div v-else class="text-xs text-zinc-500">
              <span v-if="request.approver">
                Diputuskan oleh {{ request.approver.name }} ·
                {{ request.approved_at ? new Date(request.approved_at).toLocaleString("id-ID") : "" }}
              </span>
              <p v-if="request.rejection_reason" class="mt-1">
                Catatan: {{ request.rejection_reason }}
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </PageContainer>
  </AppLayout>
</template>
