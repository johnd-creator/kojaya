<script setup lang="ts">
import { X } from "lucide-vue-next";
import { useToast } from "@/composables/useToast";

const { toasts, dismiss } = useToast();
</script>

<template>
  <div
    aria-live="polite"
    aria-atomic="true"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-[100] flex flex-col items-center gap-2 p-4 sm:bottom-4 sm:right-4 sm:left-auto sm:items-end"
  >
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-white p-4 shadow-lg dark:bg-zinc-900"
        :class="{
          'border-green-200 dark:border-green-800': toast.variant === 'success',
          'border-red-200 dark:border-red-800': toast.variant === 'error',
          'border-amber-200 dark:border-amber-800': toast.variant === 'warning',
          'border-sky-200 dark:border-sky-800': toast.variant === 'status',
          'border-zinc-200 dark:border-zinc-700': !toast.variant || toast.variant === 'info',
        }"
      >
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            {{ toast.title }}
          </p>
          <p
            v-if="toast.description"
            class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400"
          >
            {{ toast.description }}
          </p>
        </div>
        <button
          aria-label="Tutup notifikasi"
          class="mt-0.5 shrink-0 rounded-lg p-0.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
          @click="dismiss(toast.id)"
        >
          <X class="size-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active {
  transition: all 0.3s ease-out;
}
.toast-leave-active {
  transition: all 0.2s ease-in;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(1rem);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(0.5rem);
}
</style>
