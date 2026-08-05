<script setup lang="ts">
import { ref } from "vue";
import { List } from "lucide-vue-next";

type TocItem = { level: number; id: string; text: string };

const props = defineProps<{
  items: TocItem[];
  articleSlug: string;
}>();

const activeId = ref<string>(props.items[0]?.id ?? "");

function navigate(id: string, event: MouseEvent): void {
  if (typeof document === "undefined") {
    return;
  }
  const target = document.getElementById(id);
  if (!target) {
    return;
  }
  event.preventDefault();
  target.scrollIntoView({ behavior: "smooth", block: "start" });
  history.replaceState(null, "", `#${id}`);
  activeId.value = id;
}
</script>

<template>
  <nav
    v-if="items.length > 0"
    aria-label="Daftar isi"
    class="space-y-2 rounded-2xl border border-zinc-200 bg-white/80 p-4 text-sm shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80"
  >
    <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
      <List class="h-3.5 w-3.5" />
      Daftar isi
    </h2>
    <ol class="space-y-1">
      <li
        v-for="item in items"
        :key="item.id"
        :class="[
          'leading-snug',
          item.level === 2 ? 'pl-0' : 'pl-3',
          item.level === 3 ? 'pl-6' : '',
        ]"
      >
        <a
          :href="`#${item.id}`"
          :class="[
            'block rounded px-2 py-1 transition',
            item.id === activeId
              ? 'bg-emerald-50 font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200'
              : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
          ]"
          @click="navigate(item.id, $event)"
        >
          {{ item.text }}
        </a>
      </li>
    </ol>
  </nav>
</template>
