<script setup lang="ts">
import { computed } from "vue";
import { Badge } from "@/components/ui/badge";
import {
  CheckCircle2,
  XCircle,
  AlertCircle,
  Clock,
  FileText,
  Ban,
} from "lucide-vue-next";

interface Props {
  status: string;
  label?: string;
  variant?:
    | "default"
    | "secondary"
    | "destructive"
    | "outline"
    | "success"
    | "warning"
    | "info";
}

const props = defineProps<Props>();

const normalizedStatus = computed(() => props.status?.toUpperCase() || "");

const variant = computed(() => {
  if (props.variant) return props.variant;

  const status = normalizedStatus.value;

  if (
    [
      "APPROVED",
      "PAID",
      "COMPLETED",
      "ACTIVE",
      "PUBLISHED",
      "VERIFIED",
      "PRESENT",
    ].includes(status)
  ) {
    return "success";
  }

  if (
    [
      "REJECTED",
      "CANCELLED",
      "INACTIVE",
      "DENIED",
      "FAILED",
      "ERROR",
      "ABSENT",
      "OVERDUE",
      "EMERGENCY",
    ].includes(status)
  ) {
    return "destructive";
  }

  if (
    [
      "PENDING",
      "SUBMITTED",
      "DRAFT",
      "IN_PROGRESS",
      "REVIEWING",
      "PROCESSING",
      "WAITING",
      "ONGOING",
      "SICK",
      "HIGH",
    ].includes(status)
  ) {
    return "warning"; // or info depending on preference
  }

  if (["PLANNING", "PROCESSED", "OPEN", "LEAVE", "MEDIUM"].includes(status)) {
    return "info";
  }

  return "secondary";
});

const icon = computed(() => {
  const status = normalizedStatus.value;

  if (["APPROVED", "PAID", "COMPLETED", "VERIFIED", "PRESENT"].includes(status))
    return CheckCircle2;
  if (["REJECTED", "FAILED", "ERROR", "DENIED"].includes(status))
    return XCircle;
  if (["CANCELLED", "INACTIVE", "BAN", "ABSENT"].includes(status)) return Ban;
  if (["PENDING", "WAITING", "PROCESSING"].includes(status)) return Clock;
  if (["SUBMITTED", "IN_PROGRESS", "REVIEWING"].includes(status))
    return AlertCircle;
  if (["DRAFT"].includes(status)) return FileText;

  return null;
});

const variantClasses = computed(() => {
  switch (variant.value) {
    case "success":
      return "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30";
    case "warning":
      return "bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400 border-amber-200 dark:border-amber-500/30";
    case "info":
      return "bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400 border-blue-200 dark:border-blue-500/30";
    case "destructive":
      return "";
    default:
      return "";
  }
});

const badgeVariant = computed(() => {
  if (["success", "warning", "info"].includes(variant.value)) {
    return "outline";
  }
  return variant.value as any;
});
</script>

<template>
  <Badge
    :variant="badgeVariant"
    :class="['gap-1.5 px-2.5 py-0.5 transition-colors', variantClasses]"
  >
    <component :is="icon" v-if="icon" class="h-3.5 w-3.5" />
    {{ label || status }}
  </Badge>
</template>
