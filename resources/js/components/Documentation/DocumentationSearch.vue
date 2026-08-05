<script setup lang="ts">
import { Search, X } from "lucide-vue-next";
import { computed, ref, watch } from "vue";

type ArticleSummary = {
  slug: string;
  title: string;
  summary: string;
  category: string;
  module: string;
  roles: string[];
  risk_level: string;
  search_text: string;
};

const props = defineProps<{
  articles: ArticleSummary[];
  modelValue: string;
  moduleFilter: string;
  modules: string[];
}>();

const emit = defineEmits<{
  (event: "update:modelValue", value: string): void;
  (event: "update:moduleFilter", value: string): void;
}>();

const query = ref(props.modelValue);
const selectedModule = ref(props.moduleFilter);

watch(() => props.modelValue, (v) => (query.value = v));
watch(() => props.moduleFilter, (v) => (selectedModule.value = v));

watch(query, (v) => emit("update:modelValue", v));
watch(selectedModule, (v) => emit("update:moduleFilter", v));

const filtered = computed<ArticleSummary[]>(() => {
  const q = query.value.trim().toLowerCase();
  return props.articles.filter((article) => {
    if (selectedModule.value !== "" && article.module !== selectedModule.value) {
      return false;
    }
    if (q === "") {
      return true;
    }
    return (
      article.title.toLowerCase().includes(q) ||
      article.summary.toLowerCase().includes(q) ||
      article.category.toLowerCase().includes(q) ||
      (article.search_text ?? "").toLowerCase().includes(q)
    );
  });
});

const hasResults = computed(() => filtered.value.length > 0);

defineExpose({ filtered, hasResults });
</script>

<template>
  <div class="space-y-3">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
      <label class="relative flex-1">
        <span class="sr-only">Cari artikel</span>
        <Search
          class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
        />
        <input
          v-model="query"
          type="search"
          placeholder="Cari artikel berdasarkan judul, ringkasan, atau kategori"
          class="w-full rounded-lg border border-zinc-200 bg-white py-2 pl-10 pr-10 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
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

      <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
        <span>Modul</span>
        <select
          v-model="selectedModule"
          class="rounded-lg border border-zinc-200 bg-white py-2 pl-3 pr-8 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
        >
          <option value="">Semua modul</option>
          <option v-for="module in modules" :key="module" :value="module">
            {{ module }}
          </option>
        </select>
      </label>
    </div>

    <p class="text-xs text-zinc-500 dark:text-zinc-400">
      Menampilkan {{ filtered.length }} dari {{ articles.length }} artikel.
    </p>
  </div>
</template>
