<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { Gift, Building, Zap } from "lucide-vue-next";
import { ref, watch } from "vue";
import { index as payrollsIndex } from "@/actions/App/Http/Controllers/PayrollController";
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
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  payrolls: any;
  organizations: any[];
  filters: Record<string, string>;
  stats: { total_thr: number; current_year: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Core Modules", href: "#" },
  { title: "Payroll", href: payrollsIndex().url },
  { title: "THR (Tunjangan Hari Raya)", href: "#" },
];

const selectedYear = ref(
  props.filters.year || new Date().getFullYear().toString(),
);
const selectedOrg = ref(props.filters.organization_id || "");
const showGenerateModal = ref(false);

let filterTimeout: ReturnType<typeof setTimeout>;
watch([selectedYear, selectedOrg], () => {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    router.get(
      "/payrolls/thr",
      {
        year: selectedYear.value,
        organization_id: selectedOrg.value,
      },
      { preserveState: true, replace: true },
    );
  }, 400);
});

const generateForm = useForm({
  year: new Date().getFullYear().toString(),
  organization_id: "",
});

const thrPreview = ref<any>(null);
const isPreviewLoading = ref(false);

const previewTHR = async () => {
  if (!generateForm.year || !generateForm.organization_id) {
    return;
  }

  isPreviewLoading.value = true;

  try {
    const response = await fetch("/payrolls/thr/preview", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        year: generateForm.year,
        organization_id: generateForm.organization_id,
      }),
    });

    if (response.ok) {
      thrPreview.value = await response.json();
    }
  } catch (error) {
    console.error("Failed to preview THR:", error);
  } finally {
    isPreviewLoading.value = false;
  }
};

const submitGenerate = () => {
  generateForm.post("/payrolls/thr/generate", {
    onSuccess: () => {
      showGenerateModal.value = false;
      thrPreview.value = null;
    },
  });
};
</script>

