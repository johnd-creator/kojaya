<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { ChevronRight } from "lucide-vue-next";
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible";
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from "@/components/ui/sidebar";
import { useCurrentUrl } from "@/composables/useCurrentUrl";
import type { NavItem } from "@/types";

defineProps<{
  items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
  <SidebarGroup class="px-2 py-0">
    <SidebarGroupLabel>Platform</SidebarGroupLabel>
    <SidebarMenu>
      <template v-for="item in items" :key="item.title">
        <Collapsible
          v-if="item.items && item.items.length > 0"
          as-child
          :default-open="
            item.items.some((subItem) => isCurrentUrl(subItem.href))
          "
          class="group/collapsible"
        >
          <SidebarMenuItem>
            <CollapsibleTrigger as-child>
              <SidebarMenuButton :tooltip="item.title">
                <span v-if="item.icon" class="shrink-0"><component :is="item.icon" /></span>
                <span>{{ item.title }}</span>
                <ChevronRight
                  class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                />
              </SidebarMenuButton>
            </CollapsibleTrigger>
            <CollapsibleContent class="overflow-hidden">
              <SidebarMenuSub>
                <SidebarMenuSubItem
                  v-for="subItem in item.items"
                  :key="subItem.title"
                >
                  <SidebarMenuSubButton
                    as-child
                    :is-active="isCurrentUrl(subItem.href)"
                  >
                    <Link :href="subItem.href">
                      <span>{{ subItem.title }}</span>
                    </Link>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>
              </SidebarMenuSub>
            </CollapsibleContent>
          </SidebarMenuItem>
        </Collapsible>

        <SidebarMenuItem v-else>
          <SidebarMenuButton
            as-child
            :is-active="isCurrentUrl(item.href)"
            :tooltip="item.title"
          >
            <Link :href="item.href">
              <span v-if="item.icon" class="shrink-0"><component :is="item.icon" /></span>
              <span>{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </template>
    </SidebarMenu>
  </SidebarGroup>
</template>
