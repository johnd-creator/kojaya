<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { ArrowRight } from "lucide-vue-next";
import type { Component } from "vue";
import { computed } from "vue";
import { cn } from "@/lib/utils";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc";

const props = withDefaults(
  defineProps<{
    title: string;
    description?: string;
    icon?: Component;
    tone?: Tone;
    href?: string;
    hrefLabel?: string;
  }>(),
  {
    tone: "emerald",
  },
);

const iconToneClass = computed<string>(() => {
  switch (props.tone) {
    case "amber":
      return "bg-amber-100 text-amber-700 ring-amber-200/70 dark:bg-amber-900/40 dark:text-amber-300 dark:ring-amber-900/60";
    case "rose":
      return "bg-rose-100 text-rose-700 ring-rose-200/70 dark:bg-rose-900/40 dark:text-rose-300 dark:ring-rose-900/60";
    case "sky":
      return "bg-sky-100 text-sky-700 ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60";
    case "violet":
      return "bg-violet-100 text-violet-700 ring-violet-200/70 dark:bg-violet-900/40 dark:text-violet-300 dark:ring-violet-900/60";
    case "zinc":
      return "bg-zinc-100 text-zinc-700 ring-zinc-200/70 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700/60";
    default:
      return "bg-emerald-100 text-emerald-700 ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60";
  }
});
</script>

<template>
  <div class="flex flex-col gap-3 border-b border-zinc-200/70 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800/70">
    <div class="flex items-start gap-3">
      <span
        v-if="icon"
        :class="[
          'inline-flex size-9 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset',
          iconToneClass,
        ]"
      >
        <component :is="icon" class="size-4" />
      </span>
      <div>
        <h2
          :class="cn('text-base font-semibold tracking-tight text-zinc-950 sm:text-lg dark:text-white')"
        >
          {{ title }}
        </h2>
        <p
          v-if="description"
          class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400"
        >
          {{ description }}
        </p>
      </div>
    </div>
    <Link
      v-if="href"
      :href="href"
      prefetch
      class="group inline-flex items-center gap-1 self-start text-sm font-semibold text-emerald-700 transition-colors hover:text-emerald-800 sm:self-auto dark:text-emerald-300 dark:hover:text-emerald-200"
    >
      {{ hrefLabel ?? "Lihat semua" }}
      <ArrowRight class="size-3.5 transition-transform group-hover:translate-x-0.5" />
    </Link>
  </div>
</template>
