<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { DollarSign, Plus, Pencil, Trash2 } from "lucide-vue-next";
import { ref } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import FilterBar from "@/components/FilterBar.vue";
import { useTableFilters } from "@/composables/useTableFilters";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  structures: any;
  jobGrades: any[];
  organizations: any[];
  componentTypes: any[];
  filters: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "HR Master Data", href: "#" },
  { title: "Salary Structures", href: "/salary-structures" },
];

const showModal = ref(false);
const editTarget = ref<any>(null);
const deleteDialogOpen = ref(false);
const deleteTarget = ref<any>(null);

const makeItems = () =>
  props.componentTypes.map((ct) => ({
    component_type_id: ct.id,
    code: ct.code,
    name: ct.name,
    amount: 0,
  }));

const form = useForm({
  employee_type: "Organic",
  job_grade_id: "",
  organization_id: "",
  min_tenure_months: 0,
  max_tenure_months: undefined as number | undefined,
  effective_from: "",
  effective_until: "",
  items: makeItems(),
});

const openCreate = () => {
  editTarget.value = null;
  form.reset();
  form.employee_type = "Organic";
  form.items = makeItems();
  showModal.value = true;
};
const openEdit = (s: any) => {
  editTarget.value = s;
  form.employee_type = s.employee_type;
  form.job_grade_id = s.job_grade_id;
  form.organization_id = s.organization_id ?? "";
  form.min_tenure_months = s.min_tenure_months;
  form.max_tenure_months = s.max_tenure_months;
  form.effective_from = s.effective_from;
  form.effective_until = s.effective_until ?? "";
  form.items = makeItems().map((i) => {
    const existing = s.items?.find(
      (x: any) => x.salary_component_type_id === i.component_type_id,
    );
    return { ...i, amount: existing?.amount ?? 0 };
  });
  showModal.value = true;
};

