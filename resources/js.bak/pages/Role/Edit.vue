<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { index as rolesIndex, update as rolesUpdate } from '@/actions/App/Http/Controllers/RoleController';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    role: any;
    permissions: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'User Management', href: '#' },
    { title: 'Roles & Permissions', href: rolesIndex().url },
    { title: `Edit ${props.role.name}`, href: '#' },
];

const form = useForm({
    permissions: props.role.permissions.map((p: any) => p.name) as string[],
});

// Group permissions by prefix (e.g., 'view_organization_unit' -> group 'organization')
const groupedPermissions = computed(() => {
    const groups: Record<string, any[]> = {};
    
    props.permissions.forEach(p => {
        // Simple heuristic: name is usually built like action_module_scope 
        // We split by '_', find the "module" noun. 
        // Let's assume the middle part or part after first underscore is the module name.
        // E.g., view_user_all -> user, create_employee -> employee.
        const parts = p.name.split('_');
        let moduleName = 'other';
        
        if (parts.length >= 2) {
            moduleName = parts[1];
        }

        if (!groups[moduleName]) {
            groups[moduleName] = [];
        }
        groups[moduleName].push(p);
    });
    
    return groups;
});

const submit = () => {
    form.put(rolesUpdate(props.role.id).url, {
        preserveScroll: true,
    });
};

const formatModuleName = (name: string) => {
    return name.charAt(0).toUpperCase() + name.slice(1);
};
</script>

<template>
    <Head :title="`Edit Role - ${role.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-xl sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div class="mb-6 border-b border-gray-200 pb-5 dark:border-gray-700">
                            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                                Permissions for: <span class="text-indigo-600 dark:text-indigo-400">{{ role.name }}</span>
                            </h2>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Check the boxes to assign data visibility and action capabilities to this role.
                            </p>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="space-y-8">
                                <div v-for="(perms, moduleName) in groupedPermissions" :key="moduleName" class="border rounded-lg p-5 dark:border-gray-700">
                                    <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4 capitalize dark:text-gray-100 dark:border-gray-700">
                                        {{ formatModuleName(moduleName) }} Module
                                    </h3>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div 
                                            v-for="permission in perms" 
                                            :key="permission.id" 
                                            class="flex items-start"
                                        >
                                            <div class="flex h-5 items-center">
                                                <input
                                                    :id="`permission-${permission.id}`"
                                                    v-model="form.permissions"
                                                    :value="permission.name"
                                                    type="checkbox"
                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-indigo-600"
                                                />
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label :for="`permission-${permission.id}`" class="font-medium text-gray-700 dark:text-gray-300">
                                                    {{ permission.name.replace(/_/g, ' ') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex items-center gap-4">
                                <Button :disabled="form.processing">Save Permissions</Button>
                                
                                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 dark:text-green-400">Saved.</p>
                                </Transition>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
