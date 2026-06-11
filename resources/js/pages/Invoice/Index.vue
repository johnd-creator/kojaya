<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import { FileText, Plus, Search } from "lucide-vue-next";
import { computed, ref } from "vue";
import {
  create as invoicesCreate,
  show as invoicesShow,
} from "@/actions/App/Http/Controllers/InvoiceController";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import { Input } from "@/components/ui/input";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { useTableFilters } from "@/composables/useTableFilters";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  invoices: any;
  filters: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Finance", href: "#" },
  { title: "Invoices", href: "#" },
];

const filters = ref({
  search: props.filters.search || "",
  status: props.filters.status || "",
});

useTableFilters(filters, {
  route: "/invoices",
});

const setStatus = (status: string) => {
  filters.value.status = status;
};

const statusCounts: Record<string, number> = {
  DRAFT: 0,
  PENDING: 0,
  APPROVED: 0,
  PAID: 0,
  OVERDUE: 0,
  CANCELLED: 0,
};

if (props.invoices.data) {
  props.invoices.data.forEach((invoice: any) => {
    if (statusCounts[invoice.status] !== undefined) {
      statusCounts[invoice.status]++;
    }
  });
}

const totalCount = props.invoices.data?.length || 0;

const columns = [
  { header: "Invoice No.", key: "invoice_no", slot: "invoice_no" },
  { header: "Client", key: "client.name" },
  { header: "Unit", key: "unit.name" },
  { header: "Invoice Date", key: "invoice_date", format: formatDate },
  { header: "Due Date", key: "due_date", format: formatDate },
  {
    header: "Amount",
    key: "amount",
    format: formatCurrency,
    align: "right" as const,
  },
  {
    header: "Tax",
    key: "tax_amount",
    format: formatCurrency,
    align: "right" as const,
  },
  {
    header: "Total",
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

const tableData = computed(() => {
  if (props.invoices?.meta) {
    return {
      ...props.invoices.meta,
      data: props.invoices.data ?? [],
      links: props.invoices.links ?? [],
    };
  }

  return props.invoices;
});
</script>

<template>
  <Head title="Invoices" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 w-full">
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Invoices
          </h1>
          <p class="text-zinc-500 mt-1">Manage client billing and invoices.</p>
        </div>
        <Button as-child>
          <Link :href="invoicesCreate().url">
            <Plus class="h-4 w-4 mr-2" />
            New Invoice
          </Link>
        </Button>
      </div>

      <!-- Quick Filters -->
      <div class="space-y-4">
        <!-- Search -->
        <div class="relative">
          <Search class="absolute left-3 top-3 h-4 w-4 text-zinc-400" />
          <Input
            v-model="filters.search"
            placeholder="Search invoices by number..."
            class="pl-10"
          />
        </div>

        <!-- Quick Filter Chips -->
        <div class="flex flex-wrap gap-2 items-center">
          <span class="text-sm text-zinc-500">Status:</span>
          <Button
            size="sm"
            :variant="filters.status === '' ? 'default' : 'outline'"
            @click="setStatus('')"
          >
            All ({{ totalCount }})
          </Button>
          <Button
            size="sm"
            :variant="filters.status === 'DRAFT' ? 'default' : 'outline'"
            @click="setStatus('DRAFT')"
          >
            Draft ({{ statusCounts.DRAFT }})
          </Button>
          <Button
            size="sm"
            :variant="filters.status === 'PENDING' ? 'default' : 'outline'"
            @click="setStatus('PENDING')"
          >
            Pending ({{ statusCounts.PENDING }})
          </Button>
          <Button
            size="sm"
            :variant="filters.status === 'APPROVED' ? 'default' : 'outline'"
            @click="setStatus('APPROVED')"
          >
            Approved ({{ statusCounts.APPROVED }})
          </Button>
          <Button
            size="sm"
            :variant="filters.status === 'PAID' ? 'default' : 'outline'"
            @click="setStatus('PAID')"
          >
            Paid ({{ statusCounts.PAID }})
          </Button>
          <Button
            size="sm"
            :variant="filters.status === 'OVERDUE' ? 'default' : 'outline'"
            @click="setStatus('OVERDUE')"
          >
            Overdue ({{ statusCounts.OVERDUE }})
          </Button>
        </div>
      </div>

      <DataTable
        :columns="columns"
        :data="tableData"
        :searchable="false"
        empty-message="No invoices found."
        :empty-icon="FileText"
      >
        <template #invoice_no="{ row }">
          <div class="font-medium text-zinc-900 dark:text-white">
            {{ row.invoice_no }}
          </div>
        </template>

        <template #status="{ value }">
          <StatusBadge :status="value" />
        </template>

        <template #actions="{ row }">
          <Link
            :href="invoicesShow({ id: row.id }).url"
            class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
          >
            View
          </Link>
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>
