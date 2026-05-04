<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import { ArrowLeft, Save } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

interface Organization {
  id: string;
  name: string;
  code: string;
}

interface Props {
  organizations: Organization[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Asset Management", href: "#" },
  { title: "Assets", href: "/assets" },
  { title: "Create Asset", href: "/assets/create" },
];

const form = useForm({
  code: "",
  name: "",
  category: "",
  organization_id: "",
  status: "ACTIVE",
  purchase_date: "",
  serial_number: "",
});

const selectOrganizationValue = "__select_organization__";

const updateOrganizationId = (value: unknown) => {
  form.organization_id =
    value === selectOrganizationValue || value == null ? "" : String(value);
};

const updateStatus = (value: unknown) => {
  form.status = value == null ? "ACTIVE" : String(value);
};

const submit = () => {
  form.post("/assets", {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Create Asset" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-4xl mx-auto w-full"
    >
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link href="/assets">
          <Button variant="ghost" size="icon" class="h-8 w-8">
            <ArrowLeft class="h-4 w-4" />
          </Button>
        </Link>
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Create Asset
          </h1>
          <p class="text-zinc-500 mt-1">Add a new asset to the registry</p>
        </div>
      </div>

      <!-- Form -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
      >
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Code -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <Label for="code">Asset Code *</Label>
              <Input
                id="code"
                v-model="form.code"
                placeholder="AST-001"
                :disabled="form.processing"
                :class="{ 'border-red-500': form.errors.code }"
              />
              <p v-if="form.errors.code" class="text-sm text-red-500">
                {{ form.errors.code }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="serial_number">Serial Number</Label>
              <Input
                id="serial_number"
                v-model="form.serial_number"
                placeholder="SN-1234567890"
                :disabled="form.processing"
                :class="{ 'border-red-500': form.errors.serial_number }"
              />
              <p v-if="form.errors.serial_number" class="text-sm text-red-500">
                {{ form.errors.serial_number }}
              </p>
            </div>
          </div>

          <!-- Name -->
          <div class="space-y-2">
            <Label for="name">Asset Name *</Label>
            <Input
              id="name"
              v-model="form.name"
              placeholder="Generator Set 500kVA"
              :disabled="form.processing"
              :class="{ 'border-red-500': form.errors.name }"
            />
            <p v-if="form.errors.name" class="text-sm text-red-500">
              {{ form.errors.name }}
            </p>
          </div>

          <!-- Category -->
          <div class="space-y-2">
            <Label for="category">Category *</Label>
            <Input
              id="category"
              v-model="form.category"
              placeholder="Electrical Equipment"
              list="categories"
              :disabled="form.processing"
              :class="{ 'border-red-500': form.errors.category }"
            />
            <datalist id="categories">
              <option value="Electrical Equipment" />
              <option value="Mechanical Equipment" />
              <option value="Vehicle" />
              <option value="Building" />
              <option value="Tool" />
              <option value="IT Equipment" />
              <option value="Furniture" />
              <option value="Other" />
            </datalist>
            <p v-if="form.errors.category" class="text-sm text-red-500">
              {{ form.errors.category }}
            </p>
          </div>

          <!-- Organization & Status -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <Label for="organization_id">Organization *</Label>
              <Select
                :model-value="form.organization_id || selectOrganizationValue"
                @update:model-value="updateOrganizationId"
              >
                <SelectTrigger
                  :disabled="form.processing"
                  :class="{ 'border-red-500': form.errors.organization_id }"
                >
                  <SelectValue placeholder="Select Organization" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="selectOrganizationValue"
                    >Select Organization</SelectItem
                  >
                  <SelectItem
                    v-for="org in organizations"
                    :key="org.id"
                    :value="org.id"
                  >
                    {{ org.name }} ({{ org.code }})
                  </SelectItem>
                </SelectContent>
              </Select>
              <p
                v-if="form.errors.organization_id"
                class="text-sm text-red-500"
              >
                {{ form.errors.organization_id }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="status">Status *</Label>
              <Select
                :model-value="form.status"
                @update:model-value="updateStatus"
              >
                <SelectTrigger
                  :disabled="form.processing"
                  :class="{ 'border-red-500': form.errors.status }"
                >
                  <SelectValue placeholder="Select Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="ACTIVE">Active</SelectItem>
                  <SelectItem value="INACTIVE">Inactive</SelectItem>
                  <SelectItem value="UNDER_MAINTENANCE"
                    >Under Maintenance</SelectItem
                  >
                </SelectContent>
              </Select>
              <p v-if="form.errors.status" class="text-sm text-red-500">
                {{ form.errors.status }}
              </p>
            </div>
          </div>

          <!-- Purchase Date -->
          <div class="space-y-2">
            <Label for="purchase_date">Purchase Date</Label>
            <Input
              id="purchase_date"
              v-model="form.purchase_date"
              type="date"
              :disabled="form.processing"
              :class="{ 'border-red-500': form.errors.purchase_date }"
            />
            <p v-if="form.errors.purchase_date" class="text-sm text-red-500">
              {{ form.errors.purchase_date }}
            </p>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-3 pt-4">
            <Button
              type="submit"
              :disabled="form.processing"
              class="bg-indigo-600 hover:bg-indigo-700 text-white"
            >
              <Save class="h-4 w-4 mr-2" />
              {{ form.processing ? "Creating..." : "Create Asset" }}
            </Button>
            <Link href="/assets">
              <Button
                type="button"
                variant="outline"
                :disabled="form.processing"
              >
                Cancel
              </Button>
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
