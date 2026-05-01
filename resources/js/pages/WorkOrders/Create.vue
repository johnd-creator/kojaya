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

interface Asset {
    id: string;
    code: string;
    name: string;
    organization?: {
        id: string;
        name: string;
    };
}

interface Organization {
    id: string;
    name: string;
    code: string;
}

interface User {
    id: string;
    name: string;
    email: string;
}

interface Props {
    assets: Asset[];
    organizations: Organization[];
    users: User[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Asset Management', href: '#' },
    { title: 'Work Orders', href: '/work-orders' },
    { title: 'Create Work Order', href: '/work-orders/create' },
];

const form = useForm({
    asset_id: '',
    organization_id: '',
    type: 'CORRECTIVE',
    priority: 'MEDIUM',
    description: '',
    assigned_to: '',
});

const submit = () => {
    form.post('/work-orders', {
        preserveScroll: true,
    });
};

const getOrganizationFromAsset = (assetId: string) => {
    const asset = props.assets.find(a => a.id === assetId);
    return asset?.organization?.id || '';
};

const onAssetChange = () => {
    const orgId = getOrganizationFromAsset(form.asset_id);
    if (orgId) {
        form.organization_id = orgId;
    }
};
</script>

<template>
    <Head title="Create Work Order" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-4xl mx-auto w-full">
            
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/work-orders">
                    <Button variant="ghost" size="icon" class="h-8 w-8">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Create Work Order</h1>
                    <p class="text-zinc-500 mt-1">Create a new maintenance work order</p>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Asset & Organization -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <Label for="asset_id">Asset *</Label>
                            <select
                                id="asset_id"
                                v-model="form.asset_id"
                                @change="onAssetChange"
                                :disabled="form.processing"
                                class="flex h-10 w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-red-500': form.errors.asset_id }"
                            >
                                <option value="">Select Asset</option>
                                <option v-for="asset in assets" :key="asset.id" :value="asset.id">
                                    {{ asset.code }} - {{ asset.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.asset_id" class="text-sm text-red-500">{{ form.errors.asset_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="organization_id">Organization *</Label>
                            <select
                                id="organization_id"
                                v-model="form.organization_id"
                                :disabled="form.processing"
                                class="flex h-10 w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-red-500': form.errors.organization_id }"
                            >
                                <option value="">Select Organization</option>
                                <option v-for="org in organizations" :key="org.id" :value="org.id">
                                    {{ org.name }} ({{ org.code }})
                                </option>
                            </select>
                            <p v-if="form.errors.organization_id" class="text-sm text-red-500">{{ form.errors.organization_id }}</p>
                        </div>
                    </div>

                    <!-- Type & Priority -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <Label for="type">Type *</Label>
                            <select
                                id="type"
                                v-model="form.type"
                                :disabled="form.processing"
                                class="flex h-10 w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-red-500': form.errors.type }"
                            >
                                <option value="PREVENTIVE">Preventive</option>
                                <option value="CORRECTIVE">Corrective</option>
                            </select>
                            <p v-if="form.errors.type" class="text-sm text-red-500">{{ form.errors.type }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="priority">Priority *</Label>
                            <select
                                id="priority"
                                v-model="form.priority"
                                :disabled="form.processing"
                                class="flex h-10 w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-red-500': form.errors.priority }"
                            >
                                <option value="LOW">Low</option>
                                <option value="MEDIUM">Medium</option>
                                <option value="HIGH">High</option>
                                <option value="EMERGENCY">Emergency</option>
                            </select>
                            <p v-if="form.errors.priority" class="text-sm text-red-500">{{ form.errors.priority }}</p>
                        </div>
                    </div>

                    <!-- Assigned To -->
                    <div class="space-y-2">
                        <Label for="assigned_to">Assigned To</Label>
                        <select
                            id="assigned_to"
                            v-model="form.assigned_to"
                            :disabled="form.processing"
                            class="flex h-10 w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="{ 'border-red-500': form.errors.assigned_to }"
                        >
                            <option value="">Unassigned</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }} ({{ user.email }})
                            </option>
                        </select>
                        <p v-if="form.errors.assigned_to" class="text-sm text-red-500">{{ form.errors.assigned_to }}</p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            placeholder="Describe the work to be done..."
                            :disabled="form.processing"
                            class="flex min-h-[80px] w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm ring-offset-white placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="{ 'border-red-500': form.errors.description }"
                        ></textarea>
                        <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3 pt-4">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white"
                        >
                            <Save class="h-4 w-4 mr-2" />
                            {{ form.processing ? 'Creating...' : 'Create Work Order' }}
                        </Button>
                        <Link href="/work-orders">
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
