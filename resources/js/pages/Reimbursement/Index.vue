<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { Search, Plus, Eye, FileText } from "lucide-vue-next";
import { computed, ref } from "vue";
import {
  index,
  create,
  show,
} from "@/actions/App/Http/Controllers/ReimbursementController";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { useTableFilters } from "@/composables/useTableFilters";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";

interface User {
  id: number;
  name: string;
}

interface Reimbursement {
  id: string;
  submission_date: string;
  total_amount: number;
  status: "DRAFT" | "SUBMITTED" | "APPROVED" | "REJECTED" | "PAID";
  description: string;
  user: User;
  user_id: number;
}

interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  next_page_url: string | null;
  prev_page_url: string | null;
}

interface Props {
  reimbursements:
    | {
        data: Reimbursement[];
        links: any[];
        meta: Pagination;
      }
    | (Pagination & { data: Reimbursement[] });
}

const props = defineProps<Props>();

const filters = ref({
  search: "",
});

const breadcrumbs = [
  { title: "Finance", href: "#" },
  { title: "Reimbursements", href: index().url },
];

useTableFilters(filters, {
  route: index().url,
});

const tableData = computed(() => {
  const data = props.reimbursements as any;

  if (Array.isArray(data)) {
    return data;
  }

  if (data.meta) {
    return {
      ...data.meta,
      data: data.data ?? [],
      links: data.links ?? [],
    };
  }

  return data;
});

const columns = [
  { header: "Date", key: "submission_date", format: formatDate },
  { header: "User", key: "user.name", slot: "user" },
  { header: "Description", key: "description", slot: "description" },
  {
    header: "Amount",
    key: "total_amount",
    format: formatCurrency,
    align: "right" as const,
  },
  { header: "Status", key: "status", slot: "status" },
  {
    header: "Actions",
    key: "actions",
    slot: "actions",
    align: "right" as const,
  },
];
</script>

<template>
  <Head title="Reimbursements" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 w-full">
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Reimbursements
          </h1>
          <p class="text-zinc-500 mt-1">
            Manage and track reimbursement requests.
          </p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
          <div class="relative w-full md:w-64">
            <Search
              class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400"
            />
            <Input
              v-model="filters.search"
              placeholder="Search requests..."
              class="pl-9 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800"
            />
          </div>

          <Button
            as-child
            class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0"
          >
            <Link :href="create().url">
              <Plus class="h-4 w-4 mr-2" />
              New Request
            </Link>
          </Button>
        </div>
      </div>

      <DataTable
        :columns="columns"
        :data="tableData"
        :searchable="false"
        empty-message="No reimbursement requests found."
        :empty-icon="FileText"
      >
        <template #user="{ row }">
          <div class="font-medium text-zinc-900 dark:text-zinc-100">
            {{ row.user?.name || "-" }}
          </div>
        </template>

        <template #description="{ value }">
          <div
            class="max-w-[300px] truncate text-sm text-zinc-600 dark:text-zinc-400"
            :title="value"
          >
            {{ value || "-" }}
          </div>
        </template>

        <template #status="{ value }">
          <StatusBadge :status="value" />
        </template>

        <template #actions="{ row }">
          <div class="flex items-center justify-end gap-2">
            <Link :href="show({ reimbursement: row.id }).url">
              <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8 text-zinc-500 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-900/30"
              >
                <Eye class="h-4 w-4" />
              </Button>
            </Link>
          </div>
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>
