<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar";
import { toUrl } from "@/lib/utils";
import type { NavItem } from "@/types";

type Props = {
  items: NavItem[];
  class?: string;
};

const props = defineProps<Props>();

const isExternal = (href: NavItem["href"]): boolean => {
  const url = toUrl(href) ?? "";

  return /^(?:[a-z][a-z\d+.-]*:|\/\/)/i.test(url);
};
</script>

<template>
  <SidebarGroup
    :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`"
  >
    <SidebarGroupContent>
      <SidebarMenu data-testid="sidebar-footer-navigation">
        <SidebarMenuItem v-for="item in props.items" :key="item.title">
          <SidebarMenuButton
            class="text-sidebar-foreground/60 hover:text-sidebar-foreground"
            as-child
          >
            <Link
              v-if="!isExternal(item.href)"
              :href="item.href"
              :data-testid="
                item.title === 'Pusat Panduan'
                  ? 'sidebar-footer-help-link'
                  : undefined
              "
              prefetch
            >
              <span class="shrink-0"><component :is="item.icon" /></span>
              <span>{{ item.title }}</span>
            </Link>
            <a
              v-else
              :href="toUrl(item.href)"
              target="_blank"
              rel="noopener noreferrer"
            >
              <span class="shrink-0"><component :is="item.icon" /></span>
              <span>{{ item.title }}</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>
