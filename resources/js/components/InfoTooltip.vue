<script setup lang="ts">
import { Info } from "lucide-vue-next";
import { ref } from "vue";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";

defineProps<{ label: string }>();

const open = ref(false);
</script>

<template>
  <TooltipProvider :delay-duration="200">
    <Tooltip v-model:open="open" disable-closing-trigger>
      <TooltipTrigger as-child>
        <button
          type="button"
          :aria-label="label"
          class="inline-flex size-7 shrink-0 items-center justify-center rounded-full text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
          @click="open = !open"
        >
          <Info class="size-3.5" aria-hidden="true" />
        </button>
      </TooltipTrigger>
      <TooltipContent
        side="bottom"
        :collision-padding="16"
        class="max-w-[min(22rem,calc(100vw-2rem))] space-y-2 p-3 text-left text-xs leading-relaxed text-pretty"
      >
        <slot />
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>
