<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
  defineProps<{
    value: number;
    max?: number;
    tone?: "emerald" | "amber" | "rose" | "sky" | "violet";
    label?: string;
    showValue?: boolean;
  }>(),
  {
    max: 100,
    tone: "emerald",
    showValue: false,
  },
);

const clamped = computed<number>(() => {
  const ratio = (props.value / props.max) * 100;
  if (Number.isNaN(ratio)) {
    return 0;
  }
  return Math.max(0, Math.min(100, ratio));
});

const fillClass = computed<string>(() => {
  switch (props.tone) {
    case "amber":
      return "bg-gradient-to-r from-amber-500 via-amber-500 to-amber-600";
    case "rose":
      return "bg-gradient-to-r from-rose-500 via-rose-500 to-rose-600";
    case "sky":
      return "bg-gradient-to-r from-sky-500 via-sky-500 to-sky-600";
    case "violet":
      return "bg-gradient-to-r from-violet-500 via-violet-500 to-violet-600";
    default:
      return "bg-gradient-to-r from-emerald-500 via-emerald-500 to-emerald-600";
  }
});
</script>

<template>
  <div class="flex items-center gap-3">
    <div
      class="relative h-2 flex-1 overflow-hidden rounded-full bg-zinc-100 shadow-inner shadow-zinc-950/5 dark:bg-zinc-800/80"
      role="progressbar"
      :aria-valuenow="value"
      :aria-valuemin="0"
      :aria-valuemax="max"
      :aria-label="label ?? 'Progress'"
    >
      <div
        :class="['h-full rounded-full transition-[width] duration-700 ease-out', fillClass]"
        :style="{ width: `${clamped}%` }"
      />
    </div>
    <span
      v-if="showValue"
      class="min-w-[3rem] text-right text-sm font-semibold tabular-nums text-zinc-700 dark:text-zinc-200"
    >
      {{ clamped.toFixed(1) }}%
    </span>
  </div>
</template>
