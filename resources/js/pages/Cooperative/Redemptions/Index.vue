<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ShoppingBag, Sparkles } from "lucide-vue-next";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
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
  { header: "Anggota", key: "member.name", slot: "member" },
  { header: "Reward", key: "reward.name" },
  { header: "Qty", key: "quantity", align: "center" as const },
  {
    header: "Poin",
    key: "points_used",
    format: formatNumber,
    align: "right" as const,
  },
  { header: "Status", key: "status", slot: "status" },
  {
    header: "Ditukar",
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
      { title: 'Koperasi', href: '/cooperative/members' },
      { title: 'Redemptions', href: '/cooperative/redemptions' },
    ]"
  >
    <PageContainer class="max-w-none">
      <section
        class="relative overflow-hidden rounded-2xl border border-amber-200/60 bg-gradient-to-br from-white via-amber-50/60 to-rose-50/40 p-6 shadow-sm shadow-amber-950/5 sm:p-7 dark:border-amber-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
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
            Penukaran Reward
          </span>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
          >
            Reward Redemptions
          </h1>
          <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Pantau penukaran reward yang diajukan anggota.
          </p>
        </div>
      </section>

      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Riwayat Penukaran"
          :icon="ShoppingBag"
          tone="amber"
        />
        <CardContent class="px-0 pb-0">
          <DataTable :columns="columns" :data="redemptions" :searchable="false">
            <template #member="{ row }">
              <div class="font-semibold text-zinc-950 dark:text-white">
                {{ row.member.name }}
              </div>
              <div class="text-xs text-zinc-500">
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
        </CardContent>
      </Card>
    </PageContainer>
  </AppLayout>
</template>
