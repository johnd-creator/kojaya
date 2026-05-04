<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, FileText, Plus, AlertTriangle } from "lucide-vue-next";
import { ref } from "vue";
import { index as employeesIndex } from "@/actions/App/Http/Controllers/EmployeeController";
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
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  employee: any;
  contracts: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "HR & Employee", href: "#" },
  { title: "Employee Master", href: employeesIndex().url },
  {
    title: `${props.employee.first_name} ${props.employee.last_name}`,
    href: "#",
  },
  { title: "Contracts", href: "#" },
];

const showModal = ref(false);

const form = useForm({
  type: "PKWT",
  start_date: "",
  end_date: "",
  status: "ACTIVE",
});

const submitContract = () => {
  form.post(`/employees/${props.employee.id}/contracts`, {
    onSuccess: () => {
      showModal.value = false;
      form.reset();
    },
  });
};

const daysRemaining = (endDate: string | null): number | null => {
  if (!endDate) return null;
  const diff = new Date(endDate).getTime() - Date.now();
  return Math.ceil(diff / (1000 * 60 * 60 * 24));
};

const urgencyClass = (days: number | null): string => {
  if (days === null || days < 0)
    return "bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400";
  if (days <= 30)
    return "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400";
  if (days <= 60)
    return "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400";
  return "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400";
};
</script>

<template>
  <Head :title="`Contracts — ${employee.first_name} ${employee.last_name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-5xl mx-auto w-full">
      <!-- Header -->
      <div
        class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
      >
        <div class="flex items-center gap-4">
          <Button variant="outline" size="icon" as-child>
            <Link :href="employeesIndex().url"
              ><ArrowLeft class="h-4 w-4"
            /></Link>
          </Button>
          <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
              {{ employee.first_name }} {{ employee.last_name }}
            </h1>
            <p class="text-sm text-zinc-500">
              {{ employee.employee_code }} · {{ employee.organization?.name }}
            </p>
          </div>
        </div>
        <Dialog v-model:open="showModal">
          <DialogTrigger as-child>
            <Button><Plus class="h-4 w-4 mr-2" /> Add Contract</Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-md">
            <DialogHeader
              ><DialogTitle>New Employment Contract</DialogTitle></DialogHeader
            >
            <form @submit.prevent="submitContract" class="space-y-4 mt-2">
              <div class="grid gap-2">
                <Label>Contract Type</Label>
                <select
                  v-model="form.type"
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option value="PKWT">PKWT (Fixed-Term)</option>
                  <option value="PKWTT">PKWTT (Permanent)</option>
                </select>
                <span v-if="form.errors.type" class="text-xs text-red-500">{{
                  form.errors.type
                }}</span>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                  <Label>Start Date</Label>
                  <Input type="date" v-model="form.start_date" required />
                </div>
                <div class="grid gap-2">
                  <Label
                    >End Date
                    <span class="text-zinc-400">(if PKWT)</span></Label
                  >
                  <Input type="date" v-model="form.end_date" />
                </div>
              </div>
              <div class="grid gap-2">
                <Label>Status</Label>
                <select
                  v-model="form.status"
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option value="ACTIVE">Active</option>
                  <option value="EXPIRED">Expired</option>
                  <option value="TERMINATED">Terminated</option>
                </select>
              </div>
              <div class="flex justify-end gap-2 pt-2">
                <Button
                  type="button"
                  variant="outline"
                  @click="showModal = false"
                  >Cancel</Button
                >
                <Button type="submit" :disabled="form.processing"
                  >Save Contract</Button
                >
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Contract List -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden"
      >
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead
              class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800"
            >
              <tr>
                <th class="px-5 py-4">Type</th>
                <th class="px-5 py-4">Start Date</th>
                <th class="px-5 py-4">End Date</th>
                <th class="px-5 py-4">Days Remaining</th>
                <th class="px-5 py-4">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
              <tr
                v-for="contract in contracts"
                :key="contract.id"
                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
              >
                <td class="px-5 py-4">
                  <span class="font-semibold text-zinc-800 dark:text-white">{{
                    contract.type
                  }}</span>
                </td>
                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">
                  {{ new Date(contract.start_date).toLocaleDateString() }}
                </td>
                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">
                  {{
                    contract.end_date
                      ? new Date(contract.end_date).toLocaleDateString()
                      : "—"
                  }}
                </td>
                <td class="px-5 py-4">
                  <template v-if="contract.end_date">
                    <span
                      class="px-2.5 py-1 text-xs font-medium rounded-full flex items-center gap-1.5 w-fit"
                      :class="urgencyClass(daysRemaining(contract.end_date))"
                    >
                      <AlertTriangle
                        v-if="
                          daysRemaining(contract.end_date) !== null &&
                          daysRemaining(contract.end_date)! <= 30
                        "
                        class="h-3 w-3"
                      />
                      {{
                        daysRemaining(contract.end_date) !== null
                          ? `${daysRemaining(contract.end_date)} days`
                          : "Expired"
                      }}
                    </span>
                  </template>
                  <span v-else class="text-zinc-400 text-xs">Permanent</span>
                </td>
                <td class="px-5 py-4">
                  <span
                    class="px-2.5 py-1 text-xs font-medium rounded-full"
                    :class="{
                      'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400':
                        contract.status === 'ACTIVE',
                      'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400':
                        contract.status === 'EXPIRED' ||
                        contract.status === 'TERMINATED',
                    }"
                  >
                    {{ contract.status }}
                  </span>
                </td>
              </tr>
              <tr v-if="!contracts.length">
                <td colspan="5" class="py-12 text-center text-zinc-500">
                  <FileText
                    class="h-12 w-12 mx-auto text-zinc-300 dark:text-zinc-700 mb-3"
                  />
                  <p class="font-medium text-zinc-800 dark:text-zinc-200">
                    No contracts yet
                  </p>
                  <p class="text-sm mt-1">
                    Add the first employment contract for this employee.
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
