<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShieldCheck, Users, Settings2, Edit } from 'lucide-vue-next';
import { index as rolesIndex, edit as rolesEdit } from '@/actions/App/Http/Controllers/RoleController';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    roles: Array<any>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'User Management', href: '#' },
    { title: 'Roles & Permissions', href: rolesIndex().url },
];

const getRoleDescription = (name: string) => {
    const descriptions: Record<string, string> = {
        'System Admin': 'Akses penuh ke semua fitur sistem',
        'Admin Pusat': 'Mengelola seluruh data master dan aktivitas pusat',
        'Admin Unit': 'Mengelola operasional harian di unit pelaksana',
        'HR Pusat': 'Manajemen kepegawaian dan absensi pusat',
        'HR Unit': 'Manajemen kepegawaian spesifik unit',
        'Finance Pusat': 'Mengelola keuangan dan approval budget pusat',
        'Finance Unit': 'Mengelola pengeluaran dan kas kecil unit',
        'Project Manager': 'Mengelola project dan budget',
        'Site Manager': 'Memimpin proyek di lapangan',
        'Karyawan': 'Tenaga kerja lapangan (Self Only)',
    };
    return descriptions[name] || 'Custom system role';
};
</script>

<template>
    <Head title="Roles & Permissions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Roles & Permissions</h1>
                    <p class="text-zinc-500 mt-1">Manage system access levels and hierarchical privileges across organizations.</p>
                </div>
            </div>

            <!-- Roles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div 
                    v-for="role in roles" 
                    :key="role.id" 
                    class="group relative bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-900 transition-all p-6 flex flex-col"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <ShieldCheck class="h-6 w-6" />
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-xs font-semibold">
                            <Users class="h-3.5 w-3.5" />
                            {{ role.users_count || 0 }} Users
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ role.name }}</h3>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 flex-1 leading-relaxed">
                        {{ getRoleDescription(role.name) }}
                    </p>
                    
                    <div class="mt-6 pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                        <Button variant="ghost" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 -ml-2" as-child>
                            <Link :href="rolesEdit(role.id).url">
                                <Settings2 class="h-4 w-4 mr-2" />
                                Manage Permissions
                            </Link>
                        </Button>
                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>
