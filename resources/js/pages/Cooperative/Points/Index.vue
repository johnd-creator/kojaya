<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { Gift, Trophy } from "lucide-vue-next";
import PageContainer from "@/components/PageContainer.vue";
import StatsCard from "@/components/StatsCard.vue";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import { formatNumber } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";

defineProps<{
  members: {
    data: Array<{
      id: number;
      member_no: string;
      name: string;
      status: string;
      total_points: number;
      points_earned: number;
      points_redeemed: number;
      member_tier: string;
      next_tier: string | null;
      points_to_next_tier: number;
    }>;
  };
  stats: {
    active_members: number;
    total_balance: number;
  };
}>();

const columns = [
  { header: "Member", key: "name", slot: "member" },
  { header: "Tier", key: "member_tier", slot: "tier" },
  {
    header: "Balance",
    key: "total_points",
    format: formatNumber,
    align: "right" as const,
    class: "font-semibold",
  },
  {
    header: "Earned",
    key: "points_earned",
    format: formatNumber,
    align: "right" as const,
  },
  {
    header: "Redeemed",
    key: "points_redeemed",
    format: formatNumber,
    align: "right" as const,
  },
  { header: "Next Tier", key: "next_tier", slot: "nextTier" },
];
</script>

<template>
  <Head title="Member Points" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Cooperative', href: '/cooperative/members' },
      { title: 'Points', href: '/cooperative/points' },
    ]"
  >
    <PageContainer class="max-w-none">
      <div class="flex items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold">Points & Loyalty</h1>
          <p class="text-sm text-muted-foreground">
            Ringkasan poin anggota koperasi dari transaksi dan penukaran reward.
          </p>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <StatsCard
          label="Active Members"
          :value="formatNumber(stats.active_members)"
          :icon="Trophy"
        />
        <StatsCard
          label="Total Balance"
          :value="formatNumber(stats.total_balance)"
          :icon="Gift"
        />
      </div>

      <DataTable :columns="columns" :data="members" :searchable="false">
        <template #member="{ row }">
          <div class="font-medium">{{ row.name }}</div>
          <div class="text-xs text-muted-foreground">{{ row.member_no }}</div>
        </template>

        <template #tier="{ value }">
          <span class="font-medium">{{ value }}</span>
        </template>

        <template #nextTier="{ row }">
          <span v-if="row.next_tier">
            {{ row.next_tier }} ({{
              formatNumber(row.points_to_next_tier)
            }}
            pts)
          </span>
          <span v-else>Highest tier</span>
        </template>
      </DataTable>
    </PageContainer>
  </AppLayout>
</template>
