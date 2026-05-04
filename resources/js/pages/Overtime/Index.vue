<script setup lang="ts">
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { Clock, CheckCircle, XCircle, FileText, Plus } from "lucide-vue-next";
import { computed, ref } from "vue";
import { Button } from "@/components/ui/button";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import FilterBar from "@/components/FilterBar.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { useTableFilters } from "@/composables/useTableFilters";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  overtimeRequests: any;
  filters: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "HR Management", href: "#" },
  { title: "Overtime Requests", href: "/overtime" },
];

const rejectionModal = ref(false);
const selectedRequest = ref<any>(null);
const approveDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const approveTargetId = ref<string | null>(null);
const deleteTargetId = ref<string | null>(null);
const rejectionForm = useForm({
  rejection_reason: "",
});

const filters = ref({
  status: props.filters.status || "",
});
const statusOptions = [
  { label: "All Statuses", value: "" },
  { label: "Pending", value: "PENDING" },
  { label: "Approved", value: "APPROVED" },
  { label: "Rejected", value: "REJECTED" },
];

const approve = (id: string) => {
  router.post(
    `/overtime/${id}/approve`,
    {},
    {
      onFinish: () => {
        approveDialogOpen.value = false;
        approveTargetId.value = null;
      },
    },
  );
};

const openApproveDialog = (id: string) => {
  approveTargetId.value = id;
  approveDialogOpen.value = true;
};

const openRejectModal = (request: any) => {
  selectedRequest.value = request;
  rejectionForm.reset();
  rejectionModal.value = true;
};

const reject = () => {
  rejectionForm.post(`/overtime/${selectedRequest.value.id}/reject`, {
    onSuccess: () => {
      rejectionModal.value = false;
      selectedRequest.value = null;
    },
  });
};

const deleteRequest = (id: string) => {
  deleteTargetId.value = id;
  deleteDialogOpen.value = true;
};

const confirmDeleteRequest = (): void => {
  if (!deleteTargetId.value) {
    return;
  }

  router.delete(`/overtime/${deleteTargetId.value}`, {
    onFinish: () => {
      deleteDialogOpen.value = false;
      deleteTargetId.value = null;
    },
  });
};

const hariIni = new Date();
const hariIniStr = `${hariIni.getFullYear()}-${String(hariIni.getMonth() + 1).padStart(2, "0")}-${String(hariIni.getDate()).padStart(2, "0")}`;

const formatTanggal = (dateStr: string) => {
  if (!dateStr) return "-";
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  const hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"][
    d.getDay()
  ];
  const bulan = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "Mei",
    "Jun",
    "Jul",
    "Agu",
    "Sep",
    "Okt",
    "Nov",
    "Des",
  ][d.getMonth()];
  const tgl = d.getDate();
  const yr = d.getFullYear();
  const onlyDate = dateStr.substring(0, 10);
  const suffix = onlyDate === hariIniStr ? " (Hari ini)" : "";
  return `${hari}, ${tgl} ${bulan} ${yr}${suffix}`;
};

const formatJam = (time: string) => {
  if (!time) return "-";
  let hour: number;
  let minute: string;
  if (time.includes("T") || time.includes("Z")) {
    const d = new Date(time);
    if (isNaN(d.getTime())) return time;
    hour = d.getHours();
    minute = String(d.getMinutes()).padStart(2, "0");
  } else if (time.includes(":")) {
    const parts = time.split(":");
    hour = parseInt(parts[0]);
    minute = parts[1];
  } else {
    return time;
  }
  const ampm = hour >= 12 ? "siang" : "pagi";
  const display = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
  return `${display}:${minute} ${ampm}`;
};

const formatDurasi = (hours: any) => {
  const n = parseFloat(hours);
  if (isNaN(n) || n === 0) return "-";
  const h = Math.floor(n);
  const m = Math.round((n - h) * 60);
  if (h === 0) return `${m} menit`;
  if (m === 0) return `${h} jam`;
  return `${h} jam ${m} menit`;
};

const formatStatus = (status: string) => {
  switch (status) {
    case "APPROVED":
      return "Disetujui";
    case "REJECTED":
      return "Ditolak";
    case "PENDING":
      return "Menunggu";
    default:
      return status;
  }
};

const { resetFilters } = useTableFilters(filters, {
  route: "/overtime",
});

const tableData = computed(() => {
  if (props.overtimeRequests?.meta) {
    return {
      ...props.overtimeRequests.meta,
      data: props.overtimeRequests.data ?? [],
      links: props.overtimeRequests.links ?? [],
    };
  }

  return props.overtimeRequests;
});

const columns = [
  { header: "Employee", key: "employee.first_name", slot: "employee" },
  { header: "Date & Time", key: "date", slot: "date_time" },
  { header: "Hours", key: "total_hours", slot: "hours" },
  { header: "Reason", key: "reason", slot: "reason" },
  { header: "Evidence", key: "evidence_path", slot: "evidence" },
  { header: "Status", key: "status", slot: "status" },
  {
    header: "Actions",
    key: "actions",
    slot: "actions",
    align: "right" as const,
  },
];
</script>

