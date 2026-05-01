<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Truck, Plus, Calendar, AlertTriangle, Trash2, Edit2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    project: any;
    assetAllocations: any[];
    availableAssets: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: `/projects/${props.project.id}` },
    { title: 'Resources', href: `/projects/${props.project.id}/resources` },
];

const createModal = ref(false);
const editModal = ref(false);
const selectedAllocation = ref<any>(null);

const form = useForm({
    asset_id: '',
    start_date: '',
    end_date: '',
    status: 'planned',
    notes: '',
});

const editForm = useForm({
    asset_id: '', // Read-only in edit
    start_date: '',
    end_date: '',
    status: '',
    notes: '',
});

const openCreate = () => {
    form.reset();
    createModal.value = true;
};

const openEdit = (allocation: any) => {
    selectedAllocation.value = allocation;
    editForm.asset_id = allocation.asset_id;
    editForm.start_date = allocation.start_date;
    editForm.end_date = allocation.end_date;
    editForm.status = allocation.status;
    editForm.notes = allocation.notes;
    editModal.value = true;
};

const submitCreate = () => {
    form.post(`/projects/${props.project.id}/resources/assets`, {
        onSuccess: () => {
            createModal.value = false;
            form.reset();
        },
    });
};

const submitEdit = () => {
    editForm.put(`/projects/${props.project.id}/resources/assets/${selectedAllocation.value.id}`, {
        onSuccess: () => {
            editModal.value = false;
        },
    });
};

const deleteAllocation = (allocation: any) => {
    if (confirm('Are you sure you want to remove this asset allocation?')) {
        useForm({}).delete(`/projects/${props.project.id}/resources/assets/${allocation.id}`);
    }
};

const getStatusColor = (status: string) => {
    switch(status) {
        case 'mobilized': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'demobilized': return 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300';
        case 'requested': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        default: return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'; // planned
    }
};
</script>

<template>
    <Head title="Project Resources" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                        <Truck class="h-6 w-6 text-indigo-600" />
                        Asset Allocation
                    </h1>
                    <p class="text-sm text-zinc-500 mt-1">Manage heavy equipment and assets for {{ project.name }}</p>
                </div>

                <Button @click="openCreate">
                    <Plus class="h-4 w-4 mr-2" /> Allocate Asset
                </Button>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 bg-zinc-50 dark:bg-zinc-800 uppercase border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-6 py-3 font-medium">Asset Name</th>
                                <th class="px-6 py-3 font-medium">Code</th>
                                <th class="px-6 py-3 font-medium">Duration</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3 font-medium">Notes</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr v-if="assetAllocations.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                                    No assets allocated to this project yet.
                                </td>
                            </tr>
                            <tr v-for="alloc in assetAllocations" :key="alloc.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                                    {{ alloc.asset?.name || 'Unknown Asset' }}
                                </td>
                                <td class="px-6 py-4 text-zinc-500">
                                    {{ alloc.asset?.code || '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1 text-zinc-700 dark:text-zinc-300">
                                        <Calendar class="h-3 w-3 text-zinc-400" />
                                        <span>{{ alloc.start_date }}</span>
                                        <span class="text-zinc-400">→</span>
                                        <span>{{ alloc.end_date || 'Indefinite' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="getStatusColor(alloc.status)">
                                        {{ alloc.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate" :title="alloc.notes">
                                    {{ alloc.notes || '-' }}
                                </td>
                                <td class="px-6 py-4 text-right flex justify-end gap-2">
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0" @click="openEdit(alloc)">
                                        <Edit2 class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50" @click="deleteAllocation(alloc)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create Modal -->
            <Dialog v-model:open="createModal">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Allocate Asset</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="submitCreate" class="space-y-4 mt-2">
                        <div class="grid gap-2">
                            <Label>Asset</Label>
                            <select 
                                v-model="form.asset_id" 
                                class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 dark:focus-visible:ring-zinc-300"
                                required
                            >
                                <option value="" disabled>Select Asset</option>
                                <option v-for="asset in availableAssets" :key="asset.id" :value="asset.id">
                                    {{ asset.name }} ({{ asset.code }})
                                </option>
                            </select>
                            <span v-if="form.errors.asset_id" class="text-xs text-red-500">{{ form.errors.asset_id }}</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label>Start Date</Label>
                                <Input type="date" v-model="form.start_date" required />
                                <span v-if="form.errors.start_date" class="text-xs text-red-500">{{ form.errors.start_date }}</span>
                            </div>
                            <div class="grid gap-2">
                                <Label>End Date</Label>
                                <Input type="date" v-model="form.end_date" />
                                <span v-if="form.errors.end_date" class="text-xs text-red-500">{{ form.errors.end_date }}</span>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label>Status</Label>
                            <select 
                                v-model="form.status" 
                                class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 dark:focus-visible:ring-zinc-300"
                            >
                                <option value="planned">Planned</option>
                                <option value="requested">Requested</option>
                                <option value="mobilized">Mobilized</option>
                            </select>
                        </div>

                        <div class="grid gap-2">
                            <Label>Notes</Label>
                            <Input v-model="form.notes" placeholder="e.g. Needs transport trailer" />
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" @click="createModal = false">Cancel</Button>
                            <Button type="submit" :disabled="form.processing">Allocate</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Edit Modal -->
            <Dialog v-model:open="editModal">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Update Allocation</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="submitEdit" class="space-y-4 mt-2">
                         <div class="grid gap-2">
                            <Label>Asset</Label>
                            <div class="px-3 py-2 border rounded-md bg-zinc-50 text-zinc-500 text-sm">
                                {{ selectedAllocation?.asset?.name }}
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label>Start Date</Label>
                                <Input type="date" v-model="editForm.start_date" required />
                            </div>
                            <div class="grid gap-2">
                                <Label>End Date</Label>
                                <Input type="date" v-model="editForm.end_date" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label>Status</Label>
                            <select 
                                v-model="editForm.status" 
                                class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 dark:focus-visible:ring-zinc-300"
                            >
                                <option value="planned">Planned</option>
                                <option value="requested">Requested</option>
                                <option value="mobilized">Mobilized</option>
                                <option value="demobilized">Demobilized (Returned)</option>
                            </select>
                        </div>

                        <div class="grid gap-2">
                            <Label>Notes</Label>
                            <Input v-model="editForm.notes" />
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" @click="editModal = false">Cancel</Button>
                            <Button type="submit" :disabled="editForm.processing">Save Changes</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
