<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Save } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

type Employee = {
  id: number;
  employee_code: string;
  first_name: string;
  last_name?: string;
  organization_id: string;
};
type Organization = { id: string; code?: string; name: string };

const props = defineProps<{
  employees: Employee[];
  organizations: Organization[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Human Resources", href: "#" },
  { title: "Employee Transfers", href: "/employee-transfers" },
  { title: "Create", href: "/employee-transfers/create" },
];

const form = useForm({
  employee_id: "",
  to_organization_id: "",
  effective_date: "",
  reason: "",
});

const submit = () => {
  form.post("/employee-transfers");
};
</script>

<template>
  <Head title="Create Employee Transfer" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-3xl mx-auto w-full">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Button variant="outline" size="icon" as-child>
            <Link href="/employee-transfers"
              ><ArrowLeft class="h-4 w-4"
            /></Link>
          </Button>
          <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
              Create Transfer
            </h1>
            <p class="text-zinc-500 mt-1">Ajukan mutasi karyawan.</p>
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6"
      >
        <form @submit.prevent="submit" class="space-y-4">
          <div class="grid gap-2">
            <Label>Employee</Label>
            <select
              v-model="form.employee_id"
              class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
              required
            >
              <option value="" disabled>Pilih employee</option>
              <option v-for="e in employees" :key="e.id" :value="String(e.id)">
                {{ e.employee_code }} - {{ e.first_name }} {{ e.last_name }}
              </option>
            </select>
            <span v-if="form.errors.employee_id" class="text-xs text-red-500">{{
              form.errors.employee_id
            }}</span>
          </div>

          <div class="grid gap-2">
            <Label>To Organization</Label>
            <select
              v-model="form.to_organization_id"
              class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
              required
            >
              <option value="" disabled>Pilih organisasi tujuan</option>
              <option
                v-for="org in organizations"
                :key="org.id"
                :value="org.id"
              >
                {{ org.code ? `${org.code} - ${org.name}` : org.name }}
              </option>
            </select>
            <span
              v-if="form.errors.to_organization_id"
              class="text-xs text-red-500"
              >{{ form.errors.to_organization_id }}</span
            >
          </div>

          <div class="grid gap-2">
            <Label>Effective Date</Label>
            <Input v-model="form.effective_date" type="date" required />
            <span
              v-if="form.errors.effective_date"
              class="text-xs text-red-500"
              >{{ form.errors.effective_date }}</span
            >
          </div>

          <div class="grid gap-2">
            <Label>Reason</Label>
            <Input v-model="form.reason" />
            <span v-if="form.errors.reason" class="text-xs text-red-500">{{
              form.errors.reason
            }}</span>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <Button variant="outline" type="button" as-child>
              <Link href="/employee-transfers">Batal</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
              <Save class="h-4 w-4 mr-2" />Submit
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
