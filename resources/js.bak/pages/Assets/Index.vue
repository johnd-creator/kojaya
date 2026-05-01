<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Search, Settings, Wrench } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Asset {
    id: string;
    code: string;
    name: string;
    category: string;
    organization_id: string;
    status: 'ACTIVE' | 'INACTIVE' | 'UNDER_MAINTENANCE';
    purchase_date: string | null;
    serial_number: string | null;
    organization?: {
        id: string;
        name: string;
        code: string;
    };
}

interface Props {
    assets: Asset[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Asset Management', href: '#' },
    { title: 'Assets', href: '/assets' },
];

const search = ref('');
const statusFilter = ref('');
const organizationFilter = ref('');

const filteredAssets = () => {
    return props.assets.filter(asset => {
        const matchesSearch = !search.value || 
            asset.name.toLowerCase().includes(search.value.toLowerCase()) ||
            asset.code.toLowerCase().includes(search.value.toLowerCase()) ||
            asset.serial_number?.toLowerCase().includes(search.value.toLowerCase());
        
        const matchesStatus = !statusFilter.value || asset.status === statusFilter.value;
        const matchesOrganization = !organizationFilter.value || asset.organization_id === organizationFilter.value;

        return matchesSearch && matchesStatus && matchesOrganization;
    });
};

const getStatusColor = (status: string) => {
    const colors = {
        'ACTIVE': 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400',
        'INACTIVE': 'bg-zinc-50 text-zinc-700 border-zinc-200 dark:bg-zinc-900/30 dark:text-zinc-400',
        'UNDER_MAINTENANCE': 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400',
    };
    return colors[status as keyof typeof colors] || 'bg-zinc-50 text-zinc-700 border-zinc-200';
};

const getStatusBadge = (status: string) => {
    const badges = {
        'ACTIVE': 'Active',
        'INACTIVE': 'Inactive',
        'UNDER_MAINTENANCE': 'Under Maintenance',
    };
    return badges[status as keyof typeof badges] || status;
};
</script>

<template>
    <Head title="Assets" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Assets</h1>
                    <p class="text-zinc-500 mt-1">Manage and track enterprise assets across all organizational units.</p>
                </div>
                
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input 
                            v-model="search" 
                            placeholder="Search assets..." 
                            class="pl-9 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800"
                        />
                    </div>
                    
                    <Link href="/assets/create">
                        <Button class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0">
                            <Settings class="h-4 w-4 mr-2" />
                            Add Asset
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select 
                    v-model="statusFilter"
                    class="px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300"
                >
                    <option value="">All Statuses</option>
                    <option value="ACTIVE">Active</option>
                    <option value="INACTIVE">Inactive</option>
                    <option value="UNDER_MAINTENANCE">Under Maintenance</option>
                </select>

                <select 
                    v-model="organizationFilter"
                    class="px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300"
                >
                    <option value="">All Units</option>
                    <option v-for="org in [...new Set(assets.map(a => a.organization_id))]" :key="org" :value="org">
                        {{ assets.find(a => a.organization_id === org)?.organization?.name || org }}
                    </option>
                </select>
            </div>

            <!-- Assets Table -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Asset Code</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Asset Name</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Category</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Organization</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-if="filteredAssets().length === 0">
                                <td colspan="5" class="py-12 text-center text-zinc-500">
                                    <Wrench class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700 mb-3" />
                                    No assets found.
                                </td>
                            </tr>
                            <tr 
                                v-for="asset in filteredAssets()" 
                                :key="asset.id" 
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer"
                            >
                                <td class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="font-mono text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                            {{ asset.code }}
                                        </span>
                                        <span v-if="asset.serial_number" class="text-xs text-zinc-500 mt-0.5">
                                            SN: {{ asset.serial_number }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ asset.name }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ asset.category }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ asset.organization?.name || '-' }}
                                        </span>
                                        <span v-if="asset.organization" class="text-xs text-zinc-500 mt-0.5">
                                            {{ asset.organization.code }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border"
                                         :class="getStatusColor(asset.status)">
                                        {{ getStatusBadge(asset.status) }}
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
