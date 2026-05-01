<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Search, Package, AlertTriangle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Stock {
    id: string;
    warehouse: {
        id: string;
        name: string;
        code: string;
    };
    quantity: number;
    reserved_quantity: number;
}

interface SparePart {
    id: string;
    code: string;
    name: string;
    specification: string | null;
    unit: string;
    category: string | null;
    min_stock: number;
    max_stock: number;
    reorder_level: number;
    total_stock: number;
    available_stock: number;
    is_below_min: boolean;
    is_below_reorder: boolean;
    organization?: {
        id: string;
        name: string;
        code: string;
    };
    stocks: Stock[];
}

interface Props {
    spareParts: SparePart[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Storage', href: '#' },
    { title: 'Spare Parts', href: '/spare-parts' },
];

const search = ref('');
const categoryFilter = ref('');
const lowStockOnly = ref(false);

const categories = computed(() => {
    return [...new Set(props.spareParts.map((p) => p.category).filter((c): c is string => typeof c === 'string' && c.length > 0))];
});

const filteredSpareParts = () => {
    return props.spareParts.filter(part => {
        const matchesSearch = !search.value || 
            part.name.toLowerCase().includes(search.value.toLowerCase()) ||
            part.code.toLowerCase().includes(search.value.toLowerCase()) ||
            part.specification?.toLowerCase().includes(search.value.toLowerCase());
        
        const matchesCategory = !categoryFilter.value || part.category === categoryFilter.value;
        const matchesLowStock = !lowStockOnly.value || part.is_below_min;

        return matchesSearch && matchesCategory && matchesLowStock;
    });
};

const getStockColor = (part: SparePart) => {
    if (part.is_below_min) {
        return 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400';
    }
    if (part.is_below_reorder) {
        return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400';
    }
    return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400';
};

const getStockBadge = (part: SparePart) => {
    if (part.is_below_min) {
        return 'Low Stock';
    }
    if (part.is_below_reorder) {
        return 'Reorder';
    }
    return 'OK';
};
</script>

<template>
    <Head title="Spare Parts" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Spare Parts</h1>
                    <p class="text-zinc-500 mt-1">Manage spare parts inventory across all warehouses.</p>
                </div>
                
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input 
                            v-model="search" 
                            placeholder="Search parts..." 
                            class="pl-9 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800"
                        />
                    </div>
                    
                    <Link href="/spare-parts/create">
                        <Button class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0">
                            <Package class="h-4 w-4 mr-2" />
                            Add Part
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select 
                    v-model="categoryFilter"
                    class="px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300"
                >
                    <option value="">All Categories</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">
                        {{ cat }}
                    </option>
                </select>

                <label class="flex items-center gap-2 px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 cursor-pointer">
                    <input type="checkbox" v-model="lowStockOnly" class="rounded border-zinc-300" />
                    <span>Low Stock Only</span>
                </label>
            </div>

            <!-- Spare Parts Table -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Part Code</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Part Name</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Category</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Stock</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Unit</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-if="filteredSpareParts().length === 0">
                                <td colspan="6" class="py-12 text-center text-zinc-500">
                                    <Package class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700 mb-3" />
                                    No spare parts found.
                                </td>
                            </tr>
                            <tr 
                                v-for="part in filteredSpareParts()" 
                                :key="part.id" 
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer"
                            >
                                <td class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="font-mono text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                            {{ part.code }}
                                        </span>
                                        <span v-if="part.specification" class="text-xs text-zinc-500 mt-0.5">
                                            {{ part.specification }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ part.name }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ part.category || '-' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ part.available_stock }} / {{ part.max_stock }}
                                        </span>
                                        <span class="text-xs text-zinc-500 mt-0.5">
                                            Min: {{ part.min_stock }} | Reorder: {{ part.reorder_level }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ part.unit }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border"
                                             :class="getStockColor(part)">
                                            {{ getStockBadge(part) }}
                                        </div>
                                        <AlertTriangle v-if="part.is_below_min" class="h-4 w-4 text-red-500" />
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
