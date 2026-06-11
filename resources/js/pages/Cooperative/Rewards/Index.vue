<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Gift, Plus, Sparkles } from "lucide-vue-next";
import { reactive } from "vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDate, formatNumber } from "@/lib/formatters";

const props = defineProps<{
  rewards: {
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

const form = reactive({
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
  router.post("/cooperative/rewards", form);
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
    <PageContainer class="max-w-none">
      <section
        class="relative overflow-hidden rounded-2xl border border-violet-200/60 bg-gradient-to-br from-white via-violet-50/60 to-amber-50/40 p-6 shadow-sm shadow-violet-950/5 sm:p-7 dark:border-violet-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      >
        <div
          class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-violet-300/20 blur-3xl dark:bg-violet-500/10"
          aria-hidden="true"
        />
        <div class="relative space-y-3">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800 ring-1 ring-inset ring-violet-200/70 dark:bg-violet-900/40 dark:text-violet-200 dark:ring-violet-800/60"
          >
            <Sparkles class="size-3.5" />
            Katalog Reward
          </span>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
          >
            Rewards
          </h1>
          <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Kelola katalog reward yang bisa ditukar anggota dengan poin loyalty.
          </p>
        </div>
      </section>

      <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <SectionHeader
            title="Katalog"
            :description="`${props.rewards.data.length} reward`"
            :icon="Gift"
            tone="violet"
          />
          <CardContent class="px-0 pb-0">
            <DataTable
              :columns="columns"
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
                {{ value ?? "Unlimited" }}
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

        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <SectionHeader title="Tambah Reward" :icon="Plus" tone="violet" />
          <CardContent class="space-y-4 p-5">
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
            <Button class="w-full shadow-sm" @click="submit"
              >Simpan Reward</Button
            >
          </CardContent>
        </Card>
      </div>
    </PageContainer>
  </AppLayout>
</template>
