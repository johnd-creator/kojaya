<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { useCurrentUrl } from "@/composables/useCurrentUrl";
import {
  mobileNavGridClass,
  resolveMemberMobileNavItems,
  type MemberAccess,
} from "@/lib/member-mobile-nav";
import { computed } from "vue";

const { isCurrentUrl } = useCurrentUrl();
const page = usePage();

const memberAccess = computed<MemberAccess | null>(() => {
  const auth = page.props.auth as any;

  return (auth?.member_access ?? null) as MemberAccess | null;
});

const items = computed(() => resolveMemberMobileNavItems(memberAccess.value));
const gridClass = computed(() => mobileNavGridClass(items.value.length));

const isActive = (href: string): boolean => isCurrentUrl(href);
</script>

<template>
  <nav
    :class="[
      'fixed inset-x-3 bottom-3 z-40 grid rounded-2xl border border-emerald-950/10 bg-white/95 px-1.5 pt-1.5 pb-[max(0.375rem,env(safe-area-inset-bottom))] shadow-xl shadow-emerald-950/15 backdrop-blur dark:border-white/10 dark:bg-zinc-950/95 md:hidden',
      gridClass,
    ]"
    aria-label="Navigasi utama Kojayaku"
  >
    <Link
      v-for="item in items"
      :key="item.label"
      :href="item.href"
      prefetch
      class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl px-1 text-[10px] font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950"
      :class="
        isActive(item.href)
          ? 'bg-emerald-700 text-white shadow-sm'
          : 'text-zinc-500 hover:bg-emerald-50 hover:text-emerald-800 dark:text-zinc-400 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-200'
      "
      :aria-current="isActive(item.href) ? 'page' : undefined"
    >
      <component :is="item.icon" class="size-4" aria-hidden="true" />
      <span>{{ item.label }}</span>
    </Link>
  </nav>
</template>
