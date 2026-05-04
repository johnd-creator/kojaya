<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";

type PurchaseOrderStatus = "ALL" | "ISSUED" | "RECEIVED" | "CANCELLED";

const props = defineProps<{ orders: any[] }>();

const statusOptions: PurchaseOrderStatus[] = [
  "ALL",
  "ISSUED",
  "RECEIVED",
  "CANCELLED",
];
const search = ref("");
const selectedStatus = ref<PurchaseOrderStatus>("ALL");
const receiveDialogOpen = ref(false);
const receiveTargetId = ref<string | null>(null);

const columns = [
  {
    header: "PO Number",
    key: "po_no",
    format: (v: string) => v || "DRAFT",
    class: "font-mono",
  },
  { header: "Status", key: "status", slot: "status" },
  { header: "Items", key: "items_count", align: "center" as const },
  {
    header: "Total Amount",
    key: "total_amount",
    format: formatCurrency,
    align: "right" as const,
  },
  {
    header: "Issued Date",
    key: "issued_at",
    format: (v: string) => (v ? new Date(v).toLocaleDateString() : "-"),
    align: "right" as const,
  },
  { header: "", key: "actions", slot: "actions", align: "right" as const },
];

const filteredData = computed(() => {
  let data = props.orders;

  if (selectedStatus.value !== "ALL") {
    data = data.filter((po) => po.status === selectedStatus.value);
  }

  if (search.value) {
    const q = search.value.toLowerCase();
    data = data.filter(
      (po) =>
        (po.po_no || "").toLowerCase().includes(q) ||
        po.status.toLowerCase().includes(q),
    );
  }

  return data;
});

const statusCounts = computed<Record<PurchaseOrderStatus, number>>(() => {
  return {
    ALL: props.orders.length,
    ISSUED: props.orders.filter((po) => po.status === "ISSUED").length,
    RECEIVED: props.orders.filter((po) =>
      ["RECEIVED", "RECEIVED_PARTIAL"].includes(po.status),
    ).length,
    CANCELLED: props.orders.filter((po) => po.status === "CANCELLED").length,
  };
});

function handleRowClick(row: any) {
  router.visit(`/procurement/purchase-orders/${row.id}`);
}

function receiveQuick(poId: string) {
  receiveTargetId.value = poId;
  receiveDialogOpen.value = true;
}

function confirmReceiveQuick() {
  if (!receiveTargetId.value) return;

  router.post(
    `/procurement/grns/from-po/${receiveTargetId.value}`,
    {},
    {
      onFinish: () => {
        receiveDialogOpen.value = false;
        receiveTargetId.value = null;
      },
    },
  );
}
</script>

<template>
  <Head title="Purchase Orders" />

  <AppLayout>
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      <div class="flex items-center justify-between">
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Purchase Orders
          </h1>
          <p class="text-zinc-500 mt-1">
            Track and manage purchase orders to vendors.
          </p>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap gap-2">
        <Button
          v-for="status in statusOptions"
          :key="status"
          size="sm"
          :variant="selectedStatus === status ? 'default' : 'outline'"
          @click="selectedStatus = status"
        >
          {{ status === "ALL" ? "All Orders" : status }}
          <span class="ml-2 text-xs opacity-70"
            >({{ statusCounts[status] }})</span
          >
        </Button>
      </div>

      <!-- Data Table -->
      <DataTable
        :columns="columns"
        :data="filteredData"
        :searchable="true"
        search-placeholder="Search PO number..."
        :row-clickable="true"
        @row-click="handleRowClick"
        @search="(q) => (search = q)"
      >
        <template #status="{ value }">
          <StatusBadge :status="value" />
        </template>

        <template #actions="{ row }">
          <Button
            v-if="row.status === 'ISSUED'"
            size="sm"
            variant="outline"
            class="h-7 text-xs"
            @click.stop="receiveQuick(row.id)"
          >
            Receive
          </Button>
        </template>
      </DataTable>

      <ConfirmDialog
        v-model:open="receiveDialogOpen"
        title="Buat GRN"
        message="Apakah Anda yakin ingin membuat Goods Receive Note dari purchase order ini?"
        confirm-label="Buat GRN"
        @confirm="confirmReceiveQuick"
      />
    </div>
  </AppLayout>
</template>
