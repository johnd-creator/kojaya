<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { Home } from "lucide-vue-next";
import { computed, ref } from "vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import FilterBar from "@/components/FilterBar.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

interface Warehouse {
  id: string;
  code: string;
  name: string;
  location: string | null;
  type: "STORAGE" | "REPAIR" | "DISPOSAL";
  organization?: {
    id: string;
    name: string;
    code: string;
  };
}

interface Props {
  warehouses: Warehouse[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Storage", href: "#" },
  { title: "Warehouses", href: "/warehouses" },
];

const search = ref("");
const typeFilter = ref("");
const organizationFilter = ref("");

const filteredWarehouses = computed(() => {
  return props.warehouses.filter((warehouse) => {
    const matchesSearch =
      !search.value ||
      warehouse.name.toLowerCase().includes(search.value.toLowerCase()) ||
      warehouse.code.toLowerCase().includes(search.value.toLowerCase()) ||
      warehouse.location?.toLowerCase().includes(search.value.toLowerCase());

    const matchesType =
      !typeFilter.value || warehouse.type === typeFilter.value;
    const matchesOrganization =
      !organizationFilter.value ||
      warehouse.organization?.id === organizationFilter.value;

    return matchesSearch && matchesType && matchesOrganization;
  });
});

const organizations = computed(() => {
  return Array.from(
    new Map(
      props.warehouses
        .filter((warehouse) => warehouse.organization?.id)
        .map((warehouse) => [
          warehouse.organization!.id,
          {
            id: warehouse.organization!.id,
            name: warehouse.organization?.name || warehouse.organization!.id,
          },
        ]),
    ).values(),
  );
});
const typeOptions = [
  { label: "All Types", value: "" },
  { label: "Storage", value: "STORAGE" },
  { label: "Repair", value: "REPAIR" },
  { label: "Disposal", value: "DISPOSAL" },
];
const organizationOptions = computed(() => [
  { label: "All Organizations", value: "" },
  ...organizations.value.map((organization) => ({
    label: organization.name,
    value: organization.id,
  })),
]);

const clearFilters = () => {
  search.value = "";
  typeFilter.value = "";
  organizationFilter.value = "";
};

const getTypeLabel = (type: string) => {
  const labels = {
    STORAGE: "Storage",
    REPAIR: "Repair",
    DISPOSAL: "Disposal",
  };

  return labels[type as keyof typeof labels] || type;
};

const getTypeVariant = (
  type: string,
): "info" | "warning" | "destructive" | "secondary" => {
  switch (type) {
    case "STORAGE":
      return "info";
    case "REPAIR":
      return "warning";
    case "DISPOSAL":
      return "destructive";
    default:
      return "secondary";
  }
};

const columns = [
  { header: "Warehouse Code", key: "code", slot: "code" },
  { header: "Warehouse Name", key: "name", slot: "name" },
  {
    header: "Location",
    key: "location",
    format: (value: string | null) => value || "-",
  },
  { header: "Organization", key: "organization.name", slot: "organization" },
  { header: "Type", key: "type", slot: "type" },
];
</script>

<template>
  <Head title="Warehouses" />
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
            Warehouses
          </h1>
          <p class="text-zinc-500 mt-1">
            Manage warehouses and storage locations.
          </p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
          <Link href="/warehouses/create">
            <Button
              class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0"
            >
              <Home class="h-4 w-4 mr-2" />
              Add Warehouse
            </Button>
          </Link>
        </div>
      </div>

      <!-- Filters -->
      <FilterBar
        v-model:search="search"
        search-placeholder="Search warehouses..."
        @reset="clearFilters"
      >
        <SelectFilter
          v-model="typeFilter"
          :options="typeOptions"
          placeholder="All Types"
        />
        <SelectFilter
          v-model="organizationFilter"
          :options="organizationOptions"
          placeholder="All Organizations"
        />
      </FilterBar>

      <DataTable
        :columns="columns"
        :data="filteredWarehouses"
        :searchable="false"
        empty-message="No warehouses found."
        :empty-icon="Home"
      >
        <template #code="{ row }">
          <span
            class="font-mono text-sm font-medium text-indigo-600 dark:text-indigo-400"
          >
            {{ row.code }}
          </span>
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

        <template #type="{ value }">
          <StatusBadge
            :status="value"
            :label="getTypeLabel(value)"
            :variant="getTypeVariant(value)"
          />
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>
