<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { Clock, Plus, Pencil, Info } from "lucide-vue-next";
import { ref } from "vue";
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

const props = defineProps<{ shifts: any[] }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "HR Master Data", href: "#" },
  { title: "Work Shifts", href: "/work-shifts" },
];

const showModal = ref(false);
const editTarget = ref<any>(null);
const form = useForm({
  name: "",
  type: "SHIFT",
  start_time: "",
  end_time: "",
  is_flexible: false,
  flexible_minutes: 60,
});

const openCreate = () => {
  editTarget.value = null;
  form.reset();
  form.type = "SHIFT";
  form.flexible_minutes = 60;
  showModal.value = true;
};
const openEdit = (s: any) => {
  editTarget.value = s;
  form.name = s.name;
  form.type = s.type;
  form.start_time = s.start_time.slice(0, 5);
  form.end_time = s.end_time.slice(0, 5);
  form.is_flexible = s.is_flexible;
  form.flexible_minutes = s.flexible_minutes;
  showModal.value = true;
};

const submit = () => {
  if (editTarget.value) {
    form.put(`/work-shifts/${editTarget.value.id}`, {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    form.post("/work-shifts", {
      onSuccess: () => {
        showModal.value = false;
        form.reset();
      },
    });
  }
};

const shiftColor = (type: string) =>
  type === "SHIFT"
    ? "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"
    : "bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400";
</script>

<template>
  <Head title="Work Shifts" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
            Work Shifts
          </h1>
          <p class="text-zinc-500 mt-1">
            Definisi shift kerja dan aturan jam masuk.
          </p>
        </div>
        <Dialog v-model:open="showModal">
          <DialogTrigger as-child>
            <Button @click="openCreate"
              ><Plus class="h-4 w-4 mr-2" />Add Shift</Button
            >
          </DialogTrigger>
          <DialogContent class="sm:max-w-md">
            <DialogHeader
              ><DialogTitle
                >{{ editTarget ? "Edit" : "New" }} Work Shift</DialogTitle
              ></DialogHeader
            >
            <form @submit.prevent="submit" class="space-y-4 mt-2">
              <div class="grid gap-2">
                <Label>Name</Label
                ><Input v-model="form.name" placeholder="Shift Pagi" required />
              </div>
              <div class="grid gap-2">
                <Label>Type</Label>
                <select
                  v-model="form.type"
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option value="SHIFT">SHIFT (rotating)</option>
                  <option value="NON_SHIFT">NON_SHIFT (day shift)</option>
                </select>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                  <Label>Start Time</Label
                  ><Input type="time" v-model="form.start_time" required />
                </div>
                <div class="grid gap-2">
                  <Label>End Time</Label
                  ><Input type="time" v-model="form.end_time" required />
                </div>
              </div>
              <div
                v-if="form.type === 'NON_SHIFT'"
                class="rounded-lg p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 flex gap-2 text-sm text-amber-800 dark:text-amber-300"
              >
                <Info class="h-4 w-4 mt-0.5 shrink-0" />
                <p>
                  Non-shift supports <strong>flexible time</strong>: if an
                  employee clocks in late, their scheduled end time shifts by
                  the same amount.
                </p>
              </div>
              <div v-if="form.type === 'NON_SHIFT'" class="grid gap-2">
                <Label>Flexible Window (minutes)</Label>
                <Input
                  type="number"
                  v-model="form.flexible_minutes"
                  min="0"
                  max="120"
                />
                <p class="text-xs text-zinc-400">
                  Maximum tolerance. Lateness beyond this is not compensated.
                </p>
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
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden"
      >
        <table class="w-full text-sm text-left">
          <thead
            class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800"
          >
            <tr>
              <th class="px-5 py-4">Name</th>
              <th class="px-5 py-4">Type</th>
              <th class="px-5 py-4">Start</th>
              <th class="px-5 py-4">End</th>
              <th class="px-5 py-4">Flexible</th>
              <th class="px-5 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr
              v-for="s in shifts"
              :key="s.id"
              class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
            >
              <td class="px-5 py-4 font-semibold text-zinc-900 dark:text-white">
                {{ s.name }}
              </td>
              <td class="px-5 py-4">
                <span
                  class="px-2 py-0.5 text-xs font-medium rounded-full"
                  :class="shiftColor(s.type)"
                  >{{ s.type }}</span
                >
              </td>
              <td class="px-5 py-4 font-mono text-zinc-600 dark:text-zinc-300">
                {{ s.start_time.slice(0, 5) }}
              </td>
              <td class="px-5 py-4 font-mono text-zinc-600 dark:text-zinc-300">
                {{ s.end_time.slice(0, 5) }}
              </td>
              <td class="px-5 py-4">
                <span
                  v-if="s.is_flexible"
                  class="text-xs text-purple-600 dark:text-purple-400"
                  >Yes (±{{ s.flexible_minutes }}m)</span
                >
                <span v-else class="text-xs text-zinc-400">—</span>
              </td>
              <td class="px-5 py-4 flex justify-end">
                <Button size="icon" variant="ghost" @click="openEdit(s)"
                  ><Pencil class="h-4 w-4"
                /></Button>
              </td>
            </tr>
            <tr v-if="!shifts.length">
              <td colspan="6" class="py-12 text-center text-zinc-500">
                <Clock class="h-12 w-12 mx-auto text-zinc-300 mb-3" />
                <p>No shifts yet. Run WorkShiftSeeder.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
