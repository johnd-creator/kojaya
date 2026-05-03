<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Search, Building2, User, Eye, Building } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { index as clientsIndex, create as clientCreate, destroy as clientDestroy, show as clientShow, edit as clientEdit } from '@/actions/App/Http/Controllers/ClientController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    clients: any;
    filters: any;
    stats: {
        total_clients: number;
        total_pln: number;
        total_private: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Operations', href: '#' },
    { title: 'Clients', href: clientsIndex().url },
];

const search = ref(props.filters.search || '');
const clientType = ref(props.filters.client_type || '');

let searchTimeout: ReturnType<typeof setTimeout>;

const clientTypeColors: Record<string, string> = {
    PLN: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    PRIVATE: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
};

const filteredClients = computed(() => {
    return props.clients.data;
});

const applyFilters = () => {
    router.get(clientsIndex().url, {
        search: search.value,
        client_type: clientType.value,
    }, { preserveState: true, replace: true });
};

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

watch(clientType, () => {
    applyFilters();
});

const clearFilters = () => {
    search.value = '';
    clientType.value = '';
    router.get(clientsIndex().url);
};

const deleteClient = (id: string, name: string) => {
    if (confirm(`Are you sure you want to delete client "${name}"?`)) {
        router.delete(clientDestroy(id).url);
    }
};
</script>

<template>
    <Head title="Clients" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6 w-full">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Client Management</h1>
                    <p class="text-zinc-500 mt-1">Manage project clients (PLN & Private companies)</p>
                </div>
                <div class="flex gap-3">
                    <Button as-child variant="default">
                        <Link :href="clientCreate().url">
                            <Plus class="h-4 w-4 mr-2" />
                            Add Client
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Clients -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Clients</p>
                        <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">{{ stats.total_clients }}</h2>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <Building class="h-6 w-6" />
                    </div>
                </div>

                <!-- PLN Clients -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">PLN Clients</p>
                        <h2 class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ stats.total_pln }}</h2>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 flex items-center justify-center">
                        <Building2 class="h-6 w-6" />
                    </div>
                </div>

                <!-- Private Clients -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Private Clients</p>
                        <h2 class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ stats.total_private }}</h2>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <User class="h-6 w-6" />
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 h-full items-end">
                    <div>
                        <label class="text-xs font-medium text-zinc-500 mb-1 block">Search Client</label>
                        <div class="relative">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-zinc-500" />
                            <Input 
                                v-model="search" 
                                placeholder="Name or Code..." 
                                class="pl-9 bg-zinc-50 dark:bg-zinc-800/50" 
                            />
                        </div>
                    </div>
                    
                    <div>
                        <label class="text-xs font-medium text-zinc-500 mb-1 block">Filter by Type</label>
                        <select 
                            v-model="clientType"
                            class="w-full h-10 px-3 rounded-md border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 text-sm"
                        >
                            <option value="">All Types</option>
                            <option value="PLN">PLN</option>
                            <option value="PRIVATE">Private</option>
                        </select>
                    </div>

                    <div v-if="search || clientType" class="flex items-end">
                        <Button 
                            @click="clearFilters" 
                            variant="outline" 
                            class="w-full"
                        >
                            Clear Filters
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Contact Person</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Organization</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        <tr v-if="filteredClients.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-zinc-500">
                                No clients found. Create your first client to get started.
                            </td>
                        </tr>
                        <tr v-for="client in filteredClients" :key="client.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                                {{ client.code }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <Building2 class="h-4 w-4 text-zinc-400" />
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ client.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="clientTypeColors[client.client_type]">
                                    {{ client.client_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                <div class="flex items-center gap-1">
                                    <User class="h-3 w-3 text-zinc-400" />
                                    {{ client.contact_person }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">{{ client.phone }}</td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ client.organization?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="clientShow({ client: client.id }).url">
                                        <Button size="sm" variant="ghost" title="View">
                                            <Eye class="h-3 w-3" />
                                        </Button>
                                    </Link>
                                    <Link :href="clientEdit({ client: client.id }).url">
                                        <Button size="sm" variant="ghost" title="Edit">
                                            <Pencil class="h-3 w-3" />
                                        </Button>
                                    </Link>
                                    <Button size="sm" variant="ghost" @click="deleteClient(client.id, client.name)" title="Delete">
                                        <Trash2 class="h-3 w-3 text-red-500" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="clients.links && clients.links.length > 3" class="flex items-center justify-between mt-4">
                <div class="text-sm text-zinc-500">
                    Showing {{ clients.meta?.from || 0 }} to {{ clients.meta?.to || 0 }} of {{ clients.meta?.total || 0 }} clients
                </div>
                <div class="flex gap-1">
                    <Link
                        v-for="(link, index) in clients.links"
                        :key="index"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1 border border-zinc-300 dark:border-zinc-700 rounded-md text-sm"
                        :class="{
                            'opacity-50 pointer-events-none': !link.url,
                            'bg-zinc-100 dark:bg-zinc-800': link.active
                        }"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
