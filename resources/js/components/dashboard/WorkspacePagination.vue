<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";

defineProps<{
  pagination: {
    total?: number;
    last_page?: number;
    links?: Array<{ url: string | null; label: string; active: boolean }>;
  };
  label: string;
}>();

const pageLabel = (label: string) =>
  label.includes("Previous")
    ? "Sebelumnya"
    : label.includes("Next")
      ? "Berikutnya"
      : label.replaceAll("&laquo;", "").replaceAll("&raquo;", "").trim();
</script>

<template>
  <nav
    v-if="(pagination.last_page ?? 1) > 1"
    :aria-label="label"
    class="flex flex-wrap items-center justify-end gap-2"
  >
    <Button
      v-for="(link, index) in pagination.links"
      :key="index"
      as-child
      size="sm"
      :variant="link.active ? 'default' : 'outline'"
      :disabled="!link.url"
    >
      <Link
        v-if="link.url"
        :href="link.url"
        preserve-scroll
        :aria-current="link.active ? 'page' : undefined"
        >{{ pageLabel(link.label) }}</Link
      >
      <span v-else aria-disabled="true">{{ pageLabel(link.label) }}</span>
    </Button>
  </nav>
</template>
