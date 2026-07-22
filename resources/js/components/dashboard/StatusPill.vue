<script setup lang="ts">
import type { Component } from "vue";
import { computed } from "vue";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc" | "auto";

const props = withDefaults(
  defineProps<{
    tone: Tone;
    label: string;
    icon?: Component;
    dot?: boolean;
  }>(),
  {
    dot: true,
  },
);

const toneClass = computed<string>(() => {
  switch (props.tone) {
    case "emerald":
      return "bg-emerald-50 text-emerald-700 ring-emerald-200/70 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/60";
    case "amber":
      return "bg-amber-50 text-amber-700 ring-amber-200/70 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900/60";
    case "rose":
      return "bg-rose-50 text-rose-700 ring-rose-200/70 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900/60";
    case "sky":
      return "bg-sky-50 text-sky-700 ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60";
    case "violet":
      return "bg-violet-50 text-violet-700 ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/60";
    default:
      return "bg-zinc-100 text-zinc-600 ring-zinc-200/70 dark:bg-zinc-800/60 dark:text-zinc-300 dark:ring-zinc-700/60";
  }
});

const dotClass = computed<string>(() => {
  switch (props.tone) {
    case "emerald":
      return "bg-emerald-500";
    case "amber":
      return "bg-amber-500";
    case "rose":
      return "bg-rose-500";
    case "sky":
      return "bg-sky-500";
    case "violet":
      return "bg-violet-500";
    default:
      return "bg-zinc-400";
  }
});
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
      toneClass,
    ]"
  >
    <span
      v-if="dot"
      :class="['size-1.5 shrink-0 rounded-full animate-pulse-dot', dotClass]"
      aria-hidden="true"
    />
    <component v-if="icon" :is="icon" class="size-3" />
    <span>{{ label }}</span>
  </span>
</template>
