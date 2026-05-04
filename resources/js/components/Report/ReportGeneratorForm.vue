<script setup lang="ts">
import { Calendar, Download, Loader2, X } from "lucide-vue-next";
import { ref, computed, watch } from "vue";
import {
  downloadBlob,
  generatePayslip,
  generatePayrollSummary,
  generatePayrollDetail,
  generateAttendanceReport,
  generateLeaveReport,
  generateCertificateCompliance,
  generateMcuCompliance,
} from "@/components/Report/helpers";
import type { Report, ReportFilter } from "@/components/Report/helpers";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

const props = defineProps<{
  report: Report;
}>();

const emit = defineEmits<{
  close: [];
}>();

const loading = ref(false);
const filters = ref<ReportFilter>({});
const selectedFormat = ref<"pdf" | "excel">(props.report.formats[0] || "pdf");

const currentMonth = new Date().toISOString().slice(0, 7);
const currentYear = new Date().getFullYear().toString();

watch(
  () => props.report,
  () => {
    filters.value = {};
    selectedFormat.value = props.report.formats[0] || "pdf";
  },
  { immediate: true },
);

const availableFormats = computed(() => {
  return props.report.formats;
});

const showPeriodFilter = computed(() => {
  return props.report.filters.includes("period");
});

const showDateRangeFilter = computed(() => {
  return props.report.filters.includes("date_from");
});

const showEmployeeFilter = computed(() => {
  return props.report.filters.includes("employee_id");
});

const showOrganizationFilter = computed(() => {
  return props.report.filters.includes("organization_id");
});

const showUnitFilter = computed(() => {
  return props.report.filters.includes("unit_id");
});

const canGenerate = computed(() => {
  if (showPeriodFilter.value && !filters.value.period) return false;
  if (
    showDateRangeFilter.value &&
    (!filters.value.date_from || !filters.value.date_to)
  )
    return false;
  if (showEmployeeFilter.value && !filters.value.employee_id) return false;
  return true;
});

const generateReport = async () => {
  if (!canGenerate.value || loading.value) return;

  loading.value = true;

  try {
    let blob: Blob;

    switch (props.report.id) {
      case "payslip":
        if (!filters.value.employee_id || !filters.value.period) {
          alert("Employee ID and Period are required for Payslip");
          return;
        }
        blob = await generatePayslip(
          filters.value.employee_id,
          filters.value.period,
          selectedFormat.value,
        );
        downloadBlob(
          blob,
          `payslip_${filters.value.period}_${filters.value.employee_id}.${selectedFormat.value}`,
        );
        break;

      case "payroll-summary":
        blob = await generatePayrollSummary({
          ...filters,
          format: selectedFormat.value,
        });
        downloadBlob(
          blob,
          `payroll_summary_${filters.value.period || "all"}.${selectedFormat.value}`,
        );
        break;

      case "payroll-detail":
        blob = await generatePayrollDetail({
          ...filters,
          format: selectedFormat.value,
        });
        downloadBlob(
          blob,
          `payroll_detail_${filters.value.period || "all"}.${selectedFormat.value}`,
        );
        break;

      case "attendance":
        blob = await generateAttendanceReport({
          ...filters,
          format: selectedFormat.value,
        });
        downloadBlob(
          blob,
          `attendance_${filters.value.date_from || "all"}_to_${filters.value.date_to || "all"}.${selectedFormat.value}`,
        );
        break;

      case "leave":
        blob = await generateLeaveReport({
          ...filters,
          format: selectedFormat.value,
        });
        downloadBlob(
          blob,
          `leave_${filters.value.date_from || "all"}_to_${filters.value.date_to || "all"}.${selectedFormat.value}`,
        );
        break;

      case "certificate-compliance":
        blob = await generateCertificateCompliance({
          ...filters,
          format: selectedFormat.value,
        });
        downloadBlob(
          blob,
          `certificate_compliance_${filters.value.date_from || "all"}_to_${filters.value.date_to || "all"}.${selectedFormat.value}`,
        );
        break;

      case "mcu-compliance":
        blob = await generateMcuCompliance({
          ...filters,
          format: selectedFormat.value,
        });
        downloadBlob(
          blob,
          `mcu_compliance_${filters.value.date_from || "all"}_to_${filters.value.date_to || "all"}.${selectedFormat.value}`,
        );
        break;

      default:
        alert("Unknown report type");
    }

    emit("close");
  } catch (error: any) {
    console.error("Error generating report:", error);
    alert(
      `Failed to generate report: ${error.response?.data?.message || error.message}`,
    );
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="space-y-6">
    <div class="space-y-2">
      <h3 class="text-xl font-semibold">{{ props.report.name }}</h3>
      <p class="text-sm text-muted-foreground">
        {{ props.report.description }}
      </p>
    </div>

    <form @submit.prevent="generateReport" class="space-y-6">
      <div v-if="showPeriodFilter" class="space-y-2">
        <label class="text-sm font-medium">Period</label>
        <Input
          v-model="filters.period"
          type="month"
          :placeholder="currentMonth"
          class="max-w-xs"
        />
      </div>

      <div
        v-if="showDateRangeFilter"
        class="grid grid-cols-1 md:grid-cols-2 gap-4"
      >
        <div class="space-y-2">
          <label class="text-sm font-medium">From Date</label>
          <Input v-model="filters.date_from" type="date" class="w-full" />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium">To Date</label>
          <Input v-model="filters.date_to" type="date" class="w-full" />
        </div>
      </div>

      <div v-if="showEmployeeFilter" class="space-y-2">
        <label class="text-sm font-medium">Employee ID</label>
        <Input
          v-model.number="filters.employee_id"
          type="number"
          placeholder="Enter employee ID"
          class="max-w-xs"
        />
      </div>

      <div v-if="showOrganizationFilter" class="space-y-2">
        <label class="text-sm font-medium">Organization ID</label>
        <Input
          v-model.number="filters.organization_id"
          type="number"
          placeholder="Enter organization ID (optional)"
          class="max-w-xs"
        />
      </div>

      <div v-if="showUnitFilter" class="space-y-2">
        <label class="text-sm font-medium">Unit ID</label>
        <Input
          v-model.number="filters.unit_id"
          type="number"
          placeholder="Enter unit ID (optional)"
          class="max-w-xs"
        />
      </div>

      <div v-if="availableFormats.length > 1" class="space-y-2">
        <label class="text-sm font-medium">Format</label>
        <div class="flex gap-3">
          <button
            v-for="format in availableFormats"
            :key="format"
            type="button"
            @click="selectedFormat = format"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-colors',
              selectedFormat === format
                ? 'bg-primary text-primary-foreground'
                : 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
            ]"
          >
            {{ format.toUpperCase() }}
          </button>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-4 border-t">
        <Button
          type="submit"
          :disabled="!canGenerate || loading"
          class="flex-1"
        >
          <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
          <Calendar v-else class="w-4 h-4 mr-2" />
          {{
            loading
              ? "Generating..."
              : `Generate ${selectedFormat.toUpperCase()}`
          }}
        </Button>

        <Button type="button" variant="outline" @click="emit('close')">
          Cancel
        </Button>
      </div>

      <div v-if="!canGenerate" class="text-sm text-muted-foreground">
        Please fill in all required filters to generate the report
      </div>
    </form>
  </div>
</template>
