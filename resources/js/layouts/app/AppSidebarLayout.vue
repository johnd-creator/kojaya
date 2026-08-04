<script setup lang="ts">
import { usePage } from "@inertiajs/vue3";
import { computed, watch } from "vue";
import AppContent from "@/components/AppContent.vue";
import AppShell from "@/components/AppShell.vue";
import AppSidebar from "@/components/AppSidebar.vue";
import AppSidebarHeader from "@/components/AppSidebarHeader.vue";
import MemberMobileNav from "@/components/Kojayaku/MemberMobileNav.vue";
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
const memberOnly = computed(() => {
  const roles = ((page.props.auth as { roles?: Array<{ name?: string } | string> } | undefined)?.roles ?? []).map(
    (role) => (typeof role === "string" ? role : role.name ?? ""),
  );

  return roles.includes("Anggota") && roles.every((role) => role === "Anggota");
});

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
    <AppContent variant="sidebar" :class="['overflow-x-hidden', memberOnly ? 'pb-24 md:pb-0' : '']">
      <AppSidebarHeader :breadcrumbs="breadcrumbs" />
      <slot />
    </AppContent>
    <MemberMobileNav v-if="memberOnly" />
    <Toaster />
  </AppShell>
</template>
