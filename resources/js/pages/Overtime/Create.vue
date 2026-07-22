<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Save, Upload } from "lucide-vue-next";
import { computed } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  rules: any[];
  employees: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "HR Management", href: "#" },
  { title: "Overtime Requests", href: "/overtime" },
  { title: "Create Request", href: "/overtime/create" },
];

const form = useForm({
  employee_id: "",
  overtime_rule_id: "",
  date: new Date().toISOString().split("T")[0],
  start_time: "",
  end_time: "",
  reason: "",
  evidence: null as File | null,
});

const calculatedHours = computed(() => {
  if (!form.start_time || !form.end_time) return 0;

  const [startH, startM] = form.start_time.split(":").map(Number);
  const [endH, endM] = form.end_time.split(":").map(Number);

  let hours = endH + endM / 60 - (startH + startM / 60);
  if (hours < 0) hours += 24; // Handle overnight (simple)

  return hours.toFixed(2);
});

const formatDurasi = (hours: number) => {
  if (!hours || hours === 0) return "0 menit";
  const h = Math.floor(hours);
  const m = Math.round((hours - h) * 60);
  if (h === 0) return `${m} menit`;
  if (m === 0) return `${h} jam`;
  return `${h} jam ${m} menit`;
};

const submit = () => {
  form.post("/overtime", {
    forceFormData: true,
  });
};
</script>

<template>
  <Head title="Create Overtime Request" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-6 max-w-2xl mx-auto w-full">
      <div class="flex items-center gap-4">
        <Button variant="outline" size="icon" as-child>
          <Link href="/overtime"><ArrowLeft class="h-4 w-4" /></Link>
        </Button>
        <div>
          <h1
            class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Create Overtime Request
          </h1>
          <p class="text-sm text-zinc-500 mt-1">
            Submit a new request for overtime work
          </p>
        </div>
      </div>

      <form
        @submit.prevent="submit"
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 space-y-6"
      >
        <div class="grid gap-2">
          <Label>Employee</Label>
          <select
            v-model="form.employee_id"
            class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 dark:focus-visible:ring-zinc-300"
            required
          >
            <option value="" disabled>Select Employee</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
              {{ emp.first_name }} {{ emp.last_name }} ({{ emp.employee_code }})
            </option>
          </select>
          <span v-if="form.errors.employee_id" class="text-xs text-red-500">{{
            form.errors.employee_id
          }}</span>
        </div>

        <div class="grid gap-2">
          <Label>Overtime Rule (Scheme)</Label>
          <select
            v-model="form.overtime_rule_id"
            class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 dark:focus-visible:ring-zinc-300"
            required
          >
            <option value="" disabled>Select Scheme</option>
            <option v-for="rule in rules" :key="rule.id" :value="rule.id">
              {{ rule.name }} (Multiplier: {{ rule.multiplier }}x)
            </option>
          </select>
          <span
            v-if="form.errors.overtime_rule_id"
            class="text-xs text-red-500"
            >{{ form.errors.overtime_rule_id }}</span
          >
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="grid gap-2">
            <Label>Date</Label>
            <Input type="date" v-model="form.date" required />
            <span v-if="form.errors.date" class="text-xs text-red-500">{{
              form.errors.date
            }}</span>
          </div>
          <div class="grid gap-2">
            <Label>Start Time</Label>
            <Input type="time" v-model="form.start_time" required />
            <span v-if="form.errors.start_time" class="text-xs text-red-500">{{
              form.errors.start_time
            }}</span>
          </div>
          <div class="grid gap-2">
            <Label>End Time</Label>
            <Input type="time" v-model="form.end_time" required />
            <span v-if="form.errors.end_time" class="text-xs text-red-500">{{
              form.errors.end_time
            }}</span>
          </div>
        </div>

        <div
          class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg flex items-center justify-between"
        >
          <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400"
            >Estimated Duration:</span
          >
          <span
            class="text-lg font-bold text-indigo-600 dark:text-indigo-400"
            >{{ formatDurasi(Number(calculatedHours)) }}</span
          >
        </div>

        <div class="grid gap-2">
          <Label>Reason / Task Description</Label>
          <textarea
            v-model="form.reason"
            class="flex min-h-[80px] w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white placeholder:text-zinc-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 dark:focus-visible:ring-zinc-300"
            placeholder="Describe the work performed..."
          ></textarea>
          <span v-if="form.errors.reason" class="text-xs text-red-500">{{
            form.errors.reason
          }}</span>
        </div>

        <div class="grid gap-2">
          <Label>Evidence (Photo/PDF)</Label>
          <div class="flex items-center gap-4">
            <Input
              type="file"
              @input="
                form.evidence =
                  ($event.target as HTMLInputElement).files?.[0] || null
              "
              accept=".jpg,.jpeg,.png,.pdf"
            />
          </div>
          <p class="text-xs text-zinc-500">
            Upload approval email, timesheet, or photo of work. Max 2MB.
          </p>
          <span v-if="form.errors.evidence" class="text-xs text-red-500">{{
            form.errors.evidence
          }}</span>
        </div>

        <div
          class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800"
        >
          <Button variant="outline" type="button" as-child>
            <Link href="/overtime">Cancel</Link>
          </Button>
          <Button type="submit" :disabled="form.processing">
            <Save class="h-4 w-4 mr-2" /> Submit Request
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
