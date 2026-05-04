<script setup lang="ts">
import { computed } from "vue";
import type { McuResult } from "@/types";

type Props = {
  result: McuResult;
  size?: "sm" | "md" | "lg";
};

const props = withDefaults(defineProps<Props>(), {
  size: "md",
});

const badgeConfig = computed(() => {
  const configs = {
    FIT: {
      classes:
        "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300",
      label: "Fit",
      icon: "CheckCircle",
    },
    FIT_WITH_RESTRICTION: {
      classes:
        "bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300",
      label: "Fit w/ Restriction",
      icon: "AlertTriangle",
    },
    UNFIT: {
      classes: "bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300",
      label: "Unfit",
      icon: "XCircle",
    },
  };

  return configs[props.result] || configs.FIT;
});

const sizeClasses = computed(() => {
  const sizes = {
    sm: "px-2 py-0.5 text-xs",
    md: "px-2.5 py-1 text-sm",
    lg: "px-3 py-1.5 text-base",
  };

  return sizes[props.size];
});
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full font-medium"
    :class="[badgeConfig.classes, sizeClasses]"
  >
    <span>{{ badgeConfig.label }}</span>
  </span>
</template>
