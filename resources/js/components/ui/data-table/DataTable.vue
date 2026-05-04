<script setup lang="ts">
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { Search, Loader2 } from "lucide-vue-next";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import EmptyState from "@/components/EmptyState.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";

interface Column {
  header: string;
  key?: string; // Key to access data in row (e.g. 'user.name' or 'status')
  slot?: string; // Slot name for custom rendering
  class?: string; // Custom classes for the cell
  headerClass?: string; // Custom classes for the header
  align?: "left" | "center" | "right";
  format?: (value: any) => string; // Optional formatter function
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginationData {
  current_page: number;
  from: number;
  last_page: number;
  links: PaginationLink[];
  path: string;
  per_page: number;
  to: number;
  total: number;
}

type Paginated<T = any> = PaginationData & { data: T[] };

interface Props {
  columns: Column[];
  data: Paginated | any[];
  searchable?: boolean;
  searchPlaceholder?: string;
  loading?: boolean;
  emptyMessage?: string;
  emptyIcon?: any; // Icon component
  rowClickable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  searchable: true,
  searchPlaceholder: "Search...",
  loading: false,
  emptyMessage: "No records found.",
  rowClickable: false,
});

const emit = defineEmits(["search", "row-click", "page-change"]);

// Handle search input with debounce (to be implemented by parent or added here)
const onSearch = (e: Event) => {
  const target = e.target as HTMLInputElement;
  emit("search", target.value);
};

// Normalize data to array
const tableData = computed(() => {
  if (Array.isArray(props.data)) return props.data;
  return props.data.data || [];
});

const paginationData = computed(() => {
  return Array.isArray(props.data) ? null : props.data;
});

// Helper to get nested value (e.g. user.name)
const getValue = (obj: any, path: string) => {
  return path.split(".").reduce((o, i) => (o ? o[i] : null), obj);
};

const getAlignmentClass = (align?: string) => {
  switch (align) {
    case "center":
      return "text-center";
    case "right":
      return "text-right";
    default:
      return "text-left";
  }
};

const tableLabel = computed(() => {
  const primaryColumn = props.columns[0]?.header;

  return primaryColumn ? `Tabel data ${primaryColumn}` : "Tabel data";
});
</script>

<template>
  <div class="space-y-4">
    <!-- Search Bar -->
    <div v-if="searchable" class="flex items-center justify-between">
      <div class="relative w-full max-w-sm">
        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-zinc-500" />
        <Input
          type="search"
          :placeholder="searchPlaceholder"
          class="pl-9 bg-white dark:bg-zinc-900"
          @input="onSearch"
        />
      </div>
      <div class="flex items-center gap-2">
        <slot name="toolbar-actions" />
      </div>
    </div>

    <!-- Table Container -->
    <div
      class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table
          :aria-label="tableLabel"
          class="w-full text-left border-collapse"
          role="table"
        >
          <thead>
            <tr
              class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50"
            >
              <th
                v-for="(col, index) in columns"
                :key="index"
                class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider"
                :class="[getAlignmentClass(col.align), col.headerClass]"
              >
                {{ col.header }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <!-- Loading State -->
            <tr v-if="loading">
              <td
                :colspan="columns.length"
                class="py-12 text-center text-zinc-500"
              >
                <Loader2
                  class="mx-auto h-8 w-8 animate-spin text-zinc-400 mb-2"
                />
                Loading data...
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-else-if="tableData.length === 0">
              <td
                :colspan="columns.length"
                class="py-12 text-center text-zinc-500"
              >
                <EmptyState :icon="emptyIcon" :description="emptyMessage" />
              </td>
            </tr>

            <!-- Data Rows -->
            <tr
              v-else
              v-for="(row, rowIndex) in tableData"
              :key="rowIndex"
              class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group"
              :class="{ 'cursor-pointer': rowClickable }"
              @click="rowClickable && emit('row-click', row)"
            >
              <td
                v-for="(col, colIndex) in columns"
                :key="colIndex"
                class="py-4 px-6 text-sm text-zinc-600 dark:text-zinc-400"
                :class="[getAlignmentClass(col.align), col.class]"
              >
                <!-- Slot Content -->
                <slot
                  v-if="col.slot"
                  :name="col.slot"
                  :row="row"
                  :value="col.key ? getValue(row, col.key) : null"
                />

                <!-- Status Badge Special Handling -->
                <StatusBadge
                  v-else-if="col.key === 'status'"
                  :status="getValue(row, col.key)"
                />

                <!-- Formatted Value -->
                <span v-else-if="col.format">
                  {{
                    col.key
                      ? col.format(getValue(row, col.key))
                      : col.format(null)
                  }}
                </span>

                <!-- Default Value -->
                <span v-else>
                  {{ col.key ? getValue(row, col.key) : null }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="paginationData && paginationData.last_page > 1"
        class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 px-6 py-4 flex items-center justify-between"
      >
        <p class="text-sm text-zinc-500">
          Showing <span class="font-medium">{{ paginationData.from }}</span> to
          <span class="font-medium">{{ paginationData.to }}</span> of
          <span class="font-medium">{{ paginationData.total }}</span> results
        </p>
        <div class="flex items-center gap-1">
          <template v-for="(link, i) in paginationData.links" :key="i">
            <Button
              v-if="link.url"
              :variant="link.active ? 'default' : 'outline'"
              size="sm"
              class="h-8 min-w-[32px] px-2"
              :class="{ 'pointer-events-none opacity-50': !link.url }"
              as-child
            >
              <Link :href="link.url" preserve-scroll>
                <span v-html="link.label"></span>
              </Link>
            </Button>
            <span
              v-else
              v-html="link.label"
              class="px-2 text-zinc-400 text-sm"
            ></span>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>
