<script setup lang="ts">
import { Bell, Check, Loader2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { fetchNotifications, markAsRead, markAllAsRead } from '@/api/notifications.ts';
import NotificationItem from '@/components/Notification/NotificationItem.vue';
import { Button } from '@/components/ui/button';
import type { Notification, PaginatedNotifications, NotificationFilter } from '@/types';

type Props = {
    initialFilter?: NotificationFilter;
    perPage?: number;
};

const props = withDefaults(defineProps<Props>(), {
    initialFilter: 'all',
    perPage: 20,
});

const notifications = ref<Notification[]>([]);
const meta = ref<PaginatedNotifications['meta'] | null>(null);
const loading = ref(false);
const loadingMore = ref(false);
const markingAllAsRead = ref(false);
const currentFilter = ref<NotificationFilter>(props.initialFilter);
const currentPage = ref(1);

const hasNotifications = computed(() => notifications.value.length > 0);
const hasMorePages = computed(() => meta.value && currentPage.value < meta.value.last_page);

const fetchNotificationsList = async (page = 1, append = false) => {
    if (append) {
        loadingMore.value = true;
    } else {
        loading.value = true;
    }

    try {
        const response = await fetchNotifications({
            page,
            per_page: props.perPage,
        });

        if (append) {
            notifications.value = [...notifications.value, ...response.data];
        } else {
            notifications.value = response.data;
        }

        meta.value = response.meta;
        currentPage.value = response.meta.current_page;
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

const loadMore = () => {
    if (hasMorePages.value && !loadingMore.value) {
        fetchNotificationsList(currentPage.value + 1, true);
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
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    } finally {
        markingAllAsRead.value = false;
    }
};

const setFilter = (filter: NotificationFilter) => {
    if (currentFilter.value !== filter) {
        currentFilter.value = filter;
        currentPage.value = 1;
        fetchNotificationsList(1, false);
    }
};

const filteredNotifications = computed(() => {
    if (currentFilter.value === 'all') {
        return notifications.value;
    } else if (currentFilter.value === 'unread') {
        return notifications.value.filter((n) => !n.is_read);
    } else {
        return notifications.value.filter((n) => n.is_read);
    }
});

const hasUnread = computed(() => {
    return notifications.value.some((n) => !n.is_read);
});

watch(currentFilter, () => {
    currentPage.value = 1;
    fetchNotificationsList(1, false);
});

onMounted(() => {
    fetchNotificationsList();
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex gap-1 rounded-lg border border-neutral-200 p-1 dark:border-neutral-800">
                <button
                    :class="[
                        'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                        currentFilter === 'all'
                            ? 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100'
                            : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800/50 dark:hover:text-neutral-100',
                    ]"
                    @click="setFilter('all')"
                >
                    All
                </button>
                <button
                    :class="[
                        'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                        currentFilter === 'unread'
                            ? 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100'
                            : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800/50 dark:hover:text-neutral-100',
                    ]"
                    @click="setFilter('unread')"
                >
                    Unread
                </button>
                <button
                    :class="[
                        'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                        currentFilter === 'read'
                            ? 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100'
                            : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800/50 dark:hover:text-neutral-100',
                    ]"
                    @click="setFilter('read')"
                >
                    Read
                </button>
            </div>

            <Button
                v-if="hasUnread"
                variant="outline"
                size="sm"
                :disabled="markingAllAsRead"
                @click="handleMarkAllAsRead"
            >
                <Loader2 v-if="markingAllAsRead" class="mr-2 h-4 w-4 animate-spin" />
                <Check v-else class="mr-2 h-4 w-4" />
                Mark all as read
            </Button>
        </div>

        <div
            v-if="loading"
            class="flex items-center justify-center py-12"
        >
            <Loader2 class="h-8 w-8 animate-spin text-neutral-400" />
        </div>

        <div v-else-if="!hasNotifications" class="py-12 text-center">
            <Bell class="mx-auto mb-4 h-16 w-16 text-neutral-300 dark:text-neutral-700" />
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                No notifications
            </h3>
            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                {{ currentFilter === 'all' ? 'You don\'t have any notifications yet.' : `No ${currentFilter} notifications.` }}
            </p>
        </div>

        <div v-else class="space-y-1">
            <div
                v-for="notification in filteredNotifications"
                :key="notification.id"
                class="overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
            >
                <NotificationItem
                    :notification="notification"
                    :clickable="true"
                    @mark-read="handleMarkAsRead"
                />
            </div>
        </div>

        <div
            v-if="hasMorePages && !loading"
            class="flex justify-center pt-4"
        >
            <Button
                variant="outline"
                :disabled="loadingMore"
                @click="loadMore"
            >
                <Loader2 v-if="loadingMore" class="mr-2 h-4 w-4 animate-spin" />
                Load more
            </Button>
        </div>
    </div>
</template>
