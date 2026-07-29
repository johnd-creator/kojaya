<script setup lang="ts">
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { ArrowUpDown, ArrowUp, ArrowDown, Search } from "lucide-vue-next";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import EmptyState from "@/components/EmptyState.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";

interface Column {
  header: string;
  key?: string;
  slot?: string;
  class?: string;
  headerClass?: string;
  width?: string;
  align?: "left" | "center" | "right";
  format?: (value: any) => string;
  sortable?: boolean;
  sortKey?: string;
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
  emptyIcon?: any;
  rowClickable?: boolean;
  selectable?: boolean;
  isRowSelectable?: (row: any) => boolean;
  selected?: any[];
  sortField?: string;
  sortDirection?: "asc" | "desc";
  tableClass?: string;
  compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  searchable: true,
  searchPlaceholder: "Cari...",
  loading: false,
  emptyMessage: "Tidak ada data yang ditemukan.",
  rowClickable: false,
  selectable: false,
  isRowSelectable: () => true,
  selected: () => [],
  sortField: "",
  sortDirection: "asc",
  tableClass: "",
  compact: false,
});

const emit = defineEmits<{
  search: [value: string];
  "row-click": [row: any];
  "page-change": [url: string];
  "selection-change": [selected: any[]];
  sort: [field: string, direction: "asc" | "desc"];
}>();

const onSearch = (e: Event) => {
  const target = e.target as HTMLInputElement;
  emit("search", target.value);
};

const tableData = computed(() => {
  if (Array.isArray(props.data)) return props.data;
  return props.data.data || [];
});

const paginationData = computed(() => {
  return Array.isArray(props.data) ? null : props.data;
});

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

const isSelected = (row: any) => {
  return props.selected.some((s) => s.id === row.id);
};

const isRowSelectable = (row: any): boolean =>
  props.isRowSelectable?.(row) ?? true;

const toggleRow = (row: any) => {
  if (!isRowSelectable(row)) return;

  const current = [...props.selected];
  const idx = current.findIndex((s) => s.id === row.id);
  if (idx >= 0) {
    current.splice(idx, 1);
  } else {
    current.push(row);
  }
  emit("selection-change", current);
};

const toggleAll = () => {
  const selectableRows = tableData.value.filter(isRowSelectable);

  if (selectableRows.length === 0) return;

  if (allSelected.value) {
    emit("selection-change", []);
  } else {
    emit("selection-change", selectableRows);
  }
};

const allSelected = computed(() => {
  const selectableRows = tableData.value.filter(isRowSelectable);

  return (
    selectableRows.length > 0 && selectableRows.every((row) => isSelected(row))
  );
});

const someSelected = computed(() => {
  return props.selected.length > 0 && !allSelected.value;
});

const rowId = (row: any) => row.id ?? JSON.stringify(row);

const handleSort = (col: Column) => {
  if (!col.sortable) return;
  const sortKey = col.sortKey ?? col.key ?? "";
  const newDir =
    props.sortField === sortKey && props.sortDirection === "asc"
      ? "desc"
      : "asc";
  emit("sort", sortKey, newDir);
};

const sortIcon = (col: Column) => {
  if (!col.sortable) return null;
  const sortKey = col.sortKey ?? col.key ?? "";
  if (props.sortField !== sortKey) return ArrowUpDown;
  return props.sortDirection === "asc" ? ArrowUp : ArrowDown;
};
</script>

