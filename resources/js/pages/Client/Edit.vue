<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { useForm } from "@inertiajs/vue3";
import { ArrowLeft, Building2 } from "lucide-vue-next";
import { index as clientsIndex } from "@/actions/App/Http/Controllers/ClientController";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  client: any;
  organizations: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Operations", href: "#" },
  { title: "Clients", href: clientsIndex().url },
  { title: `Edit ${props.client.name}`, href: "#" },
];

const form = useForm({
  code: props.client.code,
  name: props.client.name,
  address: props.client.address || "",
  tax_id: props.client.tax_id || "",
  contact_person: props.client.contact_person,
  phone: props.client.phone,
  email: props.client.email,
  client_type: props.client.client_type,
  organization_id: props.client.organization_id || "",
});

const submit = () => {
  form.put(`/clients/${props.client.id}`, {
    onSuccess: () => {},
    onError: () => {
      console.error("Form validation failed");
    },
  });
};
</script>

<template>
  <Head :title="`Edit ${client.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-2xl mx-auto w-full">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Button variant="outline" size="icon" as-child>
          <Link :href="clientsIndex().url">
            <ArrowLeft class="h-4 w-4" />
          </Link>
        </Button>
        <div>
          <h1
            class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Edit Client
          </h1>
          <p class="text-sm text-zinc-500">Update client information</p>
        </div>
      </div>

      <!-- Form -->
      <div class="border border-zinc-200 dark:border-zinc-800 rounded-lg p-6">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Code & Name -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label for="code">Client Code</Label>
              <Input
                id="code"
                v-model="form.code"
                placeholder="e.g., PLN-001, PRIV-001"
                required
              />
              <span v-if="form.errors.code" class="text-xs text-red-500 mt-1">{{
                form.errors.code
              }}</span>
            </div>
            <div>
              <Label for="name">Client Name</Label>
              <Input
                id="name"
                v-model="form.name"
                placeholder="e.g., PLN UIP Jawa Barat"
                required
              />
              <span v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{
                form.errors.name
              }}</span>
            </div>
          </div>

          <!-- Address -->
          <div>
            <Label for="address">Address</Label>
            <Textarea
              id="address"
              v-model="form.address"
              placeholder="Complete address..."
              class="min-h-[80px]"
            />
            <span
              v-if="form.errors.address"
              class="text-xs text-red-500 mt-1"
              >{{ form.errors.address }}</span
            >
          </div>

          <!-- Tax ID & Client Type -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label for="tax_id">Tax ID (NPWP)</Label>
              <Input
                id="tax_id"
                v-model="form.tax_id"
                placeholder="XX.XXX.XXX.X-XXX.XXX"
              />
              <span
                v-if="form.errors.tax_id"
                class="text-xs text-red-500 mt-1"
                >{{ form.errors.tax_id }}</span
              >
            </div>
            <div>
              <Label for="client_type">Client Type</Label>
              <select
                id="client_type"
                v-model="form.client_type"
                class="w-full border border-zinc-300 dark:border-zinc-700 rounded-md px-3 py-2 bg-white dark:bg-zinc-950"
                required
              >
                <option value="PLN">PLN (Perusahaan Listrik Negara)</option>
                <option value="PRIVATE">Private Company</option>
              </select>
              <span
                v-if="form.errors.client_type"
                class="text-xs text-red-500 mt-1"
                >{{ form.errors.client_type }}</span
              >
            </div>
          </div>

          <!-- Contact Person -->
          <div>
            <Label for="contact_person">Contact Person</Label>
            <div class="flex items-center gap-2">
              <Building2 class="h-4 w-4 text-zinc-400" />
              <Input
                id="contact_person"
                v-model="form.contact_person"
                placeholder="Full name of contact person"
                required
              />
            </div>
            <span
              v-if="form.errors.contact_person"
              class="text-xs text-red-500 mt-1"
              >{{ form.errors.contact_person }}</span
            >
          </div>

          <!-- Phone & Email -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label for="phone">Phone Number</Label>
              <Input
                id="phone"
                v-model="form.phone"
                placeholder="e.g., 08123456789"
                required
              />
              <span
                v-if="form.errors.phone"
                class="text-xs text-red-500 mt-1"
                >{{ form.errors.phone }}</span
              >
            </div>
            <div>
              <Label for="email">Email</Label>
              <Input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="client@example.com"
                required
              />
              <span
                v-if="form.errors.email"
                class="text-xs text-red-500 mt-1"
                >{{ form.errors.email }}</span
              >
            </div>
          </div>

          <!-- Organization (Optional) -->
          <div>
            <Label for="organization_id">Organization (Optional)</Label>
            <select
              id="organization_id"
              v-model="form.organization_id"
              class="w-full border border-zinc-300 dark:border-zinc-700 rounded-md px-3 py-2 bg-white dark:bg-zinc-950"
            >
              <option value="">-- No Organization --</option>
              <option
                v-for="org in organizations"
                :key="org.id"
                :value="org.id"
              >
                {{ org.name }}
              </option>
            </select>
            <p class="text-xs text-zinc-500 mt-1">
              Assign client to specific organization if needed
            </p>
            <span
              v-if="form.errors.organization_id"
              class="text-xs text-red-500 mt-1"
              >{{ form.errors.organization_id }}</span
            >
          </div>

          <!-- Actions -->
          <div
            class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-800"
          >
            <Button
              type="button"
              variant="outline"
              @click="router.get(clientsIndex().url)"
              :disabled="form.processing"
            >
              Cancel
            </Button>
            <Button type="submit" :disabled="form.processing">
              <span v-if="form.processing">Updating...</span>
              <span v-else>Update Client</span>
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
