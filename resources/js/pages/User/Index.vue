<script setup lang="ts">
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import {
  Plus,
  Edit2,
  Trash2,
  Building,
  ShieldCheck,
  Users,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import {
  index as usersIndex,
  store as usersStore,
  update as usersUpdate,
  destroy as usersDestroy,
} from "@/actions/App/Http/Controllers/UserController";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import FilterBar from "@/components/FilterBar.vue";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useTableFilters } from "@/composables/useTableFilters";
import AppLayout from "@/layouts/AppLayout.vue";
import { show as cooperativeMemberShow } from "@/routes/cooperative/members";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  users: any;
  roles: Array<any>;
  organizations: Array<any>;
  filters: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "User Management", href: "#" },
  { title: "Users", href: usersIndex().url },
];

const filters = ref({
  search: props.filters.search || "",
});

// Form handling
const isModalOpen = ref(false);
const editingUser = ref<any>(null);
const deleteDialogOpen = ref(false);
const pendingDeleteUser = ref<any>(null);

const { resetFilters } = useTableFilters(filters, {
  route: usersIndex().url,
});

const form = useForm({
  name: "",
  email: "",
  password: "",
  role: "",
  organization_id: "",
});

const openModal = (user: any = null) => {
  editingUser.value = user;
  form.clearErrors();

  if (user) {
    form.name = user.name;
    form.email = user.email;
    form.password = "";
    form.role = user.roles.length > 0 ? user.roles[0].name : "";
    form.organization_id = user.organization_id || "";
  } else {
    form.reset();
  }
  isModalOpen.value = true;
};

const submit = () => {
  if (editingUser.value) {
    form.put(usersUpdate({ id: editingUser.value.id }).url, {
      onSuccess: () => {
        isModalOpen.value = false;
        form.reset();
      },
    });
  } else {
    form.post(usersStore().url, {
      onSuccess: () => {
        isModalOpen.value = false;
        form.reset();
      },
    });
  }
};

const deleteUser = (user: any) => {
  pendingDeleteUser.value = user;
  deleteDialogOpen.value = true;
};

const confirmDeleteUser = () => {
  if (!pendingDeleteUser.value) {
    return;
  }

  router.delete(usersDestroy({ id: pendingDeleteUser.value.id }).url, {
    onFinish: () => {
      deleteDialogOpen.value = false;
      pendingDeleteUser.value = null;
    },
  });
};

const tableData = computed(() => {
  if (props.users?.meta) {
    return {
      ...props.users.meta,
      data: props.users.data ?? [],
      links: props.users.links ?? [],
    };
  }

  return props.users;
});

const columns = [
  { header: "User", key: "name", slot: "user" },
  { header: "Contact", key: "email" },
  { header: "Organization", key: "organization.name", slot: "organization" },
  { header: "Role", key: "roles.0.name", slot: "role" },
  {
    header: "Actions",
    key: "actions",
    slot: "actions",
    align: "right" as const,
  },
];
</script>

