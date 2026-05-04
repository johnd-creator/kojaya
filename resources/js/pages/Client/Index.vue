<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import {
  Plus,
  Pencil,
  Trash2,
  Building2,
  User,
  Eye,
  Building,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import {
  index as clientsIndex,
  create as clientCreate,
  destroy as clientDestroy,
  show as clientShow,
  edit as clientEdit,
} from "@/actions/App/Http/Controllers/ClientController";
import { Button } from "@/components/ui/button";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import FilterBar from "@/components/FilterBar.vue";
import PageContainer from "@/components/PageContainer.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatsCard from "@/components/StatsCard.vue";
import { useTableFilters } from "@/composables/useTableFilters";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  clients: any;
  filters: any;
  stats: {
    total_clients: number;
    total_pln: number;
    total_private: number;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Operations", href: "#" },
  { title: "Clients", href: clientsIndex().url },
];

const filters = ref({
  search: props.filters.search || "",
  client_type: props.filters.client_type || "",
});
const clientTypeOptions = [
  { label: "All Types", value: "" },
  { label: "PLN", value: "PLN" },
  { label: "Private", value: "PRIVATE" },
];
const deleteDialogOpen = ref(false);
const pendingDeleteClient = ref<{ id: string; name: string } | null>(null);

const clientTypeColors: Record<string, string> = {
  PLN: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400",
  PRIVATE: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
};

const { resetFilters } = useTableFilters(filters, {
  route: clientsIndex().url,
  debounceMs: 500,
});

const columns = [
  { header: "Code", key: "code" },
  { header: "Name", key: "name", slot: "name" },
  { header: "Type", key: "client_type", slot: "client_type" },
  { header: "Contact Person", key: "contact_person", slot: "contact_person" },
  { header: "Phone", key: "phone" },
  { header: "Organization", key: "organization.name" },
  {
    header: "Actions",
    key: "actions",
    slot: "actions",
    align: "right" as const,
  },
];

const tableData = computed(() => {
  if (props.clients?.meta) {
    return {
      ...props.clients.meta,
      data: props.clients.data ?? [],
      links: props.clients.links ?? [],
    };
  }

  return props.clients;
});

const clearFilters = () => {
  resetFilters();
};

const deleteClient = (id: string, name: string) => {
  pendingDeleteClient.value = { id, name };
  deleteDialogOpen.value = true;
};

const confirmDeleteClient = () => {
  if (!pendingDeleteClient.value) {
    return;
  }

  router.delete(clientDestroy(pendingDeleteClient.value.id).url, {
    onFinish: () => {
      deleteDialogOpen.value = false;
      pendingDeleteClient.value = null;
    },
  });
};
</script>

<template>
  <Head title="Clients" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <PageContainer>
      <!-- Header Section -->
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Client Management
          </h1>
          <p class="text-zinc-500 mt-1">
            Manage project clients (PLN & Private companies)
          </p>
        </div>
        <div class="flex gap-3">
          <Button as-child variant="default">
            <Link :href="clientCreate().url">
              <Plus class="h-4 w-4 mr-2" />
              Add Client
            </Link>
          </Button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <StatsCard
          label="Total Clients"
          :value="stats.total_clients"
          :icon="Building"
          icon-container-class="bg-blue-50 dark:bg-blue-900/30"
          icon-class="text-blue-600 dark:text-blue-400"
        />
        <StatsCard
          label="PLN Clients"
          :value="stats.total_pln"
          :icon="Building2"
          value-class="text-yellow-600 dark:text-yellow-400"
          icon-container-class="bg-yellow-50 dark:bg-yellow-900/30"
          icon-class="text-yellow-600 dark:text-yellow-400"
        />
        <StatsCard
          label="Private Clients"
          :value="stats.total_private"
          :icon="User"
          value-class="text-indigo-600 dark:text-indigo-400"
          icon-container-class="bg-indigo-50 dark:bg-indigo-900/30"
          icon-class="text-indigo-600 dark:text-indigo-400"
        />
      </div>

      <!-- Filters -->
      <FilterBar
        v-model:search="filters.search"
        search-placeholder="Name or Code..."
        :show-reset="false"
        @reset="clearFilters"
      >
        <SelectFilter
          v-model="filters.client_type"
          :options="clientTypeOptions"
          placeholder="All Types"
        />

        <Button
          v-if="filters.search || filters.client_type"
          @click="clearFilters"
          variant="outline"
          type="button"
          class="w-full sm:w-auto"
        >
          Clear Filters
        </Button>
      </FilterBar>

      <!-- Table -->
      <DataTable
        :columns="columns"
        :data="tableData"
        :searchable="false"
        empty-message="No clients found. Create your first client to get started."
      >
        <template #name="{ row }">
          <div class="flex items-center gap-2">
            <Building2 class="h-4 w-4 text-zinc-400" />
            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{
              row.name
            }}</span>
          </div>
        </template>

        <template #client_type="{ value }">
          <span
            class="rounded-full px-2 py-0.5 text-xs font-medium"
            :class="clientTypeColors[value]"
          >
            {{ value }}
          </span>
        </template>

        <template #contact_person="{ value }">
          <div
            class="flex items-center gap-1 text-sm text-zinc-600 dark:text-zinc-400"
          >
            <User class="h-3 w-3 text-zinc-400" />
            {{ value || "-" }}
          </div>
        </template>

        <template #actions="{ row }">
          <div class="flex items-center justify-end gap-2">
            <Link :href="clientShow({ client: row.id }).url">
              <Button
                size="sm"
                variant="ghost"
                title="View"
                :aria-label="`Lihat client ${row.name}`"
              >
                <Eye class="h-3 w-3" />
              </Button>
            </Link>
            <Link :href="clientEdit({ client: row.id }).url">
              <Button
                size="sm"
                variant="ghost"
                title="Edit"
                :aria-label="`Edit client ${row.name}`"
              >
                <Pencil class="h-3 w-3" />
              </Button>
            </Link>
            <Button
              size="sm"
              variant="ghost"
              title="Delete"
              :aria-label="`Hapus client ${row.name}`"
              @click="deleteClient(row.id, row.name)"
            >
              <Trash2 class="h-3 w-3 text-red-500" />
            </Button>
          </div>
        </template>
      </DataTable>

      <ConfirmDialog
        v-model:open="deleteDialogOpen"
        variant="danger"
        title="Hapus client"
        :message="`Hapus client ${pendingDeleteClient?.name ?? ''}?`"
        confirm-label="Hapus"
        @confirm="confirmDeleteClient"
      />
    </PageContainer>
  </AppLayout>
</template>
