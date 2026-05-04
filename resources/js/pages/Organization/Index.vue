<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import {
  Search,
  Plus,
  Edit2,
  Trash2,
  Building2,
  CornerDownRight,
  CheckCircle2,
  XCircle,
} from "lucide-vue-next";
import { ref, computed } from "vue";
import { destroy } from "@/actions/App/Http/Controllers/OrganizationController";
import { index as orgsIndex } from "@/actions/App/Http/Controllers/OrganizationController";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";
import type { Organization } from "@/types/organization";
import CreateEditModal from "./CreateEditModal.vue";

interface Props {
  organizations: Organization[];
}
const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "User Management", href: "#" },
  { title: "Organizations", href: orgsIndex().url },
];

const isModalOpen = ref(false);
const editingOrganization = ref<Organization | null>(null);
const deleteDialogOpen = ref(false);
const deleteTarget = ref<{ id: string; name: string } | null>(null);
const search = ref(""); // Basic UI search state, not fully hooked to backend yet for flat display

// Transform flat list into nested tree for display
const organizationTree = computed(() => {
  // If searching visually, we might just return flat or filter
  if (search.value) {
    return props.organizations.filter(
      (o) =>
        o.name.toLowerCase().includes(search.value.toLowerCase()) ||
        o.code.toLowerCase().includes(search.value.toLowerCase()),
    );
  }
  return props.organizations;
  // Return original flat array ordered properly by backend recursive CTE
});

const openCreateModal = () => {
  editingOrganization.value = null;
  isModalOpen.value = true;
};

const openEditModal = (org: Organization) => {
  editingOrganization.value = org;
  isModalOpen.value = true;
};

const deleteOrganization = (id: string, name: string) => {
  deleteTarget.value = { id, name };
  deleteDialogOpen.value = true;
};

const confirmDeleteOrganization = (): void => {
  if (!deleteTarget.value) {
    return;
  }

  router.delete(destroy(deleteTarget.value.id).url, {
    preserveScroll: true,
    onFinish: () => {
      deleteDialogOpen.value = false;
      deleteTarget.value = null;
    },
  });
};

const closeModal = () => {
  isModalOpen.value = false;
  setTimeout(() => {
    editingOrganization.value = null;
  }, 200);
};

// Map hierarchy level to margin class
const indentClass = (level: string) => {
  switch (level) {
    case "L0":
      return "ml-0";
    case "L1":
      return "ml-6";
    case "L2":
      return "ml-12";
    case "L3":
      return "ml-18";
    case "L4":
      return "ml-24";
    default:
      return "ml-0";
  }
};

const getTypeColor = (type: string) => {
  const colors = {
    HOLDING:
      "bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400",
    SUB_HOLDING:
      "bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/30 dark:text-sky-400",
    COMPANY:
      "bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-900/30 dark:text-teal-400",
    DIVISION:
      "bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400",
    DEPARTMENT:
      "bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400",
    BRANCH:
      "bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400",
  };
  return (
    colors[type as keyof typeof colors] ||
    "bg-zinc-100 text-zinc-700 border-zinc-200"
  );
};
</script>

<template>
  <Head title="Organizations" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full"
    >
      <!-- Header Section -->
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Organizations
          </h1>
          <p class="text-zinc-500 mt-1">
            Manage company structure, divisions, departments, and subsidiaries.
          </p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
          <div class="relative w-full md:w-64">
            <Search
              class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400"
            />
            <Input
              v-model="search"
              placeholder="Filter structural units..."
              class="pl-9 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800"
            />
          </div>

          <Button
            @click="openCreateModal"
            class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0"
          >
            <Plus class="h-4 w-4 mr-2" />
            Add Unit
          </Button>
        </div>
      </div>

      <!-- Main Listing Table -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden"
      >
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr
                class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50"
              >
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider w-1/2"
                >
                  Organization Structure
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider"
                >
                  Type / Level
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider"
                >
                  Status
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider text-right"
                >
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
              <tr v-if="organizationTree.length === 0">
                <td colspan="4" class="py-12 text-center text-zinc-500">
                  <Building2
                    class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700 mb-3"
                  />
                  No organizational units found.
                </td>
              </tr>
              <tr
                v-for="org in organizationTree"
                :key="org.id"
                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group"
              >
                <td class="py-4 px-6">
                  <div class="flex flex-col" :class="indentClass(org.level)">
                    <div class="flex items-start gap-2">
                      <CornerDownRight
                        v-if="org.level !== 'L0'"
                        class="h-4 w-4 text-zinc-300 dark:text-zinc-600 mt-1 shrink-0"
                      />
                      <Building2
                        v-else
                        class="h-5 w-5 text-indigo-500 mt-0.5 shrink-0"
                      />

                      <div>
                        <p
                          class="font-medium text-zinc-900 dark:text-zinc-100 leading-tight"
                          :class="
                            org.level === 'L0'
                              ? 'text-base font-bold'
                              : 'text-sm'
                          "
                        >
                          {{ org.name }}
                        </p>
                        <p
                          class="text-xs font-mono text-zinc-500 tracking-tight mt-0.5"
                        >
                          {{ org.code }}
                        </p>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-6">
                  <div
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border"
                    :class="getTypeColor(org.type)"
                  >
                    {{ org.type }}
                    <span class="opacity-60 text-[10px] ml-1"
                      >({{ org.level }})</span
                    >
                  </div>
                </td>
                <td class="py-4 px-6">
                  <div
                    class="flex items-center gap-1.5 text-sm"
                    :class="
                      org.is_active
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : 'text-zinc-400'
                    "
                  >
                    <CheckCircle2 v-if="org.is_active" class="h-4 w-4" />
                    <XCircle v-else class="h-4 w-4" />
                    {{ org.is_active ? "Active" : "Inactive" }}
                  </div>
                </td>
                <td class="py-4 px-6 text-right">
                  <div
                    class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity"
                  >
                    <Button
                      variant="ghost"
                      size="icon"
                      @click="openEditModal(org)"
                      class="h-8 w-8 text-zinc-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30"
                    >
                      <Edit2 class="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      @click="deleteOrganization(org.id, org.name)"
                      class="h-8 w-8 text-zinc-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <CreateEditModal
      :is-open="isModalOpen"
      :organization="editingOrganization"
      :all-organizations="organizations"
      @close="closeModal"
    />

    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      variant="danger"
      title="Delete organization"
      :message="
        deleteTarget
          ? `Are you sure you want to delete ${deleteTarget.name}?`
          : 'Are you sure you want to delete this organization?'
      "
      confirm-label="Delete"
      @confirm="confirmDeleteOrganization"
    />
  </AppLayout>
</template>
