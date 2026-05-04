<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { Plus, FileText } from "lucide-vue-next";
import { computed, ref } from "vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import FilterBar from "@/components/FilterBar.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

interface WorkOrder {
  id: string;
  asset_id: string;
  organization_id: string;
  type: "PREVENTIVE" | "CORRECTIVE";
  priority: "LOW" | "MEDIUM" | "HIGH" | "EMERGENCY";
  status: "OPEN" | "IN_PROGRESS" | "COMPLETED" | "CLOSED";
  description: string | null;
  assigned_to: string | null;
  completed_at: string | null;
  created_at: string;
  asset?: {
    id: string;
    code: string;
    name: string;
  };
  organization?: {
    id: string;
    name: string;
    code: string;
  };
  assignedTo?: {
    id: string;
    name: string;
    email: string;
  };
}

interface Props {
  workOrders: WorkOrder[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Asset Management", href: "#" },
  { title: "Work Orders", href: "/work-orders" },
];

const search = ref("");
const statusFilter = ref("");
const priorityFilter = ref("");
const typeFilter = ref("");
const statusOptions = [
  { label: "All Statuses", value: "" },
  { label: "Open", value: "OPEN" },
  { label: "In Progress", value: "IN_PROGRESS" },
  { label: "Completed", value: "COMPLETED" },
  { label: "Closed", value: "CLOSED" },
];
const priorityOptions = [
  { label: "All Priorities", value: "" },
  { label: "Low", value: "LOW" },
  { label: "Medium", value: "MEDIUM" },
  { label: "High", value: "HIGH" },
  { label: "Emergency", value: "EMERGENCY" },
];
const typeOptions = [
  { label: "All Types", value: "" },
  { label: "Preventive", value: "PREVENTIVE" },
  { label: "Corrective", value: "CORRECTIVE" },
];

const filteredWorkOrders = computed(() => {
  return props.workOrders.filter((wo) => {
    const matchesSearch =
      !search.value ||
      wo.description?.toLowerCase().includes(search.value.toLowerCase()) ||
      wo.asset?.code.toLowerCase().includes(search.value.toLowerCase()) ||
      wo.asset?.name.toLowerCase().includes(search.value.toLowerCase());

    const matchesStatus =
      !statusFilter.value || wo.status === statusFilter.value;
    const matchesPriority =
      !priorityFilter.value || wo.priority === priorityFilter.value;
    const matchesType = !typeFilter.value || wo.type === typeFilter.value;

    return matchesSearch && matchesStatus && matchesPriority && matchesType;
  });
});

const clearFilters = () => {
  search.value = "";
  statusFilter.value = "";
  priorityFilter.value = "";
  typeFilter.value = "";
};

const formatLabel = (value: string) => value.replace("_", " ");

const getStatusVariant = (
  status: string,
): "info" | "warning" | "success" | "secondary" => {
  switch (status) {
    case "OPEN":
      return "info";
    case "IN_PROGRESS":
      return "warning";
    case "COMPLETED":
      return "success";
    default:
      return "secondary";
  }
};

const getPriorityVariant = (
  priority: string,
): "secondary" | "info" | "warning" | "destructive" => {
  switch (priority) {
    case "MEDIUM":
      return "info";
    case "HIGH":
      return "warning";
    case "EMERGENCY":
      return "destructive";
    default:
      return "secondary";
  }
};

const getTypeVariant = (type: string): "info" | "destructive" => {
  return type === "PREVENTIVE" ? "info" : "destructive";
};

const columns = [
  { header: "WO ID", key: "id", slot: "id" },
  { header: "Asset", key: "asset.name", slot: "asset" },
  { header: "Type / Priority", key: "type", slot: "type_priority" },
  { header: "Description", key: "description", slot: "description" },
  { header: "Assigned To", key: "assignedTo.name", slot: "assigned_to" },
  { header: "Status", key: "status", slot: "status" },
];
</script>

<template>
  <Head title="Work Orders" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full"
    >
      <!-- Header Section -->
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Work Orders
          </h1>
          <p class="text-zinc-500 mt-1">
            Track and manage maintenance work orders across all assets.
          </p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
          <Link href="/work-orders/create">
            <Button
              class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0"
            >
              <Plus class="h-4 w-4 mr-2" />
              Create WO
            </Button>
          </Link>
        </div>
      </div>

      <!-- Filters -->
      <FilterBar
        v-model:search="search"
        search-placeholder="Search work orders..."
        @reset="clearFilters"
      >
        <SelectFilter
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="All Statuses"
        />
        <SelectFilter
          v-model="priorityFilter"
          :options="priorityOptions"
          placeholder="All Priorities"
        />
        <SelectFilter
          v-model="typeFilter"
          :options="typeOptions"
          placeholder="All Types"
        />
      </FilterBar>

      <DataTable
        :columns="columns"
        :data="filteredWorkOrders"
        :searchable="false"
        empty-message="No work orders found."
        :empty-icon="FileText"
      >
        <template #id="{ row }">
          <Link
            :href="`/work-orders/${row.id}`"
            class="font-mono text-sm font-medium text-zinc-700 hover:underline dark:text-zinc-300"
          >
            {{ row.id.slice(0, 8) }}
          </Link>
        </template>

        <template #asset="{ row }">
          <div class="flex flex-col">
            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
              {{ row.asset?.name || "-" }}
            </span>
            <span v-if="row.asset" class="mt-0.5 text-xs text-zinc-500">
              {{ row.asset.code }}
            </span>
          </div>
        </template>

        <template #type_priority="{ row }">
          <div class="flex flex-col gap-1.5">
            <StatusBadge
              :status="row.type"
              :label="formatLabel(row.type)"
              :variant="getTypeVariant(row.type)"
            />
            <StatusBadge
              :status="row.priority"
              :label="formatLabel(row.priority)"
              :variant="getPriorityVariant(row.priority)"
            />
          </div>
        </template>

        <template #description="{ value }">
          <p
            class="max-w-xs line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400"
          >
            {{ value || "-" }}
          </p>
        </template>

        <template #assigned_to="{ value }">
          <span class="text-sm text-zinc-700 dark:text-zinc-300">
            {{ value || "Unassigned" }}
          </span>
        </template>

        <template #status="{ value }">
          <StatusBadge
            :status="value"
            :label="formatLabel(value)"
            :variant="getStatusVariant(value)"
          />
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>
