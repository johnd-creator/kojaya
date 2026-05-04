<script setup lang="ts">
import type { Component } from "vue";

interface Props {
  label: string;
  value: string | number;
  icon: Component;
  valueClass?: string;
  iconContainerClass?: string;
  iconClass?: string;
}

withDefaults(defineProps<Props>(), {
  valueClass: "text-zinc-900 dark:text-white",
  iconContainerClass: "bg-zinc-50 dark:bg-zinc-800/60",
  iconClass: "text-zinc-600 dark:text-zinc-300",
});
</script>

<template>
  <div
    class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
  >
    <div>
      <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
        {{ label }}
      </p>
      <h2 class="mt-1 text-3xl font-bold" :class="valueClass">{{ value }}</h2>
    </div>
    <div
      class="flex h-12 w-12 items-center justify-center rounded-full"
      :class="iconContainerClass"
    >
      <component :is="icon" class="h-6 w-6" :class="iconClass" />
    </div>
  </div>
</template>
