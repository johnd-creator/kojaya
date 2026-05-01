<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Briefcase, Plus, Pencil, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    positions: any;
    departments: any[];
    jobGrades: any[];
    filters: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'HR Master Data', href: '#' },
    { title: 'Positions', href: '/positions' },
];

const showModal = ref(false);
const editTarget = ref<any>(null);
const form = useForm({ code: '', name: '', description: '', department_id: '', job_grade_id: '' });

const openCreate = () => { editTarget.value = null; form.reset(); showModal.value = true; };
const openEdit = (p: any) => {
    editTarget.value = p;
    form.code = p.code; form.name = p.name; form.description = p.description ?? '';
    form.department_id = p.department_id; form.job_grade_id = p.job_grade_id;
    showModal.value = true;
};

const submit = () => {
    if (editTarget.value) {
        form.put(`/positions/${editTarget.value.id}`, { onSuccess: () => { showModal.value = false; } });
    } else {
        form.post('/positions', { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const destroy = (p: any) => { if (confirm(`Delete position "${p.name}"?`)) router.delete(`/positions/${p.id}`); };

const search = ref(props.filters.search || '');
const deptFilter = ref(props.filters.department_id || '');
let t: ReturnType<typeof setTimeout>;
watch([search, deptFilter], ([s, d]) => {
    clearTimeout(t);
    t = setTimeout(() => router.get('/positions', { search: s, department_id: d }, { preserveState: true, replace: true }), 400);
});
</script>

<template>
    <Head title="Positions" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Positions</h1>
                    <p class="text-zinc-500 mt-1">Jabatan karyawan per bidang dan jenjang.</p>
                </div>
                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button @click="openCreate"><Plus class="h-4 w-4 mr-2" />Add Position</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader><DialogTitle>{{ editTarget ? 'Edit' : 'New' }} Position</DialogTitle></DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4 mt-2">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2"><Label>Code</Label><Input v-model="form.code" placeholder="OCR_A" required /></div>
                                <div class="grid gap-2"><Label>Job Grade</Label>
                                    <select v-model="form.job_grade_id" required class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                        <option value="" disabled>Select grade</option>
                                        <option v-for="g in jobGrades" :key="g.id" :value="g.id">{{ g.level }} — {{ g.name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid gap-2"><Label>Position Name</Label><Input v-model="form.name" placeholder="Operator Control Room A" required /></div>
                            <div class="grid gap-2"><Label>Department / Bidang</Label>
                                <select v-model="form.department_id" required class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                    <option value="" disabled>Select department</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.code }} — {{ dept.name }}</option>
                                </select>
                            </div>
                            <div class="grid gap-2"><Label>Description</Label><Input v-model="form.description" /></div>
                            <div class="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="outline" @click="showModal = false">Cancel</Button>
                                <Button type="submit" :disabled="form.processing">Save</Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
            <div class="flex gap-3">
                <Input v-model="search" placeholder="Search positions..." class="max-w-xs" />
                <select v-model="deptFilter" class="flex h-10 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-800/50">
                    <option value="">All Departments</option>
                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                </select>
            </div>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th class="px-5 py-4">Code</th>
                            <th class="px-5 py-4">Position</th>
                            <th class="px-5 py-4">Department</th>
                            <th class="px-5 py-4">Grade</th>
                            <th class="px-5 py-4">Employees</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <tr v-for="p in positions.data" :key="p.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                            <td class="px-5 py-4 font-mono text-indigo-600 dark:text-indigo-400 text-xs">{{ p.code }}</td>
                            <td class="px-5 py-4 font-medium text-zinc-900 dark:text-white">{{ p.name }}</td>
                            <td class="px-5 py-4 text-zinc-500">{{ p.department?.name }}</td>
                            <td class="px-5 py-4"><span class="px-2 py-0.5 text-xs rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30">{{ p.job_grade?.name }}</span></td>
                            <td class="px-5 py-4 text-zinc-500">{{ p.employees_count }}</td>
                            <td class="px-5 py-4 flex justify-end gap-2">
                                <Button size="icon" variant="ghost" @click="openEdit(p)"><Pencil class="h-4 w-4" /></Button>
                                <Button size="icon" variant="ghost" class="text-red-500" @click="destroy(p)"><Trash2 class="h-4 w-4" /></Button>
                            </td>
                        </tr>
                        <tr v-if="!positions.data.length"><td colspan="6" class="py-12 text-center text-zinc-500"><Briefcase class="h-12 w-12 mx-auto text-zinc-300 mb-3" /><p>No positions yet.</p></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
