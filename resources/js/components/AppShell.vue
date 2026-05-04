<script setup lang="ts">
import { usePage } from "@inertiajs/vue3";
import { SidebarProvider } from "@/components/ui/sidebar";
import type { AppShellVariant } from "@/types";

type Props = {
  variant?: AppShellVariant;
};

defineProps<Props>();

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
  <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
    <a
      href="#main-content"
      class="sr-only absolute left-4 top-4 z-50 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground focus:not-sr-only"
    >
      Lewati ke konten utama
    </a>
    <slot />
  </div>
  <SidebarProvider v-else :default-open="isOpen">
    <a
      href="#main-content"
      class="sr-only absolute left-4 top-4 z-50 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground focus:not-sr-only"
    >
      Lewati ke konten utama
    </a>
    <slot />
  </SidebarProvider>
</template>
