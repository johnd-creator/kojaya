<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import {
  AlertTriangle,
  FileText,
  Activity,
  Download,
  Filter,
  RefreshCw,
} from "lucide-vue-next";
import { computed, onMounted, ref } from "vue";
import {
  fetchNonCompliantEmployees,
  exportComplianceReport,
} from "@/api/compliance.ts";
import CertificateBadge from "@/components/Status/CertificateBadge.vue";
import McuBadge from "@/components/Status/McuBadge.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

type NonCompliantEmployee = {
  id: string;
  name: string;
  department?: string;
  certificate_issues?: string[];
  mcu_issues?: string[];
};

type FilterType = "all" | "certificate" | "mcu";

const filters = ref<{
  type: FilterType;
  search: string;
}>({
  type: "all",
  search: "",
});

const loading = ref(false);
const exporting = ref(false);
const employees = ref<NonCompliantEmployee[]>([]);
const total = ref(0);

const filteredEmployees = computed(() => {
  let result = employees.value;

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(
      (emp) =>
        emp.name.toLowerCase().includes(search) ||
        emp.department?.toLowerCase().includes(search),
    );
  }

  if (filters.value.type !== "all") {
    const issueKey =
      filters.value.type === "certificate"
        ? "certificate_issues"
        : "mcu_issues";
    result = result.filter((emp) => emp[issueKey] && emp[issueKey].length > 0);
  }

  return result;
});

const fetchEmployees = async () => {
  loading.value = true;
  try {
    const response = await fetchNonCompliantEmployees({
      type: filters.value.type === "all" ? undefined : filters.value.type,
    });
    employees.value = response.data;
    total.value = response.total;
  } catch (error) {
    console.error("Failed to fetch non-compliant employees:", error);
  } finally {
    loading.value = false;
  }
};

const handleExport = async (type: "certificate" | "mcu") => {
  exporting.value = true;
  try {
    await exportComplianceReport(type);
  } catch (error) {
    console.error("Failed to export report:", error);
  } finally {
    exporting.value = false;
  }
};

const openEmployee = (employeeId: string) => {
  router.visit(`/employees/${employeeId}`);
};

const openEmployeeCertificates = (employeeId: string) => {
  router.visit(`/employees/${employeeId}/certificates`);
};

const openEmployeeMcu = (employeeId: string) => {
  router.visit(`/employees/${employeeId}/mcu`);
};

const hasIssues = (employee: NonCompliantEmployee): boolean => {
  const certIssues =
    employee.certificate_issues && employee.certificate_issues.length > 0;
  const mcuIssues = employee.mcu_issues && employee.mcu_issues.length > 0;
  return certIssues || mcuIssues;
};

onMounted(() => {
  fetchEmployees();
});
</script>

