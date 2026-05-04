<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { Building2, Plus, Pencil, Trash2 } from "lucide-vue-next";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import FilterBar from "@/components/FilterBar.vue";
import { useTableFilters } from "@/composables/useTableFilters";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  departments: any;
  organizations: any[];
  filters: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "HR Master Data", href: "#" },
  { title: "Departments", href: "/departments" },
];

const showModal = ref(false);
const editTarget = ref<any>(null);
const deleteDialogOpen = ref(false);
const deleteTarget = ref<any>(null);
const globalOrganizationValue = "__global__";

const updateOrganizationId = (value: unknown) => {
  form.organization_id =
    value === globalOrganizationValue || value == null ? "" : String(value);
};

const form = useForm({
  code: "",
  name: "",
  description: "",
  organization_id: "",
});

const openCreate = () => {
  editTarget.value = null;
  form.reset();
  showModal.value = true;
};
const openEdit = (dept: any) => {
  editTarget.value = dept;
  form.code = dept.code;
  form.name = dept.name;
  form.description = dept.description ?? "";
  form.organization_id = dept.organization_id ?? "";
  showModal.value = true;
};

const submit = () => {
  if (editTarget.value) {
    form.put(`/departments/${editTarget.value.id}`, {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    form.post("/departments", {
      onSuccess: () => {
        showModal.value = false;
        form.reset();
      },
    });
  }
};

const destroy = (dept: any) => {
  deleteTarget.value = dept;
  deleteDialogOpen.value = true;
};

const confirmDestroy = () => {
  if (!deleteTarget.value) {
    return;
  }

  router.delete(`/departments/${deleteTarget.value.id}`, {
    onFinish: () => {
      deleteDialogOpen.value = false;
      deleteTarget.value = null;
    },
  });
};

const filters = ref({
  search: props.filters.search || "",
});

const { resetFilters } = useTableFilters(filters, {
  route: "/departments",
  debounceMs: 400,
});
</script>

<template>
  <Head title="Departments" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-5xl mx-auto w-full">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
            Departments
          </h1>
          <p class="text-zinc-500 mt-1">
            Bidang / unit kerja dalam organisasi.
          </p>
        </div>
        <Dialog v-model:open="showModal">
          <DialogTrigger as-child>
            <Button @click="openCreate"
              ><Plus class="h-4 w-4 mr-2" />Add Department</Button
            >
          </DialogTrigger>
          <DialogContent class="sm:max-w-md">
            <DialogHeader
              ><DialogTitle
                >{{ editTarget ? "Edit" : "New" }} Department</DialogTitle
              ></DialogHeader
            >
            <form @submit.prevent="submit" class="space-y-4 mt-2">
              <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                  <Label>Code</Label
                  ><Input v-model="form.code" placeholder="OPS" required />
                  <span v-if="form.errors.code" class="text-xs text-red-500">{{
                    form.errors.code
                  }}</span>
                </div>
                <div class="grid gap-2">
                  <Label>Name</Label
                  ><Input v-model="form.name" placeholder="Operasi" required />
                  <span v-if="form.errors.name" class="text-xs text-red-500">{{
                    form.errors.name
                  }}</span>
                </div>
              </div>
              <div class="grid gap-2">
                <Label>Unit (optional — null = Global)</Label>
                <Select
                  :model-value="form.organization_id || globalOrganizationValue"
                  @update:model-value="updateOrganizationId"
                >
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Global (all units)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem :value="globalOrganizationValue"
                      >Global (all units)</SelectItem
                    >
                    <SelectItem
                      v-for="org in organizations"
                      :key="org.id"
                      :value="org.id"
                    >
                      {{ org.code }} - {{ org.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="grid gap-2">
                <Label>Description</Label><Input v-model="form.description" />
              </div>
              <div class="flex justify-end gap-2 pt-2">
                <Button
                  type="button"
                  variant="outline"
                  @click="showModal = false"
                  >Cancel</Button
                >
                <Button type="submit" :disabled="form.processing">Save</Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>
      <FilterBar
        v-model:search="filters.search"
        search-placeholder="Search departments..."
        @reset="resetFilters"
      />
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden"
      >
        <table class="w-full text-sm text-left">
          <thead
            class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800"
          >
            <tr>
              <th class="px-5 py-4">Code</th>
              <th class="px-5 py-4">Name</th>
              <th class="px-5 py-4">Unit</th>
              <th class="px-5 py-4">Positions</th>
              <th class="px-5 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr
              v-for="dept in departments.data"
              :key="dept.id"
              class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
            >
              <td
                class="px-5 py-4 font-mono font-semibold text-indigo-600 dark:text-indigo-400"
              >
                {{ dept.code }}
              </td>
              <td class="px-5 py-4 font-medium text-zinc-900 dark:text-white">
                {{ dept.name }}
              </td>
              <td class="px-5 py-4 text-zinc-500">
                {{ dept.organization?.name ?? "Global" }}
              </td>
              <td class="px-5 py-4 text-zinc-500">
                {{ dept.positions_count }}
              </td>
              <td class="px-5 py-4 flex justify-end gap-2">
                <Button size="icon" variant="ghost" @click="openEdit(dept)"
                  ><Pencil class="h-4 w-4"
                /></Button>
                <Button
                  size="icon"
                  variant="ghost"
                  class="text-red-500"
                  @click="destroy(dept)"
                  ><Trash2 class="h-4 w-4"
                /></Button>
              </td>
            </tr>
            <tr v-if="!departments.data.length">
              <td colspan="5" class="py-12 text-center text-zinc-500">
                <Building2 class="h-12 w-12 mx-auto text-zinc-300 mb-3" />
                <p>No departments yet.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <ConfirmDialog
        v-model:open="deleteDialogOpen"
        variant="danger"
        title="Hapus department"
        :message="`Hapus department ${deleteTarget?.name ?? ''}?`"
        confirm-label="Hapus"
        @confirm="confirmDestroy"
      />
    </div>
  </AppLayout>
</template>
