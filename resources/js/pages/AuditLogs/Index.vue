<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuditLogList from '@/components/Audit/AuditLogList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audit Logs',
        href: '/audit-logs',
    },
];
</script>

<template>
    <Head title="Audit Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
            <AuditLogList />
        </div>
    </AppLayout>
</template>
