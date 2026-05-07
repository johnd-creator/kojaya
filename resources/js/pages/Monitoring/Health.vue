<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  CheckCircle,
  Database,
  HardDrive,
  Radio,
  Server,
  Wifi,
} from "lucide-vue-next";

const props = defineProps<{
  health: {
    status: string;
    timestamp: string;
    components: Record<string, { status: string; [key: string]: any }>;
    counts: Record<string, number>;
  };
}>();

function statusBadge(status: string): string {
  if (status === "ok") {
    return "bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300";
  }
  if (status === "error") {
    return "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300";
  }
  return "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300";
}

const checks = [
  { key: "app", label: "Application", icon: Wifi, detail: `PHP ${props.health.components?.app?.php_version ?? ""}` },
  { key: "database", label: "Database", icon: Database, detail: props.health.components?.database?.connection ?? "" },
  { key: "queue", label: "Queue", icon: Radio, detail: `${props.health.components?.queue?.pending_jobs ?? 0} pending` },
  { key: "storage", label: "Storage", icon: HardDrive, detail: props.health.components?.storage?.disk ?? "" },
  { key: "vendor", label: "Vendor Integrations", icon: Server, detail: props.health.components?.vendor_integrations?.payment_gateway?.provider ?? "" },
];
</script>

<template>
  <AppLayout :breadcrumbs="[{ title: 'Monitoring', href: '#' }, { title: 'System Health' }]">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">System Health</h1>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">Status komponen aplikasi</p>
        </div>
        <Badge
          :class="health.status === 'ok'
            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300'
            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'"
          class="text-sm px-3 py-1"
        >
          {{ health.status.toUpperCase() }}
        </Badge>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card v-for="check in checks" :key="check.key">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
              {{ check.label }}
            </CardTitle>
            <component :is="check.icon" class="h-4 w-4 text-zinc-400" />
          </CardHeader>
          <CardContent>
            <Badge
              :class="statusBadge(props.health.components?.[check.key]?.status ?? 'unknown')"
              class="text-xs font-medium"
            >
              {{ (props.health.components?.[check.key]?.status ?? "unknown").toUpperCase() }}
            </Badge>
            <p class="text-xs text-zinc-500 mt-2">
              {{ check.detail }}
            </p>
          </CardContent>
        </Card>
      </div>

      <div class="flex items-center gap-2 text-xs text-zinc-500">
        <CheckCircle v-if="health.status === 'ok'" class="h-4 w-4 text-emerald-500" />
        <span>Last checked: {{ health.timestamp }}</span>
      </div>
    </div>
  </AppLayout>
</template>
