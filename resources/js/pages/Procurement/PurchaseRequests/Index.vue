<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { Plus, ShoppingCart, Clock, CheckCircle } from "lucide-vue-next";
import { ref, computed } from "vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";

const props = defineProps<{
  requests: any[];
  flashBudgetDetails?: any;
  canCreate?: boolean;
}>();

const search = ref("");

const columns = [
  { header: "Title", key: "title", class: "font-medium" },
  { header: "Status", key: "status", slot: "status" },
  { header: "Items", key: "items_count", align: "center" },
  {
    header: "Total Amount",
    key: "total_amount",
    format: formatCurrency,
    align: "right",
  },
  {
    header: "Submitted At",
    key: "submitted_at",
    format: (v: string) => (v ? new Date(v).toLocaleDateString() : "-"),
    align: "right",
  },
];

const stats = computed(() => {
  return {
    total: props.requests.length,
    pending: props.requests.filter((r) =>
      ["SUBMITTED", "APPROVAL_L1", "APPROVAL_L2", "APPROVAL_L3"].includes(
        r.status,
      ),
    ).length,
    approved: props.requests.filter((r) =>
      ["APPROVED", "PO_CREATED"].includes(r.status),
    ).length,
  };
});

const filteredData = computed(() => {
  if (!search.value) return props.requests;
  const q = search.value.toLowerCase();
  return props.requests.filter(
    (r) =>
      r.title.toLowerCase().includes(q) || r.status.toLowerCase().includes(q),
  );
});

function handleRowClick(row: any) {
  router.visit(`/procurement/purchase-requests/${row.id}`);
}
</script>

<template>
  <Head title="Purchase Requests" />

  <AppLayout>
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      <!-- Header -->
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Purchase Requests
          </h1>
          <p class="text-zinc-500 mt-1">
            Manage purchase requests and approvals.
          </p>
        </div>
        <div v-if="props.canCreate">
          <Button as-child>
            <Link href="/procurement/purchase-requests/create">
              <Plus class="h-4 w-4 mr-2" />
              New Request
            </Link>
          </Button>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between"
        >
          <div>
            <p class="text-sm font-medium text-zinc-500">Total Requests</p>
            <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">
              {{ stats.total }}
            </h2>
          </div>
          <div
            class="h-12 w-12 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center"
          >
            <ShoppingCart class="h-6 w-6" />
          </div>
        </div>
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between"
        >
          <div>
            <p class="text-sm font-medium text-zinc-500">Pending Approval</p>
            <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">
              {{ stats.pending }}
            </h2>
          </div>
          <div
            class="h-12 w-12 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center"
          >
            <Clock class="h-6 w-6" />
          </div>
        </div>
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between"
        >
          <div>
            <p class="text-sm font-medium text-zinc-500">Approved</p>
            <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">
              {{ stats.approved }}
            </h2>
          </div>
          <div
            class="h-12 w-12 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center"
          >
            <CheckCircle class="h-6 w-6" />
          </div>
        </div>
      </div>

      <!-- Data Table -->
      <DataTable
        :columns="columns"
        :data="filteredData"
        :searchable="true"
        search-placeholder="Search by title or status..."
        :row-clickable="true"
        @row-click="handleRowClick"
        @search="(q) => (search = q)"
      >
        <template #status="{ value }">
          <StatusBadge :status="value" />
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>