<template>
  <div class="min-w-0 space-y-4">
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

    <div
      class="min-w-0 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
    >
      <div
        class="min-w-0 max-w-full overflow-x-auto overscroll-x-contain"
        role="region"
        :aria-label="`${tableLabel}, geser untuk melihat kolom lainnya`"
        tabindex="0"
      >
        <table
          :aria-label="tableLabel"
          :class="['w-full border-collapse text-left', tableClass]"
          role="table"
        >
          <colgroup>
            <col v-if="selectable" style="width: 2.5rem" />
            <col
              v-for="(col, index) in columns"
              :key="index"
              :style="col.width ? { width: col.width } : undefined"
            />
          </colgroup>
          <thead class="sticky top-0 z-10">
            <tr
              class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50"
            >
              <th
                v-if="selectable"
                :class="compact ? 'w-10 px-3 py-3' : 'w-10 px-4 py-4'"
              >
                <input
                  type="checkbox"
                  aria-label="Pilih semua baris"
                  class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600"
                  :checked="allSelected"
                  :indeterminate="someSelected"
                  :disabled="tableData.every((row) => !isRowSelectable(row))"
                  @change="toggleAll"
                />
              </th>
              <th
                v-for="(col, index) in columns"
                :key="index"
                :class="[
                  compact ? 'px-3 py-3 text-xs' : 'px-6 py-4 text-sm',
                  compact ? 'min-w-0 max-w-0 overflow-hidden' : '',
                  'font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300',
                  getAlignmentClass(col.align),
                  col.headerClass,
                  {
                    'cursor-pointer select-none hover:text-zinc-700 dark:hover:text-zinc-300':
                      col.sortable,
                  },
                ]"
                :style="col.sortable ? { cursor: 'pointer' } : {}"
                @click="handleSort(col)"
              >
                <span class="inline-flex items-center gap-1">
                  {{ col.header }}
                  <component
                    :is="sortIcon(col)"
                    v-if="col.sortable"
                    class="size-3.5"
                  />
                </span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr v-if="loading" aria-live="polite">
              <td :colspan="columns.length + (selectable ? 1 : 0)" class="p-0">
                <span class="sr-only">Memuat data tabel.</span>
                <div class="space-y-3 p-4">
                  <div
                    v-for="row in 5"
                    :key="row"
                    class="grid gap-3"
                    :style="{
                      gridTemplateColumns: `repeat(${columns.length + (selectable ? 1 : 0)}, minmax(0, 1fr))`,
                    }"
                  >
                    <Skeleton
                      v-for="col in columns"
                      :key="col.key ?? col.header"
                      class="h-5 rounded-md"
                    />
                  </div>
                </div>
              </td>
            </tr>

            <tr v-else-if="tableData.length === 0">
              <td
                :colspan="columns.length + (selectable ? 1 : 0)"
                class="py-12 text-center text-zinc-500"
              >
                <EmptyState :icon="emptyIcon" :description="emptyMessage" />
              </td>
            </tr>

            <tr
              v-else
              v-for="(row, rowIndex) in tableData"
              :key="rowId(row)"
              class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group"
              :class="{
                'cursor-pointer': rowClickable,
                'bg-sky-50/50 dark:bg-sky-950/20': isSelected(row),
                'opacity-60': selectable && !isRowSelectable(row),
              }"
              @click="rowClickable && emit('row-click', row)"
            >
              <td
                v-if="selectable"
                :class="compact ? 'px-3 py-3' : 'px-4 py-4'"
                @click.stop
              >
                <input
                  type="checkbox"
                  :aria-label="
                    'Pilih baris ' +
                    (row.member?.name || row.name || row.id || rowIndex + 1)
                  "
                  class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600"
                  :checked="isSelected(row)"
                  :disabled="!isRowSelectable(row)"
                  :aria-disabled="!isRowSelectable(row)"
                  :title="
                    isRowSelectable(row)
                      ? 'Pilih baris'
                      : 'Baris ini tidak dapat dipilih'
                  "
                  @change="toggleRow(row)"
                />
              </td>
              <td
                v-for="(col, colIndex) in columns"
                :key="colIndex"
                :class="[
                  compact ? 'px-3 py-3' : 'px-6 py-4',
                  compact ? 'min-w-0 max-w-0 overflow-hidden' : '',
                  compact
                    ? 'text-xs text-zinc-600 dark:text-zinc-400'
                    : 'text-sm text-zinc-600 dark:text-zinc-400',
                  getAlignmentClass(col.align),
                  col.class,
                ]"
              >
                <slot
                  v-if="col.slot"
                  :name="col.slot"
                  :row="row"
                  :value="col.key ? getValue(row, col.key) : null"
                />

                <StatusBadge
                  v-else-if="col.key === 'status'"
                  :status="getValue(row, col.key)"
                />

                <span v-else-if="col.format">
                  {{
                    col.key
                      ? col.format(getValue(row, col.key))
                      : col.format(null)
                  }}
                </span>

                <span v-else>
                  {{ col.key ? getValue(row, col.key) : null }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="paginationData && paginationData.last_page > 1"
        class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 px-6 py-4 flex items-center justify-between"
      >
        <p class="text-sm text-zinc-500">
          Menampilkan
          <span class="font-medium">{{ paginationData.from }}</span> hingga
          <span class="font-medium">{{ paginationData.to }}</span> dari
          <span class="font-medium">{{ paginationData.total }}</span> data
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
              <Link
                :href="link.url"
                preserve-scroll
                @click="emit('page-change', link.url ?? '')"
              >
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
