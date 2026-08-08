<script setup lang="ts">
import { Search, X } from "lucide-vue-next";
import { ref, watch } from "vue";

const props = defineProps<{
  modelValue: string;
  resultCount?: number;
  totalCount?: number;
}>();

const emit = defineEmits<{
  (event: "update:modelValue", value: string): void;
}>();

const query = ref(props.modelValue);

watch(
  () => props.modelValue,
  (v) => (query.value = v),
);

watch(query, (v) => emit("update:modelValue", v));
</script>

<template>
  <div class="space-y-2">
    <label class="relative block">
      <span class="sr-only">Cari panduan</span>
      <Search
        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
      />
      <input
        v-model="query"
        type="search"
        aria-label="Cari panduan"
        data-testid="documentation-search"
        placeholder='Cari panduan, misalnya "bayar iuran", "pinjaman", atau "SHU"'
        class="w-full rounded-lg border border-zinc-200 bg-white py-2.5 pl-10 pr-10 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder:text-zinc-500"
      />
      <button
        v-if="query !== ''"
        type="button"
        class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800"
        aria-label="Bersihkan pencarian"
        @click="query = ''"
      >
        <X class="h-4 w-4" />
      </button>
    </label>

    <p
      v-if="totalCount !== undefined"
      class="text-xs text-zinc-500 dark:text-zinc-400"
    >
      Menampilkan {{ resultCount ?? 0 }} dari {{ totalCount }} panduan.
    </p>
  </div>
</template>
