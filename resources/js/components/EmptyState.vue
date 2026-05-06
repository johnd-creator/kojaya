<script setup lang="ts">
import type { Component } from "vue";
import { computed } from "vue";
import { FileText } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

interface Props {
  icon?: Component | null;
  title?: string;
  description?: string;
  actionLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
  title: "Tidak ada data",
  description: "Belum ada data yang bisa ditampilkan.",
  actionLabel: "",
});

const emptyIcon = computed<Component>(() => props.icon ?? FileText);

defineEmits<{
  (e: "action"): void;
}>();
</script>

<template>
  <div class="flex flex-col items-center justify-center py-10 text-center">
    <component
      :is="emptyIcon"
      class="mb-3 h-12 w-12 text-zinc-300 dark:text-zinc-700"
    />
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
      {{ title }}
    </h3>
    <p class="mt-1 max-w-md text-sm text-zinc-500 dark:text-zinc-400">
      {{ description }}
    </p>
    <Button
      v-if="actionLabel"
      variant="outline"
      class="mt-4"
      @click="$emit('action')"
    >
      {{ actionLabel }}
    </Button>
  </div>
</template>
