<script setup lang="ts">
import { computed, useId } from "vue";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc";

const props = withDefaults(
  defineProps<{
    points: number[];
    tone?: Tone;
    width?: number;
    height?: number;
    fill?: boolean;
    strokeWidth?: number;
  }>(),
  {
    tone: "emerald",
    width: 120,
    height: 36,
    fill: true,
    strokeWidth: 1.75,
  },
);

const uid = useId();
const gradientId = computed(() => `spark-grad-${uid}`);

const toneTextClass = computed<string>(() => {
  switch (props.tone) {
    case "amber":
      return "text-amber-500 dark:text-amber-400";
    case "rose":
      return "text-rose-500 dark:text-rose-400";
    case "sky":
      return "text-sky-500 dark:text-sky-400";
    case "violet":
      return "text-violet-500 dark:text-violet-400";
    case "zinc":
      return "text-zinc-400 dark:text-zinc-500";
    default:
      return "text-emerald-500 dark:text-emerald-400";
  }
});

const normalized = computed<{ x: number; y: number }[]>(() => {
  const pts = props.points.length > 0 ? props.points : [0];
  const min = Math.min(...pts);
  const max = Math.max(...pts);
  const range = max - min || 1;
  const pad = 2;
  const innerW = props.width - pad * 2;
  const innerH = props.height - pad * 2;
  return pts.map((value, index) => {
    const x =
      pts.length === 1
        ? props.width / 2
        : pad + (index / (pts.length - 1)) * innerW;
    const y = pad + (1 - (value - min) / range) * innerH;
    return { x, y };
  });
});

const linePath = computed(() =>
  normalized.value
    .map((point, index) => `${index === 0 ? "M" : "L"}${point.x.toFixed(2)},${point.y.toFixed(2)}`)
    .join(" "),
);

const fillPath = computed(() => {
  const pts = normalized.value;
  if (pts.length === 0) {
    return "";
  }
  const start = pts[0];
  const end = pts[pts.length - 1];
  return (
    `M${start.x.toFixed(2)},${(props.height - 1).toFixed(2)} ` +
    pts.map((p) => `L${p.x.toFixed(2)},${p.y.toFixed(2)}`).join(" ") +
    ` L${end.x.toFixed(2)},${(props.height - 1).toFixed(2)} Z`
  );
});
</script>

<template>
  <svg
    :viewBox="`0 0 ${width} ${height}`"
    :width="width"
    :height="height"
    preserveAspectRatio="none"
    aria-hidden="true"
    :class="['block h-auto w-full', toneTextClass]"
  >
    <defs>
      <linearGradient :id="gradientId" x1="0" x2="0" y1="0" y2="1">
        <stop offset="0%" stop-color="currentColor" stop-opacity="0.35" />
        <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
      </linearGradient>
    </defs>
    <path
      v-if="fill"
      :d="fillPath"
      :fill="`url(#${gradientId})`"
    />
    <path
      :d="linePath"
      fill="none"
      stroke="currentColor"
      :stroke-width="strokeWidth"
      stroke-linecap="round"
      stroke-linejoin="round"
      class="animate-sparkline-draw"
    />
  </svg>
</template>
