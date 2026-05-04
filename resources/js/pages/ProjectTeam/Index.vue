<script setup lang="ts">
import { Head, useForm, Link } from "@inertiajs/vue3";
import { Users, Plus, Trash2, Edit2, Search, ArrowLeft } from "lucide-vue-next";
import { ref, computed } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
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
  project: any;
  team: any[];
  availableEmployees: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: props.project.name, href: `/projects/${props.project.id}` },
  { title: "Team & Mobilization", href: `/projects/${props.project.id}/team` },
];

const activeTab = ref("list"); // list | kanban
const createModal = ref(false);
const bulkModal = ref(false);
const editModal = ref(false);
const selectedMember = ref<any>(null);
const searchQuery = ref("");
const deleteDialogOpen = ref(false);
const deleteTarget = ref<any>(null);

// Kanban columns
const mobilizationStages = [
  "RECRUITMENT",
  "SCREENING",
  "MCU",
  "ONBOARDING",
  "PLACED",
];

const form = useForm({
  employee_id: "",
  role: "",
  start_date: "",
  end_date: "",
  daily_rate_cost: 0,
  notes: "",
});

const bulkForm = useForm({
  employee_ids: [] as string[],
  role: "",
  start_date: "",
  daily_rate_cost: 0,
});

const editForm = useForm({
  role: "",
  end_date: "",
  daily_rate_cost: 0,
  status: "",
  notes: "",
});

const openCreate = () => {
  form.reset();
  createModal.value = true;
};

const openEdit = (member: any) => {
  selectedMember.value = member;
  editForm.role = member.role;
  editForm.end_date = member.end_date;
  editForm.daily_rate_cost = member.daily_rate_cost;
  editForm.status = member.status || "RECRUITMENT";
  editForm.notes = member.notes;
  editModal.value = true;
};

const submitCreate = () => {
  form.post(`/projects/${props.project.id}/team`, {
    onSuccess: () => {
      createModal.value = false;
      form.reset();
    },
  });
};

const submitBulk = () => {
  bulkForm.post(`/projects/${props.project.id}/team/bulk-assign`, {
    onSuccess: () => {
      bulkModal.value = false;
      bulkForm.reset();
    },
  });
};

const submitEdit = () => {
  editForm.put(`/projects/team/${selectedMember.value.id}`, {
    onSuccess: () => {
      editModal.value = false;
    },
  });
};

const deleteMember = (member: any) => {
  deleteTarget.value = member;
  deleteDialogOpen.value = true;
};

const confirmDeleteMember = (): void => {
  if (!deleteTarget.value) {
    return;
  }

  useForm({}).delete(`/projects/team/${deleteTarget.value.id}`, {
    onFinish: () => {
      deleteDialogOpen.value = false;
      deleteTarget.value = null;
    },
  });
};

const updateMobilizationStatus = (member: any, newStatus: string) => {
  useForm({ status: newStatus }).post(
    `/projects/team/${member.id}/mobilization`,
    {
      preserveScroll: true,
    },
  );
};

// Filtered team for list view
const filteredTeam = computed(() => {
  if (!searchQuery.value) return props.team;
  return props.team.filter(
    (m) =>
      m.employee.first_name
        .toLowerCase()
        .includes(searchQuery.value.toLowerCase()) ||
      m.employee.last_name
        .toLowerCase()
        .includes(searchQuery.value.toLowerCase()) ||
      m.role.toLowerCase().includes(searchQuery.value.toLowerCase()),
  );
});

// Kanban grouping
const kanbanData = computed(() => {
  const groups: Record<string, any[]> = {};
  mobilizationStages.forEach((stage) => (groups[stage] = []));

  props.team.forEach((member) => {
    const status = member.status || "RECRUITMENT"; // Default to recruitment if null
    if (groups[status]) {
      groups[status].push(member);
    }
  });
  return groups;
});

const getStatusColor = (status: string) => {
  switch (status) {
    case "PLACED":
      return "bg-green-100 text-green-800 border-green-200";
    case "ONBOARDING":
      return "bg-blue-100 text-blue-800 border-blue-200";
    case "MCU":
      return "bg-purple-100 text-purple-800 border-purple-200";
    case "SCREENING":
      return "bg-yellow-100 text-yellow-800 border-yellow-200";
    default:
      return "bg-zinc-100 text-zinc-800 border-zinc-200";
  }
};
</script>

