<script setup lang="ts">
import { ArrowDownRight, ArrowUpRight, Minus } from "lucide-vue-next";
import { computed } from "vue";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "auto";

const props = withDefaults(
  defineProps<{
    delta: number | null;
    label?: string;
    tone?: Tone;
    invert?: boolean;
  }>(),
  {
    tone: "auto",
    invert: false,
  },
);

const formatted = computed<string>(() => {
  if (props.delta === null || props.delta === undefined || Number.isNaN(props.delta)) {
    return "—";
  }
  const sign = props.delta > 0 ? "+" : "";
  return `${sign}${props.delta.toFixed(1)}%`;
});

const isUp = computed<boolean>(() => (props.delta ?? 0) > 0);
const isFlat = computed<boolean>(() => props.delta === null || props.delta === 0);

const resolvedTone = computed<Tone>(() => {
  if (props.tone !== "auto") {
    return props.tone;
  }
  if (isFlat.value) {
    return "zinc";
  }
  const goodDirection = props.invert ? !isUp.value : isUp.value;
  return goodDirection ? "emerald" : "rose";
});

const toneClass = computed<string>(() => {
  switch (resolvedTone.value) {
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
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums ring-1 ring-inset',
      toneClass,
    ]"
  >
    <component
      :is="isFlat ? Minus : isUp ? ArrowUpRight : ArrowDownRight"
      class="size-3"
    />
    <span>{{ formatted }}</span>
    <span v-if="label" class="text-[10px] font-medium opacity-75">{{ label }}</span>
  </span>
</template>
