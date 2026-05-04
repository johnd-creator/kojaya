<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import { ArrowLeft, Save } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
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

interface Asset {
  id: string;
  code: string;
  name: string;
  organization?: {
    id: string;
    name: string;
  };
}

interface Organization {
  id: string;
  name: string;
  code: string;
}

interface User {
  id: string;
  name: string;
  email: string;
}

interface Props {
  assets: Asset[];
  organizations: Organization[];
  users: User[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Asset Management", href: "#" },
  { title: "Work Orders", href: "/work-orders" },
  { title: "Create Work Order", href: "/work-orders/create" },
];

const form = useForm({
  asset_id: "",
  organization_id: "",
  type: "CORRECTIVE",
  priority: "MEDIUM",
  description: "",
  assigned_to: "",
});

const selectAssetValue = "__select_asset__";
const selectOrganizationValue = "__select_organization__";
const unassignedUserValue = "__unassigned__";

const getOrganizationFromAsset = (assetId: string) => {
  const asset = props.assets.find((a) => a.id === assetId);
  return asset?.organization?.id || "";
};

const updateAssetId = (value: unknown) => {
  form.asset_id =
    value === selectAssetValue || value == null ? "" : String(value);

  const organizationId = getOrganizationFromAsset(form.asset_id);
  if (organizationId) {
    form.organization_id = organizationId;
  }
};

const updateOrganizationId = (value: unknown) => {
  form.organization_id =
    value === selectOrganizationValue || value == null ? "" : String(value);
};

const updateType = (value: unknown) => {
  form.type = value == null ? "CORRECTIVE" : String(value);
};

const updatePriority = (value: unknown) => {
  form.priority = value == null ? "MEDIUM" : String(value);
};

const updateAssignedTo = (value: unknown) => {
  form.assigned_to =
    value === unassignedUserValue || value == null ? "" : String(value);
};

const submit = () => {
  form.post("/work-orders", {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Create Work Order" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-4xl mx-auto w-full"
    >
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link href="/work-orders">
          <Button variant="ghost" size="icon" class="h-8 w-8">
            <ArrowLeft class="h-4 w-4" />
          </Button>
        </Link>
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Create Work Order
          </h1>
          <p class="text-zinc-500 mt-1">Create a new maintenance work order</p>
        </div>
      </div>

      <!-- Form -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
      >
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Asset & Organization -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <Label for="asset_id">Asset *</Label>
              <Select
                :model-value="form.asset_id || selectAssetValue"
                @update:model-value="updateAssetId"
              >
                <SelectTrigger
                  :disabled="form.processing"
                  :class="{ 'border-red-500': form.errors.asset_id }"
                >
                  <SelectValue placeholder="Select Asset" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="selectAssetValue"
                    >Select Asset</SelectItem
                  >
                  <SelectItem
                    v-for="asset in assets"
                    :key="asset.id"
                    :value="asset.id"
                  >
                    {{ asset.code }} - {{ asset.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.asset_id" class="text-sm text-red-500">
                {{ form.errors.asset_id }}
              </p>
            </div>

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
          </div>

          <!-- Type & Priority -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <Label for="type">Type *</Label>
              <Select :model-value="form.type" @update:model-value="updateType">
                <SelectTrigger
                  :disabled="form.processing"
                  :class="{ 'border-red-500': form.errors.type }"
                >
                  <SelectValue placeholder="Select Type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="PREVENTIVE">Preventive</SelectItem>
                  <SelectItem value="CORRECTIVE">Corrective</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.type" class="text-sm text-red-500">
                {{ form.errors.type }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="priority">Priority *</Label>
              <Select
                :model-value="form.priority"
                @update:model-value="updatePriority"
              >
                <SelectTrigger
                  :disabled="form.processing"
                  :class="{ 'border-red-500': form.errors.priority }"
                >
                  <SelectValue placeholder="Select Priority" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="LOW">Low</SelectItem>
                  <SelectItem value="MEDIUM">Medium</SelectItem>
                  <SelectItem value="HIGH">High</SelectItem>
                  <SelectItem value="EMERGENCY">Emergency</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.priority" class="text-sm text-red-500">
                {{ form.errors.priority }}
              </p>
            </div>
          </div>

          <!-- Assigned To -->
          <div class="space-y-2">
            <Label for="assigned_to">Assigned To</Label>
            <Select
              :model-value="form.assigned_to || unassignedUserValue"
              @update:model-value="updateAssignedTo"
            >
              <SelectTrigger
                :disabled="form.processing"
                :class="{ 'border-red-500': form.errors.assigned_to }"
              >
                <SelectValue placeholder="Unassigned" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="unassignedUserValue">Unassigned</SelectItem>
                <SelectItem
                  v-for="user in users"
                  :key="user.id"
                  :value="user.id"
                >
                  {{ user.name }} ({{ user.email }})
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.assigned_to" class="text-sm text-red-500">
              {{ form.errors.assigned_to }}
            </p>
          </div>

          <!-- Description -->
          <div class="space-y-2">
            <Label for="description">Description</Label>
            <textarea
              id="description"
              v-model="form.description"
              rows="4"
              placeholder="Describe the work to be done..."
              :disabled="form.processing"
              class="flex min-h-[80px] w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm ring-offset-white placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              :class="{ 'border-red-500': form.errors.description }"
            ></textarea>
            <p v-if="form.errors.description" class="text-sm text-red-500">
              {{ form.errors.description }}
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
              {{ form.processing ? "Creating..." : "Create Work Order" }}
            </Button>
            <Link href="/work-orders">
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
