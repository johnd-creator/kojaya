<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
  Flag,
  Plus,
  Calendar,
  CheckCircle,
  Clock,
  AlertCircle,
} from "lucide-vue-next";
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

const props = defineProps<{
  project: any;
  milestones: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: props.project.name, href: `/projects/${props.project.id}` },
  { title: "Milestones", href: `/projects/${props.project.id}/milestones` },
];

const createModal = ref(false);
const form = useForm({
  name: "",
  description: "",
  due_date: "",
  progress_percentage: 0,
});

const submit = () => {
  form.post(`/projects/${props.project.id}/milestones`, {
    onSuccess: () => {
      createModal.value = false;
      form.reset();
    },
  });
};

const updateProgress = (milestone: any, percentage: number) => {
  useForm({ progress_percentage: percentage }).patch(
    `/projects/${props.project.id}/milestones/${milestone.id}/progress`,
  );
};

const getStatusColor = (status: string) => {
  switch (status) {
    case "COMPLETED":
      return "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300";
    case "IN_PROGRESS":
      return "bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300";
    case "OVERDUE":
      return "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300";
    default:
      return "bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300";
  }
};
</script>

<template>
  <Head title="Project Milestones" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      <div class="flex items-center justify-between">
        <div>
          <h1
            class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2"
          >
            <Flag class="h-6 w-6 text-indigo-600" />
            Project Milestones
          </h1>
          <p class="text-sm text-zinc-500 mt-1">
            Track key deliverables and timeline for {{ project.name }}
          </p>
        </div>

        <Dialog v-model:open="createModal">
          <DialogTrigger as-child>
            <Button> <Plus class="h-4 w-4 mr-2" /> Add Milestone </Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-md">
            <DialogHeader>
              <DialogTitle>Add New Milestone</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submit" class="space-y-4 mt-2">
              <div class="grid gap-2">
                <Label>Milestone Name</Label>
                <Input
                  v-model="form.name"
                  placeholder="e.g. Foundation Complete"
                  required
                />
                <span v-if="form.errors.name" class="text-xs text-red-500">{{
                  form.errors.name
                }}</span>
              </div>
              <div class="grid gap-2">
                <Label>Due Date</Label>
                <Input type="date" v-model="form.due_date" required />
                <span
                  v-if="form.errors.due_date"
                  class="text-xs text-red-500"
                  >{{ form.errors.due_date }}</span
                >
              </div>
              <div class="grid gap-2">
                <Label>Description (Optional)</Label>
                <Input
                  v-model="form.description"
                  placeholder="Brief details..."
                />
              </div>
              <div class="flex justify-end gap-2 pt-2">
                <Button
                  type="button"
                  variant="outline"
                  @click="createModal = false"
                  >Cancel</Button
                >
                <Button type="submit" :disabled="form.processing"
                  >Create Milestone</Button
                >
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="milestone in milestones"
          :key="milestone.id"
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 shadow-sm relative overflow-hidden"
        >
          <div class="flex justify-between items-start mb-2">
            <h3 class="font-semibold text-zinc-900 dark:text-white">
              {{ milestone.name }}
            </h3>
            <span
              class="text-xs px-2 py-1 rounded-full font-medium"
              :class="getStatusColor(milestone.status)"
            >
              {{ milestone.status.replace("_", " ") }}
            </span>
          </div>

          <div class="text-sm text-zinc-500 mb-4 flex items-center gap-1">
            <Calendar class="h-3 w-3" /> Due: {{ milestone.due_date }}
          </div>

          <div class="space-y-2">
            <div class="flex justify-between text-xs mb-1">
              <span>Progress</span>
              <span class="font-medium"
                >{{ milestone.progress_percentage }}%</span
              >
            </div>
            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
              <div
                class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${milestone.progress_percentage}%` }"
              ></div>
            </div>

            <div
              class="flex justify-between gap-2 mt-4 pt-2 border-t border-zinc-100 dark:border-zinc-800"
            >
              <Button
                variant="outline"
                size="xs"
                class="flex-1 text-xs h-7"
                @click="updateProgress(milestone, 0)"
                :disabled="milestone.progress_percentage === 0"
                >0%</Button
              >
              <Button
                variant="outline"
                size="xs"
                class="flex-1 text-xs h-7"
                @click="updateProgress(milestone, 50)"
                >50%</Button
              >
              <Button
                variant="outline"
                size="xs"
                class="flex-1 text-xs h-7"
                @click="updateProgress(milestone, 100)"
                :disabled="milestone.progress_percentage === 100"
                >100%</Button
              >
            </div>
          </div>
        </div>

        <div
          v-if="milestones.length === 0"
          class="col-span-full py-12 text-center text-zinc-500 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700"
        >
          <Flag class="h-8 w-8 mx-auto mb-2 opacity-50" />
          <p>No milestones defined yet.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
