<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Bell, Check, X, Loader2 } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { fetchNotifications, markAsRead, markAllAsRead, getUnreadCount, navigateToNotifications } from '@/api/notifications.ts';
import NotificationItem from '@/components/Notification/NotificationItem.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Separator,
} from '@/components/ui/separator';
import type { Notification } from '@/types';

type Props = {
    maxItems?: number;
};

const props = withDefaults(defineProps<Props>(), {
    maxItems: 5,
});

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);
const markingAllAsRead = ref(false);
const pollingInterval = ref<number | null>(null);

const hasNotifications = computed(() => notifications.value.length > 0);
const hasUnread = computed(() => unreadCount.value > 0);

const fetchUnreadCount = async () => {
    try {
        const response = await getUnreadCount();
        unreadCount.value = response.count;
    } catch (error) {
        console.error('Failed to fetch unread count:', error);
    }
};

const fetchRecentNotifications = async () => {
    loading.value = true;
    try {
        const response = await fetchNotifications({
            per_page: props.maxItems,
        });
        notifications.value = response.data;
        await fetchUnreadCount();
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    } finally {
        loading.value = false;
    }
};

const handleMarkAsRead = async (id: string) => {
    try {
        await markAsRead(id);
        const notification = notifications.value.find((n) => n.id === id);
        if (notification) {
            notification.is_read = true;
            notification.read_at = new Date().toISOString();
        }
        await fetchUnreadCount();
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

const handleMarkAllAsRead = async () => {
    markingAllAsRead.value = true;
    try {
        await markAllAsRead();
        notifications.value.forEach((n) => {
            n.is_read = true;
            n.read_at = new Date().toISOString();
        });
        await fetchUnreadCount();
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    } finally {
        markingAllAsRead.value = false;
    }
};

const startPolling = () => {
    pollingInterval.value = window.setInterval(() => {
        fetchUnreadCount();
    }, 30000);
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
};

onMounted(() => {
    fetchRecentNotifications();
    startPolling();
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button
                variant="ghost"
                size="icon"
                class="group relative h-9 w-9"
            >
                <Bell
                    class="h-5 w-5 transition-all"
                    :class="[
                        hasUnread
                            ? 'text-neutral-900 dark:text-neutral-100'
                            : 'text-neutral-500 group-hover:text-neutral-900 dark:group-hover:text-neutral-100',
                    ]"
                />
                <span
                    v-if="hasUnread"
                    class="absolute -right-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
                <span class="sr-only">Notifications</span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-80 p-0">
            <div class="flex items-center justify-between border-b border-neutral-200 px-3 py-2.5 dark:border-neutral-800">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                    Notifications
                </h3>
                <div class="flex items-center gap-1">
                    <button
                        v-if="hasUnread"
                        :disabled="markingAllAsRead"
                        class="rounded-md p-1 text-xs text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 disabled:opacity-50 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100"
                        title="Mark all as read"
                        @click="handleMarkAllAsRead"
                    >
                        <Loader2 v-if="markingAllAsRead" class="h-3.5 w-3.5 animate-spin" />
                        <Check v-else class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>

            <div class="max-h-80 overflow-y-auto">
                <div v-if="loading" class="flex items-center justify-center py-8">
                    <Loader2 class="h-6 w-6 animate-spin text-neutral-400" />
                </div>

                <div v-else-if="!hasNotifications" class="py-8 text-center">
                    <Bell class="mx-auto mb-2 h-10 w-10 text-neutral-300 dark:text-neutral-700" />
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">
                        No notifications yet
                    </p>
                </div>

                <div v-else>
                    <NotificationItem
                        v-for="notification in notifications"
                        :key="notification.id"
                        :notification="notification"
                        :clickable="true"
                        @mark-read="handleMarkAsRead"
                    />
                </div>
            </div>

            <Separator v-if="hasNotifications" />

            <div v-if="hasNotifications" class="border-t border-neutral-200 p-2 dark:border-neutral-800">
                <Link
                    :href="'/notifications'"
                    class="block rounded-md px-3 py-2 text-center text-sm font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100"
                >
                    View all notifications
                </Link>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
