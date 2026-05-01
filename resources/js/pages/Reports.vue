<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { BarChart3 } from 'lucide-vue-next'
import { ref } from 'vue'
import type {Report} from '@/components/Report/helpers';
import PayslipViewer from '@/components/Report/PayslipViewer.vue'
import ReportGeneratorForm from '@/components/Report/ReportGeneratorForm.vue'
import ReportList from '@/components/Report/ReportList.vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Reports', href: '/reports' },
]

const selectedReport = ref<Report | null>(null)
const showGeneratorModal = ref(false)
const showPayslipViewer = ref(false)
const payslipViewerData = ref({
  employeeId: 0,
  period: '',
  employeeName: ''
})

const openReportGenerator = (report: Report) => {
  selectedReport.value = report
  showGeneratorModal.value = true
}

const closeGeneratorModal = () => {
  showGeneratorModal.value = false
  selectedReport.value = null
}

const viewPayslip = (employeeId: number, period: string, employeeName?: string) => {
  payslipViewerData.value = {
    employeeId,
    period,
    employeeName: employeeName || `Employee #${employeeId}`
  }
  showPayslipViewer.value = true
}
</script>

<template>
  <Head title="Reports" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-3">
            <BarChart3 class="w-8 h-8" />
            Reports
          </h1>
          <p class="text-zinc-500 mt-1">Generate and download various reports</p>
        </div>
      </div>

      <ReportList @select="openReportGenerator" @view-payslip="viewPayslip" />

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
      
    </div>
  </AppLayout>
</template>
