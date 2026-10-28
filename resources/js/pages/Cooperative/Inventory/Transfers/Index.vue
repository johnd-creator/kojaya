<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { Plus } from "lucide-vue-next";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDate } from "@/lib/formatters";
import { index, create } from "@/routes/cooperative/pos/inventory/transfers";
import type { PaginationMeta } from "@/types/pagination";
const props = defineProps<{
  transfers: PaginationMeta & {
    data: Array<{
      id: number;
      transfer_no: string;
      transferred_at: string;
      status: string;
      fromLocation?: { name: string } | null;
      toLocation?: { name: string } | null;
      requester?: { name: string } | null;
    }>;
  };
}>();
const columns = [
  { header: "No. Transfer", key: "transfer_no" },
  { header: "Tanggal", key: "transferred_at", format: formatDate },
  { header: "Lokasi Asal", key: "fromLocation.name" },
  { header: "Lokasi Tujuan", key: "toLocation.name" },
  { header: "Petugas", key: "requester.name" },
  { header: "Status", key: "status", slot: "status" },
].map((column) => ({
  ...column,
  headerClass: "max-w-none! whitespace-nowrap",
  class: "max-w-none! whitespace-nowrap",
}));
const statusLabels: Record<string, string> = {
  DRAFT: "Draft",
  REVIEW: "Dalam Review",
  APPROVED: "Disetujui",
  POSTED: "Tercatat",
  CANCELLED: "Dibatalkan",
};
</script>
<template>
  <Head title="Transfer Stok" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Inventory POS', href: '#' },
      { title: 'Transfer Stok', href: index().url },
    ]"
  >
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Transfer Stok"
        description="Telusuri perpindahan stok antar lokasi dan status dokumennya."
        ><Button as-child size="sm"
          ><Link :href="create().url"
            ><Plus class="size-4" /> Transfer Baru</Link
          ></Button
        ></WorkspaceHeader
      >
      <WorkspaceSummary
        :items="[
          { label: 'Transfer di Halaman Ini', value: transfers.data.length },
          {
            label: 'Draft di Halaman Ini',
            value: transfers.data.filter((row) => row.status === 'DRAFT')
              .length,
          },
          {
            label: 'Tercatat di Halaman Ini',
            value: transfers.data.filter((row) => row.status === 'POSTED')
              .length,
            tone: 'emerald',
          },
        ]"
        label="Ringkasan halaman ini"
      />
      <section class="min-w-0 space-y-3">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-sm font-semibold">Daftar Transfer Stok</h2>
          <span class="text-xs text-zinc-500"
            >{{ props.transfers.data.length }} ditampilkan</span
          >
        </div>
        <DataTable
          :columns="columns"
          :data="transfers"
          :searchable="false"
          compact
          table-class="min-w-[900px] table-auto"
          empty-message="Belum ada dokumen transfer stok. Gunakan Transfer Baru untuk memulai."
        >
          <template #status="{ value }"
            ><StatusBadge
              :status="value"
              :label="statusLabels[value] ?? value"
              :variant="value === 'POSTED' ? 'success' : undefined"
          /></template>
        </DataTable>
      </section>
    </PageContainer>
  </AppLayout>
</template>
