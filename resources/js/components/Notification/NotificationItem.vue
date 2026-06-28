<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import {
  AlertTriangle,
  AlertCircle,
  Activity,
  UserCheck,
  Clock,
  DollarSign,
  CreditCard,
  Briefcase,
  Bell,
  Check,
  X,
  Info,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import { markAsRead } from "@/api/notifications.ts";
import type { Notification } from "@/types";

type Props = {
  notification: Notification;
  clickable?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
  clickable: true,
});

const emit = defineEmits<{
  click: [notification: Notification];
  "mark-read": [id: string];
}>();

const loading = ref(false);

const iconMap = {
  "App\\Notifications\\ContractExpiring": {
    icon: AlertTriangle,
    colorClass: "text-yellow-600",
    bgClass: "bg-yellow-50 dark:bg-yellow-950",
  },
  "App\\Notifications\\SioExpiring": {
    icon: AlertCircle,
    colorClass: "text-red-600",
    bgClass: "bg-red-50 dark:bg-red-950",
  },
  "App\\Notifications\\McuDue": {
    icon: Activity,
    colorClass: "text-blue-600",
    bgClass: "bg-blue-50 dark:bg-blue-950",
  },
  "App\\Notifications\\LeaveApprovalRequired": {
    icon: UserCheck,
    colorClass: "text-green-600",
    bgClass: "bg-green-50 dark:bg-green-950",
  },
  "App\\Notifications\\OvertimeApprovalRequired": {
    icon: Clock,
    colorClass: "text-orange-600",
    bgClass: "bg-orange-50 dark:bg-orange-950",
  },
  "App\\Notifications\\PayrollApprovalRequired": {
    icon: DollarSign,
    colorClass: "text-green-600",
    bgClass: "bg-green-50 dark:bg-green-950",
  },
  "App\\Notifications\\InvoicePaymentReminder": {
    icon: CreditCard,
    colorClass: "text-red-600",
    bgClass: "bg-red-50 dark:bg-red-950",
  },
  "App\\Notifications\\WorkOrderAssigned": {
    icon: Briefcase,
    colorClass: "text-blue-600",
    bgClass: "bg-blue-50 dark:bg-blue-950",
  },
};

const iconData = computed(() => {
  const severityMap = {
    critical: {
      icon: AlertCircle,
      colorClass: "text-red-600",
      bgClass: "bg-red-50 dark:bg-red-950",
    },
    warning: {
      icon: AlertTriangle,
      colorClass: "text-yellow-600",
      bgClass: "bg-yellow-50 dark:bg-yellow-950",
    },
    success: {
      icon: Check,
      colorClass: "text-green-600",
      bgClass: "bg-green-50 dark:bg-green-950",
    },
    info: {
      icon: Info,
      colorClass: "text-blue-600",
      bgClass: "bg-blue-50 dark:bg-blue-950",
    },
  };

  if (props.notification.severity) {
    return (
      severityMap[props.notification.severity as keyof typeof severityMap] ||
      severityMap.info
    );
  }

  return (
    iconMap[props.notification.type as keyof typeof iconMap] || {
      icon: Bell,
      colorClass: "text-neutral-600",
      bgClass: "bg-neutral-50 dark:bg-neutral-800",
    }
  );
});

const IconComponent = computed(() => iconData.value.icon);

const title = computed(() => {
  return props.notification.title || props.notification.data.title || "Notifikasi";
});

const message = computed(() => {
  return props.notification.message || props.notification.data.message || "";
});

const timeAgo = computed(() => {
  const now = new Date();
  const created = new Date(props.notification.created_at);
  const diffInSeconds = Math.floor((now.getTime() - created.getTime()) / 1000);

  if (diffInSeconds < 60) {
    return "Just now";
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60);
    return `${minutes} ${minutes === 1 ? "minute" : "minutes"} ago`;
  } else if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600);
    return `${hours} ${hours === 1 ? "hour" : "hours"} ago`;
  } else if (diffInSeconds < 604800) {
    const days = Math.floor(diffInSeconds / 86400);
    return `${days} ${days === 1 ? "day" : "days"} ago`;
  } else {
    return created.toLocaleDateString();
  }
});

const handleClick = async () => {
  if (!props.clickable) return;

  emit("click", props.notification);

  if (!props.notification.is_read) {
    loading.value = true;
    try {
      await markAsRead(props.notification.id);
      emit("mark-read", props.notification.id);
    } catch (error) {
      console.error("Failed to mark notification as read:", error);
    } finally {
      loading.value = false;
    }
  }

  const actionUrl = props.notification.action?.url || props.notification.data.url;
  if (actionUrl) {
    router.visit(actionUrl);
  }
};
</script>

<template>
  <div
    class="relative flex items-start gap-3 p-3 transition-colors"
    :class="[
      clickable && !loading
        ? 'cursor-pointer hover:bg-neutral-100 dark:hover:bg-neutral-800'
        : '',
      notification.is_read ? 'opacity-70' : 'bg-white dark:bg-neutral-900',
    ]"
    @click="handleClick"
  >
    <div
      class="flex shrink-0 items-center justify-center rounded-full p-2"
      :class="[iconData.bgClass, iconData.colorClass]"
    >
      <span class="contents"><component :is="IconComponent" class="h-4 w-4" /></span>
    </div>

    <div class="min-w-0 flex-1">
      <div class="flex items-start justify-between gap-2">
        <p
          class="truncate text-sm font-medium"
          :class="[
            notification.is_read
              ? 'text-neutral-600 dark:text-neutral-400'
              : 'text-neutral-900 dark:text-neutral-100',
          ]"
        >
          {{ title }}
        </p>
        <span
          v-if="!notification.is_read"
          class="shrink-0 rounded-full bg-blue-500 p-1"
          title="Unread"
        >
          <span class="block h-1.5 w-1.5 rounded-full bg-white" />
        </span>
      </div>

      <p
        v-if="message"
        class="mt-0.5 line-clamp-2 text-xs text-neutral-600 dark:text-neutral-400"
      >
        {{ message }}
      </p>

      <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-500">
        {{ timeAgo }}
      </p>
    </div>

    <div
      v-if="loading"
      class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-neutral-900/50"
    >
      <div
        class="h-4 w-4 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-neutral-100"
      />
    </div>
  </div>
</template>