const submit = () => {
  if (editTarget.value) {
    form.put(`/salary-structures/${editTarget.value.id}`, {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    form.post("/salary-structures", {
      onSuccess: () => {
        showModal.value = false;
        form.reset();
        form.items = makeItems();
      },
    });
  }
};

const destroy = (s: any) => {
  deleteTarget.value = s;
  deleteDialogOpen.value = true;
};

const confirmDestroy = (): void => {
  if (!deleteTarget.value) {
    return;
  }

  router.delete(`/salary-structures/${deleteTarget.value.id}`, {
    onFinish: () => {
      deleteDialogOpen.value = false;
      deleteTarget.value = null;
    },
  });
};

const totalGross = (structure: any) =>
  structure.items?.reduce(
    (sum: number, i: any) => sum + parseFloat(i.amount),
    0,
  ) ?? 0;

const filters = ref({
  employee_type: props.filters.employee_type || "",
  job_grade_id: props.filters.job_grade_id || "",
});

const { resetFilters } = useTableFilters(filters, {
  route: "/salary-structures",
  debounceMs: 400,
});
</script>

<template>
  <Head title="Salary Structures" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
            Salary Structures
          </h1>
          <p class="text-zinc-500 mt-1">
            Matriks komponen gaji (P1/P2/TGT/TPL/TP) per tipe × jenjang × lokasi
            × masa kerja.
          </p>
        </div>
        <Dialog v-model:open="showModal">
          <DialogTrigger as-child>
            <Button @click="openCreate"
              ><Plus class="h-4 w-4 mr-2" />New Structure</Button
            >
          </DialogTrigger>
          <DialogContent class="sm:max-w-lg max-h-[90vh] overflow-y-auto">
            <DialogHeader
              ><DialogTitle
                >{{ editTarget ? "Edit" : "New" }} Salary Structure</DialogTitle
              ></DialogHeader
            >
            <form @submit.prevent="submit" class="space-y-4 mt-2">
              <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                  <Label>Employee Type</Label>
                  <select
                    v-model="form.employee_type"
                    class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                  >
                    <option value="Organic">Organic</option>
                    <option value="TKWT">TKWT</option>
                  </select>
                </div>
                <div class="grid gap-2">
                  <Label>Job Grade</Label>
                  <select
                    v-model="form.job_grade_id"
                    required
                    class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                  >
                    <option value="" disabled>Select grade</option>
                    <option v-for="g in jobGrades" :key="g.id" :value="g.id">
                      {{ g.level }} — {{ g.name }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="grid gap-2">
                <Label>Unit (null = berlaku semua unit)</Label>
                <select
                  v-model="form.organization_id"
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option value="">Global (semua unit)</option>
                  <option
                    v-for="org in organizations"
                    :key="org.id"
                    :value="org.id"
                  >
                    {{ org.code }} - {{ org.name }}
                  </option>
                </select>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                  <Label>Min Tenure (bulan)</Label
                  ><Input
                    type="number"
                    v-model="form.min_tenure_months"
                    min="0"
                  />
                </div>
                <div class="grid gap-2">
                  <Label>Max Tenure (bulan, kosong=∞)</Label
                  ><Input
                    type="number"
                    v-model="form.max_tenure_months"
                    min="0"
                    placeholder="∞"
                  />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                  <Label>Berlaku Mulai</Label
                  ><Input type="date" v-model="form.effective_from" required />
                </div>
                <div class="grid gap-2">
                  <Label>Berlaku Sampai (kosong=aktif)</Label
                  ><Input type="date" v-model="form.effective_until" />
                </div>
              </div>

              <!-- Salary Components -->
              <div
                class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden"
              >
                <div
                  class="bg-zinc-50 dark:bg-zinc-800/50 px-4 py-2 text-xs font-semibold uppercase text-zinc-500"
                >
                  Komponen Gaji
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                  <div
                    v-for="(item, idx) in form.items"
                    :key="item.component_type_id"
                    class="flex items-center gap-4 px-4 py-3"
                  >
                    <div
                      class="w-12 font-mono font-bold text-sm text-indigo-600 dark:text-indigo-400"
                    >
                      {{ item.code }}
                    </div>
                    <div
                      class="flex-1 text-sm text-zinc-700 dark:text-zinc-300"
                    >
                      {{ item.name }}
                    </div>
                    <Input
                      type="number"
                      v-model="form.items[idx].amount"
                      min="0"
                      class="w-36 text-right tabular-nums"
                      placeholder="0"
                    />
                  </div>
                </div>
              </div>

              <div class="flex justify-end gap-2 pt-2">
                <Button
                  type="button"
                  variant="outline"
                  @click="showModal = false"
                  >Cancel</Button
                >
                <Button type="submit" :disabled="form.processing"
                  >Save Structure</Button
                >
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Filters -->
      <FilterBar :show-search="false" @reset="resetFilters">
        <select
          v-model="filters.employee_type"
          class="flex h-10 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-800/50"
        >
          <option value="">All Types</option>
          <option value="Organic">Organic</option>
          <option value="TKWT">TKWT</option>
        </select>
        <select
          v-model="filters.job_grade_id"
          class="flex h-10 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-800/50"
        >
          <option value="">All Grades</option>
          <option v-for="g in jobGrades" :key="g.id" :value="g.id">
            {{ g.name }}
          </option>
        </select>
      </FilterBar>

      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden"
      >
        <table class="w-full text-sm text-left">
          <thead
            class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800"
          >
            <tr>
              <th class="px-5 py-4">Type</th>
              <th class="px-5 py-4">Grade</th>
              <th class="px-5 py-4">Unit</th>
              <th class="px-5 py-4">Tenure</th>
              <th class="px-5 py-4">Period</th>
              <th class="px-5 py-4 text-right">Total Gross</th>
              <th class="px-5 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr
              v-for="s in structures.data"
              :key="s.id"
              class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
            >
              <td class="px-5 py-4">
                <span
                  class="px-2 py-0.5 text-xs font-medium rounded-full"
                  :class="
                    s.employee_type === 'Organic'
                      ? 'bg-green-100 text-green-700'
                      : 'bg-orange-100 text-orange-700'
                  "
                  >{{ s.employee_type }}</span
                >
              </td>
              <td class="px-5 py-4 font-medium">{{ s.job_grade?.name }}</td>
              <td class="px-5 py-4 text-zinc-500 text-xs">
                {{ s.organization?.name ?? "Global" }}
              </td>
              <td class="px-5 py-4 text-zinc-500 text-xs font-mono">
                {{ s.min_tenure_months }}m –
                {{
                  s.max_tenure_months != null ? `${s.max_tenure_months}m` : "∞"
                }}
              </td>
              <td class="px-5 py-4 text-zinc-500 text-xs">
                {{ s.effective_from }} → {{ s.effective_until ?? "∞" }}
              </td>
              <td
                class="px-5 py-4 text-right font-semibold text-emerald-600 dark:text-emerald-400 tabular-nums"
              >
                {{ formatCurrency(totalGross(s)) }}
              </td>
              <td class="px-5 py-4 flex justify-end gap-2">
                <Button size="icon" variant="ghost" @click="openEdit(s)"
                  ><Pencil class="h-4 w-4"
                /></Button>
                <Button
                  size="icon"
                  variant="ghost"
                  class="text-red-500"
                  @click="destroy(s)"
                  ><Trash2 class="h-4 w-4"
                /></Button>
              </td>
            </tr>
            <tr v-if="!structures.data.length">
              <td colspan="7" class="py-12 text-center text-zinc-500">
                <DollarSign class="h-12 w-12 mx-auto text-zinc-300 mb-3" />
                <p>
                  No salary structures yet. Add one to define employee
                  compensation.
                </p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      variant="danger"
      title="Delete salary structure"
      message="Delete this salary structure?"
      confirm-label="Delete"
      @confirm="confirmDestroy"
    />
  </AppLayout>
</template>
