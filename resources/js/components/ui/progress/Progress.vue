<script setup lang="ts">
import type { ProgressRootEmits, ProgressRootProps } from "reka-ui";
import { ProgressRoot, useForwardPropsEmits } from "reka-ui";
import type { HTMLAttributes } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps<
  ProgressRootProps & { class?: HTMLAttributes["class"] }
>();
const emits = defineEmits<ProgressRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
  <ProgressRoot
    :class="
      cn(
        'relative h-2 w-full overflow-hidden rounded-full bg-primary/20',
        props.class,
      )
    "
    v-bind="forwarded"
  >
    <slot />
  </ProgressRoot>
</template>
