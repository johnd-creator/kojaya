<script setup lang="ts">
import { watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import AppContent from "@/components/AppContent.vue";
import AppShell from "@/components/AppShell.vue";
import AppSidebar from "@/components/AppSidebar.vue";
import AppSidebarHeader from "@/components/AppSidebarHeader.vue";
import Toaster from "@/components/ui/toast/Toaster.vue";
import { useToast } from "@/composables/useToast";
import type { BreadcrumbItem } from "@/types";

type Props = {
  breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
});

const { toast } = useToast();
const page = usePage();

watch(
  () => (page.props as any)?.flash,
  (flash) => {
    if (!flash) return;

    const entries: Array<[string, string]> = [
      ["success", flash.success],
      ["error", flash.error],
      ["warning", flash.warning],
      ["status", flash.status],
    ];

    for (const [variant, message] of entries) {
      if (!message) continue;
      toast({ title: message, variant: variant as any });
    }
  },
);
</script>

<template>
  <AppShell variant="sidebar">
    <AppSidebar />
    <AppContent variant="sidebar" class="overflow-x-hidden">
      <AppSidebarHeader :breadcrumbs="breadcrumbs" />
      <slot />
    </AppContent>
    <Toaster />
  </AppShell>
</template>
