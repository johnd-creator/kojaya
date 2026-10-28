<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDateTime, formatNumber } from "@/lib/formatters";
import type { PaginationMeta } from "@/types/pagination";

defineProps<{
  redemptions: PaginationMeta & {
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
  { header: "Aksi", key: "actions", slot: "actions", align: "right" as const },
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
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Penukaran Reward"
        description="Pantau pengajuan dan riwayat penukaran reward anggota. Buka detail untuk menindaklanjuti sesuai kewenangan."
      ></WorkspaceHeader>

      <WorkspaceSummary
        :items="[
          { label: 'Penukaran di Halaman Ini', value: redemptions.data.length },
          {
            label: 'Poin Ditukar di Halaman Ini',
            value: formatNumber(
              redemptions.data.reduce(
                (sum, row) => sum + Number(row.points_used),
                0,
              ),
            ),
          },
          {
            label: 'Jumlah Reward di Halaman Ini',
            value: formatNumber(
              redemptions.data.reduce(
                (sum, row) => sum + Number(row.quantity),
                0,
              ),
            ),
          },
        ]"
      />
      <Card
        class="gap-0 rounded-xl py-0 overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <div class="flex items-center justify-between gap-2 border-b px-4 py-3">
          <h2 class="text-sm font-semibold">Riwayat Penukaran</h2>
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
            :data="redemptions"
            :searchable="false"
            compact
            table-class="min-w-[900px] table-auto"
            empty-message="Belum ada pengajuan penukaran reward."
          >
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
