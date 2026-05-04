<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import {
  ArrowLeft,
  CheckCircle2,
  Circle,
  ClipboardList,
  User,
  Wrench,
} from "lucide-vue-next";
import { computed } from "vue";
import { Button } from "@/components/ui/button";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

interface WorkOrderChecklist {
  id: string;
  work_order_id: string;
  item_name: string;
  item_description: string | null;
  is_checked: boolean;
  notes: string | null;
  checked_at: string | null;
  checkedBy?: { id: string; name: string } | null;
}

interface WorkOrder {
  id: string;
  type: "PREVENTIVE" | "CORRECTIVE";
  priority: "LOW" | "MEDIUM" | "HIGH" | "EMERGENCY";
  status: "OPEN" | "IN_PROGRESS" | "COMPLETED" | "CLOSED";
  description: string | null;
  assignedTo?: { id: string; name: string; email: string } | null;
  created_at: string;
  completed_at: string | null;
  asset?: { id: string; code: string; name: string } | null;
  organization?: { id: string; name: string; code: string } | null;
  checklists?: WorkOrderChecklist[];
}

const props = defineProps<{ workOrder: WorkOrder }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Asset Management", href: "#" },
  { title: "Work Orders", href: "/work-orders" },
  {
    title: props.workOrder.id.slice(0, 8),
    href: `/work-orders/${props.workOrder.id}`,
  },
];

const priorityColor = (priority: WorkOrder["priority"]) => {
  const colors: Record<WorkOrder["priority"], string> = {
    LOW: "bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900/30 dark:text-slate-400",
    MEDIUM:
      "bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/30 dark:text-sky-400",
    HIGH: "bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-900/30 dark:text-orange-400",
    EMERGENCY:
      "bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400",
  };
  return colors[priority];
};

const checklists = computed(() => props.workOrder.checklists ?? []);
const checklistTotal = computed(() => checklists.value.length);
const checklistDone = computed(
  () => checklists.value.filter((i) => i.is_checked).length,
);
const checklistPercent = computed(() => {
  if (checklistTotal.value === 0) return 0;
  return Math.round((checklistDone.value / checklistTotal.value) * 100);
});

const formatDateTime = (value: string | null) => {
  if (!value) return "-";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString();
};
</script>

<template>
  <Head :title="`Work Order ${workOrder.id.slice(0, 8)}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full"
    >
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link href="/work-orders">
            <Button variant="ghost" size="icon" class="h-8 w-8">
              <ArrowLeft class="h-4 w-4" />
            </Button>
          </Link>
          <div>
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
            >
              Work Order
              <span class="font-mono">{{ workOrder.id.slice(0, 8) }}</span>
            </h1>
            <p class="text-zinc-500 mt-1">
              {{ workOrder.asset?.code }} - {{ workOrder.asset?.name }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <StatusBadge
            :status="workOrder.status"
            :label="workOrder.status.replace('_', ' ')"
          />
          <div
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium border"
            :class="priorityColor(workOrder.priority)"
          >
            {{ workOrder.priority }}
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <div
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
          >
            <div class="flex items-center gap-2 mb-4">
              <Wrench class="h-5 w-5 text-zinc-400" />
              <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                Details
              </h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <div class="text-zinc-500">Type</div>
                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                  {{ workOrder.type }}
                </div>
              </div>
              <div>
                <div class="text-zinc-500">Organization</div>
                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                  {{ workOrder.organization?.name || "-" }}
                </div>
              </div>
              <div>
                <div class="text-zinc-500">Created</div>
                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                  {{ formatDateTime(workOrder.created_at) }}
                </div>
              </div>
              <div>
                <div class="text-zinc-500">Completed</div>
                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                  {{ formatDateTime(workOrder.completed_at) }}
                </div>
              </div>
            </div>

            <div class="mt-5">
              <div class="text-sm text-zinc-500 mb-2">Description</div>
              <div
                v-if="workOrder.description"
                class="rounded-lg border border-zinc-200 dark:border-zinc-800 p-4 text-sm text-zinc-700 dark:text-zinc-200"
              >
                {{ workOrder.description }}
              </div>
              <div
                v-else
                class="rounded-lg border border-dashed border-zinc-200 dark:border-zinc-800 p-4 text-sm text-zinc-500"
              >
                No description
              </div>
            </div>
          </div>

          <div
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
          >
            <div class="flex items-center justify-between gap-4 mb-4">
              <div class="flex items-center gap-2">
                <ClipboardList class="h-5 w-5 text-zinc-400" />
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                  Checklist
                </h2>
              </div>
              <div class="text-sm text-zinc-500">
                {{ checklistDone }} / {{ checklistTotal }} ({{
                  checklistPercent
                }}%)
              </div>
            </div>

            <div
              class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden"
            >
              <div
                class="h-full bg-emerald-500"
                :style="{ width: `${checklistPercent}%` }"
              ></div>
            </div>

            <div
              v-if="checklistTotal === 0"
              class="py-10 text-center text-zinc-500"
            >
              No checklist assigned to this work order.
            </div>
            <div
              v-else
              class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden"
            >
              <div
                v-for="item in checklists"
                :key="item.id"
                class="p-4 flex items-start gap-3"
              >
                <div class="mt-0.5">
                  <CheckCircle2
                    v-if="item.is_checked"
                    class="h-5 w-5 text-emerald-600"
                  />
                  <Circle
                    v-else
                    class="h-5 w-5 text-zinc-300 dark:text-zinc-600"
                  />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between gap-4">
                    <div>
                      <div class="font-medium text-zinc-900 dark:text-zinc-100">
                        {{ item.item_name }}
                      </div>
                      <div
                        v-if="item.item_description"
                        class="text-sm text-zinc-500 mt-0.5"
                      >
                        {{ item.item_description }}
                      </div>
                    </div>
                    <div
                      v-if="item.is_checked"
                      class="text-xs text-zinc-500 text-right shrink-0"
                    >
                      <div>{{ item.checkedBy?.name || "Checked" }}</div>
                      <div>{{ formatDateTime(item.checked_at) }}</div>
                    </div>
                  </div>
                  <div
                    v-if="item.notes"
                    class="mt-2 text-sm text-zinc-700 dark:text-zinc-200 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg p-3"
                  >
                    {{ item.notes }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
          >
            <div class="flex items-center gap-2 mb-4">
              <User class="h-5 w-5 text-zinc-400" />
              <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                Assignment
              </h2>
            </div>
            <div class="text-sm">
              <div class="text-zinc-500">Assigned To</div>
              <div class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                {{ workOrder.assignedTo?.name || "Unassigned" }}
              </div>
              <div
                v-if="workOrder.assignedTo?.email"
                class="text-zinc-500 mt-1"
              >
                {{ workOrder.assignedTo.email }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
