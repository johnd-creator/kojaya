<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/formatters';

defineProps<{
    notifications: { data: Array<{ id: string; type: string; read_at?: string | null; created_at: string; data?: { title?: string; message?: string } | null }> };
}>();
</script>

<template>
    <Head title="Notifikasi Saya" />
    <AppLayout :breadcrumbs="[{ title: 'Kojayaku', href: '/member' }, { title: 'Notifikasi', href: '/member/notifications' }]">
        <PageContainer>
            <div class="space-y-4">
                <div v-for="notification in notifications.data" :key="notification.id" class="rounded-lg border p-4">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="font-semibold">{{ notification.data?.title || notification.type }}</div>
                            <div class="text-sm text-muted-foreground">{{ notification.data?.message || 'Notifikasi sistem tersedia untuk akun Anda.' }}</div>
                        </div>
                        <div class="text-sm text-muted-foreground">{{ formatDateTime(notification.created_at) }}</div>
                    </div>
                    <div class="mt-2 text-xs" :class="notification.read_at ? 'text-muted-foreground' : 'text-primary font-medium'">
                        {{ notification.read_at ? 'Sudah dibaca' : 'Belum dibaca' }}
                    </div>
                </div>
                <div v-if="notifications.data.length === 0" class="rounded-lg border p-10 text-center text-muted-foreground">Belum ada notifikasi.</div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
