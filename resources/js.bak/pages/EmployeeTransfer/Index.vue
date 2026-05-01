<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRightLeft } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    transfers: any;
    organizations: Array<{ id: string; code?: string; name: string }>;
    filters: { status?: string; organization_id?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Human Resources', href: '#' },
    { title: 'Employee Transfers', href: '/employee-transfers' },
];

const statusFilter = ref(props.filters.status || '');
const orgFilter = ref(props.filters.organization_id || '');

const params = computed(() => ({
    status: statusFilter.value || undefined,
    organization_id: orgFilter.value || undefined,
}));

let t: ReturnType<typeof setTimeout>;
watch([statusFilter, orgFilter], () => {
    clearTimeout(t);
    t = setTimeout(() => {
        router.get('/employee-transfers', params.value, { preserveState: true, replace: true });
    }, 250);
});
</script>

<template>
    <Head title="Employee Transfers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Employee Transfers</h1>
                    <p class="text-zinc-500 mt-1">Mutasi karyawan antar organisasi.</p>
                </div>
                <Button as-child>
                    <Link href="/employee-transfers/create">Buat Transfer</Link>
                </Button>
            </div>

            <div class="flex flex-wrap gap-3">
                <select v-model="statusFilter" class="flex h-10 w-full max-w-[220px] rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <option value="">Semua status</option>
                    <option value="PENDING">PENDING</option>
                    <option value="APPROVED">APPROVED</option>
                    <option value="REJECTED">REJECTED</option>
                </select>
                <select v-model="orgFilter" class="flex h-10 w-full max-w-[320px] rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <option value="">Semua organisasi</option>
                    <option v-for="org in organizations" :key="org.id" :value="org.id">
                        {{ org.code ? `${org.code} - ${org.name}` : org.name }}
                    </option>
                </select>
            </div>

            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th class="px-5 py-4">Employee</th>
                            <th class="px-5 py-4">From</th>
                            <th class="px-5 py-4">To</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <tr v-for="tfr in transfers.data" :key="tfr.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                            <td class="px-5 py-4 text-zinc-900 dark:text-white">
                                {{ tfr.employee?.employee_code }} - {{ tfr.employee?.first_name }} {{ tfr.employee?.last_name }}
                            </td>
                            <td class="px-5 py-4 text-zinc-500">{{ tfr.from_organization?.name ?? '-' }}</td>
                            <td class="px-5 py-4 text-zinc-500">{{ tfr.to_organization?.name ?? '-' }}</td>
                            <td class="px-5 py-4 text-zinc-500">{{ tfr.status }}</td>
                            <td class="px-5 py-4 text-right">
                                <Button variant="ghost" size="sm" as-child>
                                    <Link :href="`/employee-transfers/${tfr.id}`">Detail</Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!transfers.data.length">
                            <td colspan="5" class="py-12 text-center text-zinc-500">
                                <ArrowRightLeft class="h-12 w-12 mx-auto text-zinc-300 mb-3" />
                                <p>Belum ada transfer.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
