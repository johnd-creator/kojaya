<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { Layers, Plus, Pencil } from "lucide-vue-next";
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

const props = defineProps<{ jobGrades: any[] }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "HR Master Data", href: "#" },
  { title: "Job Grades", href: "/job-grades" },
];

const showModal = ref(false);
const editTarget = ref<any>(null);
const form = useForm({ code: "", name: "", level: "" });

const openCreate = () => {
  editTarget.value = null;
  form.reset();
  showModal.value = true;
};
const openEdit = (g: any) => {
  editTarget.value = g;
  form.code = g.code;
  form.name = g.name;
  form.level = g.level;
  showModal.value = true;
};

const submit = () => {
  if (editTarget.value) {
    form.put(`/job-grades/${editTarget.value.id}`, {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    form.post("/job-grades", {
      onSuccess: () => {
        showModal.value = false;
        form.reset();
      },
    });
  }
};

const levelLabel = (level: number) =>
  [
    "",
    "Pelaksana",
    "Pelaksana Senior",
    "Penyelia Dasar",
    "Penyelia Atas",
    "Manajer",
    "Direksi",
  ][level] ?? `Level ${level}`;
</script>

<template>
  <Head title="Job Grades" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-3xl mx-auto w-full">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
            Job Grades
          </h1>
          <p class="text-zinc-500 mt-1">
            Jenjang jabatan karyawan (Pelaksana → Direksi).
          </p>
        </div>
        <Dialog v-model:open="showModal">
          <DialogTrigger as-child>
            <Button @click="openCreate"
              ><Plus class="h-4 w-4 mr-2" />Add Grade</Button
            >
          </DialogTrigger>
          <DialogContent class="sm:max-w-sm">
            <DialogHeader
              ><DialogTitle
                >{{ editTarget ? "Edit" : "New" }} Job Grade</DialogTitle
              ></DialogHeader
            >
            <form @submit.prevent="submit" class="space-y-4 mt-2">
              <div class="grid gap-2">
                <Label>Code</Label
                ><Input
                  v-model="form.code"
                  placeholder="PELAKSANA_SENIOR"
                  required
                />
              </div>
              <div class="grid gap-2">
                <Label>Name</Label
                ><Input
                  v-model="form.name"
                  placeholder="Pelaksana Senior"
                  required
                />
              </div>
              <div class="grid gap-2">
                <Label>Level (1=lowest)</Label
                ><Input type="number" v-model="form.level" min="1" required />
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
              <th class="px-5 py-4">Level</th>
              <th class="px-5 py-4">Code</th>
              <th class="px-5 py-4">Name</th>
              <th class="px-5 py-4">Positions</th>
              <th class="px-5 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr
              v-for="g in jobGrades"
              :key="g.id"
              class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
            >
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs dark:bg-indigo-900/30"
                  >{{ g.level }}</span
                >
              </td>
              <td
                class="px-5 py-4 font-mono text-zinc-600 dark:text-zinc-400 text-xs"
              >
                {{ g.code }}
              </td>
              <td class="px-5 py-4 font-semibold text-zinc-900 dark:text-white">
                {{ g.name }}
              </td>
              <td class="px-5 py-4 text-zinc-500">{{ g.positions_count }}</td>
              <td class="px-5 py-4 flex justify-end">
                <Button size="icon" variant="ghost" @click="openEdit(g)"
                  ><Pencil class="h-4 w-4"
                /></Button>
              </td>
            </tr>
            <tr v-if="!jobGrades.length">
              <td colspan="5" class="py-12 text-center text-zinc-500">
                <Layers class="h-12 w-12 mx-auto text-zinc-300 mb-3" />
                <p>No grades yet. Run JobGradeSeeder.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
