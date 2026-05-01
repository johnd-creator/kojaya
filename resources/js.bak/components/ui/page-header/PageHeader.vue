<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Home } from 'lucide-vue-next';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';

interface BreadcrumbItemType {
  title: string;
  href?: string;
}

interface Props {
  title: string;
  description?: string;
  breadcrumbs?: BreadcrumbItemType[];
}

defineProps<Props>();
</script>

<template>
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8">
    <div class="space-y-1.5">
      <!-- Breadcrumbs -->
      <Breadcrumb v-if="breadcrumbs && breadcrumbs.length > 0" class="mb-2">
        <BreadcrumbList>
          <!-- Home Link (Optional, using hardcoded /dashboard or omitted if preferred) -->
          <BreadcrumbItem>
            <BreadcrumbLink as-child>
              <Link href="/dashboard">
                <Home class="h-4 w-4" />
              </Link>
            </BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbSeparator />
          
          <template v-for="(item, index) in breadcrumbs" :key="index">
            <BreadcrumbItem>
              <template v-if="index === breadcrumbs.length - 1 || !item.href">
                <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
              </template>
              <template v-else>
                <BreadcrumbLink as-child>
                  <Link :href="item.href">{{ item.title }}</Link>
                </BreadcrumbLink>
              </template>
            </BreadcrumbItem>
            <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
          </template>
        </BreadcrumbList>
      </Breadcrumb>

      <!-- Title & Description -->
      <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
        {{ title }}
      </h2>
      <p v-if="description" class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ description }}
      </p>
    </div>

    <!-- Actions Slot -->
    <div v-if="$slots.actions" class="flex items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>
