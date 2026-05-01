<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="p-6 bg-white border rounded-xl shadow-sm dark:bg-zinc-900 dark:border-zinc-800">
                    <h3 class="text-sm font-medium text-zinc-500 uppercase">Total Users</h3>
                    <p class="mt-2 text-3xl font-bold">1</p>
                </div>
                <div class="p-6 bg-white border rounded-xl shadow-sm dark:bg-zinc-900 dark:border-zinc-800">
                    <h3 class="text-sm font-medium text-zinc-500 uppercase">Active Roles</h3>
                    <p class="mt-2 text-3xl font-bold">3</p>
                </div>
                <div class="p-6 bg-white border rounded-xl shadow-sm dark:bg-zinc-900 dark:border-zinc-800">
                    <h3 class="text-sm font-medium text-zinc-500 uppercase">System Status</h3>
                    <p class="mt-2 text-3xl font-bold text-green-500">Online</p>
                </div>
            </div>
            <div class="p-8 bg-white border rounded-xl shadow-sm dark:bg-zinc-900 dark:border-zinc-800">
                <h1 class="text-2xl font-bold mb-4">Selamat Datang di Sistem ERP Perusahaan</h1>
                <p class="text-zinc-600 dark:text-zinc-400">
                    Ini adalah dashboard utama Anda. Gunakan menu di samping untuk mengelola pengguna, peran, dan modul perusahaan lainnya.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
