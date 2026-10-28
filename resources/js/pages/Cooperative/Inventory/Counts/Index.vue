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
import { index, create, show } from "@/routes/cooperative/pos/inventory/counts";
import type { PaginationMeta } from "@/types/pagination";
const props = defineProps<{
  counts: PaginationMeta & {
    data: Array<{
      id: number;
      count_no: string;
      counted_at: string;
      status: string;
      location?: { name: string } | null;
      requester?: { name: string } | null;
    }>;
  };
}>();
const columns = [
  { header: "No. Opname", key: "count_no", slot: "number" },
  { header: "Tanggal", key: "counted_at", format: formatDate },
  { header: "Lokasi", key: "location.name" },
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
  <Head title="Stock Opname" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Inventory POS', href: '#' },
      { title: 'Stock Opname', href: index().url },
    ]"
  >
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Stock Opname"
        description="Hitung stok fisik dan tinjau selisih sebelum persetujuan."
        ><Button as-child size="sm"
          ><Link :href="create().url"
            ><Plus class="size-4" /> Buat Opname</Link
          ></Button
        ></WorkspaceHeader
      >
      <WorkspaceSummary
        :items="[
          { label: 'Opname di Halaman Ini', value: counts.data.length },
          {
            label: 'Draft di Halaman Ini',
            value: counts.data.filter((row) => row.status === 'DRAFT').length,
          },
          {
            label: 'Disetujui di Halaman Ini',
            value: counts.data.filter((row) => row.status === 'APPROVED')
              .length,
            tone: 'emerald',
          },
        ]"
        label="Ringkasan halaman ini"
      />
      <section class="min-w-0 space-y-3">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-sm font-semibold">Daftar Stock Opname</h2>
          <span class="text-xs text-zinc-500"
            >{{ props.counts.data.length }} ditampilkan</span
          >
        </div>
        <DataTable
          :columns="columns"
          :data="counts"
          :searchable="false"
          compact
          table-class="min-w-[900px] table-auto"
          empty-message="Belum ada dokumen stock opname. Gunakan Buat Opname untuk memulai."
        >
          <template #number="{ row }"
            ><Link
              :href="show(row.id).url"
              class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400"
              >{{ row.count_no }}</Link
            ></template
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
