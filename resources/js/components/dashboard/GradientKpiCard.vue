<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { ArrowRight } from "lucide-vue-next";
import { computed } from "vue";
import type { Component } from "vue";
import { cn } from "@/lib/utils";
import Sparkline from "./Sparkline.vue";
import TrendBadge from "./TrendBadge.vue";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet";

const props = withDefaults(
  defineProps<{
    label: string;
    value: string;
    meta?: string;
    icon: Component;
    tone: Tone;
    href: string;
    sparklinePoints?: number[];
    trend?: number | null;
    trendLabel?: string;
    invertTrend?: boolean;
  }>(),
  {
    sparklinePoints: () => [],
    trend: null,
  },
);

const toneClass = computed(() => {
  switch (props.tone) {
    case "amber":
      return {
        glow: "before:bg-[radial-gradient(circle_at_top_right,rgba(245,158,11,0.18),transparent_60%)]",
        icon: "bg-amber-100 text-amber-700 ring-amber-200/70 dark:bg-amber-900/40 dark:text-amber-300 dark:ring-amber-900/60",
        ring: "hover:ring-amber-300/60 dark:hover:ring-amber-700/60",
        accent: "bg-amber-500",
        chevron: "group-hover:text-amber-600 dark:group-hover:text-amber-300",
      };
    case "rose":
      return {
        glow: "before:bg-[radial-gradient(circle_at_top_right,rgba(244,63,94,0.18),transparent_60%)]",
        icon: "bg-rose-100 text-rose-700 ring-rose-200/70 dark:bg-rose-900/40 dark:text-rose-300 dark:ring-rose-900/60",
        ring: "hover:ring-rose-300/60 dark:hover:ring-rose-700/60",
        accent: "bg-rose-500",
        chevron: "group-hover:text-rose-600 dark:group-hover:text-rose-300",
      };
    case "sky":
      return {
        glow: "before:bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.18),transparent_60%)]",
        icon: "bg-sky-100 text-sky-700 ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60",
        ring: "hover:ring-sky-300/60 dark:hover:ring-sky-700/60",
        accent: "bg-sky-500",
        chevron: "group-hover:text-sky-600 dark:group-hover:text-sky-300",
      };
    case "violet":
      return {
        glow: "before:bg-[radial-gradient(circle_at_top_right,rgba(139,92,246,0.18),transparent_60%)]",
        icon: "bg-violet-100 text-violet-700 ring-violet-200/70 dark:bg-violet-900/40 dark:text-violet-300 dark:ring-violet-900/60",
        ring: "hover:ring-violet-300/60 dark:hover:ring-violet-700/60",
        accent: "bg-violet-500",
        chevron: "group-hover:text-violet-600 dark:group-hover:text-violet-300",
      };
    default:
      return {
        glow: "before:bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.20),transparent_60%)]",
        icon: "bg-emerald-100 text-emerald-700 ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60",
        ring: "hover:ring-emerald-300/60 dark:hover:ring-emerald-700/60",
        accent: "bg-emerald-500",
        chevron: "group-hover:text-emerald-600 dark:group-hover:text-emerald-300",
      };
  }
});
</script>

<template>
  <Link
    :href="href"
    prefetch
    :aria-label="`Buka modul ${label}`"
    :class="[
      'group relative isolate flex flex-col gap-4 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white/95 p-5 shadow-sm shadow-zinc-950/5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-zinc-950/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-zinc-800/80 dark:bg-zinc-900/80 dark:focus-visible:ring-offset-zinc-950',
      'before:pointer-events-none before:absolute before:inset-0 before:opacity-0 before:transition-opacity before:duration-300 group-hover:before:opacity-100',
      toneClass.glow,
      toneClass.ring,
    ]"
  >
    <span
      :class="['absolute inset-x-0 top-0 h-0.5 origin-left scale-x-0 transition-transform duration-300 group-hover:scale-x-100', toneClass.accent]"
    />
    <div class="flex items-start justify-between gap-3">
      <div class="space-y-1.5">
        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
          {{ label }}
        </p>
        <p
          class="text-2xl font-bold tabular-nums tracking-tight text-zinc-950 sm:text-[1.7rem] dark:text-white"
        >
          {{ value }}
        </p>
      </div>
      <span
        :class="[
          'inline-flex size-11 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset transition-transform duration-300 group-hover:scale-110',
          toneClass.icon,
        ]"
      >
        <component :is="icon" class="size-5" />
      </span>
    </div>

    <div v-if="sparklinePoints.length > 0" class="-mx-1">
      <Sparkline :points="sparklinePoints" :tone="tone" :height="42" :width="240" />
    </div>

    <div class="flex items-center justify-between gap-2">
      <p
        v-if="meta"
        class="text-xs leading-relaxed text-zinc-500 dark:text-zinc-400"
      >
        {{ meta }}
      </p>
      <div class="ml-auto flex items-center gap-2">
        <TrendBadge
          v-if="trend !== null"
          :delta="trend"
          :label="trendLabel"
          tone="auto"
          :invert="invertTrend"
        />
        <ArrowRight
          :class="[
            'size-4 text-zinc-300 transition-all duration-200 group-hover:translate-x-0.5 dark:text-zinc-600',
            toneClass.chevron,
          ]"
        />
      </div>
    </div>
  </Link>
</template>