<template>
  <Head title="Users" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full"
    >
      <!-- Header Section -->
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Users
          </h1>
          <p class="text-zinc-500 mt-1">
            Manage system administrators, employees, and their access scopes.
          </p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
          <Button
            @click="openModal()"
            class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0"
          >
            <Plus class="h-4 w-4 mr-2" />
            Add User
          </Button>
        </div>
      </div>

      <FilterBar
        v-model:search="filters.search"
        search-placeholder="Search users..."
        @reset="resetFilters"
      />

      <DataTable
        :columns="columns"
        :data="tableData"
        :searchable="false"
        empty-message="No users found matching your criteria."
        :empty-icon="Users"
      >
        <template #user="{ row }">
          <div class="flex items-center gap-3">
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 font-bold text-white shadow-inner"
            >
              {{ row.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <p class="font-medium text-zinc-900 dark:text-zinc-100">
                {{ row.name }}
              </p>
              <p class="text-xs text-zinc-500">
                Joined {{ new Date(row.created_at).toLocaleDateString() }}
              </p>
            </div>
          </div>
        </template>

        <template #organization="{ row }">
          <div
            class="inline-flex items-center gap-1.5 rounded-md border border-sky-100 bg-sky-50 px-2.5 py-1 text-sm font-medium text-sky-700 dark:border-sky-800/50 dark:bg-sky-900/30 dark:text-sky-300"
          >
            <Building class="h-3.5 w-3.5" />
            {{ row.organization ? row.organization.name : "Unassigned" }}
          </div>
        </template>

        <template #role="{ row }">
          <div class="flex flex-col items-start gap-2">
            <div
              class="inline-flex items-center gap-1.5 rounded-md border border-amber-100 bg-amber-50 px-2.5 py-1 text-sm font-medium text-amber-700 dark:border-amber-800/50 dark:bg-amber-900/30 dark:text-amber-300"
            >
              <ShieldCheck class="h-3.5 w-3.5" />
              {{ row.roles.length > 0 ? row.roles[0].name : "No Role" }}
            </div>
            <Link
              v-if="row.cooperative_member"
              :href="cooperativeMemberShow(row.cooperative_member.id).url"
              class="inline-flex items-center rounded-md border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 hover:text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300"
            >
              {{ row.cooperative_member.member_no }}
            </Link>
          </div>
        </template>

        <template #actions="{ row }">
          <div class="flex items-center justify-end gap-2">
            <Button
              variant="ghost"
              size="icon"
              class="text-zinc-500 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-900/30"
              @click="openModal(row)"
            >
              <Edit2 class="h-4 w-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30"
              @click="deleteUser(row)"
            >
              <Trash2 class="h-4 w-4" />
            </Button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create/Edit Modal using plain accessible dialog approach since we might miss nested dialog components -->
    <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{{
            editingUser ? "Edit User" : "Create New User"
          }}</DialogTitle>
          <DialogDescription>
            Fill in the details to {{ editingUser ? "update" : "create" }} a
            user account. Organization access will be restricted based on their
            role level.
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submit" class="space-y-4 py-4">
          <div class="space-y-2">
            <Label for="name">Full Name</Label>
            <Input
              id="name"
              v-model="form.name"
              autocomplete="name"
              :aria-invalid="!!form.errors.name"
              required
            />
            <InputError :message="form.errors.name" />
          </div>

          <div class="space-y-2">
            <Label for="email">Email Address</Label>
            <Input
              id="email"
              type="email"
              v-model="form.email"
              autocomplete="username"
              :aria-invalid="!!form.errors.email"
              required
            />
            <InputError :message="form.errors.email" />
          </div>

          <div class="space-y-2" v-if="!editingUser">
            <Label for="password">Password</Label>
            <Input
              id="password"
              type="password"
              v-model="form.password"
              autocomplete="new-password"
              :aria-invalid="!!form.errors.password"
              required
            />
            <InputError :message="form.errors.password" />
          </div>
          <div class="space-y-2" v-else>
            <Label for="password_edit">New Password (optional)</Label>
            <Input
              id="password_edit"
              type="password"
              v-model="form.password"
              autocomplete="new-password"
              :aria-invalid="!!form.errors.password"
              placeholder="Leave blank to keep current"
            />
            <InputError :message="form.errors.password" />
          </div>

          <div class="space-y-2">
            <Label>System Role</Label>
            <Select v-model="form.role">
              <SelectTrigger
                class="w-full"
                :aria-invalid="!!form.errors.role"
              >
                <SelectValue placeholder="Select a PRD Role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="role in roles"
                  :key="role.id"
                  :value="role.name"
                >
                  {{ role.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.role" />
          </div>

          <div class="space-y-2">
            <Label>Organization Unit (Cabang)</Label>
            <Select v-model="form.organization_id">
              <SelectTrigger
                class="w-full"
                :aria-invalid="!!form.errors.organization_id"
              >
                <SelectValue placeholder="Select an Organization Level" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="org in organizations"
                  :key="org.id"
                  :value="org.id"
                >
                  {{ org.name }} ({{ org.code }})
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.organization_id" />
          </div>

          <DialogFooter class="pt-4">
            <Button type="button" variant="outline" @click="isModalOpen = false"
              >Cancel</Button
            >
            <Button
              type="submit"
              :disabled="form.processing"
              class="bg-indigo-600 hover:bg-indigo-700 text-white"
            >
              {{ editingUser ? "Save Changes" : "Create User" }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      variant="danger"
      title="Hapus user"
      :message="`Hapus user ${pendingDeleteUser?.name ?? ''}?`"
      confirm-label="Hapus"
      @confirm="confirmDeleteUser"
    />
  </AppLayout>
</template>
