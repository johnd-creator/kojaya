<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { Package, AlertTriangle } from "lucide-vue-next";
import { computed, ref } from "vue";
import FilterBar from "@/components/FilterBar.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

interface Stock {
  id: string;
  warehouse: {
    id: string;
    name: string;
    code: string;
  };
  quantity: number;
  reserved_quantity: number;
}

interface SparePart {
  id: string;
  code: string;
  name: string;
  specification: string | null;
  unit: string;
  category: string | null;
  min_stock: number;
  max_stock: number;
  reorder_level: number;
  total_stock: number;
  available_stock: number;
  is_below_min: boolean;
  is_below_reorder: boolean;
  organization?: {
    id: string;
    name: string;
    code: string;
  };
  stocks: Stock[];
}

interface Props {
  spareParts: SparePart[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Storage", href: "#" },
  { title: "Spare Parts", href: "/spare-parts" },
];

const search = ref("");
const categoryFilter = ref("");
const lowStockOnly = ref(false);

const categories = computed(() => {
  return [
    ...new Set(
      props.spareParts
        .map((p) => p.category)
        .filter((c): c is string => typeof c === "string" && c.length > 0),
    ),
  ];
});
const categoryOptions = computed(() => [
  { label: "All Categories", value: "" },
  ...categories.value.map((category) => ({ label: category, value: category })),
]);

const filteredSpareParts = computed(() => {
  return props.spareParts.filter((part) => {
    const matchesSearch =
      !search.value ||
      part.name.toLowerCase().includes(search.value.toLowerCase()) ||
      part.code.toLowerCase().includes(search.value.toLowerCase()) ||
      part.specification?.toLowerCase().includes(search.value.toLowerCase());

    const matchesCategory =
      !categoryFilter.value || part.category === categoryFilter.value;
    const matchesLowStock = !lowStockOnly.value || part.is_below_min;

    return matchesSearch && matchesCategory && matchesLowStock;
  });
});

const clearFilters = () => {
  search.value = "";
  categoryFilter.value = "";
  lowStockOnly.value = false;
};

const getStockVariant = (
  part: SparePart,
): "destructive" | "warning" | "success" => {
  if (part.is_below_min) {
    return "destructive";
  }
  if (part.is_below_reorder) {
    return "warning";
  }
  return "success";
};

const getStockBadge = (part: SparePart) => {
  if (part.is_below_min) {
    return "Low Stock";
  }
  if (part.is_below_reorder) {
    return "Reorder";
  }
  return "OK";
};

const columns = [
  { header: "Part Code", key: "code", slot: "code" },
  { header: "Part Name", key: "name", slot: "name" },
  {
    header: "Category",
    key: "category",
    format: (value: string | null) => value || "-",
  },
  { header: "Stock", key: "available_stock", slot: "stock" },
  { header: "Unit", key: "unit" },
  { header: "Status", key: "id", slot: "status" },
];
</script>

<template>
  <Head title="Spare Parts" />
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
            Spare Parts
          </h1>
          <p class="text-zinc-500 mt-1">
            Manage spare parts inventory across all warehouses.
          </p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
          <Link href="/spare-parts/create">
            <Button
              class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0"
            >
              <Package class="h-4 w-4 mr-2" />
              Add Part
            </Button>
          </Link>
        </div>
      </div>

      <!-- Filters -->
      <FilterBar
        v-model:search="search"
        search-placeholder="Search parts..."
        @reset="clearFilters"
      >
        <SelectFilter
          v-model="categoryFilter"
          :options="categoryOptions"
          placeholder="All Categories"
        />

        <label
          class="flex items-center gap-2 px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 cursor-pointer"
        >
          <input
            type="checkbox"
            v-model="lowStockOnly"
            class="rounded border-zinc-300"
          />
          <span>Low Stock Only</span>
        </label>
      </FilterBar>

      <DataTable
        :columns="columns"
        :data="filteredSpareParts"
        :searchable="false"
        empty-message="No spare parts found."
        :empty-icon="Package"
      >
        <template #code="{ row }">
          <div class="flex flex-col">
            <span
              class="font-mono text-sm font-medium text-indigo-600 dark:text-indigo-400"
            >
              {{ row.code }}
            </span>
            <span v-if="row.specification" class="mt-0.5 text-xs text-zinc-500">
              {{ row.specification }}
            </span>
          </div>
        </template>

        <template #name="{ row }">
          <span class="font-medium text-zinc-900 dark:text-zinc-100">
            {{ row.name }}
          </span>
        </template>

        <template #stock="{ row }">
          <div class="flex flex-col">
            <span class="font-medium text-zinc-900 dark:text-zinc-100">
              {{ row.available_stock }} / {{ row.max_stock }}
            </span>
            <span class="mt-0.5 text-xs text-zinc-500">
              Min: {{ row.min_stock }} | Reorder: {{ row.reorder_level }}
            </span>
          </div>
        </template>

        <template #status="{ row }">
          <div class="flex items-center gap-2">
            <StatusBadge
              :status="getStockBadge(row)"
              :label="getStockBadge(row)"
              :variant="getStockVariant(row)"
            />
            <AlertTriangle
              v-if="row.is_below_min"
              class="h-4 w-4 text-red-500"
            />
          </div>
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>
