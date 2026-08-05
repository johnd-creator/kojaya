<script setup lang="ts">
import { computed } from "vue";
import DOMPurify from "isomorphic-dompurify";
import { marked } from "marked";

const props = defineProps<{
  body: string;
  slug: string;
}>();

const renderer = new marked.Renderer();

const sanitisedHtml = computed(() => {
  const raw = marked.parse(props.body, {
    async: false,
    gfm: true,
    breaks: false,
    renderer,
  });

  return DOMPurify.sanitize(raw, {
    ALLOWED_TAGS: [
      "h1", "h2", "h3", "h4", "h5", "h6",
      "p", "br", "hr",
      "ul", "ol", "li",
      "strong", "em", "code", "pre",
      "blockquote",
      "table", "thead", "tbody", "tr", "th", "td",
      "a", "img",
      "figure", "figcaption",
    ],
    ALLOWED_ATTR: [
      "href", "title", "rel", "target", "id",
      "src", "alt", "loading", "width", "height",
      "scope",
    ],
    ALLOWED_URI_REGEXP: /^(?:https?:|mailto:|tel:|#|\/)/i,
    FORBID_ATTR: ["style", "onerror", "onload", "onclick"],
    FORBID_TAGS: ["script", "iframe", "form", "input", "object", "embed"],
  });
});
</script>

<template>
  <article
    class="prose prose-zinc max-w-none text-sm leading-7 text-zinc-700 dark:prose-invert dark:text-zinc-200"
    v-html="sanitisedHtml"
  />
</template>
