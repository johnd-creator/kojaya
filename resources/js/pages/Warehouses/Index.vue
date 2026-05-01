<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Search, Home, Package } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Warehouse {
    id: string;
    code: string;
    name: string;
    location: string | null;
    type: 'STORAGE' | 'REPAIR' | 'DISPOSAL';
    organization?: {
        id: string;
        name: string;
        code: string;
    };
}

interface Props {
    warehouses: Warehouse[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Storage', href: '#' },
    { title: 'Warehouses', href: '/warehouses' },
];

const search = ref('');
const typeFilter = ref('');
const organizationFilter = ref('');

const filteredWarehouses = () => {
    return props.warehouses.filter(warehouse => {
        const matchesSearch = !search.value || 
            warehouse.name.toLowerCase().includes(search.value.toLowerCase()) ||
            warehouse.code.toLowerCase().includes(search.value.toLowerCase()) ||
            warehouse.location?.toLowerCase().includes(search.value.toLowerCase());
        
        const matchesType = !typeFilter.value || warehouse.type === typeFilter.value;
        const matchesOrganization = !organizationFilter.value || warehouse.organization?.id === organizationFilter.value;

        return matchesSearch && matchesType && matchesOrganization;
    });
};

const getTypeColor = (type: string) => {
    const colors = {
        'STORAGE': 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400',
        'REPAIR': 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400',
        'DISPOSAL': 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[type as keyof typeof colors] || 'bg-zinc-50 text-zinc-700 border-zinc-200';
};

const getTypeBadge = (type: string) => {
    const badges = {
        'STORAGE': 'Storage',
        'REPAIR': 'Repair',
        'DISPOSAL': 'Disposal',
    };
    return badges[type as keyof typeof badges] || type;
};
</script>

<template>
    <Head title="Warehouses" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Warehouses</h1>
                    <p class="text-zinc-500 mt-1">Manage warehouses and storage locations.</p>
                </div>
                
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input 
                            v-model="search" 
                            placeholder="Search warehouses..." 
                            class="pl-9 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800"
                        />
                    </div>
                    
                    <Link href="/warehouses/create">
                        <Button class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0">
                            <Home class="h-4 w-4 mr-2" />
                            Add Warehouse
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select 
                    v-model="typeFilter"
                    class="px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300"
                >
                    <option value="">All Types</option>
                    <option value="STORAGE">Storage</option>
                    <option value="REPAIR">Repair</option>
                    <option value="DISPOSAL">Disposal</option>
                </select>

                <select 
                    v-model="organizationFilter"
                    class="px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300"
                >
                    <option value="">All Organizations</option>
                    <option v-for="org in [...new Set(warehouses.map(w => w.organization?.id).filter(Boolean))]" :key="org" :value="org">
                        {{ warehouses.find(w => w.organization?.id === org)?.organization?.name || org }}
                    </option>
                </select>
            </div>

            <!-- Warehouses Table -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Warehouse Code</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Warehouse Name</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Location</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Organization</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Type</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-if="filteredWarehouses().length === 0">
                                <td colspan="5" class="py-12 text-center text-zinc-500">
                                    <Home class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700 mb-3" />
                                    No warehouses found.
                                </td>
                            </tr>
                            <tr 
                                v-for="warehouse in filteredWarehouses()" 
                                :key="warehouse.id" 
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer"
                            >
                                <td class="py-4 px-6">
                                    <span class="font-mono text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ warehouse.code }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ warehouse.name }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ warehouse.location || '-' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ warehouse.organization?.name || '-' }}
                                        </span>
                                        <span v-if="warehouse.organization" class="text-xs text-zinc-500 mt-0.5">
                                            {{ warehouse.organization.code }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border"
                                         :class="getTypeColor(warehouse.type)">
                                        {{ getTypeBadge(warehouse.type) }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
