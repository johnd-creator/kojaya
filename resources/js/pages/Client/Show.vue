<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import {
  ArrowLeft,
  Building2,
  User,
  Mail,
  Phone,
  MapPin,
  FileText,
  Calendar,
} from "lucide-vue-next";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  client: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Operations", href: "#" },
  { title: "Clients", href: "/clients" },
  { title: props.client.name, href: "#" },
];

const clientTypeColors: Record<string, string> = {
  PLN: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400",
  PRIVATE: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
};
</script>

<template>
  <Head :title="client.name" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="outline" size="icon" as-child>
            <Link href="/clients">
              <ArrowLeft class="h-4 w-4" />
            </Link>
          </Button>
          <div class="flex items-center gap-3">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800"
            >
              <Building2 class="h-6 w-6 text-zinc-600 dark:text-zinc-400" />
            </div>
            <div>
              <h1
                class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"
              >
                {{ client.name }}
              </h1>
              <p class="text-sm text-zinc-500">{{ client.code }}</p>
            </div>
          </div>
        </div>
        <div class="flex gap-2">
          <Link :href="`/clients/${client.id}/edit`">
            <Button>Edit</Button>
          </Link>
        </div>
      </div>

      <!-- Client Information -->
      <Card>
        <CardHeader>
          <CardTitle>Client Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <span class="text-sm font-medium text-zinc-500">Client Type</span>
              <span
                class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full"
                :class="clientTypeColors[client.client_type]"
              >
                {{ client.client_type }}
              </span>
            </div>
            <div v-if="client.organization">
              <span class="text-sm font-medium text-zinc-500"
                >Organization</span
              >
              <span class="ml-2 text-sm text-zinc-900 dark:text-white">{{
                client.organization.name
              }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-start gap-2">
              <User class="h-4 w-4 text-zinc-400 mt-1" />
              <div>
                <p class="text-sm font-medium text-zinc-500">Contact Person</p>
                <p class="text-sm text-zinc-900 dark:text-white">
                  {{ client.contact_person }}
                </p>
              </div>
            </div>
            <div class="flex items-start gap-2">
              <Mail class="h-4 w-4 text-zinc-400 mt-1" />
              <div>
                <p class="text-sm font-medium text-zinc-500">Email</p>
                <p class="text-sm text-zinc-900 dark:text-white">
                  {{ client.email }}
                </p>
              </div>
            </div>
            <div class="flex items-start gap-2">
              <Phone class="h-4 w-4 text-zinc-400 mt-1" />
              <div>
                <p class="text-sm font-medium text-zinc-500">Phone</p>
                <p class="text-sm text-zinc-900 dark:text-white">
                  {{ client.phone }}
                </p>
              </div>
            </div>
            <div v-if="client.tax_id" class="flex items-start gap-2">
              <FileText class="h-4 w-4 text-zinc-400 mt-1" />
              <div>
                <p class="text-sm font-medium text-zinc-500">Tax ID (NPWP)</p>
                <p class="text-sm text-zinc-900 dark:text-white">
                  {{ client.tax_id }}
                </p>
              </div>
            </div>
          </div>

          <div v-if="client.address" class="flex items-start gap-2">
            <MapPin class="h-4 w-4 text-zinc-400 mt-1" />
            <div>
              <p class="text-sm font-medium text-zinc-500">Address</p>
              <p
                class="text-sm text-zinc-900 dark:text-white whitespace-pre-line"
              >
                {{ client.address }}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Projects -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center justify-between">
            <span>Projects</span>
            <Link :href="`/projects/create?client_id=${client.id}`">
              <Button size="sm">New Project</Button>
            </Link>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div
            v-if="client.projects && client.projects.length > 0"
            class="space-y-3"
          >
            <div
              v-for="project in client.projects"
              :key="project.id"
              class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
            >
              <div class="flex-1">
                <Link :href="`/projects/${project.id}`" class="block">
                  <div class="font-medium text-zinc-900 dark:text-white">
                    {{ project.name }}
                  </div>
                  <div class="text-sm text-zinc-500">
                    {{ project.project_code }}
                  </div>
                </Link>
              </div>
              <div class="flex items-center gap-4">
                <div class="text-right">
                  <div
                    class="text-sm font-medium text-zinc-900 dark:text-white"
                  >
                    {{ formatCurrency(project.budget) }}
                  </div>
                  <div class="text-xs text-zinc-500">Budget</div>
                </div>
                <StatusBadge :status="project.status" />
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <p class="text-sm text-zinc-500">
              No projects found for this client.
            </p>
            <Link :href="`/projects/create?client_id=${client.id}`">
              <Button class="mt-4">Create First Project</Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
