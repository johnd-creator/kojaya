<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import { LogOut, Moon, Sun } from "lucide-vue-next";
import { computed, onMounted, onUnmounted, ref } from "vue";
import Breadcrumbs from "@/components/Breadcrumbs.vue";
import ContextualHelpButton from "@/components/Documentation/ContextualHelpButton.vue";
import NotificationIcon from "@/components/Notification/NotificationIcon.vue";
import { Button } from "@/components/ui/button";
import { SidebarTrigger } from "@/components/ui/sidebar";
import { useAppearance } from "@/composables/useAppearance";
import type { BreadcrumbItem } from "@/types";

withDefaults(
  defineProps<{
    breadcrumbs?: BreadcrumbItem[];
  }>(),
  {
    breadcrumbs: () => [],
  },
);

const { resolvedAppearance, updateAppearance } = useAppearance();
const page = usePage();
const now = ref(new Date());
let clockTimer: number | undefined;

const formattedDateTime = computed(() =>
  new Intl.DateTimeFormat("id-ID", {
    weekday: "short",
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(now.value),
);

const toggleAppearance = (): void => {
  updateAppearance(resolvedAppearance.value === "dark" ? "light" : "dark");
};

const memberOnly = computed(() => {
  const roles = ((page.props.auth as { roles?: Array<{ name?: string } | string> } | undefined)?.roles ?? []).map(
    (role) => (typeof role === "string" ? role : role.name ?? ""),
  );

  return roles.includes("Anggota") && roles.every((role) => role === "Anggota");
});

onMounted(() => {
  clockTimer = window.setInterval(() => {
    now.value = new Date();
  }, 1000);
});

onUnmounted(() => {
  if (clockTimer) {
    window.clearInterval(clockTimer);
  }
});
</script>

<template>
  <header
    class="flex h-16 w-full shrink-0 items-center justify-between border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
  >
    <div class="flex items-center gap-2">
      <SidebarTrigger class="-ml-1" />
      <template v-if="breadcrumbs && breadcrumbs.length > 0">
        <Breadcrumbs :breadcrumbs="breadcrumbs" />
      </template>
      <ContextualHelpButton class="hidden md:inline-flex" />
    </div>
    <div class="flex items-center gap-2">
      <div
        v-if="memberOnly"
        class="hidden items-center rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200 sm:flex"
      >
        Kojayaku · Anggota
      </div>
      <div
        v-else
        class="hidden items-center rounded-md border border-sidebar-border/70 px-3 py-1.5 text-sm text-muted-foreground sm:flex"
      >
        {{ formattedDateTime }}
      </div>
      <Button
        variant="ghost"
        size="icon"
        class="rounded-full"
        :aria-label="
          resolvedAppearance === 'dark'
            ? 'Aktifkan mode terang'
            : 'Aktifkan mode gelap'
        "
        @click="toggleAppearance"
      >
        <Sun v-if="resolvedAppearance === 'dark'" class="h-5 w-5" />
        <Moon v-else class="h-5 w-5" />
      </Button>
      <NotificationIcon :max-items="5" />
      <Button
        variant="ghost"
        size="icon"
        class="rounded-full"
        aria-label="Keluar"
        as-child
      >
        <Link href="/logout" method="post" as="button">
          <LogOut class="h-5 w-5" />
        </Link>
      </Button>
    </div>
  </header>
</template>
