<script setup lang="ts">
import { Deferred, Head, useForm } from "@inertiajs/vue3";
import { Banknote, Building, Zap } from "lucide-vue-next";
import { computed, ref } from "vue";
import { index as payrollsIndex } from "@/actions/App/Http/Controllers/PayrollController";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import FilterBar from "@/components/FilterBar.vue";
import PageContainer from "@/components/PageContainer.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatsCard from "@/components/StatsCard.vue";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useTableFilters } from "@/composables/useTableFilters";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  payrolls: any;
  organizations: any[];
  filters: Record<string, string>;
  stats?: {
    total_net_salary: number;
    total_records: number;
    current_period: string;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Core Modules", href: "#" },
  { title: "Payroll", href: payrollsIndex().url },
];

const filters = ref({
  period: props.filters.period || "",
  organization_id: props.filters.organization_id || "",
  status: props.filters.status || "",
});
const organizationOptions = computed(() => [
  { label: "All Units", value: "" },
  ...props.organizations.map((organization) => ({
    label: organization.name,
    value: organization.id,
  })),
]);
const statusOptions = [
  { label: "Any", value: "" },
  { label: "Draft", value: "DRAFT" },
  { label: "Processed", value: "PROCESSED" },
  { label: "Paid", value: "PAID" },
];
const showGenerateModal = ref(false);

const { resetFilters } = useTableFilters(filters, {
  route: payrollsIndex().url,
  debounceMs: 400,
});

const generateForm = useForm({
  period: new Date().toISOString().slice(0, 7),
  organization_id: "",
});

const submitGenerate = () => {
  generateForm.post("/payrolls/generate", {
    onSuccess: () => {
      showGenerateModal.value = false;
    },
  });
};

const getStatusVariant = (status: string): "secondary" | "info" | "success" => {
  switch (status) {
    case "PROCESSED":
      return "info";
    case "PAID":
      return "success";
    default:
      return "secondary";
  }
};

const tableData = computed(() => {
  if (props.payrolls?.meta) {
    return {
      ...props.payrolls.meta,
      data: props.payrolls.data ?? [],
      links: props.payrolls.links ?? [],
    };
  }

  return props.payrolls;
});

const columns = [
  { header: "Employee", key: "employee.first_name", slot: "employee" },
  { header: "Unit", key: "organization.name" },
  { header: "Period", key: "period", class: "font-mono" },
  {
    header: "Basic Salary",
    key: "basic_salary",
    slot: "basic_salary",
    align: "right" as const,
  },
  {
    header: "BPJS",
    key: "bpjs_amount",
    slot: "bpjs_amount",
    align: "right" as const,
  },
  {
    header: "PPh 21",
    key: "tax_amount",
    slot: "tax_amount",
    align: "right" as const,
  },
  {
    header: "Net Salary",
    key: "net_salary",
    slot: "net_salary",
    align: "right" as const,
  },
  { header: "Status", key: "status", slot: "status" },
  {
    header: "Actions",
    key: "actions",
    slot: "actions",
    align: "center" as const,
  },
];
</script>

