<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: string;
    name: string;
    code: string;
}

interface Props {
    organizations: Organization[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Storage', href: '#' },
    { title: 'Warehouses', href: '/warehouses' },
    { title: 'Create Warehouse', href: '/warehouses/create' },
];

const form = useForm({
    code: '',
    name: '',
    organization_id: '',
    location: '',
    type: 'STORAGE',
});

const submit = () => {
    form.post('/warehouses', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create Warehouse" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-4xl mx-auto w-full">
            
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/warehouses">
                    <Button variant="ghost" size="icon" class="h-8 w-8">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Create Warehouse</h1>
                    <p class="text-zinc-500 mt-1">Add a new warehouse or storage location</p>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Code & Type -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <Label for="code">Warehouse Code *</Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="WH-001"
                                :disabled="form.processing"
                                :class="{ 'border-red-500': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="text-sm text-red-500">{{ form.errors.code }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="type">Type *</Label>
                            <select
                                id="type"
                                v-model="form.type"
                                :disabled="form.processing"
                                class="flex h-10 w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-red-500': form.errors.type }"
                            >
                                <option value="STORAGE">Storage</option>
                                <option value="REPAIR">Repair</option>
                                <option value="DISPOSAL">Disposal</option>
                            </select>
                            <p v-if="form.errors.type" class="text-sm text-red-500">{{ form.errors.type }}</p>
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="space-y-2">
                        <Label for="name">Warehouse Name *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Main Storage Warehouse"
                            :disabled="form.processing"
                            :class="{ 'border-red-500': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <!-- Location -->
                    <div class="space-y-2">
                        <Label for="location">Location</Label>
                        <Input
                            id="location"
                            v-model="form.location"
                            placeholder="Building A, Floor 1, Jakarta"
                            :disabled="form.processing"
                            :class="{ 'border-red-500': form.errors.location }"
                        />
                        <p v-if="form.errors.location" class="text-sm text-red-500">{{ form.errors.location }}</p>
                    </div>

                    <!-- Organization -->
                    <div class="space-y-2">
                        <Label for="organization_id">Organization *</Label>
                        <select
                            id="organization_id"
                            v-model="form.organization_id"
                            :disabled="form.processing"
                            class="flex h-10 w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="{ 'border-red-500': form.errors.organization_id }"
                            required
                        >
                            <option value="">Select Organization</option>
                            <option v-for="org in organizations" :key="org.id" :value="org.id">
                                {{ org.name }} ({{ org.code }})
                            </option>
                        </select>
                        <p v-if="form.errors.organization_id" class="text-sm text-red-500">{{ form.errors.organization_id }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3 pt-4">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white"
                        >
                            <Save class="h-4 w-4 mr-2" />
                            {{ form.processing ? 'Creating...' : 'Create Warehouse' }}
                        </Button>
                        <Link href="/warehouses">
                            <Button type="button" variant="outline" :disabled="form.processing">
                                Cancel
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
