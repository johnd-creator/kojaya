<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import {
  store,
  update,
} from "@/actions/App/Http/Controllers/OrganizationController";
import { Button } from "@/components/ui/button";
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
import type { Organization } from "@/types/organization";

interface Props {
  isOpen: boolean;
  organization: Organization | null;
  allOrganizations: Organization[];
}

const props = defineProps<Props>();
const emit = defineEmits(["close"]);

const form = useForm({
  name: "",
  code: "",
  type: "BRANCH",
  level: "L2",
  parent_id: "" as string | null,
  is_active: true,
  address: "",
  phone: "",
  email: "",
});

watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      if (props.organization) {
        form.name = props.organization.name;
        form.code = props.organization.code;
        form.type = props.organization.type;
        form.level = props.organization.level;
        form.parent_id = props.organization.parent_id || "";
        form.is_active = props.organization.is_active;
        form.address = props.organization.address || "";
        form.phone = props.organization.phone || "";
        form.email = props.organization.email || "";
      } else {
        form.reset();
      }
    }
  },
);

// ...

const submit = () => {
  // Nullify parent_id if empty string so validation passes
  if (form.parent_id === "") {
    form.parent_id = null;
  }

  if (props.organization) {
    form.put(update(props.organization.id).url, {
      preserveScroll: true,
      onSuccess: () => emit("close"),
    });
  } else {
    form.post(store().url, {
      preserveScroll: true,
      onSuccess: () => emit("close"),
    });
  }
};
</script>

<template>
  <Dialog :open="isOpen" @update:open="(val) => !val && emit('close')">
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>{{
          organization ? "Edit Organization" : "Create Organization"
        }}</DialogTitle>
        <DialogDescription>
          Fill in the details for the organization branch or unit.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="mt-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <Label for="code">Code</Label>
            <Input
              id="code"
              v-model="form.code"
              type="text"
              class="mt-1 block w-full"
              required
            />
            <p v-if="form.errors.code" class="mt-2 text-sm text-red-600">
              {{ form.errors.code }}
            </p>
          </div>

          <div>
            <Label for="name">Name</Label>
            <Input
              id="name"
              v-model="form.name"
              type="text"
              class="mt-1 block w-full"
              required
            />
            <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">
              {{ form.errors.name }}
            </p>
          </div>

          <div>
            <Label for="level">Hierarchy Level</Label>
            <select
              v-model="form.level"
              id="level"
              class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-zinc-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm p-2"
            >
              <option value="L0">L0 (Head Office)</option>
              <option value="L1">L1 (Regional)</option>
              <option value="L2">L2 (Unit / Branch)</option>
              <option value="L3">L3 (Site / Location)</option>
            </select>
            <p v-if="form.errors.level" class="mt-2 text-sm text-red-600">
              {{ form.errors.level }}
            </p>
          </div>

          <div>
            <Label for="type">Organization Type</Label>
            <select
              v-model="form.type"
              id="type"
              class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-zinc-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm p-2"
            >
              <option value="HEAD_OFFICE">Head Office</option>
              <option value="REGIONAL">Regional</option>
              <option value="BRANCH">Branch</option>
              <option value="SITE">Site</option>
            </select>
            <p v-if="form.errors.type" class="mt-2 text-sm text-red-600">
              {{ form.errors.type }}
            </p>
          </div>

          <div class="md:col-span-2">
            <Label for="parent_id">Parent Organization</Label>
            <select
              v-model="form.parent_id"
              id="parent_id"
              class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-zinc-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm p-2"
            >
              <option value="">-- No Parent (Root) --</option>
              <option
                v-for="org in allOrganizations.filter(
                  (o) => !organization || o.id !== organization.id,
                )"
                :key="org.id"
                :value="org.id"
              >
                {{ org.code }} - {{ org.name }}
              </option>
            </select>
            <p v-if="form.errors.parent_id" class="mt-2 text-sm text-red-600">
              {{ form.errors.parent_id }}
            </p>
          </div>
        </div>

        <DialogFooter class="mt-6">
          <Button variant="secondary" @click="emit('close')" type="button"
            >Cancel</Button
          >
          <Button
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
            type="submit"
          >
            {{ organization ? "Update" : "Create" }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