<template>
  <Head title="Project Team" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      <div class="flex items-center gap-4">
        <Link :href="`/projects/${project.id}`">
          <Button variant="ghost" size="icon" class="rounded-full">
            <ArrowLeft class="h-5 w-5" />
          </Button>
        </Link>
        <div
          class="flex flex-col md:flex-row md:items-center justify-between gap-4 flex-1"
        >
          <div>
            <h1
              class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2"
            >
              <Users class="h-6 w-6 text-indigo-600" />
              Team & Mobilization
            </h1>
            <p class="text-sm text-zinc-500 mt-1">
              Manage manpower allocation and mobilization status for
              {{ project.name }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <div class="flex bg-zinc-100 dark:bg-zinc-800 p-1 rounded-lg">
              <button
                @click="activeTab = 'list'"
                class="px-3 py-1.5 text-sm font-medium rounded-md transition-all"
                :class="
                  activeTab === 'list'
                    ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white'
                    : 'text-zinc-500 hover:text-zinc-700'
                "
              >
                List View
              </button>
              <button
                @click="activeTab = 'kanban'"
                class="px-3 py-1.5 text-sm font-medium rounded-md transition-all"
                :class="
                  activeTab === 'kanban'
                    ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white'
                    : 'text-zinc-500 hover:text-zinc-700'
                "
              >
                Kanban Board
              </button>
            </div>

            <Dialog v-model:open="bulkModal">
              <DialogTrigger as-child>
                <Button variant="outline">
                  <Users class="h-4 w-4 mr-2" /> Bulk Assign
                </Button>
              </DialogTrigger>
              <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                  <DialogTitle>Bulk Assign Employees</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitBulk" class="space-y-4 mt-2">
                  <div class="grid gap-2">
                    <Label>Select Employees</Label>
                    <div
                      class="border rounded-md max-h-48 overflow-y-auto p-2 space-y-2"
                    >
                      <div
                        v-for="emp in availableEmployees"
                        :key="emp.id"
                        class="flex items-center gap-2"
                      >
                        <input
                          type="checkbox"
                          :value="emp.id"
                          v-model="bulkForm.employee_ids"
                          class="rounded border-zinc-300"
                        />
                        <span class="text-sm"
                          >{{ emp.first_name }} {{ emp.last_name }} ({{
                            emp.employee_code
                          }})</span
                        >
                      </div>
                      <div
                        v-if="availableEmployees.length === 0"
                        class="text-sm text-zinc-500 text-center py-4"
                      >
                        No available employees found.
                      </div>
                    </div>
                    <span
                      v-if="bulkForm.errors.employee_ids"
                      class="text-xs text-red-500"
                      >{{ bulkForm.errors.employee_ids }}</span
                    >
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                      <Label>Role</Label>
                      <Input
                        v-model="bulkForm.role"
                        placeholder="e.g. Technician"
                        required
                      />
                    </div>
                    <div class="grid gap-2">
                      <Label>Daily Rate</Label>
                      <Input
                        type="number"
                        v-model="bulkForm.daily_rate_cost"
                        required
                      />
                    </div>
                  </div>
                  <div class="grid gap-2">
                    <Label>Start Date</Label>
                    <Input type="date" v-model="bulkForm.start_date" required />
                  </div>
                  <div class="flex justify-end gap-2 pt-2">
                    <Button
                      type="button"
                      variant="outline"
                      @click="bulkModal = false"
                      >Cancel</Button
                    >
                    <Button type="submit" :disabled="bulkForm.processing"
                      >Assign Selected</Button
                    >
                  </div>
                </form>
              </DialogContent>
            </Dialog>

            <Button @click="openCreate">
              <Plus class="h-4 w-4 mr-2" /> Add Member
            </Button>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div v-if="activeTab === 'list'" class="space-y-4">
        <div class="relative">
          <Search class="absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
          <Input
            v-model="searchQuery"
            placeholder="Search by name or role..."
            class="pl-9 max-w-sm"
          />
        </div>

        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden"
        >
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead
                class="text-xs text-zinc-500 bg-zinc-50 dark:bg-zinc-800 uppercase border-b border-zinc-200 dark:border-zinc-800"
              >
                <tr>
                  <th class="px-6 py-3 font-medium">Employee</th>
                  <th class="px-6 py-3 font-medium">Role</th>
                  <th class="px-6 py-3 font-medium">Duration</th>
                  <th class="px-6 py-3 font-medium">Mobilization</th>
                  <th class="px-6 py-3 font-medium">Cost/Day</th>
                  <th class="px-6 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                <tr v-if="filteredTeam.length === 0">
                  <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                    No team members found.
                  </td>
                </tr>
                <tr
                  v-for="member in filteredTeam"
                  :key="member.id"
                  class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
                >
                  <td
                    class="px-6 py-4 font-medium text-zinc-900 dark:text-white"
                  >
                    {{ member.employee.first_name }}
                    {{ member.employee.last_name }}
                    <div class="text-xs text-zinc-500 font-normal">
                      {{ member.employee.employee_code }}
                    </div>
                  </td>
                  <td class="px-6 py-4 text-zinc-500">
                    {{ member.role }}
                  </td>
                  <td class="px-6 py-4">
                    <div
                      class="flex items-center gap-1 text-zinc-700 dark:text-zinc-300"
                    >
                      <span>{{ member.start_date }}</span>
                      <span class="text-zinc-400">→</span>
                      <span>{{ member.end_date || "Ongoing" }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                      :class="getStatusColor(member.status || 'RECRUITMENT')"
                    >
                      {{ member.status || "RECRUITMENT" }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    {{ formatCurrency(member.daily_rate_cost) }}
                  </td>
                  <td class="px-6 py-4 text-right flex justify-end gap-2">
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0"
                      @click="openEdit(member)"
                    >
                      <Edit2 class="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                      @click="deleteMember(member)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Kanban View -->
      <div
        v-if="activeTab === 'kanban'"
        class="flex gap-4 overflow-x-auto pb-4 min-h-[600px]"
      >
        <div
          v-for="stage in mobilizationStages"
          :key="stage"
          class="flex-shrink-0 w-80 flex flex-col gap-3"
        >
          <div class="flex items-center justify-between px-2">
            <h3 class="font-semibold text-zinc-700 dark:text-zinc-300 text-sm">
              {{ stage }}
            </h3>
            <span
              class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-xs px-2 py-0.5 rounded-full"
            >
              {{ kanbanData[stage].length }}
            </span>
          </div>

          <div
            class="bg-zinc-50/50 dark:bg-zinc-900/50 rounded-xl p-2 min-h-[200px] h-full border border-zinc-200 dark:border-zinc-800 flex flex-col gap-2"
          >
            <div
              v-for="member in kanbanData[stage]"
              :key="member.id"
              class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 cursor-move hover:shadow-md transition-shadow group relative"
            >
              <div class="flex justify-between items-start mb-1">
                <span class="font-medium text-sm"
                  >{{ member.employee.first_name }}
                  {{ member.employee.last_name }}</span
                >
                <Button
                  variant="ghost"
                  size="sm"
                  class="h-6 w-6 p-0 opacity-0 group-hover:opacity-100 transition-opacity"
                  @click="openEdit(member)"
                >
                  <Edit2 class="h-3 w-3 text-zinc-400" />
                </Button>
              </div>
              <div class="text-xs text-zinc-500 mb-2">{{ member.role }}</div>

              <!-- Quick Move Actions -->
              <div
                class="flex justify-between mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-700"
              >
                <Button
                  v-if="stage !== 'RECRUITMENT'"
                  variant="ghost"
                  size="sm"
                  class="text-[10px] h-6 px-1"
                  @click="
                    updateMobilizationStatus(
                      member,
                      mobilizationStages[mobilizationStages.indexOf(stage) - 1],
                    )
                  "
                >
                  ← Prev
                </Button>
                <span v-else></span>

                <Button
                  v-if="stage !== 'PLACED'"
                  variant="ghost"
                  size="sm"
                  class="text-[10px] h-6 px-1 text-indigo-600"
                  @click="
                    updateMobilizationStatus(
                      member,
                      mobilizationStages[mobilizationStages.indexOf(stage) + 1],
                    )
                  "
                >
                  Next →
                </Button>
              </div>
            </div>

            <div
              v-if="kanbanData[stage].length === 0"
              class="text-center py-8 text-zinc-400 text-xs italic"
            >
              No members
            </div>
          </div>
        </div>
      </div>

      <!-- Create Modal -->
      <Dialog v-model:open="createModal">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Add Team Member</DialogTitle>
          </DialogHeader>
          <form @submit.prevent="submitCreate" class="space-y-4 mt-2">
            <div class="grid gap-2">
              <Label>Employee</Label>
              <select
                v-model="form.employee_id"
                class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 dark:focus-visible:ring-zinc-300"
                required
              >
                <option value="" disabled>Select Employee</option>
                <option
                  v-for="emp in availableEmployees"
                  :key="emp.id"
                  :value="emp.id"
                >
                  {{ emp.first_name }} {{ emp.last_name }} ({{
                    emp.employee_code
                  }})
                </option>
              </select>
              <span
                v-if="form.errors.employee_id"
                class="text-xs text-red-500"
                >{{ form.errors.employee_id }}</span
              >
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label>Role</Label>
                <Input
                  v-model="form.role"
                  placeholder="e.g. Site Engineer"
                  required
                />
                <span v-if="form.errors.role" class="text-xs text-red-500">{{
                  form.errors.role
                }}</span>
              </div>
              <div class="grid gap-2">
                <Label>Daily Rate</Label>
                <Input type="number" v-model="form.daily_rate_cost" required />
                <span
                  v-if="form.errors.daily_rate_cost"
                  class="text-xs text-red-500"
                  >{{ form.errors.daily_rate_cost }}</span
                >
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label>Start Date</Label>
                <Input type="date" v-model="form.start_date" required />
                <span
                  v-if="form.errors.start_date"
                  class="text-xs text-red-500"
                  >{{ form.errors.start_date }}</span
                >
              </div>
              <div class="grid gap-2">
                <Label>End Date</Label>
                <Input type="date" v-model="form.end_date" />
                <span
                  v-if="form.errors.end_date"
                  class="text-xs text-red-500"
                  >{{ form.errors.end_date }}</span
                >
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <Button
                type="button"
                variant="outline"
                @click="createModal = false"
                >Cancel</Button
              >
              <Button type="submit" :disabled="form.processing"
                >Add Member</Button
              >
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Edit Modal -->
      <Dialog v-model:open="editModal">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Update Member Status</DialogTitle>
          </DialogHeader>
          <form @submit.prevent="submitEdit" class="space-y-4 mt-2">
            <div class="grid gap-2">
              <Label>Employee</Label>
              <div
                class="px-3 py-2 border rounded-md bg-zinc-50 text-zinc-500 text-sm"
              >
                {{ selectedMember?.employee?.first_name }}
                {{ selectedMember?.employee?.last_name }}
              </div>
            </div>

            <div class="grid gap-2">
              <Label>Role</Label>
              <Input v-model="editForm.role" required />
            </div>

            <div class="grid gap-2">
              <Label>Mobilization Status</Label>
              <select
                v-model="editForm.status"
                class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 dark:focus-visible:ring-zinc-300"
              >
                <option
                  v-for="stage in mobilizationStages"
                  :key="stage"
                  :value="stage"
                >
                  {{ stage }}
                </option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label>End Date</Label>
                <Input type="date" v-model="editForm.end_date" />
              </div>
              <div class="grid gap-2">
                <Label>Daily Rate</Label>
                <Input
                  type="number"
                  v-model="editForm.daily_rate_cost"
                  required
                />
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <Button type="button" variant="outline" @click="editModal = false"
                >Cancel</Button
              >
              <Button type="submit" :disabled="editForm.processing"
                >Save Changes</Button
              >
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        v-model:open="deleteDialogOpen"
        variant="danger"
        title="Remove team member"
        message="Are you sure you want to remove this team member?"
        confirm-label="Remove"
        @confirm="confirmDeleteMember"
      />
    </div>
  </AppLayout>
</template>
