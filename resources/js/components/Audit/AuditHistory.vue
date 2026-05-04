<script setup lang="ts">
import { ref, onMounted } from "vue";
import { auditLogsApi } from "@/Actions/auditLogs";
import type { AuditLog } from "@/Actions/auditLogs";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { formatDateTime } from "@/lib/formatters";

interface Props {
  subjectType: string;
  subjectId: number;
  title?: string;
  description?: string;
}

const props = withDefaults(defineProps<Props>(), {
  title: "Audit History",
  description: "View all changes made to this record",
});

const logs = ref<AuditLog[]>([]);
const loading = ref(false);
const expanded = ref(false);

const fetchHistory = async () => {
  loading.value = true;
  try {
    logs.value = await auditLogsApi.history(props.subjectType, props.subjectId);
  } catch (error) {
    console.error("Failed to fetch audit history:", error);
  } finally {
    loading.value = false;
  }
};

const getActionIcon = (action: string): string => {
  const icons: Record<string, string> = {
    CREATE: "➕",
    UPDATE: "✏️",
    DELETE: "🗑️",
    LOGIN: "🔐",
    LOGOUT: "🚪",
    FAILED_LOGIN: "⚠️",
  };
  return icons[action] || "📝";
};

const getActionColor = (action: string): string => {
  const colors: Record<string, string> = {
    CREATE: "text-green-600 dark:text-green-400",
    UPDATE: "text-blue-600 dark:text-blue-400",
    DELETE: "text-red-600 dark:text-red-400",
    LOGIN: "text-purple-600 dark:text-purple-400",
    LOGOUT: "text-gray-600 dark:text-gray-400",
    FAILED_LOGIN: "text-yellow-600 dark:text-yellow-400",
  };
  return colors[action] || "text-gray-600";
};

const getChangesSummary = (log: AuditLog): string => {
  if (log.action === "CREATE") {
    const count = Object.keys(log.new_values || {}).length;
    return `Created with ${count} field${count !== 1 ? "s" : ""}`;
  }
  if (log.action === "UPDATE") {
    const changed = Object.keys(log.new_values || {});
    return `Updated ${changed.length} field${changed.length !== 1 ? "s" : ""}: ${changed.slice(0, 3).join(", ")}${changed.length > 3 ? "..." : ""}`;
  }
  if (log.action === "DELETE") {
    return "Deleted";
  }
  return log.action.toLowerCase().replace("_", " ");
};

onMounted(() => {
  fetchHistory();
});
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <div>
          <CardTitle>{{ title }}</CardTitle>
          <CardDescription>{{ description }}</CardDescription>
        </div>
        <Button variant="ghost" size="sm" @click="expanded = !expanded">
          {{ expanded ? "Collapse" : "Expand" }}
        </Button>
      </div>
    </CardHeader>
    <CardContent>
      <div
        v-if="loading"
        class="text-center py-4 text-sm text-muted-foreground"
      >
        Loading history...
      </div>
      <div
        v-else-if="logs.length === 0"
        class="text-center py-4 text-sm text-muted-foreground"
      >
        No history available
      </div>
      <div v-else class="space-y-4">
        <div class="relative">
          <div class="absolute left-3 top-0 bottom-0 w-0.5 bg-border" />
          <div class="space-y-4">
            <div
              v-for="(log, index) in expanded ? logs : logs.slice(0, 3)"
              :key="log.id"
              class="relative pl-8"
            >
              <div
                class="absolute left-0 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-background border"
                :class="getActionColor(log.action)"
              >
                {{ getActionIcon(log.action) }}
              </div>
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="font-medium text-sm">{{
                    log.user?.name || "System"
                  }}</span>
                  <span class="text-xs text-muted-foreground">
                    {{ formatDateTime(log.created_at) }}
                  </span>
                </div>
                <p class="text-sm text-muted-foreground">
                  {{ getChangesSummary(log) }}
                </p>
              </div>
            </div>
          </div>
        </div>
        <div v-if="!expanded && logs.length > 3" class="text-center">
          <Button variant="ghost" size="sm" @click="expanded = true">
            View {{ logs.length - 3 }} more entries
          </Button>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
