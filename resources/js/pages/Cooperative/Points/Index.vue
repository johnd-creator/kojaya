<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { Gift, Sparkles, Trophy } from "lucide-vue-next";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import PageContainer from "@/components/PageContainer.vue";
import StatsCard from "@/components/StatsCard.vue";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatNumber } from "@/lib/formatters";

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
  { header: "Anggota", key: "name", slot: "member" },
  { header: "Tier", key: "member_tier", slot: "tier" },
  {
    header: "Balance",
    key: "total_points",
    slot: "balance",
    align: "right" as const,
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
    <PageContainer class="max-w-none">
      <section
        class="relative overflow-hidden rounded-2xl border border-amber-200/60 bg-gradient-to-br from-white via-amber-50/60 to-violet-50/40 p-6 shadow-sm shadow-amber-950/5 sm:p-7 dark:border-amber-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      >
        <div
          class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-amber-300/20 blur-3xl dark:bg-amber-500/10"
          aria-hidden="true"
        />
        <div class="relative space-y-3">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200/70 dark:bg-amber-900/40 dark:text-amber-200 dark:ring-amber-800/60"
          >
            <Sparkles class="size-3.5" />
            Loyalty Program
          </span>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
          >
            Points & Loyalty
          </h1>
          <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Ringkasan poin anggota koperasi dari transaksi dan penukaran reward.
          </p>
        </div>
      </section>

      <div class="grid gap-4 md:grid-cols-2">
        <StatsCard
          label="Anggota Aktif"
          :value="formatNumber(stats.active_members)"
          :icon="Trophy"
        />
        <StatsCard
          label="Total Balance"
          :value="formatNumber(stats.total_balance)"
          :icon="Gift"
        />
      </div>

      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Daftar Poin Anggota"
          :icon="Trophy"
          tone="amber"
        />
        <CardContent class="px-0 pb-0">
          <DataTable :columns="columns" :data="members" :searchable="false">
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
                class="text-lg font-bold tabular-nums text-amber-700 dark:text-amber-300"
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