<template>
  <Head title="Payroll" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <PageContainer>
      <!-- Header -->
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Payroll
          </h1>
          <p class="text-zinc-500 mt-1">
            Monthly payroll periods with PPh 21 TER & BPJS calculations.
          </p>
        </div>
        <Dialog v-model:open="showGenerateModal">
          <DialogTrigger as-child>
            <Button v-can="'process_payroll'">
              <Zap class="h-4 w-4 mr-2" />
              Generate Payroll
            </Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-sm">
            <DialogHeader
              ><DialogTitle>Generate Payroll</DialogTitle></DialogHeader
            >
            <form @submit.prevent="submitGenerate" class="space-y-4 mt-2">
              <p class="text-sm text-zinc-500">
                Generates payroll for all <strong>ACTIVE</strong> employees in
                the selected unit for the specified period. Skips
                already-generated records.
              </p>
              <div class="grid gap-2">
                <Label>Period (YYYY-MM)</Label>
                <Input type="month" v-model="generateForm.period" required />
                <span
                  v-if="generateForm.errors.period"
                  class="text-xs text-red-500"
                  >{{ generateForm.errors.period }}</span
                >
              </div>
              <div class="grid gap-2">
                <Label>Unit / Organization</Label>
                <Select v-model="generateForm.organization_id">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select unit..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="org in organizations"
                      :key="org.id"
                      :value="org.id"
                    >
                      {{ org.code }} - {{ org.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <span
                  v-if="generateForm.errors.organization_id"
                  class="text-xs text-red-500"
                  >{{ generateForm.errors.organization_id }}</span
                >
              </div>
              <div class="flex justify-end gap-2 pt-2">
                <Button
                  type="button"
                  variant="outline"
                  @click="showGenerateModal = false"
                  >Cancel</Button
                >
                <Button v-can="'process_payroll'" type="submit" :disabled="generateForm.processing">
                  <span v-if="generateForm.processing">Generating...</span>
                  <span v-else>Generate</span>
                </Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Stats + Filters -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Deferred data="stats">
          <template #fallback>
            <div aria-live="polite" class="sr-only">
              Memuat statistik payroll.
            </div>
            <Skeleton class="h-32 rounded-xl border" />
            <Skeleton class="h-32 rounded-xl border" />
          </template>

          <StatsCard
            :label="`Total Net (${stats?.current_period ?? filters.period ?? '-'})`"
            :value="formatCurrency(stats?.total_net_salary ?? 0)"
            :icon="Banknote"
            value-class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-white"
            icon-container-class="bg-emerald-50 dark:bg-emerald-900/30"
            icon-class="text-emerald-500"
          />
          <StatsCard
            label="Employees Processed"
            :value="stats?.total_records ?? 0"
            :icon="Building"
            icon-container-class="bg-indigo-50 dark:bg-indigo-900/30"
            icon-class="text-indigo-500"
          />
        </Deferred>

        <!-- Filters span 2 cols -->
        <div
          class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm"
        >
          <FilterBar :show-search="false" @reset="resetFilters">
            <Input
              v-model="filters.period"
              type="month"
              class="sm:max-w-[180px]"
            />
            <SelectFilter
              v-model="filters.organization_id"
              :options="organizationOptions"
              placeholder="All Units"
            />
            <SelectFilter
              v-model="filters.status"
              :options="statusOptions"
              placeholder="Any"
              class="w-full sm:max-w-[180px]"
            />
          </FilterBar>
        </div>
      </div>

      <DataTable
        :columns="columns"
        :data="tableData"
        :searchable="false"
        empty-message='No payroll records found. Use "Generate Payroll" to create records for a period.'
        :empty-icon="Banknote"
      >
        <template #employee="{ row }">
          <div class="font-medium text-zinc-900 dark:text-white">
            {{ row.employee?.first_name }} {{ row.employee?.last_name }}
          </div>
          <span class="block text-xs text-zinc-400">{{
            row.employee?.employee_code
          }}</span>
        </template>

        <template #basic_salary="{ value }">
          <span class="tabular-nums text-zinc-700 dark:text-zinc-300">{{
            formatCurrency(value)
          }}</span>
        </template>

        <template #bpjs_amount="{ value }">
          <span class="tabular-nums text-red-600 dark:text-red-400">{{
            formatCurrency(value)
          }}</span>
        </template>

        <template #tax_amount="{ value }">
          <span class="tabular-nums text-orange-600 dark:text-orange-400">{{
            formatCurrency(value)
          }}</span>
        </template>

        <template #net_salary="{ value }">
          <span
            class="tabular-nums font-semibold text-emerald-700 dark:text-emerald-400"
            >{{ formatCurrency(value) }}</span
          >
        </template>

        <template #status="{ value }">
          <StatusBadge :status="value" :variant="getStatusVariant(value)" />
        </template>

        <template #actions="{ row }">
          <a
            :href="`/payrolls/${row.id}/download-pdf`"
            target="_blank"
            class="text-sm font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
          >
            Download PDF
          </a>
        </template>
      </DataTable>
    </PageContainer>
  </AppLayout>
</template>
