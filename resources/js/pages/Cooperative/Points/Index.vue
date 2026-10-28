<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatNumber } from "@/lib/formatters";
import type { PaginationMeta } from "@/types/pagination";

defineProps<{
  members: PaginationMeta & {
    data: Array<{
      id: string | number;
      member_no: string;
      name: string;
      status: string;
      organization_id?: string | null;
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
  filters?: {
    search?: string;
    status?: string;
    organization_id?: string;
  };
}>();

const columns = [
  { header: "Anggota", key: "name", slot: "member" },
  { header: "Tier", key: "member_tier", slot: "tier" },
  {
    header: "Saldo Poin",
    key: "total_points",
    slot: "balance",
    align: "right" as const,
  },
  {
    header: "Diperoleh",
    key: "points_earned",
    format: formatNumber,
    align: "right" as const,
  },
  {
    header: "Ditukar",
    key: "points_redeemed",
    format: formatNumber,
    align: "right" as const,
  },
  { header: "Target Tier", key: "next_tier", slot: "nextTier" },
];
</script>

<template>
  <Head title="Member Points" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '/cooperative/members' },
      { title: 'Points', href: '/cooperative/points' },
    ]"
  >
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Poin Anggota"
        description="Pantau saldo, perolehan, penukaran poin, dan perkembangan tier anggota."
      ></WorkspaceHeader>

      <WorkspaceSummary
        :items="[
          { label: 'Anggota Aktif', value: formatNumber(stats.active_members) },
          {
            label: 'Saldo Poin',
            value: formatNumber(stats.total_balance),
            tone: 'emerald',
          },
          { label: 'Anggota Ditampilkan', value: members.data.length },
        ]"
      />
      <Card
        data-testid="points-table-card"
        class="gap-0 rounded-xl py-0 overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <div class="flex items-center justify-between gap-2 border-b px-4 py-3">
          <h2 class="text-sm font-semibold">Daftar Poin Anggota</h2>
        </div>
        <CardContent class="px-0 pb-0">
          <DataTable
            :columns="
              columns.map((column) => ({
                ...column,
                headerClass: 'max-w-none! whitespace-nowrap',
                class: 'max-w-none! whitespace-nowrap',
              }))
            "
            :data="members"
            :searchable="false"
            compact
            table-class="min-w-[800px] table-auto"
            empty-message="Belum ada anggota pada daftar poin ini."
          >
            <template #member="{ row }">
              <div class="font-semibold text-zinc-950 dark:text-white">
                {{ row.name }}
              </div>
              <div class="text-xs text-zinc-500">{{ row.member_no }}</div>
            </template>
            <template #tier="{ value }">
              <Badge
                variant="outline"
                class="bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/20 dark:text-amber-300"
                >{{ value }}</Badge
              >
            </template>
            <template #balance="{ value }">
              <span
                class="text-sm font-semibold tabular-nums text-amber-700 dark:text-amber-300"
                >{{ formatNumber(value) }}</span
              >
            </template>
            <template #nextTier="{ row }">
              <span v-if="row.next_tier" class="text-xs text-zinc-500"
                >{{ row.next_tier }} ({{
                  formatNumber(row.points_to_next_tier)
                }}
                pts lagi)</span
              >
              <Badge
                v-else
                variant="outline"
                class="bg-violet-100 px-2 py-0.5 text-xs text-violet-700 dark:bg-violet-500/20 dark:text-violet-300"
                >Tier Tertinggi</Badge
              >
            </template>
          </DataTable>
        </CardContent>
      </Card>
    </PageContainer>
  </AppLayout>
</template>
