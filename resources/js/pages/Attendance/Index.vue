<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { CalendarDays, Clock, Search, Plus, CheckCircle2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index as attendancesIndex, store as attendanceStore } from '@/actions/App/Http/Controllers/AttendanceController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    attendances: any;
    organizations: any[];
    employees: any[];
    filters: Record<string, string>;
    stats: { today_present: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Core Modules', href: '#' },
    { title: 'Attendance', href: attendancesIndex().url },
];

const search = ref('');
const selectedOrg = ref(props.filters.organization_id || '');
const selectedStatus = ref(props.filters.status || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const showModal = ref(false);

let filterTimeout: ReturnType<typeof setTimeout>;
const applyFilters = () => {
    router.get(attendancesIndex().url, {
        organization_id: selectedOrg.value,
        status: selectedStatus.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, { preserveState: true, replace: true });
};
watch([selectedOrg, selectedStatus, dateFrom, dateTo], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(applyFilters, 400);
});

const form = useForm({
    employee_id: '',
    organization_id: '',
    date: new Date().toISOString().split('T')[0],
    clock_in: '',
    clock_out: '',
    status: 'PRESENT',
    notes: '',
});

const statusColors: Record<string, string> = {
    PRESENT: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    ABSENT: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    SICK: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    LEAVE: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    OFF: 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
};

const submitAttendance = () => {
    form.post(attendanceStore().url, {
        onSuccess: () => { showModal.value = false; form.reset(); },
    });
};
</script>

<template>
    <Head title="Attendance" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 w-full">

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Attendance</h1>
                    <p class="text-zinc-500 mt-1">Daily attendance records per employee and unit.</p>
                </div>
                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button><Plus class="h-4 w-4 mr-2" /> Record Attendance</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Record Attendance</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submitAttendance" class="space-y-4 mt-2">
                            <div class="grid gap-2">
                                <Label>Employee</Label>
                                <select v-model="form.employee_id" required class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                    <option value="" disabled>Select employee...</option>
                                    <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                        {{ emp.employee_code }} — {{ emp.first_name }} {{ emp.last_name }}
                                    </option>
                                </select>
                                <span v-if="form.errors.employee_id" class="text-xs text-red-500">{{ form.errors.employee_id }}</span>
                            </div>
                            <div class="grid gap-2">
                                <Label>Unit / Organization</Label>
                                <select v-model="form.organization_id" required class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                    <option value="" disabled>Select unit...</option>
                                    <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.code }} - {{ org.name }}</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label>Date</Label>
                                    <Input type="date" v-model="form.date" required />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Status</Label>
                                    <select v-model="form.status" class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                        <option value="PRESENT">Present</option>
                                        <option value="ABSENT">Absent</option>
                                        <option value="SICK">Sick</option>
                                        <option value="LEAVE">Leave</option>
                                        <option value="OFF">Off</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label>Clock In</Label>
                                    <Input type="time" v-model="form.clock_in" />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Clock Out</Label>
                                    <Input type="time" v-model="form.clock_out" />
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label>Notes (optional)</Label>
                                <Input v-model="form.notes" placeholder="Any notes..." />
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="outline" @click="showModal = false">Cancel</Button>
                                <Button type="submit" :disabled="form.processing">Save</Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Stats + Filters -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500">Present Today</p>
                        <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">{{ stats.today_present }}</h2>
                    </div>
                    <CheckCircle2 class="h-10 w-10 text-green-500 opacity-80" />
                </div>
                <div class="lg:col-span-3 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="text-xs font-medium text-zinc-500 mb-1 block">From Date</label>
                            <Input type="date" v-model="dateFrom" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-zinc-500 mb-1 block">To Date</label>
                            <Input type="date" v-model="dateTo" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-zinc-500 mb-1 block">Unit</label>
                            <select v-model="selectedOrg" class="w-full flex h-10 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-800/50">
                                <option value="">All Units</option>
                                <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-zinc-500 mb-1 block">Status</label>
                            <select v-model="selectedStatus" class="w-full flex h-10 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-800/50">
                                <option value="">Any</option>
                                <option value="PRESENT">Present</option>
                                <option value="ABSENT">Absent</option>
                                <option value="SICK">Sick</option>
                                <option value="LEAVE">Leave</option>
                                <option value="OFF">Off</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-5 py-4">Employee</th>
                                <th class="px-5 py-4">Unit</th>
                                <th class="px-5 py-4">Date</th>
                                <th class="px-5 py-4">Clock In</th>
                                <th class="px-5 py-4">Clock Out</th>
                                <th class="px-5 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="att in attendances.data" :key="att.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                                <td class="px-5 py-4 font-medium text-zinc-900 dark:text-white">
                                    {{ att.employee?.first_name }} {{ att.employee?.last_name }}
                                    <span class="block text-xs text-zinc-400">{{ att.employee?.employee_code }}</span>
                                </td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ att.organization?.name ?? '—' }}</td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ new Date(att.date).toLocaleDateString() }}</td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ att.clock_in ?? '—' }}</td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ att.clock_out ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full" :class="statusColors[att.status]">
                                        {{ att.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!attendances.data.length">
                                <td colspan="6" class="py-12 text-center text-zinc-500">
                                    <CalendarDays class="h-12 w-12 mx-auto text-zinc-300 dark:text-zinc-700 mb-3" />
                                    <p class="font-medium text-zinc-800 dark:text-zinc-200">No attendance records found</p>
                                    <p class="text-sm mt-1">Adjust filters or record a new attendance.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div v-if="attendances.links?.length > 3" class="px-5 py-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                    <p class="text-sm text-zinc-500">Showing {{ attendances.from }}–{{ attendances.to }} of {{ attendances.total }}</p>
                    <div class="flex gap-1">
                        <a v-for="(link, i) in attendances.links" :key="i" :href="link.url || '#'"
                            class="px-3 py-1 text-sm rounded-md" :class="[link.active ? 'bg-indigo-600 text-white' : 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800', !link.url ? 'opacity-40 pointer-events-none' : '']"
                            v-html="link.label" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
