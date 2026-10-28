<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { Plus } from "lucide-vue-next";
import { ref } from "vue";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import InputError from "@/components/InputError.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDate, formatNumber } from "@/lib/formatters";
import { store } from "@/routes/cooperative/rewards";
import type { PaginationMeta } from "@/types/pagination";

const props = defineProps<{
  rewards: PaginationMeta & {
    data: Array<{
      id: string;
      name: string;
      category: string;
      description: string | null;
      points_required: number;
      stock: number | null;
      valid_until: string | null;
      is_active: boolean;
    }>;
  };
}>();

const createOpen = ref(false);

const form = useForm({
  name: "",
  category: "BARANG",
  description: "",
  points_required: 100,
  stock: 10,
  valid_until: "",
  image_url: "",
  is_active: true,
});

const columns = [
  { header: "Reward", key: "name", slot: "reward" },
  { header: "Kategori", key: "category", slot: "category" },
  {
    header: "Poin",
    key: "points_required",
    format: formatNumber,
    align: "right" as const,
  },
  { header: "Stok", key: "stock", slot: "stock", align: "right" as const },
  {
    header: "Valid Sampai",
    key: "valid_until",
    format: formatDate,
    align: "right" as const,
  },
  { header: "Status", key: "is_active", slot: "status" },
];

const submit = (): void => {
  form.post(store().url, {
    preserveScroll: true,
    onSuccess: () => {
      createOpen.value = false;
      form.reset();
    },
  });
};
</script>

<template>
  <Head title="Rewards" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '/cooperative/members' },
      { title: 'Rewards', href: '/cooperative/rewards' },
    ]"
  >
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Katalog Reward"
        description="Kelola reward, kebutuhan poin, masa berlaku, dan ketersediaan stok."
        ><Button size="sm" @click="createOpen = true"
          ><Plus class="size-4" /> Tambah Reward</Button
        ></WorkspaceHeader
      >

      <WorkspaceSummary
        :items="[
          { label: 'Reward di Halaman Ini', value: rewards.data.length },
          {
            label: 'Aktif di Halaman Ini',
            value: rewards.data.filter((reward) => reward.is_active).length,
            tone: 'emerald',
          },
          {
            label: 'Stok Habis di Halaman Ini',
            value: rewards.data.filter((reward) => reward.stock === 0).length,
          },
        ]"
      />
      <Dialog v-model:open="createOpen"
        ><DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-lg"
          ><DialogHeader
            ><DialogTitle>Tambah Reward</DialogTitle
            ><DialogDescription
              >Isi rincian reward dan kebutuhan poin anggota.</DialogDescription
            ></DialogHeader
          >
          <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
              <Label for="reward-name">Nama Reward</Label>
              <Input id="reward-name" v-model="form.name" />
            </div>
            <div class="space-y-2">
              <Label for="reward-category">Kategori</Label>
              <select
                id="reward-category"
                v-model="form.category"
                class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-violet-300 dark:border-zinc-800 dark:bg-zinc-950"
              >
                <option value="BARANG">BARANG</option>
                <option value="DISKON">DISKON</option>
                <option value="LAYANAN">LAYANAN</option>
              </select>
            </div>
            <div class="space-y-2">
              <Label for="reward-points">Poin Dibutuhkan</Label>
              <Input
                id="reward-points"
                v-model.number="form.points_required"
                type="number"
                min="1"
              />
            </div>
            <div class="space-y-2">
              <Label for="reward-stock">Stok</Label>
              <Input
                id="reward-stock"
                v-model.number="form.stock"
                type="number"
                min="0"
              />
            </div>
            <div class="space-y-2">
              <Label for="reward-until">Valid Sampai</Label>
              <Input id="reward-until" v-model="form.valid_until" type="date" />
            </div>
            <InputError
              v-for="(message, field) in form.errors"
              :key="field"
              :message="message"
              role="alert"
            />
            <Button
              class="w-full shadow-sm"
              type="submit"
              :disabled="form.processing"
              >Simpan Reward</Button
            >
          </form></DialogContent
        ></Dialog
      >
      <Card
        class="gap-0 rounded-xl py-0 overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <div class="flex items-center justify-between border-b px-4 py-3">
          <h2 class="text-sm font-semibold">Daftar Reward</h2>
          <span class="text-xs text-zinc-500"
            >{{ rewards.data.length }} ditampilkan</span
          >
        </div>
        <CardContent class="px-0 pb-0">
          <DataTable
            :columns="
              columns.map((column) => ({
                ...column,
                headerClass: 'max-w-none! whitespace-nowrap',
                class:
                  column.slot === 'reward'
                    ? 'max-w-none! min-w-[240px] whitespace-normal'
                    : 'max-w-none! whitespace-nowrap',
              }))
            "
            compact
            table-class="min-w-[850px] table-auto"
            empty-message="Belum ada reward. Gunakan Tambah Reward untuk mengisi katalog."
            :data="props.rewards"
            :searchable="false"
          >
            <template #reward="{ row }">
              <div class="font-semibold text-zinc-950 dark:text-white">
                {{ row.name }}
              </div>
              <div class="text-xs text-zinc-500">
                {{ row.description || "-" }}
              </div>
            </template>
            <template #category="{ value }">
              <Badge
                variant="outline"
                class="bg-violet-100 px-2 py-0.5 text-xs text-violet-700 dark:bg-violet-500/20 dark:text-violet-300"
                >{{ value }}</Badge
              >
            </template>
            <template #stock="{ value }">
              {{ value ?? "Tanpa batas" }}
            </template>
            <template #status="{ value }">
              <StatusBadge
                :status="value ? 'ACTIVE' : 'INACTIVE'"
                :variant="value ? 'success' : 'secondary'"
              />
            </template>
          </DataTable>
        </CardContent>
      </Card>
    </PageContainer>
  </AppLayout>
</template>
