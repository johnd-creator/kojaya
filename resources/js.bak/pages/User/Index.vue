<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Search, Plus, Edit2, Trash2, User as UserIcon, Building, ShieldCheck } from 'lucide-vue-next';
import { ref } from 'vue';
import { index as usersIndex, store as usersStore, update as usersUpdate, destroy as usersDestroy } from '@/actions/App/Http/Controllers/UserController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    users: any;
    roles: Array<any>;
    organizations: Array<any>;
    filters: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'User Management', href: '#' },
    { title: 'Users', href: usersIndex().url },
];

const search = ref(props.filters.search || '');

const handleSearch = () => {
    router.get(usersIndex().url, { search: search.value }, { preserveState: true, replace: true });
};

// Form handling
const isModalOpen = ref(false);
const editingUser = ref<any>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
    organization_id: '',
});

const openModal = (user: any = null) => {
    editingUser.value = user;
    if (user) {
        form.name = user.name;
        form.email = user.email;
        form.password = '';
        form.role = user.roles.length > 0 ? user.roles[0].name : '';
        form.organization_id = user.organization_id || '';
    } else {
        form.reset();
    }
    isModalOpen.value = true;
};

const submit = () => {
    if (editingUser.value) {
        form.put(usersUpdate({ id: editingUser.value.id }).url, {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(usersStore().url, {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

const deleteUser = (user: any) => {
    if (confirm(`Are you sure you want to delete ${user.name}?`)) {
        router.delete(usersDestroy({ id: user.id }).url);
    }
};
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Users</h1>
                    <p class="text-zinc-500 mt-1">Manage system administrators, employees, and their access scopes.</p>
                </div>
                
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input 
                            v-model="search" 
                            @input="handleSearch"
                            placeholder="Search users..." 
                            class="pl-9 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800"
                        />
                    </div>
                    
                    <Button @click="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0">
                        <Plus class="h-4 w-4 mr-2" />
                        Add User
                    </Button>
                </div>
            </div>

            <!-- Users Table/Grid -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">User</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Contact</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Organization</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider">Role</th>
                                <th class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold shrink-0 shadow-inner">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ user.name }}</p>
                                            <p class="text-xs text-zinc-500">Joined {{ new Date(user.created_at).toLocaleDateString() }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-zinc-600 dark:text-zinc-400">
                                    {{ user.email }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-sky-50 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 text-sm font-medium border border-sky-100 dark:border-sky-800/50">
                                        <Building class="h-3.5 w-3.5" />
                                        {{ user.organization ? user.organization.name : 'Unassigned' }}
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-sm font-medium border border-amber-100 dark:border-amber-800/50">
                                        <ShieldCheck class="h-3.5 w-3.5" />
                                        {{ user.roles.length > 0 ? user.roles[0].name : 'No Role' }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button variant="ghost" size="icon" @click="openModal(user)" class="text-zinc-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                                            <Edit2 class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" @click="deleteUser(user)" class="text-zinc-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="py-12 text-center text-zinc-500">
                                    No users found matching your criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Footer -->
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50 flex items-center justify-between" v-if="users.links && users.links.length > 3">
                    <div class="flex flex-wrap items-center gap-1">
                        <component 
                            :is="link.url ? 'a' : 'span'"
                            v-for="(link, k) in users.links" 
                            :key="k"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-md text-sm cursor-pointer"
                            :class="{
                                'bg-indigo-600 text-white font-medium': link.active,
                                'text-zinc-500 hover:bg-zinc-200 dark:hover:bg-zinc-800': !link.active && link.url,
                                'text-zinc-300 dark:text-zinc-700 cursor-not-allowed': !link.url
                            }"
                        />
                    </div>
                </div>
            </div>
            
        </div>

        <!-- Create/Edit Modal using plain accessible dialog approach since we might miss nested dialog components -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ editingUser ? 'Edit User' : 'Create New User' }}</DialogTitle>
                    <DialogDescription>
                        Fill in the details to {{ editingUser ? 'update' : 'create' }} a user account. Organization access will be restricted based on their role level.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="name">Full Name</Label>
                        <Input id="name" v-model="form.name" required />
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="email">Email Address</Label>
                        <Input id="email" type="email" v-model="form.email" required />
                    </div>

                    <div class="space-y-2" v-if="!editingUser">
                        <Label for="password">Password</Label>
                        <Input id="password" type="password" v-model="form.password" required />
                    </div>
                    <div class="space-y-2" v-else>
                        <Label for="password_edit">New Password (optional)</Label>
                        <Input id="password_edit" type="password" v-model="form.password" placeholder="Leave blank to keep current" />
                    </div>
                    
                    <div class="space-y-2">
                        <Label>System Role</Label>
                        <select v-model="form.role" class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:focus-visible:ring-zinc-300" required>
                            <option value="" disabled>Select a PRD Role</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label>Organization Unit (Cabang)</Label>
                        <select v-model="form.organization_id" class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:focus-visible:ring-zinc-300" required>
                            <option value="" disabled>Select an Organization Level</option>
                            <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.name }} ({{ org.code }})</option>
                        </select>
                    </div>

                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="isModalOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {{ editingUser ? 'Save Changes' : 'Create User' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
