<script setup lang="ts">
import { X, Download, FileText, Loader2 } from "lucide-vue-next";
import { ref, computed } from "vue";
import { generatePayslip, downloadBlob } from "@/components/Report/helpers";
import { Button } from "@/components/ui/button";

const props = defineProps<{
  open: boolean;
  employeeId: number;
  period: string;
  employeeName?: string;
}>();

const emit = defineEmits<{
  close: [];
}>();

const loading = ref(false);
const pdfUrl = ref<string | null>(null);

const loadPayslip = async () => {
  if (!props.employeeId || !props.period) return;

  loading.value = true;
  pdfUrl.value = null;

  try {
    const blob = await generatePayslip(props.employeeId, props.period, "pdf");
    pdfUrl.value = URL.createObjectURL(blob);
  } catch (error: any) {
    console.error("Error loading payslip:", error);
    alert(
      `Failed to load payslip: ${error.response?.data?.message || error.message}`,
    );
  } finally {
    loading.value = false;
  }
};

const downloadPayslip = () => {
  if (pdfUrl.value) {
    downloadBlob(
      new Blob([pdfUrl.value], { type: "application/pdf" }),
      `payslip_${props.period}_${props.employeeId}.pdf`,
    );
  }
};

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      loadPayslip();
    } else {
      if (pdfUrl.value) {
        URL.revokeObjectURL(pdfUrl.value);
        pdfUrl.value = null;
      }
    }
  },
  { immediate: true },
);
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    @click.self="emit('close')"
  >
    <div
      class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col"
    >
      <div
        class="flex items-center justify-between p-4 border-b dark:border-zinc-800"
      >
        <div>
          <h3 class="text-lg font-semibold">Payslip Preview</h3>
          <p class="text-sm text-muted-foreground">
            {{ employeeName || `Employee #${employeeId}` }} - {{ period }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            @click="downloadPayslip"
            :disabled="!pdfUrl || loading"
          >
            <Download class="w-4 h-4 mr-2" />
            Download
          </Button>
          <Button variant="ghost" size="sm" @click="emit('close')">
            <X class="w-4 h-4" />
          </Button>
        </div>
      </div>

      <div class="flex-1 overflow-auto p-4">
        <div v-if="loading" class="flex items-center justify-center h-full">
          <div class="text-center">
            <Loader2 class="w-8 h-8 animate-spin mx-auto mb-4" />
            <p class="text-sm text-muted-foreground">Loading payslip...</p>
          </div>
        </div>

        <div v-else-if="pdfUrl" class="h-full">
          <iframe
            :src="pdfUrl"
            class="w-full h-full border-0 rounded"
            type="application/pdf"
          ></iframe>
        </div>

        <div v-else class="flex items-center justify-center h-full">
          <div class="text-center">
            <FileText class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
            <p class="text-sm text-muted-foreground">Unable to load payslip</p>
            <Button
              variant="outline"
              size="sm"
              class="mt-4"
              @click="loadPayslip"
            >
              Retry
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
