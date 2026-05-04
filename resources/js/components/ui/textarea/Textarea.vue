<script setup lang="ts">
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps<{
  class?: any;
  defaultValue?: string;
  modelValue?: string;
  placeholder?: string;
  disabled?: boolean;
  rows?: number;
}>();

const emits = defineEmits<{
  (e: "update:modelValue", payload: string): void;
}>();

const modelValue = computed({
  get: () => props.modelValue ?? props.defaultValue ?? "",
  set: (value: string) => emits("update:modelValue", value),
});

const textareaClasses = computed(() =>
  cn(
    "flex min-h-[60px] w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm placeholder:text-zinc-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50 dark:placeholder:text-zinc-400",
    props.class,
  ),
);
</script>

<template>
  <textarea
    v-model="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :rows="rows || 3"
    :class="textareaClasses"
  />
</template>
