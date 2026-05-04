<script setup lang="ts">
import { useForm, router } from "@inertiajs/vue3";
import { Plus, Pencil, Trash2, Check, X } from "lucide-vue-next";
import { ref, computed } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const props = defineProps<{
  employee: any;
  allEmployees: any[];
}>();

const childrenCount = computed(() => {
  return (
    props.employee.families?.filter((f: any) => f.relationship === "Child")
      .length || 0
  );
});

const showModal = ref(false);
const editingId = ref<number | null>(null);
const deleteDialogOpen = ref(false);
const deleteTargetId = ref<number | null>(null);

const form = useForm({
  employee_id: props.employee.id,
  name: "",
  relationship: "Child",
  birth_date: "",
  gender: "Male",
  nik_ktp: "",
  is_working_here: false,
  related_employee_id: "",
} as any);

const openModal = (familyMember: any = null) => {
  if (familyMember) {
    editingId.value = familyMember.id;
    form.employee_id = familyMember.employee_id;
    form.name = familyMember.name;
    form.relationship = familyMember.relationship;
    form.birth_date = familyMember.birth_date
      ? familyMember.birth_date.split("T")[0]
      : "";
    form.gender = familyMember.gender;
    form.nik_ktp = familyMember.nik_ktp || "";
    form.is_working_here = familyMember.is_working_here;
    form.related_employee_id = familyMember.related_employee_id || "";
  } else {
    editingId.value = null;
    form.reset();
    form.employee_id = props.employee.id;
    form.relationship = "Child";
    form.gender = "Male";
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.reset();
  form.clearErrors();
};

const submit = () => {
  if (editingId.value) {
    form.put(`/employee-families/${editingId.value}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post("/employee-families", {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteMember = (id: number) => {
  deleteTargetId.value = id;
  deleteDialogOpen.value = true;
};

const confirmDeleteMember = (): void => {
  if (deleteTargetId.value === null) {
    return;
  }

  router.delete(`/employee-families/${deleteTargetId.value}`, {
    onFinish: () => {
      deleteDialogOpen.value = false;
      deleteTargetId.value = null;
    },
  });
};

const getRelatedEmployeeName = (id: number) => {
  const emp = props.allEmployees.find((e) => e.id === id);
  return emp
    ? `${emp.employee_code} - ${emp.first_name} ${emp.last_name}`
    : "Unknown";
};
</script>

<template>
  <div
    class="mt-8 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm p-6 overflow-hidden"
  >
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-medium text-zinc-900 dark:text-white">
          Family Members
        </h3>
        <p class="text-sm text-zinc-500">
          Manage spouse and children for BPJS and tax purposes. Constraints: Max
          1 Spouse, Max 3 Children
          <span class="font-medium text-blue-600 dark:text-blue-400"
            >(Current: {{ childrenCount }}/3)</span
          >.
        </p>
      </div>
      <Button @click="openModal()" variant="default" size="sm">
        <Plus class="h-4 w-4 mr-2" />
        Add Dependent
      </Button>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead
          class="text-xs text-zinc-500 bg-zinc-50 dark:bg-zinc-800 uppercase border-b dark:border-zinc-700"
        >
          <tr>
            <th class="px-4 py-3 font-medium">Name</th>
            <th class="px-4 py-3 font-medium">Relationship</th>
            <th class="px-4 py-3 font-medium">Birth Date / Gender</th>
            <th class="px-4 py-3 font-medium">NIK</th>
            <th class="px-4 py-3 font-medium">Works Here?</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="employee.families?.length === 0">
            <td colspan="6" class="px-4 py-6 text-center text-zinc-500">
              No family members registered.
            </td>
          </tr>
          <tr
            v-for="member in employee.families"
            :key="member.id"
            class="border-b dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
          >
            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
              {{ member.name }}
              <span
                v-if="member.is_shared"
                class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700"
                title="Managed by spouse's profile"
              >
                Shared
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="
                  member.relationship === 'Child'
                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                    : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300'
                "
              >
                {{ member.relationship }}
              </span>
            </td>
            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
              {{
                member.birth_date
                  ? new Date(member.birth_date).toLocaleDateString()
                  : "-"
              }}
              <span class="text-xs text-zinc-400 mx-1">|</span>
              {{ member.gender || "-" }}
            </td>
            <td class="px-4 py-3 text-zinc-600">{{ member.nik_ktp || "-" }}</td>
            <td class="px-4 py-3">
              <div v-if="member.is_working_here" class="flex flex-col">
                <span
                  class="inline-flex items-center text-green-600 dark:text-green-400 text-xs font-medium"
                >
                  <Check class="h-3 w-3 mr-1" /> Yes
                </span>
                <span
                  v-if="member.related_employee_id"
                  class="text-xs text-zinc-500 mt-0.5"
                >
                  {{ getRelatedEmployeeName(member.related_employee_id) }}
                </span>
              </div>
              <span v-else class="text-zinc-400 text-xs flex items-center"
                ><X class="h-3 w-3 mr-1" /> No</span
              >
            </td>
            <td class="px-4 py-3 text-right space-x-2">
              <template v-if="!member.is_shared">
                <Button
                  @click="openModal(member)"
                  variant="ghost"
                  size="icon"
                  class="h-8 w-8"
                >
                  <Pencil class="h-4 w-4 text-zinc-500" />
                </Button>
                <Button
                  @click="deleteMember(member.id)"
                  variant="ghost"
                  size="icon"
                  class="h-8 w-8 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </template>
              <span v-else class="text-xs text-zinc-400 italic"
                >Managed by Spouse</span
              >
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add/Edit Modal (Simple inline for now) -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div
        class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl w-full max-w-lg border border-zinc-200 dark:border-zinc-800 overflow-hidden"
      >
        <div
          class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center"
        >
          <h3 class="text-lg font-semibold">
            {{ editingId ? "Edit Dependent" : "Add Dependent" }}
          </h3>
          <button @click="closeModal" class="text-zinc-400 hover:text-zinc-600">
            <X class="h-5 w-5" />
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div class="grid gap-2">
            <Label>Name</Label>
            <Input v-model="form.name" required placeholder="Full Name" />
            <span v-if="form.errors.name" class="text-xs text-red-500">{{
              form.errors.name
            }}</span>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label>Relationship</Label>
              <select
                v-model="form.relationship"
                class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none dark:border-zinc-800 dark:bg-zinc-950"
              >
                <option value="Child">Child</option>
                <option value="Husband">Husband</option>
                <option value="Wife">Wife</option>
              </select>
              <span
                v-if="form.errors.relationship"
                class="text-xs text-red-500"
                >{{ form.errors.relationship }}</span
              >
            </div>

            <div class="grid gap-2">
              <Label>Gender</Label>
              <select
                v-model="form.gender"
                class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none dark:border-zinc-800 dark:bg-zinc-950"
              >
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label>Birth Date (Optional)</Label>
              <Input type="date" v-model="form.birth_date" />
            </div>
            <div class="grid gap-2">
              <Label>NIK / National ID (Optional)</Label>
              <Input v-model="form.nik_ktp" placeholder="16 Digit NIK" />
              <span v-if="form.errors.nik_ktp" class="text-xs text-red-500">{{
                form.errors.nik_ktp
              }}</span>
            </div>
          </div>

          <!-- Is working here check -->
          <div
            class="pt-4 border-t border-zinc-100 dark:border-zinc-800 space-y-4"
          >
            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                id="working_here"
                v-model="form.is_working_here"
                class="rounded border-zinc-300"
              />
              <Label for="working_here" class="cursor-pointer"
                >Is this family member also an employee here?</Label
              >
            </div>

            <div v-if="form.is_working_here" class="grid gap-2 pl-6">
              <Label>Select Employee Profile</Label>
              <select
                v-model="form.related_employee_id"
                class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none dark:border-zinc-800 dark:bg-zinc-950"
              >
                <option value="">-- Select Employee --</option>
                <option
                  v-for="emp in allEmployees"
                  :key="emp.id"
                  :value="emp.id"
                  :disabled="emp.id === employee.id"
                >
                  {{ emp.employee_code }} - {{ emp.first_name }}
                  {{ emp.last_name }}
                </option>
              </select>
              <p class="text-xs text-zinc-500">
                Choosing the related employee will synchronize the maximum 3
                children quota across both employees.
              </p>
            </div>
          </div>

          <!-- Global Form Errors -->
          <div
            v-if="form.errors.shared_quota"
            class="p-3 bg-red-50 text-red-600 rounded-md text-sm border border-red-100"
          >
            {{ form.errors.shared_quota }}
          </div>
          <div
            v-else-if="
              Object.keys(form.errors).length > 0 &&
              !form.errors.name &&
              !form.errors.relationship &&
              !form.errors.nik_ktp
            "
            class="p-3 bg-red-50 text-red-600 rounded-md text-sm border border-red-100"
          >
            Please check your inputs. Ensure you don't exceed the 1 spouse or 3
            children limits.
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <Button type="button" variant="outline" @click="closeModal"
              >Cancel</Button
            >
            <Button type="submit" :disabled="form.processing">
              <span v-if="form.processing">Saving...</span>
              <span v-else>Save Dependent</span>
            </Button>
          </div>
        </form>
      </div>
    </div>

    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      variant="danger"
      title="Remove family member"
      message="Are you sure you want to remove this family member?"
      confirm-label="Remove"
      @confirm="confirmDeleteMember"
    />
  </div>
</template>
