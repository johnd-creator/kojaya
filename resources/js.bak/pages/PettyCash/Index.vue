<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { Search, Plus, Edit2, Trash2, Wallet, CheckCircle2, XCircle, ArrowRight } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { index, destroy } from '@/actions/App/Http/Controllers/PettyCashAccountController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import CreateEditModal from './CreateEditModal.vue';

interface PettyCashAccount {
    id: string;
    organization_id: string;
    name: string;
    balance: number;
    limit: number;
    status: 'ACTIVE' | 'INACTIVE';
    description: string;
    organization: {
        id: string;
        name: string;
    };
}

interface Organization {
    id: string;
    name: string;
}

interface Props {
    accounts: PettyCashAccount[];
    organizations: Organization[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Finance', href: '#' },
    { title: 'Petty Cash', href: index().url },
];

const isModalOpen = ref(false);
const editingAccount = ref<PettyCashAccount | null>(null);
const search = ref('');

const filteredAccounts = computed(() => {
    if (search.value) {
        return props.accounts.filter(a => 
            a.name.toLowerCase().includes(search.value.toLowerCase()) || 
            a.organization.name.toLowerCase().includes(search.value.toLowerCase())
        );
    }
    return props.accounts;
});

const openCreateModal = () => {
    editingAccount.value = null;
    isModalOpen.value = true;
};

const openEditModal = (account: PettyCashAccount) => {
    editingAccount.value = account;
    isModalOpen.value = true;
};

const deleteAccount = (id: string, name: string) => {
    if (confirm(`Are you sure you want to delete ${name}?`)) {
        router.delete(destroy({ petty_cash: id }).url);
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => {
        editingAccount.value = null;
    }, 200);
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);
};
</script>

<template>
    <Head title="Petty Cash" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Petty Cash</h1>
                    <p class="text-zinc-500 mt-1">Manage petty cash accounts and transactions.</p>
                </div>
                
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input 
                            v-model="search" 
                            placeholder="Search accounts..." 
                            class="pl-9 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800"
                        />
                    </div>
                    
                    <Button @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0">
                        <Plus class="h-4 w-4 mr-2" />
                        New Account
                    </Button>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Account Name</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Organization</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider text-right">Balance</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider text-right">Limit</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Status</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-if="filteredAccounts.length === 0">
                                <td colspan="6" class="py-12 text-center text-zinc-500">
                                    <Wallet class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700 mb-3" />
                                    No petty cash accounts found.
                                </td>
                            </tr>
                            <tr 
                                v-for="account in filteredAccounts" 
                                :key="account.id" 
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group"
                            >
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center shrink-0">
                                            <Wallet class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                                        </div>
                                        <div>
                                            <Link :href="route('petty-cash.show', account.id)" class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                                {{ account.name }}
                                            </Link>
                                            <p class="text-xs text-zinc-500 mt-0.5 truncate max-w-[200px]">{{ account.description }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ account.organization.name }}
                                </td>
                                <td class="py-4 px-6 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ formatCurrency(account.balance) }}
                                </td>
                                <td class="py-4 px-6 text-right text-sm text-zinc-500">
                                    {{ formatCurrency(account.limit) }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-1.5 text-sm" 
                                         :class="account.status === 'ACTIVE' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400'">
                                        <CheckCircle2 v-if="account.status === 'ACTIVE'" class="h-4 w-4" />
                                        <XCircle v-else class="h-4 w-4" />
                                        {{ account.status }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Link :href="route('petty-cash.show', account.id)">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-zinc-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                                <ArrowRight class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button variant="ghost" size="icon" @click="openEditModal(account)" class="h-8 w-8 text-zinc-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                            <Edit2 class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" @click="deleteAccount(account.id, account.name)" class="h-8 w-8 text-zinc-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <CreateEditModal 
            :is-open="isModalOpen"
            :account="editingAccount"
            :organizations="organizations"
            @close="closeModal"
        />
    </AppLayout>
</template>
