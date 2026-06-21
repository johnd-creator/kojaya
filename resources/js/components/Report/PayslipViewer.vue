<script setup lang="ts">
import { Download, FileText } from "lucide-vue-next";
import { ref, watch } from "vue";
import { generatePayslip, downloadBlob } from "@/components/Report/helpers";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogTitle,
} from "@/components/ui/dialog";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";

const props = defineProps<{
  open: boolean;
  employeeId: number;
  period: string;
  employeeName?: string;
}>();

const emit = defineEmits<{
  close: [];
}>();

const dialogOpen = ref(false);

watch(
  () => props.open,
  (val) => {
    dialogOpen.value = val;
  },
  { immediate: true },
);

watch(dialogOpen, (val) => {
  if (!val) {
    emit("close");
  }
});

const loading = ref(false);
const errorMessage = ref("");
const pdfUrl = ref<string | null>(null);

const loadPayslip = async () => {
  if (!props.employeeId || !props.period) return;

  loading.value = true;
  errorMessage.value = "";
  pdfUrl.value = null;

  try {
    const blob = await generatePayslip(props.employeeId, props.period, "pdf");
    pdfUrl.value = URL.createObjectURL(blob);
  } catch (error: any) {
    console.error("Error loading payslip:", error);
    errorMessage.value =
      error.response?.data?.message || error.message || "Gagal memuat payslip";
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
  <Dialog v-model:open="dialogOpen">
    <DialogContent
      class="sm:max-w-4xl max-h-[90vh] flex flex-col p-0 gap-0"
      aria-label="Pratinjau payslip"
    >
      <div
        class="flex items-center justify-between p-4 border-b dark:border-zinc-800"
      >
        <div>
          <DialogTitle class="text-lg font-semibold">Pratinjau Payslip</DialogTitle>
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
          <DialogClose as-child>
            <Button variant="ghost" size="sm" aria-label="Tutup pratinjau payslip">
              <span aria-hidden="true">&times;</span>
            </Button>
          </DialogClose>
        </div>
      </div>

      <div class="flex-1 overflow-auto p-4 min-h-[400px]">
        <div v-if="loading" aria-live="polite" class="h-full">
          <span class="sr-only">Memuat payslip.</span>
          <div class="flex h-full flex-col gap-4 rounded-lg border bg-zinc-50 p-6 dark:bg-zinc-800/50">
            <Skeleton class="h-6 w-48 rounded-md" />
            <Skeleton class="h-4 w-32 rounded-md" />
            <div class="mt-4 flex-1 space-y-3">
              <Skeleton v-for="n in 8" :key="n" class="h-4 w-full rounded-md" />
            </div>
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
            <p role="alert" class="text-sm text-muted-foreground">{{ errorMessage || 'Gagal memuat payslip' }}</p>
            <Button
              variant="outline"
              size="sm"
              class="mt-4"
              @click="loadPayslip"
            >
              Coba Lagi
            </Button>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
