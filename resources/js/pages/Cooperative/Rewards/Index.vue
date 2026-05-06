<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { reactive } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { formatDate, formatNumber } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";

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
  { header: "Category", key: "category" },
  {
    header: "Points",
    key: "points_required",
    format: formatNumber,
    align: "right" as const,
  },
  { header: "Stock", key: "stock", slot: "stock", align: "right" as const },
  {
    header: "Valid Until",
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
      { title: 'Cooperative', href: '/cooperative/members' },
      { title: 'Rewards', href: '/cooperative/rewards' },
    ]"
  >
    <PageContainer>
      <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <DataTable :columns="columns" :data="props.rewards" :searchable="false">
          <template #reward="{ row }">
            <div class="font-medium">{{ row.name }}</div>
            <div class="text-xs text-muted-foreground">
              {{ row.description || "-" }}
            </div>
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

        <div class="rounded-lg border p-4">
          <h2 class="text-lg font-semibold">Tambah Reward</h2>
          <div class="mt-4 space-y-4">
            <div class="space-y-2">
              <Label for="reward-name">Nama Reward</Label>
              <Input id="reward-name" v-model="form.name" />
            </div>
            <div class="space-y-2">
              <Label for="reward-category">Kategori</Label>
              <select
                id="reward-category"
                v-model="form.category"
                class="h-10 w-full rounded-md border bg-background px-3 text-sm"
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
            <Button class="w-full" @click="submit">Simpan Reward</Button>
          </div>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
