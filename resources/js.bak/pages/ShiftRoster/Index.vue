<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, RefreshCw } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    rosters: Record<string, Array<{
        id: number;
        date: string;
        shift_group: string;
        work_shift_id: number | null;
        is_off_day: boolean;
        notes: string | null;
        work_shift: { id: number; name: string; start_time: string; end_time: string } | null;
    }>>;
    workShifts: Array<{ id: number; name: string; start_time: string; end_time: string }>;
    year: number;
    month: number;
    daysInMonth: number;
    groups: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'HR Master Data', href: '#' },
    { title: 'Shift Roster', href: '/shift-rosters' },
];

const today = new Date().toISOString().split('T')[0];

const days = computed(() => {
    const result = [];
    for (let d = 1; d <= props.daysInMonth; d++) {
        const date = `${props.year}-${String(props.month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        result.push(date);
    }
    return result;
});

const monthLabel = computed(() => {
    return new Date(props.year, props.month - 1, 1).toLocaleString('id-ID', { month: 'long', year: 'numeric' });
});

const getRosterEntry = (date: string, group: string) => {
    const dayRosters = props.rosters[date] || [];
    return dayRosters.find(r => r.shift_group === group) || null;
};

const shiftColors: Record<string, string> = {};
const shiftBgColors: Record<string, string> = {
    default: 'bg-zinc-50 dark:bg-zinc-800/30 text-zinc-500',
    off: 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400',
};
const shiftPalette = [
    'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
    'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
    'bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300',
    'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
];
props.workShifts.forEach((shift, idx) => {
    shiftColors[shift.id] = shiftPalette[idx % shiftPalette.length];
});

const getCellClass = (date: string, group: string): string => {
    const entry = getRosterEntry(date, group);
    if (!entry) return shiftBgColors.default;
    if (entry.is_off_day) return shiftBgColors.off;
    if (entry.work_shift_id && shiftColors[entry.work_shift_id]) {
        return shiftColors[entry.work_shift_id];
    }
    return shiftBgColors.default;
};

const getCellLabel = (date: string, group: string): string => {
    const entry = getRosterEntry(date, group);
    if (!entry) return '–';
    if (entry.is_off_day) return 'OFF';
    return entry.work_shift?.name?.replace('Shift ', '').toUpperCase() ?? '–';
};

const navigate = (direction: number) => {
    let m = props.month + direction;
    let y = props.year;
    if (m > 12) { m = 1; y++; }
    if (m < 1)  { m = 12; y--; }
    router.get('/shift-rosters', { year: y, month: m }, { preserveState: false });
};

// Generate form
const generateForm = useForm({
    year: props.year,
    month: props.month,
});
const generateRoster = () => {
    generateForm.year = props.year;
    generateForm.month = props.month;
    generateForm.post('/shift-rosters/generate', { preserveScroll: true });
};

// Edit overlay
const editing = ref<{ id: number; work_shift_id: number | null; is_off_day: boolean; notes: string } | null>(null);
const openEdit = (date: string, group: string) => {
    const entry = getRosterEntry(date, group);
    if (!entry) return;
    editing.value = {
        id: entry.id,
        work_shift_id: entry.work_shift_id,
        is_off_day: entry.is_off_day,
        notes: entry.notes ?? '',
    };
};
const saveEdit = () => {
    if (!editing.value) return;
    router.patch(`/shift-rosters/${editing.value.id}`, {
        work_shift_id: editing.value.work_shift_id,
        is_off_day: editing.value.is_off_day,
        notes: editing.value.notes,
    }, {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });
};
</script>

<template>
    <Head title="Shift Roster" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 max-w-full mx-auto w-full">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Shift Roster</h1>
                    <p class="text-zinc-500 mt-1">Monthly rotating shift schedule for operational groups A, B, C, and D.</p>
                </div>
                <div class="flex gap-3 items-center">
                    <Button variant="outline" @click="navigate(-1)">
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <span class="min-w-[160px] text-center font-semibold text-zinc-900 dark:text-white">{{ monthLabel }}</span>
                    <Button variant="outline" @click="navigate(1)">
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                    <Button @click="generateRoster" :disabled="generateForm.processing" class="ml-2">
                        <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': generateForm.processing }" />
                        Generate Roster
                    </Button>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap gap-3 text-xs">
                <div v-for="shift in workShifts" :key="shift.id" class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm inline-block" :class="shiftColors[shift.id]?.split(' ')[0]"></span>
                    <span class="text-zinc-600 dark:text-zinc-400">{{ shift.name }} ({{ shift.start_time.slice(0,5) }}–{{ shift.end_time.slice(0,5) }})</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm inline-block bg-zinc-200 dark:bg-zinc-700"></span>
                    <span class="text-zinc-600 dark:text-zinc-400">OFF / Libur</span>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm bg-white dark:bg-zinc-900">
                <table class="w-full text-xs min-w-[900px]">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                            <th class="sticky left-0 bg-white dark:bg-zinc-900 px-4 py-3 text-left text-zinc-500 uppercase font-medium w-16 z-10">Group</th>
                            <th 
                                v-for="date in days" 
                                :key="date" 
                                class="px-1.5 py-3 text-center font-medium min-w-[42px]"
                                :class="date === today ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-zinc-500'"
                            >
                                <div>{{ new Date(date + 'T00:00:00').getDate() }}</div>
                                <div class="text-zinc-400 font-normal">{{ new Date(date + 'T00:00:00').toLocaleString('id-ID', { weekday: 'short' }).slice(0,3) }}</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="group in groups" :key="group" class="border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                            <td class="sticky left-0 bg-white dark:bg-zinc-900 px-4 py-2 z-10">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-bold text-sm">
                                    {{ group }}
                                </span>
                            </td>
                            <td 
                                v-for="date in days" 
                                :key="date"
                                class="px-1 py-1.5 text-center"
                                :class="date === today ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : ''"
                            >
                                <button
                                    @click="openEdit(date, group)"
                                    class="w-full rounded-md py-1.5 px-1 font-semibold transition-all hover:ring-2 hover:ring-indigo-400 hover:ring-offset-1 text-[10px] leading-tight"
                                    :class="getCellClass(date, group)"
                                    :title="`Group ${group} — ${date}: ${getCellLabel(date, group)}`"
                                >
                                    {{ getCellLabel(date, group) }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-zinc-400 text-center">Click any cell to override the shift assignment for that day.</p>
        </div>

        <!-- Edit Modal Overlay -->
        <div v-if="editing" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="editing = null">
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xl w-full max-w-sm p-6 space-y-4">
                <h3 class="font-semibold text-zinc-900 dark:text-white text-lg">Override Shift</h3>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 flex items-center gap-2">
                        <input type="checkbox" v-model="editing.is_off_day" class="rounded" />
                        Mark as Off Day (Libur)
                    </label>
                </div>

                <div v-if="!editing.is_off_day" class="space-y-2">
                    <label class="text-xs font-medium text-zinc-500 block">Shift</label>
                    <select 
                        v-model="editing.work_shift_id" 
                        class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                    >
                        <option :value="null">— Not set —</option>
                        <option v-for="shift in workShifts" :key="shift.id" :value="shift.id">
                            {{ shift.name }} ({{ shift.start_time.slice(0,5) }}–{{ shift.end_time.slice(0,5) }})
                        </option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-medium text-zinc-500 block">Catatan (opsional)</label>
                    <input 
                        v-model="editing.notes" 
                        type="text"
                        placeholder="Alasan perubahan..."
                        class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                    />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <Button variant="outline" @click="editing = null">Batal</Button>
                    <Button @click="saveEdit">Simpan</Button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
