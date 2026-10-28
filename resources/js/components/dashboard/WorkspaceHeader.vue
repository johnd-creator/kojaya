<script setup lang="ts">
import InfoTooltip from "@/components/InfoTooltip.vue";

defineProps<{ title: string; description?: string }>();
</script>

<template>
  <header class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex min-w-0 items-center gap-2">
      <h1
        class="text-xl font-semibold tracking-tight text-zinc-950 sm:text-2xl dark:text-white"
      >
        {{ title }}
      </h1>
      <InfoTooltip v-if="description" :label="`Tentang ${title}`">
        {{ description }}
      </InfoTooltip>
    </div>
    <div v-if="$slots.default" class="flex flex-wrap items-center gap-2">
      <slot />
    </div>
  </header>
</template>
