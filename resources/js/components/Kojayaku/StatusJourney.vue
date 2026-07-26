<script setup lang="ts">
import { CheckCircle2, Circle, CircleDashed } from "lucide-vue-next";
import { computed } from "vue";
import { Badge } from "@/components/ui/badge";
import { formatCurrency, formatDateTime } from "@/lib/formatters";

type JourneyStep = {
  label: string;
  completed: boolean;
  completed_at?: string | null;
};

const props = defineProps<{
  title: string;
  currentStatus: string;
  reference?: string | null;
  amount?: number | string | null;
  steps: JourneyStep[];
}>();

const completedCount = computed(
  () => props.steps.filter((step) => step.completed).length,
);
const progressWidth = computed(() => {
  if (props.steps.length === 0) {
    return "0%";
  }

  return `${Math.round((completedCount.value / props.steps.length) * 100)}%`;
});
const currentStep = computed(() => {
  return props.steps[completedCount.value]?.label ?? "Selesai";
});
</script>

<template>
  <section
    class="rounded-3xl border border-zinc-100 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6"
  >
    <div
      class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
      <div>
        <p
          class="text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-400"
        >
          Tahap pengajuan
        </p>
        <h2 class="mt-1 font-black tracking-tight">{{ title }}</h2>
        <p class="mt-1 text-sm text-muted-foreground">
          {{ reference || "Belum ada aktivitas terbaru" }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Badge variant="outline">{{ currentStatus }}</Badge>
        <span v-if="amount" class="text-sm font-medium">{{
          formatCurrency(amount)
        }}</span>
      </div>
    </div>

    <div v-if="steps.length > 0" class="mt-5 space-y-2">
      <div class="flex items-center justify-between text-xs">
        <span class="font-semibold text-zinc-600 dark:text-zinc-300">{{
          currentStep
        }}</span>
        <span class="font-black text-emerald-700 dark:text-emerald-400">{{
          progressWidth
        }}</span>
      </div>
      <div
        class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"
        aria-label="Progress pengajuan"
      >
        <div
          class="h-full rounded-full bg-emerald-600 transition-all"
          :style="{ width: progressWidth }"
        />
      </div>
    </div>

    <ol class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <li
        v-for="(step, index) in steps"
        :key="step.label"
        class="flex gap-3 rounded-2xl border border-zinc-100 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-950/30"
      >
        <CheckCircle2
          v-if="step.completed"
          class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"
        />
        <CircleDashed
          v-else-if="index === completedCount"
          class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"
        />
        <Circle v-else class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" />
        <div class="min-w-0">
          <div class="text-sm font-medium">{{ step.label }}</div>
          <div class="mt-1 text-xs text-muted-foreground">
            {{
              step.completed_at
                ? formatDateTime(step.completed_at)
                : step.completed
                  ? "Selesai"
                  : "Menunggu"
            }}
          </div>
        </div>
      </li>
    </ol>
  </section>
</template>
