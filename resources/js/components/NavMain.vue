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
import { ref } from "vue";
import { useCurrentUrl } from "@/composables/useCurrentUrl";
import type { NavItem } from "@/types";

defineProps<{
  items: NavItem[];
  label?: string;
}>();

const { isCurrentUrl } = useCurrentUrl();
const openItems = ref<Record<string, boolean>>({});

const hasActiveChild = (item: NavItem): boolean =>
  item.items?.some((subItem) => isCurrentUrl(subItem.href)) ?? false;

const isItemOpen = (item: NavItem): boolean =>
  hasActiveChild(item) || openItems.value[item.title] === true;

const updateItemOpenState = (item: NavItem, open: boolean): void => {
  if (hasActiveChild(item)) {
    return;
  }

  openItems.value[item.title] = open;
};
</script>

<template>
  <SidebarGroup class="px-2 py-0">
    <SidebarGroupLabel>{{ label ?? "Platform" }}</SidebarGroupLabel>
    <SidebarMenu>
      <template v-for="item in items" :key="item.title">
        <Collapsible
          v-if="item.items && item.items.length > 0"
          as-child
          :open="isItemOpen(item)"
          @update:open="updateItemOpenState(item, $event)"
          class="group/collapsible"
        >
          <SidebarMenuItem>
            <CollapsibleTrigger as-child>
              <SidebarMenuButton :tooltip="item.title">
                <span v-if="item.icon" class="shrink-0"
                  ><component :is="item.icon"
                /></span>
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
                    <Link :href="subItem.href" prefetch>
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
            <Link :href="item.href" prefetch>
              <span v-if="item.icon" class="shrink-0"
                ><component :is="item.icon"
              /></span>
              <span>{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </template>
    </SidebarMenu>
  </SidebarGroup>
</template>
