<script setup lang="ts">
import { Download, Eye, RefreshCw, ScrollText } from "lucide-vue-next";
import { computed, onMounted, ref } from "vue";
import { auditLogsApi } from "@/Actions/auditLogs";
import type { AuditLog, AuditLogFilters } from "@/Actions/auditLogs";
import FilterBar from "@/components/FilterBar.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import { Input } from "@/components/ui/input";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { formatDateTime } from "@/lib/formatters";
import AuditLogDetail from "./AuditLogDetail.vue";

const logs = ref<AuditLog[]>([]);
const loading = ref(false);
const selectedLog = ref<AuditLog | null>(null);
const detailOpen = ref(false);

const filters = ref<AuditLogFilters>({
  per_page: 15,
  page: 1,
});

const fetchLogs = async () => {
  loading.value = true;
  try {
    const response = await auditLogsApi.list(filters.value);
    logs.value = response.data;
  } catch (error) {
    console.error("Failed to fetch audit logs:", error);
  } finally {
    loading.value = false;
  }
};

const viewDetail = (log: AuditLog) => {
  selectedLog.value = log;
  detailOpen.value = true;
};

const applyFilters = () => {
  filters.value.page = 1;
  fetchLogs();
};

const resetFilters = () => {
  filters.value = {
    per_page: 15,
    page: 1,
  };
  fetchLogs();
};

const exportLogs = async () => {
  try {
    const response = await auditLogsApi.export(filters.value);
    const dataStr = JSON.stringify(response.data, null, 2);
    const dataBlob = new Blob([dataStr], { type: "application/json" });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `audit-logs-${new Date().toISOString()}.json`;
    link.click();
    URL.revokeObjectURL(url);
  } catch (error) {
    console.error("Failed to export logs:", error);
  }
};

const modules = [
  { value: "employees", label: "Employees" },
  { value: "payrolls", label: "Payrolls" },
  { value: "invoices", label: "Invoices" },
  { value: "auth", label: "Authentication" },
  { value: "employeeCertificates", label: "Certificates" },
  { value: "medicalCheckups", label: "Medical Checkups" },
];

const actions = [
  { value: "CREATE", label: "Create" },
  { value: "UPDATE", label: "Update" },
  { value: "DELETE", label: "Delete" },
  { value: "LOGIN", label: "Login" },
  { value: "LOGOUT", label: "Logout" },
  { value: "FAILED_LOGIN", label: "Failed Login" },
];

const getActionVariant = (
  action: string,
): "success" | "info" | "destructive" | "secondary" | "warning" => {
  switch (action) {
    case "CREATE":
      return "success";
    case "UPDATE":
      return "info";
    case "DELETE":
      return "destructive";
    case "LOGIN":
      return "secondary";
    case "FAILED_LOGIN":
      return "warning";
    default:
      return "secondary";
  }
};

const formatModuleLabel = (module: string): string => {
  return module.replace(/_/g, " ");
};

const columns = [
  { header: "Timestamp", key: "created_at", slot: "timestamp" },
  { header: "User", key: "user.name", slot: "user" },
  { header: "Action", key: "action", slot: "action" },
  { header: "Module", key: "module", slot: "module" },
  { header: "Subject", key: "subject_type", slot: "subject" },
  { header: "IP Address", key: "ip_address", slot: "ip_address" },
  { header: "Actions", key: "actions", slot: "actions" },
];

const hasActiveFilters = computed(() => {
  return Boolean(
    filters.value.module ||
    filters.value.action ||
    filters.value.date_from ||
    filters.value.date_to,
  );
});

onMounted(() => {
  fetchLogs();
});
</script>

<template>
  <div class="space-y-6">
    <div
      class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="space-y-1">
        <h1
          class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
        >
          Audit Logs
        </h1>
        <p class="text-muted-foreground text-sm">
          View and track all system activities and changes
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <Button variant="outline" size="sm" @click="exportLogs">
          <Download class="mr-2 h-4 w-4" />
          Export
        </Button>
        <Button size="sm" @click="fetchLogs">
          <RefreshCw class="mr-2 h-4 w-4" />
          Refresh
        </Button>
      </div>
    </div>

    <FilterBar
      :show-search="false"
      :show-apply="true"
      :show-reset="hasActiveFilters"
      @submit="applyFilters"
      @reset="resetFilters"
    >
      <SelectFilter
        v-model="filters.module"
        :options="[{ value: '', label: 'All Modules' }, ...modules]"
        placeholder="All Modules"
        class="w-full sm:max-w-[220px]"
      />

      <SelectFilter
        v-model="filters.action"
        :options="[{ value: '', label: 'All Actions' }, ...actions]"
        placeholder="All Actions"
        class="w-full sm:max-w-[180px]"
      />

      <Input v-model="filters.date_from" type="date" class="sm:max-w-[180px]" />
      <Input v-model="filters.date_to" type="date" class="sm:max-w-[180px]" />
    </FilterBar>

    <DataTable
      :columns="columns"
      :data="logs"
      :loading="loading"
      :searchable="false"
      empty-message="No audit logs found."
      :empty-icon="ScrollText"
    >
      <template #timestamp="{ value }">
        {{ formatDateTime(value) }}
      </template>

      <template #user="{ value }">
        {{ value || "System" }}
      </template>

      <template #action="{ value }">
        <StatusBadge :status="value" :variant="getActionVariant(value)" />
      </template>

      <template #module="{ value }">
        <span class="capitalize">{{ formatModuleLabel(value) }}</span>
      </template>

      <template #subject="{ row }">
        <span v-if="row.subject_type">
          {{ row.subject_type.split("\\").pop() }} #{{ row.subject_id }}
        </span>
        <span v-else class="text-muted-foreground">N/A</span>
      </template>

      <template #ip_address="{ value }">
        <span class="font-mono text-xs">{{ value || "N/A" }}</span>
      </template>

      <template #actions="{ row }">
        <Button variant="ghost" size="sm" @click="viewDetail(row)">
          <Eye class="mr-1 h-4 w-4" />
          View
        </Button>
      </template>
    </DataTable>
    <AuditLogDetail
      v-if="selectedLog"
      v-model:open="detailOpen"
      :log="selectedLog"
    />
  </div>
</template>
