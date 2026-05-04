<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import {
  ArrowLeft,
  Wrench,
  Calendar,
  MapPin,
  Tag,
  Edit2,
  Trash2,
} from "lucide-vue-next";
import { ref } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

interface Asset {
  id: string;
  code: string;
  name: string;
  category: string;
  organization_id: string;
  status: "ACTIVE" | "INACTIVE" | "UNDER_MAINTENANCE";
  purchase_date: string | null;
  serial_number: string | null;
  organization?: {
    id: string;
    name: string;
    code: string;
  };
  workOrders?: Array<{
    id: string;
    type: string;
    priority: string;
    status: string;
    description: string | null;
    created_at: string;
  }>;
}

interface Props {
  asset: Asset;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Asset Management", href: "#" },
  { title: "Assets", href: "/assets" },
  { title: props.asset.name, href: `/assets/${props.asset.id}` },
];

const deleteDialogOpen = ref(false);

const getStatusColor = (status: string) => {
  const colors = {
    ACTIVE:
      "bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400",
    INACTIVE:
      "bg-zinc-50 text-zinc-700 border-zinc-200 dark:bg-zinc-900/30 dark:text-zinc-400",
    UNDER_MAINTENANCE:
      "bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400",
  };
  return (
    colors[status as keyof typeof colors] ||
    "bg-zinc-50 text-zinc-700 border-zinc-200"
  );
};

const getStatusBadge = (status: string) => {
  const badges = {
    ACTIVE: "Active",
    INACTIVE: "Inactive",
    UNDER_MAINTENANCE: "Under Maintenance",
  };
  return badges[status as keyof typeof badges] || status;
};

const getPriorityColor = (priority: string) => {
  const colors = {
    LOW: "bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900/30 dark:text-slate-400",
    MEDIUM:
      "bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/30 dark:text-sky-400",
    HIGH: "bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-900/30 dark:text-orange-400",
    EMERGENCY:
      "bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400",
  };
  return (
    colors[priority as keyof typeof colors] ||
    "bg-zinc-50 text-zinc-700 border-zinc-200"
  );
};

const deleteAsset = () => {
  deleteDialogOpen.value = true;
};

const confirmDeleteAsset = (): void => {
  router.delete(`/assets/${props.asset.id}`, {
    onFinish: () => {
      deleteDialogOpen.value = false;
    },
  });
};
</script>

<template>
  <Head :title="asset.name" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full"
    >
      <!-- Header -->
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link href="/assets">
            <Button variant="ghost" size="icon" class="h-8 w-8">
              <ArrowLeft class="h-4 w-4" />
            </Button>
          </Link>
          <div>
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
            >
              {{ asset.name }}
            </h1>
            <p class="text-zinc-500 mt-1 font-mono text-sm">{{ asset.code }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <div
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium border"
            :class="getStatusColor(asset.status)"
          >
            {{ getStatusBadge(asset.status) }}
          </div>
          <Link :href="`/assets/${asset.id}/edit`">
            <Button variant="ghost" size="icon" class="h-8 w-8">
              <Edit2 class="h-4 w-4" />
            </Button>
          </Link>
          <Button
            variant="ghost"
            size="icon"
            @click="deleteAsset"
            class="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50"
          >
            <Trash2 class="h-4 w-4" />
          </Button>
        </div>
      </div>

      <!-- Asset Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
        >
          <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
            Asset Information
          </h2>
          <div class="space-y-4">
            <div class="flex items-start gap-3">
              <Tag class="h-5 w-5 text-zinc-400 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm text-zinc-500">Category</p>
                <p class="font-medium text-zinc-900 dark:text-zinc-100">
                  {{ asset.category }}
                </p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <MapPin class="h-5 w-5 text-zinc-400 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm text-zinc-500">Organization</p>
                <p class="font-medium text-zinc-900 dark:text-zinc-100">
                  {{ asset.organization?.name || "-" }}
                </p>
                <p
                  v-if="asset.organization"
                  class="text-xs text-zinc-500 mt-0.5"
                >
                  {{ asset.organization.code }}
                </p>
              </div>
            </div>
            <div v-if="asset.serial_number" class="flex items-start gap-3">
              <Wrench class="h-5 w-5 text-zinc-400 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm text-zinc-500">Serial Number</p>
                <p class="font-mono text-sm text-zinc-900 dark:text-zinc-100">
                  {{ asset.serial_number }}
                </p>
              </div>
            </div>
            <div v-if="asset.purchase_date" class="flex items-start gap-3">
              <Calendar class="h-5 w-5 text-zinc-400 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm text-zinc-500">Purchase Date</p>
                <p class="font-medium text-zinc-900 dark:text-zinc-100">
                  {{
                    new Date(asset.purchase_date).toLocaleDateString("en-US", {
                      year: "numeric",
                      month: "long",
                      day: "numeric",
                    })
                  }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
        >
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
              Work Orders
            </h2>
            <span class="text-sm text-zinc-500"
              >{{ asset.workOrders?.length || 0 }} total</span
            >
          </div>
          <div
            v-if="!asset.workOrders || asset.workOrders.length === 0"
            class="text-center py-8 text-zinc-500"
          >
            <Wrench
              class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700 mb-3"
            />
            No work orders yet.
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="wo in asset.workOrders"
              :key="wo.id"
              class="p-3 border border-zinc-200 dark:border-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                  <p
                    class="text-sm font-mono text-zinc-700 dark:text-zinc-300 mb-1"
                  >
                    {{ wo.id.slice(0, 8) }}
                  </p>
                  <p
                    v-if="wo.description"
                    class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2"
                  >
                    {{ wo.description }}
                  </p>
                  <p v-else class="text-sm text-zinc-400 italic">
                    No description
                  </p>
                  <p class="text-xs text-zinc-500 mt-2">
                    {{ new Date(wo.created_at).toLocaleDateString() }}
                  </p>
                </div>
                <div class="flex flex-col gap-1.5 shrink-0">
                  <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium border"
                    :class="getPriorityColor(wo.priority)"
                  >
                    {{ wo.priority }}
                  </span>
                  <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium border bg-blue-50 text-blue-700 border-blue-200"
                  >
                    {{ wo.type }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      variant="danger"
      title="Delete asset"
      :message="`Are you sure you want to delete ${props.asset.name}?`"
      confirm-label="Delete"
      @confirm="confirmDeleteAsset"
    />
  </AppLayout>
</template>
