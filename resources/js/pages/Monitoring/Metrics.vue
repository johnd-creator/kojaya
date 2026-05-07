<script setup lang="ts">
import { computed } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  CreditCard,
  ListChecks,
  RefreshCw,
  Timer,
  TrendingDown,
  XCircle,
} from "lucide-vue-next";

const props = defineProps<{
  metrics: {
    pending_approvals: Record<string, number>;
    failed_webhooks_24h: number;
    failed_pushes_24h: number;
    overdue_loan_ratio: number;
    queue_failures: number;
    slow_endpoints: Array<{ method: string; path: string; avg_time_ms: number }>;
    generated_at: string;
  };
}>();

function totalPending(): number {
  return Object.values(props.metrics.pending_approvals ?? {}).reduce((a, b) => a + b, 0);
}

function formatMs(ms: number): string {
  if (ms >= 1000) return `${(ms / 1000).toFixed(1)}s`;
  return `${Math.round(ms)}ms`;
}

const metricsCards = computed(() => [
  {
    key: "pending_approvals",
    label: "Pending Approvals",
    icon: ListChecks,
    value: totalPending(),
    format: (v: number) => String(v),
    color: "text-amber-500",
  },
  {
    key: "failed_webhooks_24h",
    label: "Failed Webhooks (24h)",
    icon: XCircle,
    value: props.metrics.failed_webhooks_24h,
    format: (v: number) => String(v),
    color: "text-red-500",
  },
  {
    key: "failed_pushes_24h",
    label: "Failed Push (24h)",
    icon: RefreshCw,
    value: props.metrics.failed_pushes_24h,
    format: (v: number) => String(v),
    color: "text-red-500",
  },
  {
    key: "overdue_loan_ratio",
    label: "Overdue Loan Ratio",
    icon: TrendingDown,
    value: props.metrics.overdue_loan_ratio,
    format: (v: number) => `${(v * 100).toFixed(1)}%`,
    color: "text-amber-500",
  },
  {
    key: "queue_failures",
    label: "Queue Failures",
    icon: CreditCard,
    value: props.metrics.queue_failures,
    format: (v: number) => String(v),
    color: "text-blue-500",
  },
]);
</script>

<template>
  <AppLayout :breadcrumbs="[{ title: 'Monitoring', href: '#' }, { title: 'App Metrics' }]">
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">App Metrics</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Metrik operasional aplikasi</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card v-for="card in metricsCards" :key="card.key">
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
              {{ card.label }}
            </CardTitle>
            <component :is="card.icon" :class="card.color" class="h-4 w-4" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold" :class="card.color">
              {{ card.format(card.value) }}
            </div>
          </CardContent>
        </Card>
      </div>

      <Card v-if="props.metrics.slow_endpoints?.length">
        <CardHeader>
          <CardTitle class="text-sm font-medium flex items-center gap-2">
            <Timer class="h-4 w-4" />
            Slow Endpoints
          </CardTitle>
        </CardHeader>
        <CardContent>
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-zinc-500">
                <th class="pb-2 font-medium">Method</th>
                <th class="pb-2 font-medium">Path</th>
                <th class="pb-2 font-medium text-right">Avg Time</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(ep, i) in props.metrics.slow_endpoints" :key="i" class="border-t border-zinc-100 dark:border-zinc-800">
                <td class="py-2">
                  <span class="text-xs font-mono">{{ ep.method }}</span>
                </td>
                <td class="py-2 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ ep.path }}</td>
                <td class="py-2 text-right text-xs text-zinc-500">{{ formatMs(ep.avg_time_ms) }}</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
