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
    { title: 'Spare Parts', href: '/spare-parts' },
    { title: 'Create Spare Part', href: '/spare-parts/create' },
];

const form = useForm({
    code: '',
    name: '',
    specification: '',
    unit: '',
    min_stock: 0,
    max_stock: 100,
    reorder_level: 10,
    category: '',
    organization_id: '',
});

const submit = () => {
    form.post('/spare-parts', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create Spare Part" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-4xl mx-auto w-full">
            
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/spare-parts">
                    <Button variant="ghost" size="icon" class="h-8 w-8">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Create Spare Part</h1>
                    <p class="text-zinc-500 mt-1">Add a new spare part to inventory</p>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Code -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <Label for="code">Part Code *</Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="SP-001"
                                :disabled="form.processing"
                                :class="{ 'border-red-500': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="text-sm text-red-500">{{ form.errors.code }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="unit">Unit of Measure *</Label>
                            <Input
                                id="unit"
                                v-model="form.unit"
                                placeholder="pcs, kg, meter, etc."
                                :disabled="form.processing"
                                :class="{ 'border-red-500': form.errors.unit }"
                            />
                            <p v-if="form.errors.unit" class="text-sm text-red-500">{{ form.errors.unit }}</p>
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="space-y-2">
                        <Label for="name">Part Name *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder=" Bearing 6205"
                            :disabled="form.processing"
                            :class="{ 'border-red-500': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <!-- Specification -->
                    <div class="space-y-2">
                        <Label for="specification">Specification</Label>
                        <Input
                            id="specification"
                            v-model="form.specification"
                            placeholder="25mm x 52mm x 15mm"
                            :disabled="form.processing"
                            :class="{ 'border-red-500': form.errors.specification }"
                        />
                        <p v-if="form.errors.specification" class="text-sm text-red-500">{{ form.errors.specification }}</p>
                    </div>

                    <!-- Category -->
                    <div class="space-y-2">
                        <Label for="category">Category</Label>
                        <Input
                            id="category"
                            v-model="form.category"
                            placeholder="Electrical, Mechanical, etc."
                            list="categories"
                            :disabled="form.processing"
                            :class="{ 'border-red-500': form.errors.category }"
                        />
                        <datalist id="categories">
                            <option value="Electrical" />
                            <option value="Mechanical" />
                            <option value="Hydraulic" />
                            <option value="Pneumatic" />
                            <option value="Safety" />
                            <option value="Consumables" />
                            <option value="Other" />
                        </datalist>
                        <p v-if="form.errors.category" class="text-sm text-red-500">{{ form.errors.category }}</p>
                    </div>

                    <!-- Stock Settings -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <Label for="min_stock">Min Stock *</Label>
                            <Input
                                id="min_stock"
                                v-model.number="form.min_stock"
                                type="number"
                                min="0"
                                :disabled="form.processing"
                                :class="{ 'border-red-500': form.errors.min_stock }"
                            />
                            <p v-if="form.errors.min_stock" class="text-sm text-red-500">{{ form.errors.min_stock }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="max_stock">Max Stock *</Label>
                            <Input
                                id="max_stock"
                                v-model.number="form.max_stock"
                                type="number"
                                min="0"
                                :disabled="form.processing"
                                :class="{ 'border-red-500': form.errors.max_stock }"
                            />
                            <p v-if="form.errors.max_stock" class="text-sm text-red-500">{{ form.errors.max_stock }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="reorder_level">Reorder Level *</Label>
                            <Input
                                id="reorder_level"
                                v-model.number="form.reorder_level"
                                type="number"
                                min="0"
                                :disabled="form.processing"
                                :class="{ 'border-red-500': form.errors.reorder_level }"
                            />
                            <p v-if="form.errors.reorder_level" class="text-sm text-red-500">{{ form.errors.reorder_level }}</p>
                        </div>
                    </div>

                    <!-- Organization -->
                    <div class="space-y-2">
                        <Label for="organization_id">Organization</Label>
                        <select
                            id="organization_id"
                            v-model="form.organization_id"
                            :disabled="form.processing"
                            class="flex h-10 w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-950 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="{ 'border-red-500': form.errors.organization_id }"
                        >
                            <option value="">Select Organization (Optional)</option>
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
                            {{ form.processing ? 'Creating...' : 'Create Spare Part' }}
                        </Button>
                        <Link href="/spare-parts">
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