<template>
  <Head title="Overtime Requests" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-6 w-full">
      <div
        class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
      >
        <div>
          <h1
            class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2"
          >
            <Clock class="h-6 w-6 text-indigo-600" />
            Overtime Requests
          </h1>
          <p class="text-sm text-zinc-500 mt-1">
            Manage employee overtime submissions and approvals
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Button as-child>
            <Link href="/overtime/create">
              <Plus class="h-4 w-4 mr-2" />
              New Request
            </Link>
          </Button>
        </div>
      </div>

      <FilterBar :show-search="false" @reset="resetFilters">
        <SelectFilter
          v-model="filters.status"
          :options="statusOptions"
          placeholder="All Statuses"
          class="w-full sm:max-w-[180px]"
        />
      </FilterBar>

      <DataTable
        :columns="columns"
        :data="tableData"
        :searchable="false"
        empty-message="No overtime requests found."
        :empty-icon="Clock"
      >
        <template #employee="{ row }">
          <div class="font-medium text-zinc-900 dark:text-white">
            {{ row.employee?.first_name }} {{ row.employee?.last_name }}
          </div>
          <div class="text-xs text-zinc-500">
            {{ row.employee?.employee_code }}
          </div>
        </template>

        <template #date_time="{ row }">
          <div class="font-medium text-zinc-900 dark:text-white">
            {{ formatTanggal(row.date) }}
          </div>
          <div class="mt-0.5 text-xs text-zinc-500">
            {{ formatJam(row.start_time) }} - {{ formatJam(row.end_time) }}
          </div>
        </template>

        <template #hours="{ row }">
          <span class="font-medium text-zinc-900 dark:text-white">
            {{ formatDurasi(row.total_hours) }}
          </span>
        </template>

        <template #reason="{ value }">
          <div class="max-w-xs truncate" :title="value">
            {{ value || "-" }}
          </div>
        </template>

        <template #evidence="{ value }">
          <a
            v-if="value"
            :href="`/storage/${value}`"
            target="_blank"
            class="flex items-center text-indigo-600 hover:underline"
          >
            <FileText class="mr-1 h-4 w-4" /> View
          </a>
          <span v-else class="text-zinc-400">-</span>
        </template>

        <template #status="{ value }">
          <StatusBadge :status="value" :label="formatStatus(value)" />
        </template>

        <template #actions="{ row }">
          <div class="flex justify-end gap-2">
            <template v-if="row.status === 'PENDING'">
              <Button
                variant="outline"
                size="sm"
                class="h-8 w-8 p-0 text-green-600 hover:bg-green-50 hover:text-green-700"
                title="Approve"
                @click="openApproveDialog(row.id)"
              >
                <CheckCircle class="h-4 w-4" />
              </Button>
              <Button
                variant="outline"
                size="sm"
                class="h-8 w-8 p-0 text-red-600 hover:bg-red-50 hover:text-red-700"
                title="Reject"
                @click="openRejectModal(row)"
              >
                <XCircle class="h-4 w-4" />
              </Button>
            </template>
            <Button
              v-if="row.status !== 'APPROVED'"
              variant="ghost"
              size="sm"
              class="h-8 w-8 p-0 text-zinc-400 hover:text-red-600"
              title="Delete"
              @click="deleteRequest(row.id)"
            >
              <span class="sr-only">Delete</span>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="lucide lucide-trash-2"
              >
                <path d="M3 6h18" />
                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                <line x1="10" x2="10" y1="11" y2="17" />
                <line x1="14" x2="14" y1="11" y2="17" />
              </svg>
            </Button>
          </div>
        </template>
      </DataTable>

      <!-- Rejection Modal -->
      <Dialog v-model:open="rejectionModal">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Reject Overtime Request</DialogTitle>
          </DialogHeader>
          <form @submit.prevent="reject" class="space-y-4 mt-2">
            <div class="grid gap-2">
              <Label>Reason for Rejection</Label>
              <textarea
                v-model="rejectionForm.rejection_reason"
                class="flex min-h-[80px] w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white placeholder:text-zinc-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 dark:focus-visible:ring-zinc-300"
                required
                placeholder="Please explain why this request is rejected..."
              ></textarea>
              <span
                v-if="rejectionForm.errors.rejection_reason"
                class="text-xs text-red-500"
                >{{ rejectionForm.errors.rejection_reason }}</span
              >
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <Button
                type="button"
                variant="outline"
                @click="rejectionModal = false"
                >Cancel</Button
              >
              <Button
                type="submit"
                variant="destructive"
                :disabled="rejectionForm.processing"
                >Reject Request</Button
              >
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        v-model:open="approveDialogOpen"
        title="Setujui lembur"
        message="Apakah Anda yakin ingin menyetujui permintaan lembur ini?"
        confirm-label="Setujui"
        @confirm="approve(approveTargetId!)"
      />

      <ConfirmDialog
        v-model:open="deleteDialogOpen"
        variant="danger"
        title="Hapus permintaan lembur"
        message="Apakah Anda yakin ingin menghapus permintaan lembur ini?"
        confirm-label="Hapus"
        @confirm="confirmDeleteRequest"
      />
    </div>
  </AppLayout>
</template>
