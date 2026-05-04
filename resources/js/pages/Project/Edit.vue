<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Save, Users, Calendar } from "lucide-vue-next";
import {
  index as projectsIndex,
  edit as projectEdit,
} from "@/actions/App/Http/Controllers/ProjectController";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  project: any;
  organizations: any[];
  clients: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Operations", href: "#" },
  { title: "Projects", href: projectsIndex().url },
  { title: "Edit Project", href: "#" },
];

const form = useForm({
  project_code: props.project.project_code,
  name: props.project.name,
  description: props.project.description || "",
  organization_id: props.project.organization_id,
  client_id: props.project.client_id || "",
  start_date: props.project.start_date
    ? props.project.start_date.split("T")[0]
    : "",
  end_date: props.project.end_date ? props.project.end_date.split("T")[0] : "",
  budget: props.project.budget,
  status: props.project.status,
  notes: props.project.notes || "",
});

const submit = () => {
  form.put(projectEdit(props.project.id).url);
};
</script>

<template>
  <Head title="Edit Project" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
      <div class="flex items-center gap-4">
        <Button variant="outline" size="icon" as-child>
          <Link :href="projectsIndex().url">
            <ArrowLeft class="h-4 w-4" />
          </Link>
        </Button>
        <div>
          <h1
            class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Edit Project
          </h1>
          <p class="text-sm text-zinc-500">
            {{ form.project_code }} - {{ form.name }}
          </p>
        </div>
      </div>

      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm p-6"
      >
        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <div class="grid gap-2">
                <Label for="project_code">Project Code</Label>
                <Input id="project_code" v-model="form.project_code" required />
                <span
                  v-if="form.errors.project_code"
                  class="text-xs text-red-500"
                  >{{ form.errors.project_code }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="name">Project Name</Label>
                <Input id="name" v-model="form.name" required />
                <span v-if="form.errors.name" class="text-xs text-red-500">{{
                  form.errors.name
                }}</span>
              </div>

              <div class="grid gap-2">
                <Label for="organization_id">Organization</Label>
                <select
                  id="organization_id"
                  v-model="form.organization_id"
                  required
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option
                    v-for="org in organizations"
                    :key="org.id"
                    :value="org.id"
                  >
                    {{ org.code }} - {{ org.name }}
                  </option>
                </select>
                <span
                  v-if="form.errors.organization_id"
                  class="text-xs text-red-500"
                  >{{ form.errors.organization_id }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="client_id">Client</Label>
                <select
                  id="client_id"
                  v-model="form.client_id"
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option value="">No Client</option>
                  <option
                    v-for="client in clients"
                    :key="client.id"
                    :value="client.id"
                  >
                    {{ client.name }}
                  </option>
                </select>
                <span
                  v-if="form.errors.client_id"
                  class="text-xs text-red-500"
                  >{{ form.errors.client_id }}</span
                >
              </div>
            </div>

            <div class="space-y-4">
              <div class="grid gap-2">
                <Label for="start_date">Start Date</Label>
                <Input
                  id="start_date"
                  type="date"
                  v-model="form.start_date"
                  required
                />
                <span
                  v-if="form.errors.start_date"
                  class="text-xs text-red-500"
                  >{{ form.errors.start_date }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="end_date">End Date</Label>
                <Input
                  id="end_date"
                  type="date"
                  v-model="form.end_date"
                  required
                />
                <span
                  v-if="form.errors.end_date"
                  class="text-xs text-red-500"
                  >{{ form.errors.end_date }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="budget">Budget (IDR)</Label>
                <Input
                  id="budget"
                  type="number"
                  v-model="form.budget"
                  min="0"
                  step="1000000"
                  required
                />
                <span v-if="form.errors.budget" class="text-xs text-red-500">{{
                  form.errors.budget
                }}</span>
              </div>

              <div class="grid gap-2">
                <Label for="status">Status</Label>
                <select
                  id="status"
                  v-model="form.status"
                  required
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option value="PLANNING">Planning</option>
                  <option value="ONGOING">Ongoing</option>
                  <option value="ON_HOLD">On Hold</option>
                  <option value="COMPLETED">Completed</option>
                  <option value="CANCELLED">Cancelled</option>
                </select>
                <span v-if="form.errors.status" class="text-xs text-red-500">{{
                  form.errors.status
                }}</span>
              </div>
            </div>
          </div>

          <div class="grid gap-2">
            <Label for="description">Description</Label>
            <Textarea
              id="description"
              v-model="form.description"
              :rows="3"
              placeholder="Project description..."
            ></Textarea>
            <span v-if="form.errors.description" class="text-xs text-red-500">{{
              form.errors.description
            }}</span>
          </div>

          <div class="grid gap-2">
            <Label for="notes">Notes</Label>
            <Textarea
              id="notes"
              v-model="form.notes"
              :rows="2"
              placeholder="Additional notes..."
            ></Textarea>
            <span v-if="form.errors.notes" class="text-xs text-red-500">{{
              form.errors.notes
            }}</span>
          </div>

          <div
            class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800"
          >
            <Button type="button" variant="outline" as-child>
              <Link :href="projectsIndex().url">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
              <Save class="h-4 w-4 mr-2" />
              <span v-if="form.processing">Saving...</span>
              <span v-else>Update Project</span>
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
