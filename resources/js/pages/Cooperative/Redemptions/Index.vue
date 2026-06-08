<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDateTime, formatNumber } from "@/lib/formatters";

defineProps<{
  redemptions: {
    data: Array<{
      id: string;
      status: string;
      quantity: number;
      points_used: number;
      redeemed_at: string;
      reward: { name: string };
      member: { name: string; member_no: string };
    }>;
  };
}>();

const columns = [
  { header: "Member", key: "member.name", slot: "member" },
  { header: "Reward", key: "reward.name" },
  { header: "Qty", key: "quantity", align: "center" as const },
  {
    header: "Points",
    key: "points_used",
    format: formatNumber,
    align: "right" as const,
  },
  { header: "Status", key: "status", slot: "status" },
  {
    header: "Redeemed At",
    key: "redeemed_at",
    format: formatDateTime,
    align: "right" as const,
  },
  { header: "", key: "actions", slot: "actions", align: "right" as const },
];
</script>

<template>
  <Head title="Reward Redemptions" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Cooperative', href: '/cooperative/members' },
      { title: 'Redemptions', href: '/cooperative/redemptions' },
    ]"
  >
    <PageContainer class="max-w-none">
      <div>
        <h1 class="text-2xl font-semibold">Reward Redemptions</h1>
        <p class="text-sm text-muted-foreground">
          Pantau penukaran reward yang diajukan anggota.
        </p>
      </div>

      <DataTable :columns="columns" :data="redemptions" :searchable="false">
        <template #member="{ row }">
          <div class="font-medium">{{ row.member.name }}</div>
          <div class="text-xs text-muted-foreground">
            {{ row.member.member_no }}
          </div>
        </template>

        <template #status="{ value }">
          <StatusBadge :status="value" />
        </template>

        <template #actions="{ row }">
          <Button as-child size="sm" variant="outline">
            <Link :href="`/cooperative/redemptions/${row.id}`">Detail</Link>
          </Button>
        </template>
      </DataTable>
    </PageContainer>
  </AppLayout>
</template>
