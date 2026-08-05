<script setup lang="ts">
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { BookOpen, ExternalLink } from "lucide-vue-next";

type HelpEntry = {
  route: string;
  slug: string;
  role: string;
  permission?: string;
  screenshot_state: string;
  label: string;
};

const props = defineProps<{
  entries: HelpEntry[];
  currentRoute: string;
  hasPermission: (permission?: string) => boolean;
}>();

const match = computed<HelpEntry | null>(() => {
  return (
    props.entries.find((entry) => {
      if (entry.route !== props.currentRoute) {
        return false;
      }
      if (entry.permission && !props.hasPermission(entry.permission)) {
        return false;
      }
      return true;
    }) ?? null
  );
});
</script>

<template>
  <Link
    v-if="match"
    :href="`/documentation/${match.slug}`"
    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200 dark:hover:bg-emerald-500/20"
  >
    <BookOpen class="h-3.5 w-3.5" />
    Lihat Panduan: {{ match.label }}
    <ExternalLink class="h-3 w-3" />
  </Link>
</template>
