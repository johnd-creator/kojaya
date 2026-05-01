<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { FileSpreadsheet, Plus, Eye, Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    budgets: any;
    organizations: Array<{ id: string; code: string; name: string }>;
    filters: Record<string, string>;
    can: { selectOrganization: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Finance', href: '#' },
    { title: 'RKAP', href: '/budgets' },
];

const showModal = ref(false);
const editTarget = ref<any>(null);

const currentYear = new Date().getFullYear().toString();

const form = useForm({
    organization_id: props.can.selectOrganization ? (props.filters.organization_id ?? '') : '',
    year: currentYear,
    period: 'ANNUAL',
    status: 'DRAFT',
});

const openCreate = () => {
    editTarget.value = null;
    form.reset();
    form.year = currentYear;
    form.period = 'ANNUAL';
    form.status = 'DRAFT';
    showModal.value = true;
};

const openEdit = (budget: any) => {
    editTarget.value = budget;
    form.organization_id = budget.organization_id ?? '';
    form.year = budget.year;
    form.period = budget.period;
    form.status = budget.status;
    showModal.value = true;
};

const submit = () => {
    if (editTarget.value) {
        form.put(`/budgets/${editTarget.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
        return;
    }

    form.post('/budgets', {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
};

const destroy = (budget: any) => {
    if (confirm(`Delete RKAP ${budget.year} (${budget.period})?`)) {
        router.delete(`/budgets/${budget.id}`);
    }
};

const yearFilter = ref(props.filters.year || '');
const statusFilter = ref(props.filters.status || '');
const orgFilter = ref(props.filters.organization_id || '');

const filterParams = computed(() => ({
    year: yearFilter.value || undefined,
    status: statusFilter.value || undefined,
    organization_id: props.can.selectOrganization ? (orgFilter.value || undefined) : undefined,
}));

let t: ReturnType<typeof setTimeout>;
watch([yearFilter, statusFilter, orgFilter], () => {
    clearTimeout(t);
    t = setTimeout(() => {
        router.get('/budgets', filterParams.value, { preserveState: true, replace: true });
    }, 250);
});
</script>

<template>
    <Head title="RKAP" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">RKAP</h1>
                    <p class="text-zinc-500 mt-1">Rencana Kerja dan Anggaran Perusahaan.</p>
                </div>
                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button @click="openCreate"><Plus class="h-4 w-4 mr-2" />Buat RKAP</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader><DialogTitle>{{ editTarget ? 'Edit' : 'Buat' }} RKAP</DialogTitle></DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4 mt-2">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label>Tahun</Label>
                                    <Input v-model="form.year" inputmode="numeric" placeholder="2026" required />
                                    <span v-if="form.errors.year" class="text-xs text-red-500">{{ form.errors.year }}</span>
                                </div>
                                <div class="grid gap-2">
                                    <Label>Periode</Label>
                                    <select v-model="form.period" class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                        <option value="ANNUAL">ANNUAL</option>
                                        <option value="Q1">Q1</option>
                                        <option value="Q2">Q2</option>
                                        <option value="Q3">Q3</option>
                                        <option value="Q4">Q4</option>
                                    </select>
                                    <span v-if="form.errors.period" class="text-xs text-red-500">{{ form.errors.period }}</span>
                                </div>
                            </div>

                            <div v-if="can.selectOrganization" class="grid gap-2">
                                <Label>Organisasi</Label>
                                <select v-model="form.organization_id" class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                    <option value="">(Default: organisasi user)</option>
                                    <option v-for="org in organizations" :key="org.id" :value="org.id">
                                        {{ org.code }} - {{ org.name }}
                                    </option>
                                </select>
                                <span v-if="form.errors.organization_id" class="text-xs text-red-500">{{ form.errors.organization_id }}</span>
                            </div>

                            <div class="grid gap-2">
                                <Label>Status</Label>
                                <select v-model="form.status" class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                    <option value="DRAFT">DRAFT</option>
                                    <option value="ACTIVE">ACTIVE</option>
                                    <option value="CLOSED">CLOSED</option>
                                </select>
                                <span v-if="form.errors.status" class="text-xs text-red-500">{{ form.errors.status }}</span>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="outline" @click="showModal = false">Batal</Button>
                                <Button type="submit" :disabled="form.processing">Simpan</Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="flex flex-wrap gap-3">
                <Input v-model="yearFilter" placeholder="Filter tahun (YYYY)" class="max-w-[220px]" />
                <select v-model="statusFilter" class="flex h-10 w-full max-w-[220px] rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <option value="">Semua status</option>
                    <option value="DRAFT">DRAFT</option>
                    <option value="ACTIVE">ACTIVE</option>
                    <option value="CLOSED">CLOSED</option>
                </select>
                <select v-if="can.selectOrganization" v-model="orgFilter" class="flex h-10 w-full max-w-[320px] rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <option value="">Semua organisasi</option>
                    <option v-for="org in organizations" :key="org.id" :value="org.id">
                        {{ org.code }} - {{ org.name }}
                    </option>
                </select>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th class="px-5 py-4">Tahun</th>
                            <th class="px-5 py-4">Periode</th>
                            <th class="px-5 py-4">Organisasi</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Lines</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <tr v-for="b in budgets.data" :key="b.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                            <td class="px-5 py-4 font-semibold text-zinc-900 dark:text-white">{{ b.year }}</td>
                            <td class="px-5 py-4 text-zinc-500">{{ b.period }}</td>
                            <td class="px-5 py-4 text-zinc-500">{{ b.organization?.name ?? '-' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                    :class="b.status === 'DRAFT' ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200'
                                        : b.status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'
                                        : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200'">
                                    {{ b.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-zinc-500">{{ b.lines_count }}</td>
                            <td class="px-5 py-4 flex justify-end gap-2">
                                <Button size="icon" variant="ghost" as-child>
                                    <Link :href="`/budgets/${b.id}`"><Eye class="h-4 w-4" /></Link>
                                </Button>
                                <Button size="icon" variant="ghost" @click="openEdit(b)" :disabled="b.status !== 'DRAFT'">
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button size="icon" variant="ghost" class="text-red-500" @click="destroy(b)" :disabled="b.status !== 'DRAFT'">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!budgets.data.length">
                            <td colspan="6" class="py-12 text-center text-zinc-500">
                                <FileSpreadsheet class="h-12 w-12 mx-auto text-zinc-300 mb-3" />
                                <p>Belum ada RKAP.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

