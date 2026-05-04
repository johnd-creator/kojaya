<script setup lang="ts">
import type { HTMLAttributes } from "vue";
import { computed } from "vue";
import { cn } from "@/lib/utils";

type PageContainerVariant = "list" | "detail" | "form";

const props = withDefaults(
  defineProps<{
    variant?: PageContainerVariant;
    class?: HTMLAttributes["class"];
  }>(),
  {
    variant: "list",
  },
);

const variantClasses = computed(() => {
  switch (props.variant) {
    case "form":
      return "max-w-4xl";
    case "detail":
      return "max-w-6xl";
    default:
      return "max-w-7xl";
  }
});
</script>

<template>
  <section
    :class="
      cn(
        'mx-auto flex w-full flex-1 flex-col gap-6 px-4 py-6 sm:px-6',
        variantClasses,
        props.class,
      )
    "
  >
    <slot />
  </section>
</template>
