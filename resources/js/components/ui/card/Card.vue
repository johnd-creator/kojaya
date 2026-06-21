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
        'text-card-foreground flex flex-col gap-6 rounded-2xl border border-zinc-200 bg-white py-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
