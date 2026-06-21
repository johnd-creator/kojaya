<script setup lang="ts">
import { X } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

interface BulkAction {
  label: string;
  variant?: "default" | "destructive" | "outline" | "secondary";
  action: string;
  requireConfirm?: boolean;
  confirmMessage?: string;
}

const props = withDefaults(
  defineProps<{
    selected: any[];
    actions: BulkAction[];
  }>(),
  {
    selected: () => [],
    actions: () => [],
  },
);

const emit = defineEmits<{
  action: [action: string, selected: any[]];
  clear: [];
}>();
</script>

<template>
  <Transition
    enter-active-class="transition-all duration-200 ease-out"
    enter-from-class="opacity-0 -translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition-all duration-150 ease-in"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 -translate-y-2"
  >
    <div
      v-if="selected.length > 0"
      class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-4 py-2.5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
    >
      <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
        {{ selected.length }} item dipilih
      </span>
      <div class="flex items-center gap-2">
        <Button
          v-for="act in actions"
          :key="act.action"
          size="sm"
          :variant="act.variant ?? 'default'"
          @click="emit('action', act.action, selected)"
        >
          {{ act.label }}
        </Button>
      </div>
      <Button
        variant="ghost"
        size="icon"
        class="ml-auto size-7"
        aria-label="Hapus pilihan"
        @click="emit('clear')"
      >
        <X class="size-4" />
      </Button>
    </div>
  </Transition>
</template>
