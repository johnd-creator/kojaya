<script setup lang="ts">
import { computed } from "vue";
import type { AuditLog } from "@/Actions/auditLogs";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { formatDateTime } from "@/lib/formatters";

interface Props {
  log: AuditLog | null;
  open: boolean;
}

interface Emits {
  (e: "update:open", value: boolean): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit("update:open", value),
});

const formatJson = (data: Record<string, unknown> | null): string => {
  if (!data) return "No data";
  return JSON.stringify(data, null, 2);
};

const getActionBadgeColor = (action: string): string => {
  const colors: Record<string, string> = {
    CREATE: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
    UPDATE: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    DELETE: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
    LOGIN:
      "bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200",
    LOGOUT: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200",
    FAILED_LOGIN:
      "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200",
  };
  return colors[action] || "bg-gray-100 text-gray-800";
};
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Audit Log Details</DialogTitle>
        <DialogDescription>
          View detailed information about this activity
        </DialogDescription>
      </DialogHeader>

      <div v-if="log" class="space-y-6">
        <div class="flex items-center gap-4">
          <span
            class="px-3 py-1 rounded-full text-sm font-medium"
            :class="getActionBadgeColor(log.action)"
          >
            {{ log.action }}
          </span>
          <span class="text-sm text-muted-foreground">
            {{ log.module }}
          </span>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="space-y-1">
            <p class="text-sm font-medium text-muted-foreground">User</p>
            <p class="text-sm">
              {{ log.user ? `${log.user.name} (${log.user.email})` : "System" }}
            </p>
          </div>

          <div class="space-y-1">
            <p class="text-sm font-medium text-muted-foreground">Timestamp</p>
            <p class="text-sm">{{ formatDateTime(log.created_at) }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-sm font-medium text-muted-foreground">IP Address</p>
            <p class="text-sm font-mono">{{ log.ip_address || "N/A" }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-sm font-medium text-muted-foreground">Subject</p>
            <p class="text-sm">
              {{
                log.subject_type
                  ? `${log.subject_type} #${log.subject_id}`
                  : "N/A"
              }}
            </p>
          </div>
        </div>

        <div v-if="log.old_values" class="space-y-2">
          <h4 class="text-sm font-semibold">Old Values</h4>
          <pre class="rounded-lg bg-muted p-4 text-xs overflow-x-auto">{{
            formatJson(log.old_values)
          }}</pre>
        </div>

        <div v-if="log.new_values" class="space-y-2">
          <h4 class="text-sm font-semibold">New Values</h4>
          <pre class="rounded-lg bg-muted p-4 text-xs overflow-x-auto">{{
            formatJson(log.new_values)
          }}</pre>
        </div>

        <div class="space-y-1">
          <p class="text-sm font-medium text-muted-foreground">User Agent</p>
          <p class="text-xs break-all">{{ log.user_agent || "N/A" }}</p>
        </div>
      </div>

      <div class="flex justify-end">
        <Button variant="outline" @click="isOpen = false">Close</Button>
      </div>
    </DialogContent>
  </Dialog>
</template>
