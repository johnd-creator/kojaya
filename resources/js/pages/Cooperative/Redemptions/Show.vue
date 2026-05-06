<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { formatDateTime, formatNumber } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";

const props = defineProps<{
  redemption: {
    id: string;
    status: string;
    quantity: number;
    points_used: number;
    delivery_address?: string | null;
    notes?: string | null;
    redeemed_at: string;
    processed_at?: string | null;
    reward: {
      id: string;
      name: string;
      category: string;
      points_required: number;
      stock?: number | null;
    };
    member: {
      id: number;
      name: string;
      member_no: string;
      email?: string | null;
      phone?: string | null;
    };
    point_transaction?: {
      id: string;
      points: number;
      balance_before: number;
      balance_after: number;
    } | null;
  };
}>();

const form = useForm({
  status: props.redemption.status,
  notes: props.redemption.notes ?? "",
});

function submitStatus(): void {
  form.put(`/cooperative/redemptions/${props.redemption.id}/status`, {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head :title="`Redemption ${redemption.reward.name}`" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Cooperative', href: '/cooperative/members' },
      { title: 'Redemptions', href: '/cooperative/redemptions' },
      { title: redemption.member.name, href: '#' },
    ]"
  >
    <PageContainer variant="detail">
      <div
        class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
      >
        <div>
          <h1 class="text-3xl font-bold tracking-tight">
            {{ redemption.reward.name }}
          </h1>
          <p class="mt-1 text-sm text-zinc-500">
            {{ redemption.member.name }} · {{ redemption.member.member_no }}
          </p>
        </div>
        <StatusBadge :status="redemption.status" />
      </div>

      <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Quantity</div>
          <div class="mt-1 text-lg font-semibold">
            {{ redemption.quantity }}
          </div>
        </div>
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Poin Dipakai</div>
          <div class="mt-1 text-lg font-semibold">
            {{ formatNumber(redemption.points_used) }}
          </div>
        </div>
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Poin per Item</div>
          <div class="mt-1 text-lg font-semibold">
            {{ formatNumber(redemption.reward.points_required) }}
          </div>
        </div>
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-xs text-zinc-500">Stok Reward</div>
          <div class="mt-1 text-lg font-semibold">
            {{ redemption.reward.stock ?? "Unlimited" }}
          </div>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="space-y-6">
          <div class="rounded-lg border bg-white p-6 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Informasi Redemption</h2>
            <dl class="mt-4 grid gap-4 text-sm md:grid-cols-2">
              <div>
                <dt class="text-zinc-500">Kategori Reward</dt>
                <dd>{{ redemption.reward.category }}</dd>
              </div>
              <div>
                <dt class="text-zinc-500">Tanggal Redeem</dt>
                <dd>{{ formatDateTime(redemption.redeemed_at) }}</dd>
              </div>
              <div>
                <dt class="text-zinc-500">Diproses Pada</dt>
                <dd>{{ formatDateTime(redemption.processed_at) }}</dd>
              </div>
              <div>
                <dt class="text-zinc-500">Transaksi Poin</dt>
                <dd>{{ redemption.point_transaction?.id ?? "-" }}</dd>
              </div>
              <div class="md:col-span-2">
                <dt class="text-zinc-500">Alamat Pengiriman</dt>
                <dd>{{ redemption.delivery_address || "-" }}</dd>
              </div>
              <div class="md:col-span-2">
                <dt class="text-zinc-500">Catatan</dt>
                <dd>{{ redemption.notes || "-" }}</dd>
              </div>
            </dl>
          </div>

          <div class="rounded-lg border bg-white p-6 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Data Anggota</h2>
            <dl class="mt-4 grid gap-4 text-sm md:grid-cols-2">
              <div>
                <dt class="text-zinc-500">Nama</dt>
                <dd>{{ redemption.member.name }}</dd>
              </div>
              <div>
                <dt class="text-zinc-500">Nomor Anggota</dt>
                <dd>{{ redemption.member.member_no }}</dd>
              </div>
              <div>
                <dt class="text-zinc-500">Email</dt>
                <dd>{{ redemption.member.email || "-" }}</dd>
              </div>
              <div>
                <dt class="text-zinc-500">Telepon</dt>
                <dd>{{ redemption.member.phone || "-" }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <div class="space-y-6">
          <div class="rounded-lg border bg-white p-6 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Update Status</h2>
            <form class="mt-4 space-y-4" @submit.prevent="submitStatus">
              <div class="space-y-2">
                <Label for="redemption-status">Status</Label>
                <select
                  id="redemption-status"
                  v-model="form.status"
                  class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
                  :disabled="redemption.status === 'CANCELLED'"
                >
                  <option value="PROCESSING">PROCESSING</option>
                  <option value="SHIPPED">SHIPPED</option>
                  <option value="DELIVERED">DELIVERED</option>
                  <option value="CANCELLED">CANCELLED</option>
                </select>
              </div>
              <div class="space-y-2">
                <Label for="redemption-notes">Catatan</Label>
                <textarea
                  id="redemption-notes"
                  v-model="form.notes"
                  class="min-h-28 w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950"
                  placeholder="Catatan proses atau alasan pembatalan"
                />
              </div>
              <Button
                type="submit"
                class="w-full"
                :disabled="form.processing || redemption.status === 'CANCELLED'"
              >
                Simpan Status
              </Button>
            </form>
          </div>

          <div class="rounded-lg border bg-white p-6 text-sm dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Dampak Poin</h2>
            <div class="mt-4 space-y-3">
              <div class="flex justify-between">
                <span class="text-zinc-500">Poin transaksi</span>
                <span>{{ redemption.point_transaction?.points ?? "-" }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-zinc-500">Saldo sebelum</span>
                <span>{{
                  redemption.point_transaction
                    ? formatNumber(redemption.point_transaction.balance_before)
                    : "-"
                }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-zinc-500">Saldo setelah</span>
                <span>{{
                  redemption.point_transaction
                    ? formatNumber(redemption.point_transaction.balance_after)
                    : "-"
                }}</span>
              </div>
            </div>
          </div>

          <Button as-child variant="outline" class="w-full">
            <Link href="/cooperative/redemptions"
              >Kembali ke daftar redemption</Link
            >
          </Button>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
