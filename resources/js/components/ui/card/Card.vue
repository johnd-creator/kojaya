<script setup lang="ts">
import type { HTMLAttributes } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps<{
  class?: HTMLAttributes["class"];
}>();
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'text-card-foreground flex flex-col gap-6 rounded-xl border border-zinc-200/80 bg-white/95 py-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
