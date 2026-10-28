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
import { formatDate, formatCurrency } from "@/lib/formatters";
import { index, create } from "@/routes/cooperative/pos/inventory/receipts";
import type { PaginationMeta } from "@/types/pagination";
const props = defineProps<{
  receipts: PaginationMeta & {
    data: Array<{
      id: number;
      receipt_no: string;
      received_at: string;
      total_amount: number | string;
      status: string;
      reference_no?: string | null;
      supplier?: { name: string } | null;
      location?: { name: string } | null;
      receiver?: { name: string } | null;
    }>;
  };
}>();
const columns = [
  { header: "No. Penerimaan", key: "receipt_no" },
  { header: "Tanggal", key: "received_at", format: formatDate },
  { header: "Supplier", key: "supplier.name" },
  { header: "Lokasi", key: "location.name" },
  { header: "Referensi", key: "reference_no" },
  { header: "Petugas", key: "receiver.name" },
  {
    header: "Nominal",
    key: "total_amount",
    format: formatCurrency,
    align: "right" as const,
  },
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
  <Head title="Penerimaan Stok" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Inventory POS', href: '#' },
      { title: 'Penerimaan Stok', href: index().url },
    ]"
  >
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Penerimaan Stok"
        description="Telusuri stok masuk, supplier, referensi penerimaan, dan lokasi penyimpanan."
        ><Button as-child size="sm"
          ><Link :href="create().url"
            ><Plus class="size-4" /> Catat Penerimaan</Link
          ></Button
        ></WorkspaceHeader
      >
      <WorkspaceSummary
        :items="[
          { label: 'Penerimaan di Halaman Ini', value: receipts.data.length },
          {
            label: 'Nominal di Halaman Ini',
            value: formatCurrency(
              receipts.data.reduce(
                (sum, row) => sum + Number(row.total_amount),
                0,
              ),
            ),
          },
          {
            label: 'Tercatat di Halaman Ini',
            value: receipts.data.filter((row) => row.status === 'POSTED')
              .length,
            tone: 'emerald',
          },
        ]"
        label="Ringkasan halaman ini"
      />
      <section class="min-w-0 space-y-3">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-sm font-semibold">Daftar Penerimaan Stok</h2>
          <span class="text-xs text-zinc-500"
            >{{ props.receipts.data.length }} ditampilkan</span
          >
        </div>
        <DataTable
          :columns="columns"
          :data="receipts"
          :searchable="false"
          compact
          table-class="min-w-[900px] table-auto"
          empty-message="Belum ada dokumen penerimaan stok. Gunakan Catat Penerimaan untuk memulai."
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
