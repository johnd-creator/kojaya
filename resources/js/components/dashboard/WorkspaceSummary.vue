<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import InfoTooltip from "@/components/InfoTooltip.vue";

defineProps<{
  items: Array<{
    label: string;
    value: string | number;
    hint?: string;
    href?: string;
    tone?: string;
  }>;
  label?: string;
}>();

const columns: Record<number, string> = {
  1: "sm:grid-cols-1",
  2: "sm:grid-cols-2",
  3: "sm:grid-cols-3",
  4: "lg:grid-cols-4",
  5: "sm:grid-cols-3 xl:grid-cols-5",
  6: "sm:grid-cols-3 xl:grid-cols-6",
  7: "sm:grid-cols-4 xl:grid-cols-7",
  8: "sm:grid-cols-4 2xl:grid-cols-8",
};
</script>

<template>
  <dl
    :aria-label="label ?? 'Ringkasan'"
    :class="[
      'grid grid-cols-2 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50/60 dark:border-zinc-800 dark:bg-zinc-900/60',
      columns[items.length] ?? 'sm:grid-cols-3 xl:grid-cols-4',
    ]"
    data-testid="workspace-summary"
  >
    <div
      v-for="item in items"
      :key="item.label"
      class="min-w-0 border-r border-zinc-200/70 px-4 py-3 last:border-r-0 dark:border-zinc-800"
    >
      <dt
        class="flex min-h-7 items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400"
      >
        {{ item.label }}
        <InfoTooltip v-if="item.hint" :label="`Keterangan ${item.label}`">{{
          item.hint
        }}</InfoTooltip>
      </dt>
      <dd
        :class="[
          'mt-1 break-words text-base font-semibold tabular-nums tracking-tight sm:text-lg',
          item.tone === 'rose'
            ? 'text-rose-700 dark:text-rose-400'
            : item.tone === 'emerald'
              ? 'text-emerald-700 dark:text-emerald-400'
              : 'text-zinc-950 dark:text-white',
        ]"
      >
        <Link
          v-if="item.href"
          :href="item.href"
          class="rounded-sm underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-emerald-600"
          >{{ item.value }}</Link
        >
        <span v-else>{{ item.value }}</span>
      </dd>
    </div>
  </dl>
</template>
