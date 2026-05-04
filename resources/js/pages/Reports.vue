<script setup lang="ts">
import { Deferred, Head } from "@inertiajs/vue3";
import { BarChart3 } from "lucide-vue-next";
import { ref } from "vue";
import type { Report } from "@/components/Report/helpers";
import PageContainer from "@/components/PageContainer.vue";
import PayslipViewer from "@/components/Report/PayslipViewer.vue";
import ReportGeneratorForm from "@/components/Report/ReportGeneratorForm.vue";
import ReportList from "@/components/Report/ReportList.vue";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [{ title: "Reports", href: "/reports" }];

defineProps<{
  reports?: Report[];
}>();

const selectedReport = ref<Report | null>(null);
const showGeneratorModal = ref(false);
const showPayslipViewer = ref(false);
const payslipViewerData = ref({
  employeeId: 0,
  period: "",
  employeeName: "",
});

const openReportGenerator = (report: Report) => {
  selectedReport.value = report;
  showGeneratorModal.value = true;
};

const closeGeneratorModal = () => {
  showGeneratorModal.value = false;
  selectedReport.value = null;
};

const viewPayslip = (
  employeeId: number,
  period: string,
  employeeName?: string,
) => {
  payslipViewerData.value = {
    employeeId,
    period,
    employeeName: employeeName || `Employee #${employeeId}`,
  };
  showPayslipViewer.value = true;
};
</script>

<template>
  <Head title="Reports" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <PageContainer>
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-3"
          >
            <BarChart3 class="w-8 h-8" />
            Reports
          </h1>
          <p class="text-zinc-500 mt-1">
            Generate and download various reports
          </p>
        </div>
      </div>

      <Deferred data="reports">
        <template #fallback>
          <div aria-live="polite" class="sr-only">Memuat daftar laporan.</div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Skeleton
              v-for="card in 6"
              :key="card"
              class="h-48 rounded-lg border"
            />
          </div>
        </template>

        <ReportList
          :reports="reports"
          @select="openReportGenerator"
          @view-payslip="viewPayslip"
        />
      </Deferred>

      <Dialog v-model:open="showGeneratorModal">
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Generate Report</DialogTitle>
          </DialogHeader>

          <ReportGeneratorForm
            v-if="selectedReport"
            :report="selectedReport"
            @close="closeGeneratorModal"
          />
        </DialogContent>
      </Dialog>

      <PayslipViewer
        v-if="showPayslipViewer"
        :open="showPayslipViewer"
        :employee-id="payslipViewerData.employeeId"
        :period="payslipViewerData.period"
        :employee-name="payslipViewerData.employeeName"
        @close="showPayslipViewer = false"
      />
    </PageContainer>
  </AppLayout>
</template>
