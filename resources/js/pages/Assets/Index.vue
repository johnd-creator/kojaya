<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { Settings, Wrench } from "lucide-vue-next";
import { computed, ref } from "vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import FilterBar from "@/components/FilterBar.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

interface Asset {
  id: string;
  code: string;
  name: string;
  category: string;
  organization_id: string;
  status: "ACTIVE" | "INACTIVE" | "UNDER_MAINTENANCE";
  purchase_date: string | null;
  serial_number: string | null;
  organization?: {
    id: string;
    name: string;
    code: string;
  };
}

interface Props {
  assets: Asset[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Asset Management", href: "#" },
  { title: "Assets", href: "/assets" },
];

const search = ref("");
const statusFilter = ref("");
const organizationFilter = ref("");

const filteredAssets = computed(() => {
  return props.assets.filter((asset) => {
    const matchesSearch =
      !search.value ||
      asset.name.toLowerCase().includes(search.value.toLowerCase()) ||
      asset.code.toLowerCase().includes(search.value.toLowerCase()) ||
      asset.serial_number?.toLowerCase().includes(search.value.toLowerCase());

    const matchesStatus =
      !statusFilter.value || asset.status === statusFilter.value;
    const matchesOrganization =
      !organizationFilter.value ||
      asset.organization_id === organizationFilter.value;

    return matchesSearch && matchesStatus && matchesOrganization;
  });
});

const organizations = computed(() => {
  return Array.from(
    new Map(
      props.assets
        .filter((asset) => asset.organization_id)
        .map((asset) => [
          asset.organization_id,
          {
            id: asset.organization_id,
            name: asset.organization?.name || asset.organization_id,
          },
        ]),
    ).values(),
  );
});
const statusOptions = [
  { label: "All Statuses", value: "" },
  { label: "Active", value: "ACTIVE" },
  { label: "Inactive", value: "INACTIVE" },
  { label: "Under Maintenance", value: "UNDER_MAINTENANCE" },
];
const organizationOptions = computed(() => [
  { label: "All Units", value: "" },
  ...organizations.value.map((organization) => ({
    label: organization.name,
    value: organization.id,
  })),
]);

const clearFilters = () => {
  search.value = "";
  statusFilter.value = "";
  organizationFilter.value = "";
};

const getStatusLabel = (status: string) => {
  const labels = {
    ACTIVE: "Active",
    INACTIVE: "Inactive",
    UNDER_MAINTENANCE: "Under Maintenance",
  };

  return labels[status as keyof typeof labels] || status;
};

const getStatusVariant = (
  status: string,
): "success" | "destructive" | "warning" | "secondary" => {
  switch (status) {
    case "ACTIVE":
      return "success";
    case "INACTIVE":
      return "destructive";
    case "UNDER_MAINTENANCE":
      return "warning";
    default:
      return "secondary";
  }
};

const columns = [
  { header: "Asset Code", key: "code", slot: "code" },
  { header: "Asset Name", key: "name", slot: "name" },
  { header: "Category", key: "category" },
  { header: "Organization", key: "organization.name", slot: "organization" },
  { header: "Status", key: "status", slot: "status" },
];
</script>

<template>
  <Head title="Assets" />
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
            Assets
          </h1>
          <p class="text-zinc-500 mt-1">
            Manage and track enterprise assets across all organizational units.
          </p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
          <Link href="/assets/create">
            <Button
              class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0"
            >
              <Settings class="h-4 w-4 mr-2" />
              Add Asset
            </Button>
          </Link>
        </div>
      </div>

      <!-- Filters -->
      <FilterBar
        v-model:search="search"
        search-placeholder="Search assets..."
        @reset="clearFilters"
      >
        <SelectFilter
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="All Statuses"
        />
        <SelectFilter
          v-model="organizationFilter"
          :options="organizationOptions"
          placeholder="All Units"
        />
      </FilterBar>

      <DataTable
        :columns="columns"
        :data="filteredAssets"
        :searchable="false"
        empty-message="No assets found."
        :empty-icon="Wrench"
      >
        <template #code="{ row }">
          <div class="flex flex-col">
            <span
              class="font-mono text-sm font-medium text-indigo-600 dark:text-indigo-400"
            >
              {{ row.code }}
            </span>
            <span v-if="row.serial_number" class="mt-0.5 text-xs text-zinc-500">
              SN: {{ row.serial_number }}
            </span>
          </div>
        </template>

        <template #name="{ row }">
          <span class="font-medium text-zinc-900 dark:text-zinc-100">
            {{ row.name }}
          </span>
        </template>

        <template #organization="{ row }">
          <div class="flex flex-col">
            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
              {{ row.organization?.name || "-" }}
            </span>
            <span v-if="row.organization" class="mt-0.5 text-xs text-zinc-500">
              {{ row.organization.code }}
            </span>
          </div>
        </template>

        <template #status="{ value }">
          <StatusBadge
            :status="value"
            :label="getStatusLabel(value)"
            :variant="getStatusVariant(value)"
          />
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>