<template>
  <Head title="THR (Tunjangan Hari Raya)" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      <!-- Header -->
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            THR (Tunjangan Hari Raya)
          </h1>
          <p class="text-zinc-500 mt-1">
            Religious holiday allowance calculated proportionally based on
            months worked.
          </p>
        </div>
        <Dialog v-model:open="showGenerateModal">
          <DialogTrigger as-child>
            <Button>
              <Zap class="h-4 w-4 mr-2" />
              Generate THR
            </Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader><DialogTitle>Generate THR</DialogTitle></DialogHeader>
            <div class="space-y-4 mt-2">
              <p class="text-sm text-zinc-500">
                Generates THR for all <strong>ACTIVE</strong> employees in the
                selected unit. THR is calculated proportionally based on months
                worked in the current year.
              </p>

              <div class="grid gap-2">
                <Label>Year</Label>
                <Input
                  type="number"
                  v-model="generateForm.year"
                  min="2020"
                  max="2099"
                  required
                />
                <span
                  v-if="generateForm.errors.year"
                  class="text-xs text-red-500"
                  >{{ generateForm.errors.year }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label>Unit / Organization</Label>
                <select
                  v-model="generateForm.organization_id"
                  required
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option value="" disabled>Select unit...</option>
                  <option
                    v-for="org in organizations"
                    :key="org.id"
                    :value="org.id"
                  >
                    {{ org.code }} - {{ org.name }}
                  </option>
                </select>
                <span
                  v-if="generateForm.errors.organization_id"
                  class="text-xs text-red-500"
                  >{{ generateForm.errors.organization_id }}</span
                >
              </div>

              <!-- Preview Button -->
              <div v-if="!thrPreview" class="flex justify-end pt-2">
                <Button
                  type="button"
                  variant="outline"
                  @click="previewTHR"
                  :disabled="
                    !generateForm.year ||
                    !generateForm.organization_id ||
                    isPreviewLoading
                  "
                >
                  <span v-if="isPreviewLoading">Loading Preview...</span>
                  <span v-else>Preview THR Calculation</span>
                </Button>
              </div>

              <!-- Preview Section -->
              <div
                v-if="thrPreview"
                class="border-t border-zinc-200 dark:border-zinc-800 pt-4 mt-4"
              >
                <h4 class="font-semibold text-zinc-900 dark:text-white mb-3">
                  THR Preview Summary
                </h4>

                <div class="grid grid-cols-3 gap-4 mb-4">
                  <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-3">
                    <p class="text-xs text-zinc-500">Total Employees</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-white">
                      {{ thrPreview.total_employees }}
                    </p>
                  </div>
                  <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-3">
                    <p class="text-xs text-zinc-500">Estimated Total THR</p>
                    <p
                      class="text-xl font-bold text-amber-600 dark:text-amber-400"
                    >
                      {{ formatCurrency(thrPreview.total_thr) }}
                    </p>
                  </div>
                  <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-3">
                    <p class="text-xs text-zinc-500">Organization</p>
                    <p
                      class="text-sm font-medium text-zinc-900 dark:text-white truncate"
                    >
                      {{ thrPreview.organization_name }}
                    </p>
                  </div>
                </div>

                <!-- Breakdown by Months Worked -->
                <div class="space-y-2">
                  <p
                    class="text-sm font-medium text-zinc-700 dark:text-zinc-300"
                  >
                    Breakdown by Months Worked:
                  </p>
                  <div class="grid grid-cols-2 gap-2">
                    <div
                      v-for="(item, index) in thrPreview.breakdown"
                      :key="index"
                      class="flex items-center justify-between text-sm p-2 bg-white dark:bg-zinc-800 rounded border border-zinc-200 dark:border-zinc-800"
                    >
                      <span class="text-zinc-600 dark:text-zinc-400"
                        >{{ item.months }} months</span
                      >
                      <span class="font-medium"
                        >{{ item.count }} employees</span
                      >
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div
                class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-800"
              >
                <Button
                  type="button"
                  variant="outline"
                  @click="
                    showGenerateModal = false;
                    thrPreview = null;
                  "
                  >Cancel</Button
                >
                <Button
                  type="submit"
                  :disabled="generateForm.processing || !thrPreview"
                >
                  <span v-if="generateForm.processing">Generating...</span>
                  <span v-else>Generate THR</span>
                </Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between"
        >
          <div>
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
              Total THR {{ stats.current_year }}
            </p>
            <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">
              {{ formatCurrency(stats.total_thr) }}
            </h2>
          </div>
          <div
            class="h-12 w-12 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center"
          >
            <Gift class="h-6 w-6" />
          </div>
        </div>

        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                THR Records
              </p>
              <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">
                {{ payrolls.total }}
              </h2>
            </div>
            <div
              class="h-12 w-12 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center"
            >
              <Building class="h-6 w-6" />
            </div>
          </div>
          <div class="mt-3 flex gap-2">
            <select
              v-model="selectedYear"
              class="flex h-8 rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-800 dark:bg-zinc-950"
            >
              <option value="">All Years</option>
              <option
                v-for="year in [2026, 2025, 2024]"
                :key="year"
                :value="year"
              >
                {{ year }}
              </option>
            </select>
            <select
              v-model="selectedOrg"
              class="flex h-8 rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-800 dark:bg-zinc-950"
            >
              <option value="">All Units</option>
              <option
                v-for="org in organizations"
                :key="org.id"
                :value="org.id"
              >
                {{ org.code }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden flex-1"
      >
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead
              class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800"
            >
              <tr>
                <th class="px-6 py-4 font-medium">Employee</th>
                <th class="px-6 py-4 font-medium">Unit / Organization</th>
                <th class="px-6 py-4 font-medium">Period</th>
                <th class="px-6 py-4 font-medium">Months Worked</th>
                <th class="px-6 py-4 font-medium">THR Amount</th>
                <th class="px-6 py-4 font-medium">Status</th>
                <th class="px-6 py-4 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
              <tr
                v-for="payroll in payrolls.data"
                :key="payroll.id"
                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div
                      class="h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500"
                    >
                      {{ payroll.employee?.first_name?.charAt(0) || "?" }}
                    </div>
                    <div>
                      <div class="font-medium text-zinc-900 dark:text-white">
                        {{ payroll.employee?.first_name }}
                        {{ payroll.employee?.last_name }}
                      </div>
                      <div class="text-xs text-zinc-500">
                        {{ payroll.employee?.employee_code }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div
                    v-if="payroll.organization"
                    class="flex items-center gap-2"
                  >
                    <Building class="h-4 w-4 text-zinc-400" />
                    <span class="text-zinc-700 dark:text-zinc-300">{{
                      payroll.organization.name
                    }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="text-zinc-700 dark:text-zinc-300">{{
                    payroll.period
                  }}</span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <span class="text-zinc-700 dark:text-zinc-300"
                      >{{ payroll.thr_proportion_months }} months</span
                    >
                    <span class="text-xs text-zinc-500">/ 12</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="font-medium text-zinc-900 dark:text-white">{{
                    formatCurrency(payroll.thr_amount)
                  }}</span>
                </td>
                <td class="px-6 py-4">
                  <StatusBadge :status="payroll.status" />
                </td>
                <td class="px-6 py-4 text-right">
                  <a
                    :href="`/payrolls/${payroll.id}`"
                    class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300"
                    >View</a
                  >
                </td>
              </tr>
              <tr v-if="payrolls.data.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                  <Gift
                    class="h-12 w-12 mx-auto text-zinc-300 dark:text-zinc-700 mb-3"
                  />
                  <p
                    class="text-base font-medium text-zinc-900 dark:text-zinc-100"
                  >
                    No THR records found
                  </p>
                  <p class="text-sm mt-1">
                    Generate THR for a specific year and organization.
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="payrolls.links && payrolls.links.length > 3"
          class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between"
        >
          <p class="text-sm text-zinc-500">
            Showing
            <span class="font-medium text-zinc-900 dark:text-white">{{
              payrolls.from
            }}</span>
            to
            <span class="font-medium text-zinc-900 dark:text-white">{{
              payrolls.to
            }}</span>
            of
            <span class="font-medium text-zinc-900 dark:text-white">{{
              payrolls.total
            }}</span>
            results
          </p>
          <div class="flex gap-1">
            <a
              v-for="(link, i) in payrolls.links"
              :key="i"
              :href="link.url || '#'"
              class="px-3 py-1 text-sm rounded-md transition-colors"
              :class="[
                link.active
                  ? 'bg-indigo-600 text-white'
                  : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800',
                !link.url ? 'opacity-50 cursor-not-allowed hidden' : '',
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
