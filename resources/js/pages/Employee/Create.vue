<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Save } from "lucide-vue-next";
import {
  index as employeesIndex,
  store as employeeStore,
} from "@/actions/App/Http/Controllers/EmployeeController";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

defineProps<{
  organizations: any[];
  departments: any[];
  positions: any[];
  jobGrades: any[];
  workShifts: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "HR & Employee", href: "#" },
  { title: "Employee Master", href: employeesIndex().url },
  { title: "Create Employee", href: "#" },
];

const form = useForm({
  first_name: "",
  last_name: "",
  email: "",
  employee_code: "",
  organization_id: "",
  gender: "M",
  birth_date: "",
  hire_date: new Date().toISOString().split("T")[0],
  status: "ACTIVE",
  employee_type: "TKWT",
  department_id: "",
  position_id: "",
  job_grade_id: "",
  work_shift_id: "",
  shift_group: "",
});

const submit = () => {
  form.post(employeeStore().url);
};
</script>

<template>
  <Head title="Create Employee" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
      <div class="flex items-center gap-4">
        <Button variant="outline" size="icon" as-child>
          <Link :href="employeesIndex().url">
            <ArrowLeft class="h-4 w-4" />
          </Link>
        </Button>
        <div>
          <h1
            class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            New Employee
          </h1>
          <p class="text-sm text-zinc-500">
            Add a new employee and assign them to a unit.
          </p>
        </div>
      </div>

      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm p-6 overflow-hidden"
      >
        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Personal Details -->
            <div class="space-y-4">
              <h3
                class="text-lg font-medium text-zinc-900 dark:text-white mb-2"
              >
                Personal Details
              </h3>

              <div class="grid gap-2">
                <Label for="first_name">First Name</Label>
                <Input
                  id="first_name"
                  v-model="form.first_name"
                  required
                  autofocus
                />
                <span
                  v-if="form.errors.first_name"
                  class="text-xs text-red-500"
                  >{{ form.errors.first_name }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="last_name">Last Name</Label>
                <Input id="last_name" v-model="form.last_name" />
                <span
                  v-if="form.errors.last_name"
                  class="text-xs text-red-500"
                  >{{ form.errors.last_name }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="email">Email Address</Label>
                <Input
                  id="email"
                  type="email"
                  v-model="form.email"
                  placeholder="employee@company.com"
                />
                <span v-if="form.errors.email" class="text-xs text-red-500">{{
                  form.errors.email
                }}</span>
              </div>

              <div class="grid gap-2">
                <Label for="gender">Gender</Label>
                <select
                  id="gender"
                  v-model="form.gender"
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                >
                  <option value="M">Male</option>
                  <option value="F">Female</option>
                </select>
                <span v-if="form.errors.gender" class="text-xs text-red-500">{{
                  form.errors.gender
                }}</span>
              </div>

              <div class="grid gap-2">
                <Label for="birth_date">Birth Date</Label>
                <Input id="birth_date" type="date" v-model="form.birth_date" />
                <span
                  v-if="form.errors.birth_date"
                  class="text-xs text-red-500"
                  >{{ form.errors.birth_date }}</span
                >
              </div>
            </div>

            <!-- Employment Details -->
            <div class="space-y-4">
              <h3
                class="text-lg font-medium text-zinc-900 dark:text-white mb-2"
              >
                Employment Info
              </h3>

              <div class="grid gap-2">
                <Label for="employee_code">Employee ID (NIK)</Label>
                <Input
                  id="employee_code"
                  v-model="form.employee_code"
                  required
                />
                <span
                  v-if="form.errors.employee_code"
                  class="text-xs text-red-500"
                  >{{ form.errors.employee_code }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="organization_id">Unit / Organization</Label>
                <select
                  id="organization_id"
                  v-model="form.organization_id"
                  required
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                >
                  <option value="" disabled>Select a unit...</option>
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
                <Label for="hire_date">Hire Date</Label>
                <Input
                  id="hire_date"
                  type="date"
                  v-model="form.hire_date"
                  required
                />
                <span
                  v-if="form.errors.hire_date"
                  class="text-xs text-red-500"
                  >{{ form.errors.hire_date }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="status">Initial Status</Label>
                <select
                  id="status"
                  v-model="form.status"
                  required
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                >
                  <option value="ACTIVE">Active</option>
                  <option value="RESIGNED">Resigned</option>
                  <option value="TERMINATED">Terminated</option>
                </select>
                <span v-if="form.errors.status" class="text-xs text-red-500">{{
                  form.errors.status
                }}</span>
              </div>
            </div>

            <!-- HR Classification -->
            <div class="space-y-4">
              <h3
                class="text-lg font-medium text-zinc-900 dark:text-white mb-2"
              >
                HR Classification
              </h3>

              <div class="grid gap-2">
                <Label for="employee_type">Employee Type</Label>
                <select
                  id="employee_type"
                  v-model="form.employee_type"
                  required
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                >
                  <option value="TKWT">TKWT (Contract)</option>
                  <option value="Organic">Organic (Permanent)</option>
                </select>
                <span
                  v-if="form.errors.employee_type"
                  class="text-xs text-red-500"
                  >{{ form.errors.employee_type }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="department_id">Department (Optional)</Label>
                <select
                  id="department_id"
                  v-model="form.department_id"
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                >
                  <option value="">No Department</option>
                  <option
                    v-for="dept in departments"
                    :key="dept.id"
                    :value="dept.id"
                  >
                    {{ dept.name }}
                  </option>
                </select>
              </div>

              <div class="grid gap-2">
                <Label for="position_id">Position (Optional)</Label>
                <select
                  id="position_id"
                  v-model="form.position_id"
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                >
                  <option value="">No Position</option>
                  <option
                    v-for="pos in positions"
                    :key="pos.id"
                    :value="pos.id"
                  >
                    {{ pos.name }}
                  </option>
                </select>
              </div>

              <div class="grid gap-2">
                <Label for="job_grade_id">Job Grade (Optional)</Label>
                <select
                  id="job_grade_id"
                  v-model="form.job_grade_id"
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                >
                  <option value="">No Job Grade</option>
                  <option v-for="jg in jobGrades" :key="jg.id" :value="jg.id">
                    {{ jg.code }} - Level {{ jg.level }}
                  </option>
                </select>
              </div>

              <div class="grid gap-2">
                <Label for="work_shift_id">Default Shift (Optional)</Label>
                <select
                  id="work_shift_id"
                  v-model="form.work_shift_id"
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                >
                  <option value="">No Default Shift</option>
                  <option
                    v-for="shift in workShifts"
                    :key="shift.id"
                    :value="shift.id"
                  >
                    {{ shift.name }}
                  </option>
                </select>
              </div>

              <div class="grid gap-2">
                <Label for="shift_group">Shift Group (Operasional)</Label>
                <select
                  id="shift_group"
                  v-model="form.shift_group"
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                >
                  <option value="">Non-Shift / Tidak ada Group</option>
                  <option value="A">Group A</option>
                  <option value="B">Group B</option>
                  <option value="C">Group C</option>
                  <option value="D">Group D</option>
                </select>
              </div>
            </div>
          </div>

          <div
            class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800"
          >
            <Button type="button" variant="outline" as-child>
              <Link :href="employeesIndex().url">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
              <Save class="h-4 w-4 mr-2" />
              <span v-if="form.processing">Saving...</span>
              <span v-else>Save Employee</span>
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
