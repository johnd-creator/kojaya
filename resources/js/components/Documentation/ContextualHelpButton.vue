<script setup lang="ts">
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { BookOpen, ExternalLink } from "lucide-vue-next";

type HelpArticle = {
  slug: string;
  title: string;
  summary: string;
  category: string;
  module: string;
};

type HelpEntry = {
  route: string;
  slug: string;
  role: string;
  permission?: string | null;
  screenshot_state: string;
  label: string;
  article: HelpArticle;
};

const page = usePage();
const help = computed<HelpEntry | null>(() => {
  const value = (page.props as Record<string, unknown>)['contextualHelp'];
  if (!value || typeof value !== 'object') {
    return null;
  }
  return value as HelpEntry;
});
</script>

<template>
  <Link
    v-if="help"
    :href="`/documentation/${help.slug}`"
    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200 dark:hover:bg-emerald-500/20 print:hidden"
    data-testid="contextual-help-button"
  >
    <BookOpen class="h-3.5 w-3.5" aria-hidden="true" />
    Lihat Panduan: {{ help.label }}
    <ExternalLink class="h-3 w-3" aria-hidden="true" />
  </Link>
</template>
