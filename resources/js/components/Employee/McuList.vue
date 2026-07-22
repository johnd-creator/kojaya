<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import {
  Activity,
  Heart,
  AlertCircle,
  Calendar,
  Plus,
  Upload,
  Trash2,
  Pencil,
  File as FileIcon,
} from "lucide-vue-next";
import { computed, onMounted, ref } from "vue";
import { fetchMcuRecords, deleteMcu } from "@/api/medicalCheckups";
import McuBadge from "@/components/Status/McuBadge.vue";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import type { MedicalCheckup, McuResult } from "@/types";
import ConfirmDialog from "@/components/ConfirmDialog.vue";

type Props = {
  employeeId: string;
  readonly?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
  readonly: false,
});

const mcuRecords = ref<MedicalCheckup[]>([]);
const loading = ref(false);
const deletingId = ref<string | null>(null);
const confirmDeleteId = ref<string | null>(null);
const deleteDialogOpen = computed({
  get: () => confirmDeleteId.value !== null,
  set: (open: boolean) => {
    if (!open) confirmDeleteId.value = null;
  },
});
const browserWindow = globalThis.window;

const hasRecords = computed(() => mcuRecords.value.length > 0);

const groupedRecords = computed(() => {
  return {
    fit: mcuRecords.value.filter((m) => m.result === "FIT"),
    fitWithRestriction: mcuRecords.value.filter(
      (m) => m.result === "FIT_WITH_RESTRICTION",
    ),
    unfit: mcuRecords.value.filter((m) => m.result === "UNFIT"),
  };
});

const fitCount = computed(() => groupedRecords.value.fit.length);
const unfitCount = computed(() => groupedRecords.value.unfit.length);

const isOverdue = (mcu: MedicalCheckup): boolean => {
  if (!mcu.next_checkup_date) return false;
  const nextDate = new Date(mcu.next_checkup_date);
  const today = new Date();
  return nextDate < today;
};

const formatDate = (date: string): string => {
  const d = new Date(date);
  return d.toLocaleDateString("en-GB", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
};

const getDaysUntilNext = (date: string | null): string => {
  if (!date) return "Not set";
  const nextDate = new Date(date);
  const today = new Date();
  const daysUntilNext = Math.ceil(
    (nextDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24),
  );

  if (daysUntilNext < 0) {
    return `${Math.abs(daysUntilNext)} days overdue`;
  } else if (daysUntilNext === 0) {
    return "Due today";
  } else if (daysUntilNext <= 30) {
    return `${daysUntilNext} days left`;
  } else {
    return `${daysUntilNext} days left`;
  }
};

const fitToWorkLabel = (fit: boolean): string => {
  return fit ? "Yes" : "No";
};

const fetchList = async () => {
  loading.value = true;
  try {
    const response = await fetchMcuRecords(props.employeeId);
    mcuRecords.value = response.data;
  } catch (error) {
    console.error("Failed to fetch MCU records:", error);
  } finally {
    loading.value = false;
  }
};

const handleDelete = async (id: string) => {
  deletingId.value = id;
  try {
    await deleteMcu(props.employeeId, id);
    mcuRecords.value = mcuRecords.value.filter((m) => m.id !== id);
  } catch (error) {
    console.error("Failed to delete MCU record:", error);
  } finally {
    deletingId.value = null;
  }
};

const openCreate = () => {
  router.visit(`/employees/${props.employeeId}/mcu/create`);
};

const openEdit = (id: string) => {
  router.visit(`/employees/${props.employeeId}/mcu/${id}/edit`);
};

const getDocumentUrl = (path: string | null): string | null => {
  if (!path) return null;
  return `/storage/${path}`;
};

onMounted(() => {
  fetchList();
});
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h3
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-100"
        >
          Medical Check-ups
        </h3>
        <p class="text-sm text-neutral-500 dark:text-neutral-400">
          {{ fitCount }} fit, {{ unfitCount }} need attention
        </p>
      </div>

      <Button v-if="!readonly" variant="outline" size="sm" @click="openCreate">
        <Plus class="mr-2 h-4 w-4" />
        Add MCU Record
      </Button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-12">
      <div
        class="h-8 w-8 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-neutral-100"
      />
    </div>

    <div v-else-if="!hasRecords" class="py-12 text-center">
      <Activity
        class="mx-auto mb-4 h-16 w-16 text-neutral-300 dark:text-neutral-700"
      />
      <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
        No MCU records yet
      </h3>
      <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
        Add medical check-up records to track employee health status
      </p>
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="mcu in mcuRecords"
        :key="mcu.id"
        class="flex items-center justify-between rounded-lg border border-neutral-200 bg-white p-4 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800"
      >
        <div class="flex items-center gap-4">
          <div
            class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900"
          >
            <Heart class="h-5 w-5 text-green-600 dark:text-green-400" />
          </div>

          <div>
            <p class="font-medium text-neutral-900 dark:text-neutral-100">
              {{ formatDate(mcu.checkup_date) }}
            </p>
            <div
              class="mt-1 flex items-center gap-2 text-xs text-neutral-500 dark:text-neutral-500"
            >
              <span>{{ mcu.doctor_name || "Doctor not specified" }}</span>
              <span>•</span>
              <span>{{ mcu.clinic_name || "Clinic not specified" }}</span>
            </div>
            <div class="mt-1 text-xs">
              <span class="text-neutral-500">Next MCU: </span>
              <span
                :class="[
                  isOverdue(mcu)
                    ? 'text-red-600 font-medium'
                    : 'text-neutral-700',
                ]"
              >
                {{
                  mcu.next_checkup_date
                    ? formatDate(mcu.next_checkup_date)
                    : "Not set"
                }}
                <template v-if="mcu.next_checkup_date">
                  ({{ getDaysUntilNext(mcu.next_checkup_date) }})
                </template>
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <McuBadge :result="mcu.result" size="sm" />

          <div class="flex items-center gap-1">
            <span
              class="text-xs"
              :class="[
                mcu.fit_to_work
                  ? 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-950'
                  : 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-950',
              ]"
            >
              {{ fitToWorkLabel(mcu.fit_to_work) }}
            </span>
          </div>

          <DropdownMenu v-if="!readonly">
            <DropdownMenuTrigger :as-child="true">
              <Button variant="ghost" size="icon" class="h-8 w-8">
                <span class="sr-only">Actions</span>
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
                  class="lucide lucide-more-horizontal"
                >
                  <circle cx="12" cy="12" r="1" />
                  <circle cx="19" cy="12" r="1" />
                  <circle cx="5" cy="12" r="1" />
                </svg>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem @click="openEdit(mcu.id)">
                <Pencil class="mr-2 h-4 w-4" />
                Edit
              </DropdownMenuItem>
              <DropdownMenuItem
                v-if="mcu.document_path"
                @click="browserWindow.open(getDocumentUrl(mcu.document_path) ?? '', '_blank')"
              >
                <FileIcon class="mr-2 h-4 w-4" />
                View Document
              </DropdownMenuItem>
              <DropdownMenuItem
                class="text-red-600 focus:text-red-600"
                @click="confirmDeleteId = mcu.id"
              >
                <Trash2 class="mr-2 h-4 w-4" />
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </div>
    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      title="Delete MCU Record?"
      message="Are you sure you want to delete this MCU record? This action cannot be undone."
      confirm-label="Delete"
      variant="danger"
      @confirm="confirmDeleteId && handleDelete(confirmDeleteId)"
      @cancel="confirmDeleteId = null"
    />
  </div>
</template>