<template>
  <div class="space-y-4">
    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Non-Compliant Employees</CardTitle>
          <div class="flex items-center gap-2">
            <div class="flex items-center gap-2">
              <Select
                v-model="filters.type"
                @update:model-value="fetchEmployees"
              >
                <SelectTrigger class="w-[180px]">
                  <SelectValue placeholder="Filter by type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Issues</SelectItem>
                  <SelectItem value="certificate">Certificates Only</SelectItem>
                  <SelectItem value="mcu">MCU Only</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Input
              v-model="filters.search"
              placeholder="Search employees..."
              class="w-[200px]"
              @update:model-value="fetchEmployees"
            >
              <template #prefix>
                <Filter class="h-4 w-4 text-neutral-400" />
              </template>
            </Input>

            <Button
              variant="outline"
              size="sm"
              :disabled="loading"
              @click="fetchEmployees"
            >
              <RefreshCw
                :class="{ 'animate-spin': loading }"
                class="mr-2 h-4 w-4"
              />
              Refresh
            </Button>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div
            class="h-8 w-8 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-neutral-100"
          />
        </div>

        <div
          v-else-if="filteredEmployees.length === 0"
          class="py-12 text-center"
        >
          <AlertTriangle
            class="mx-auto mb-4 h-16 w-16 text-neutral-300 dark:text-neutral-700"
          />
          <h3
            class="text-lg font-semibold text-neutral-900 dark:text-neutral-100"
          >
            No non-compliant employees found
          </h3>
          <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            All employees are compliant! 🎉
          </p>
        </div>

        <div v-else class="space-y-2">
          <div
            class="rounded-lg border border-neutral-200 dark:border-neutral-800"
          >
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr
                    class="border-b border-neutral-200 dark:border-neutral-800"
                  >
                    <th
                      class="px-4 py-3 text-left font-medium text-neutral-700 dark:text-neutral-300"
                    >
                      Employee
                    </th>
                    <th
                      class="px-4 py-3 text-left font-medium text-neutral-700 dark:text-neutral-300"
                    >
                      Department
                    </th>
                    <th
                      class="px-4 py-3 text-left font-medium text-neutral-700 dark:text-neutral-300"
                    >
                      Certificate Issues
                    </th>
                    <th
                      class="px-4 py-3 text-left font-medium text-neutral-700 dark:text-neutral-300"
                    >
                      MCU Issues
                    </th>
                    <th
                      class="px-4 py-3 text-right font-medium text-neutral-700 dark:text-neutral-300"
                    >
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="employee in filteredEmployees"
                    :key="employee.id"
                    class="border-b border-neutral-200 dark:border-neutral-800 hover:bg-neutral-50 dark:hover:bg-neutral-800"
                  >
                    <td class="px-4 py-3">
                      <div>
                        <div
                          class="font-medium text-neutral-900 dark:text-neutral-100"
                        >
                          {{ employee.name }}
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3">
                      <div class="text-neutral-600 dark:text-neutral-400">
                        {{ employee.department || "-" }}
                      </div>
                    </td>
                    <td class="px-4 py-3">
                      <div
                        v-if="
                          employee.certificate_issues &&
                          employee.certificate_issues.length > 0
                        "
                        class="space-y-1"
                      >
                        <Badge
                          v-for="(
                            issue, index
                          ) in employee.certificate_issues.slice(0, 2)"
                          :key="`${employee.id}-cert-${index}`"
                          variant="destructive"
                        >
                          {{ issue }}
                        </Badge>
                        <span
                          v-if="employee.certificate_issues.length > 2"
                          class="text-xs text-neutral-500"
                        >
                          +{{ employee.certificate_issues.length - 2 }} more
                        </span>
                      </div>
                      <span v-else class="text-neutral-400">-</span>
                    </td>
                    <td class="px-4 py-3">
                      <div
                        v-if="
                          employee.mcu_issues && employee.mcu_issues.length > 0
                        "
                        class="space-y-1"
                      >
                        <Badge
                          v-for="(issue, index) in employee.mcu_issues.slice(
                            0,
                            2,
                          )"
                          :key="`${employee.id}-mcu-${index}`"
                          variant="destructive"
                        >
                          {{ issue }}
                        </Badge>
                        <span
                          v-if="employee.mcu_issues.length > 2"
                          class="text-xs text-neutral-500"
                        >
                          +{{ employee.mcu_issues.length - 2 }} more
                        </span>
                      </div>
                      <span v-else class="text-neutral-400">-</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          @click="openEmployeeCertificates(employee.id)"
                        >
                          <FileText class="h-3.5 w-3.5 mr-1" />
                          Certificates
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          @click="openEmployeeMcu(employee.id)"
                        >
                          <Activity class="h-3.5 w-3.5 mr-1" />
                          MCU
                        </Button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div
            class="flex items-center justify-between px-4 py-3 border-t border-neutral-200 dark:border-neutral-800"
          >
            <span class="text-sm text-neutral-500 dark:text-neutral-400">
              Showing {{ filteredEmployees.length }} of {{ total }} employees
            </span>
            <Button
              variant="outline"
              size="sm"
              :disabled="exporting"
              @click="handleExport('certificate')"
            >
              <Download class="mr-2 h-4 w-4" />
              Export Certificates
            </Button>
            <Button
              variant="outline"
              size="sm"
              :disabled="exporting"
              @click="handleExport('mcu')"
            >
              <Download class="mr-2 h-4 w-4" />
              Export MCU
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
