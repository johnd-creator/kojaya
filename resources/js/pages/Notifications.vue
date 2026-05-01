<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import NotificationList from '@/components/Notification/NotificationList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: '/',
    },
    {
        title: 'Notifications',
        href: '/notifications',
    },
];
</script>

<template>
    <Head title="Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                        Notifications
                    </h1>
                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                        View and manage all your notifications in one place.
                    </p>
                </div>

                <NotificationList :per-page="20" />
            </div>
        </div>
    </AppLayout>
</template>
