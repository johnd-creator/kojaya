<script setup lang="ts">
import { ProgressIndicator, type ProgressIndicatorProps } from 'reka-ui'
import { cn } from '@/lib/utils'
import type { HTMLAttributes } from 'vue'

const props = defineProps<ProgressIndicatorProps & { class?: HTMLAttributes['class'] }>()
</script>

<template>
  <ProgressIndicator
    :class="cn('h-full w-full flex-1 bg-primary transition-all', props.class)"
    v-bind="props"
  />
</template>
