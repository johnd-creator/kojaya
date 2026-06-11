<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { Users, Plus, Building, UserCheck } from "lucide-vue-next";
import { computed, ref } from "vue";
import {
  create as employeeCreate,
  edit as employeeEdit,
} from "@/actions/App/Http/Controllers/EmployeeController";
import FilterBar from "@/components/FilterBar.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatsCard from "@/components/StatsCard.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { useTableFilters } from "@/composables/useTableFilters";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  employees: any;
  organizations: any[];
  filters: Record<string, string>;
  stats: {
    total_active: number;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "HR & Employee", href: "#" },
  { title: "Employee Master", href: "/employees" },
];

const filters = ref({
  search: props.filters.search || "",
  organization_id: props.filters.organization_id || "",
  status: props.filters.status || "",
});
const organizationOptions = computed(() => [
  { label: "All Units", value: "" },
  ...props.organizations.map((organization) => ({
    label: `${organization.code} - ${organization.name}`,
    value: organization.id,
  })),
]);
const statusOptions = [
  { label: "Any Status", value: "" },
  { label: "Active", value: "ACTIVE" },
  { label: "Resigned", value: "RESIGNED" },
  { label: "Terminated", value: "TERMINATED" },
];

const { resetFilters } = useTableFilters(filters, {
  route: "/employees",
  debounceMs: 500,
});

const tableData = computed(() => {
  if (props.employees?.meta) {
    return {
      ...props.employees.meta,
      data: props.employees.data ?? [],
      links: props.employees.links ?? [],
    };
  }

  return props.employees;
});

const columns = [
  { header: "Employee", key: "first_name", slot: "employee" },
  {
    header: "Unit / Organization",
    key: "organization.name",
    slot: "organization",
  },
  { header: "Details", key: "position.name", slot: "details" },
  { header: "Hire Date", key: "hire_date", slot: "hire_date" },
  { header: "Status", key: "status", slot: "status" },
  {
    header: "Actions",
    key: "actions",
    slot: "actions",
    align: "right" as const,
  },
];

const getStatusVariant = (
  status: string,
): "success" | "destructive" | "secondary" => {
  switch (status) {
    case "ACTIVE":
      return "success";
    case "RESIGNED":
    case "TERMINATED":
      return "destructive";
    default:
      return "secondary";
  }
};
</script>

<template>
  <Head title="Employee Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      <!-- Header Section -->
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Employee Master
          </h1>
          <p class="text-zinc-500 mt-1">
            Manage employee profiles, contracts, and placements.
          </p>
        </div>
        <div class="flex gap-3">
          <Button as-child variant="default">
            <Link :href="employeeCreate().url">
              <Plus class="h-4 w-4 mr-2" />
              Add Employee
            </Link>
          </Button>
        </div>
      </div>

      <!-- Stats & Filters -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <StatsCard
          label="Total Active Employees"
          :value="stats.total_active"
          :icon="UserCheck"
          icon-container-class="bg-indigo-50 dark:bg-indigo-900/30"
          icon-class="text-indigo-600 dark:text-indigo-400"
        />

        <!-- Filters -->
        <div
          class="lg:col-span-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm"
        >
          <FilterBar
            v-model:search="filters.search"
            search-placeholder="Name or Code..."
            @reset="resetFilters"
          >
            <SelectFilter
              v-model="filters.organization_id"
              :options="organizationOptions"
              placeholder="All Units"
            />
            <SelectFilter
              v-model="filters.status"
              :options="statusOptions"
              placeholder="Any Status"
            />
          </FilterBar>
        </div>
      </div>

      <DataTable
        :columns="columns"
        :data="tableData"
        :searchable="false"
        empty-message="No employees found. Try adjusting your filters or add a new employee."
        :empty-icon="Users"
      >
        <template #employee="{ row }">
          <div class="flex items-center gap-3">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800"
            >
              <Users class="h-5 w-5" />
            </div>
            <div>
              <div class="font-medium text-zinc-900 dark:text-white">
                {{ row.first_name }} {{ row.last_name }}
              </div>
              <div class="text-xs text-zinc-500">{{ row.employee_code }}</div>
            </div>
          </div>
        </template>

        <template #organization="{ row }">
          <div v-if="row.organization" class="flex items-center gap-2">
            <Building class="h-4 w-4 text-zinc-400" />
            <span class="text-zinc-700 dark:text-zinc-300">{{
              row.organization.name
            }}</span>
          </div>
          <span v-else class="italic text-zinc-400">No Unit</span>
        </template>

        <template #details="{ row }">
          <div class="flex flex-col gap-1">
            <span
              v-if="row.position"
              class="text-sm font-medium text-zinc-900 dark:text-white"
              >{{ row.position.name }}</span
            >
            <span v-else class="text-sm italic text-zinc-400">No Position</span>
            <div class="flex items-center gap-2 text-xs">
              <span
                v-if="row.job_grade"
                class="rounded border border-zinc-200 bg-zinc-100 px-1.5 py-0.5 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400"
                >{{ row.job_grade.code }}</span
              >
              <span
                class="rounded border border-indigo-200 bg-indigo-50 px-1.5 py-0.5 text-indigo-700 dark:border-indigo-800/50 dark:bg-indigo-900/30 dark:text-indigo-400"
                >{{ row.employee_type || "Organic" }}</span
              >
            </div>
          </div>
        </template>

        <template #hire_date="{ value }">
          <span class="text-zinc-700 dark:text-zinc-300">
            {{ value ? new Date(value).toLocaleDateString() : "-" }}
          </span>
        </template>

        <template #status="{ value }">
          <StatusBadge :status="value" :variant="getStatusVariant(value)" />
        </template>

        <template #actions="{ row }">
          <Link
            :href="employeeEdit(row.id).url"
            class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
            >Edit</Link
          >
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>
