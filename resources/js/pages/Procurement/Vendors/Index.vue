<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { Plus } from "lucide-vue-next";
import { ref, computed } from "vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";

const props = defineProps<{ vendors: any[] }>();

const search = ref("");

const columns = [
  { header: "Vendor Name", key: "name", class: "font-medium" },
  { header: "Code", key: "code", class: "font-mono text-xs" },
  { header: "Email", key: "email", slot: "email" },
  { header: "Phone", key: "phone" },
  { header: "Status", key: "status", slot: "status" },
  { header: "", key: "actions", slot: "actions", align: "right" },
];

const filteredData = computed(() => {
  if (!search.value) return props.vendors;
  const q = search.value.toLowerCase();
  return props.vendors.filter(
    (v) =>
      v.name.toLowerCase().includes(q) ||
      (v.code || "").toLowerCase().includes(q),
  );
});

function handleRowClick(row: any) {
  // router.visit(`/procurement/vendors/${row.id}`) // Assuming show page exists or will exist
}
</script>

<template>
  <Head title="Vendors" />

  <AppLayout>
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      <div class="flex items-center justify-between">
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Vendors
          </h1>
          <p class="text-zinc-500 mt-1">
            Manage suppliers and vendor information.
          </p>
        </div>
        <div>
          <!-- <Button as-child>
            <Link href="/procurement/vendors/create">
              <Plus class="h-4 w-4 mr-2" />
              Add Vendor
            </Link>
          </Button> -->
        </div>
      </div>

      <!-- Data Table -->
      <DataTable
        :columns="columns"
        :data="filteredData"
        :searchable="true"
        search-placeholder="Search vendors..."
        :row-clickable="false"
        @row-click="handleRowClick"
        @search="(q) => (search = q)"
      >
        <template #email="{ value }">
          <a
            v-if="value"
            :href="`mailto:${value}`"
            class="text-indigo-600 hover:underline"
            @click.stop
            >{{ value }}</a
          >
          <span v-else class="text-zinc-400">-</span>
        </template>

        <template #status="{ value }">
          <StatusBadge
            :status="value ? 'ACTIVE' : 'INACTIVE'"
            :variant="value ? 'success' : 'secondary'"
          />
        </template>

        <template #actions="{ row }">
          <!-- <Button variant="ghost" size="icon" class="h-8 w-8">
            <Edit class="h-4 w-4" />
          </Button> -->
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>
