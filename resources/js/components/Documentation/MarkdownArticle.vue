<script setup lang="ts">
import { computed, watch } from "vue";
import { renderMarkdownArticle } from "@/components/Documentation/markdown-renderer";

const props = defineProps<{
  body: string;
  slug: string;
}>();

export type TocItem = {
  level: number;
  id: string;
  text: string;
};

const emit = defineEmits<{
  (event: "toc-ready", items: TocItem[]): void;
}>();

const rendered = computed(() => renderMarkdownArticle(props.body));
const sanitisedHtml = computed(() => rendered.value.html);
const toc = computed<TocItem[]>(() => rendered.value.toc);

watch(toc, (items) => emit("toc-ready", items), {
  immediate: true,
  deep: true,
});
</script>

<template>
  <article
    :id="`article-${slug}`"
    class="prose prose-zinc max-w-none text-sm leading-7 text-zinc-700 dark:prose-invert dark:text-zinc-200"
    v-html="sanitisedHtml"
  />
</template>
