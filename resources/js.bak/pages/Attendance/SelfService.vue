<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Clock, CheckCircle2, AlertCircle } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    employee: any;
    todayAttendance: any | null;
    todayRoster: {
        is_off_day: boolean;
        work_shift: { name: string; start_time: string; end_time: string; is_flexible: boolean } | null;
    } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'HR & Employee', href: '#' },
    { title: 'Attendance ESS', href: '/attendance' },
];

const form = useForm({});

const currentTime = ref(new Date());
let timer: ReturnType<typeof setInterval>;

onMounted(() => {
    timer = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    clearInterval(timer);
});

const formatTime = (date: Date) => {
    return date.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

const formatDate = (date: Date) => {
    return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
};

const isLocating = ref(false);

const checkIn = () => {
    isLocating.value = true;
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                isLocating.value = false;
                form.transform((data) => ({
                    ...data,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                })).post('/attendance/check-in', {
                    preserveScroll: true,
                });
            },
            (error) => {
                isLocating.value = false;
                form.post('/attendance/check-in', {
                    preserveScroll: true,
                });
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    } else {
        isLocating.value = false;
        form.post('/attendance/check-in', {
            preserveScroll: true,
        });
    }
};

const checkOut = () => {
    form.post('/attendance/check-out', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Self Service Attendance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col justify-center items-center py-12 px-6 max-w-3xl mx-auto w-full">
            
            <div v-if="!employee" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-8 text-center max-w-md w-full">
                <AlertCircle class="h-12 w-12 text-red-500 mx-auto mb-4" />
                <h2 class="text-xl font-bold text-red-900 dark:text-red-400 mb-2">No Employee Profile</h2>
                <p class="text-red-700 dark:text-red-300 text-sm">
                    You do not have an active employee profile associated with your account. Please contact HR.
                </p>
            </div>

            <div v-else class="w-full">
                <!-- Header -->
                <div class="text-center mb-10">
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white mb-2">Self Service Attendance</h1>
                    <p class="text-zinc-500">Record your daily attendance and track your working hours.</p>
                </div>

                <!-- Clock Card -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden mb-8">
                    <div class="bg-zinc-50 dark:bg-zinc-950 p-8 text-center border-b border-zinc-200 dark:border-zinc-800">
                        <div class="inline-flex items-center justify-center p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-full mb-4">
                            <Clock class="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <h2 class="text-5xl font-mono font-bold text-zinc-900 dark:text-white mb-2 tabular-nums tracking-tighter">
                            {{ formatTime(currentTime) }}
                        </h2>
                        <p class="text-zinc-500 font-medium">{{ formatDate(currentTime) }}</p>
                    </div>

                    <div class="p-8">
                        <!-- Employee & Roster Info -->
                        <div class="flex justify-between items-center mb-8 pb-8 border-b border-zinc-100 dark:border-zinc-800">
                            <div>
                                <p class="text-sm text-zinc-500 mb-1">Employee</p>
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ employee.first_name }} {{ employee.last_name }}</p>
                                <p class="text-xs text-zinc-500">{{ employee.employee_code }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-zinc-500 mb-1">
                                    <template v-if="employee.shift_group">Group {{ employee.shift_group }} — </template>Today's Shift
                                </p>
                                <template v-if="todayRoster">
                                    <span v-if="todayRoster.is_off_day" class="inline-block px-2.5 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500 text-xs font-semibold">🌙 Hari Istirahat</span>
                                    <p v-else-if="todayRoster.work_shift" class="font-semibold text-zinc-900 dark:text-white">{{ todayRoster.work_shift.name }}</p>
                                    <p v-if="!todayRoster.is_off_day && todayRoster.work_shift" class="text-xs text-zinc-400">{{ todayRoster.work_shift.start_time.slice(0,5) }} – {{ todayRoster.work_shift.end_time.slice(0,5) }}</p>
                                </template>
                                <template v-else>
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ employee.work_shift ? employee.work_shift.name : 'Standard Shift' }}</p>
                                    <p v-if="employee.work_shift && employee.work_shift.is_flexible" class="text-xs text-indigo-500">Flexible Hours Enabled</p>
                                </template>
                            </div>
                        </div>

                        <!-- Off-Day Banner -->
                        <div v-if="todayRoster && todayRoster.is_off_day" class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-lg p-4 flex gap-3 text-sm text-amber-800 dark:text-amber-300">
                            <AlertCircle class="h-5 w-5 shrink-0 mt-0.5" />
                            <div>
                                <p class="font-semibold mb-1">Hari Istirahat — Group {{ employee.shift_group }}</p>
                                <p>Hari ini adalah jadwal libur untuk group kamu. Tidak perlu melakukan absensi.</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Button 
                                    @click="checkIn" 
                                    :disabled="form.processing || isLocating || (todayAttendance && todayAttendance.clock_in)"
                                    class="w-full h-14 text-base shadow-sm"
                                    :variant="(todayAttendance && todayAttendance.clock_in) ? 'secondary' : 'default'"
                                >
                                    <CheckCircle2 v-if="todayAttendance && todayAttendance.clock_in" class="mr-2 h-5 w-5" />
                                    {{ (todayAttendance && todayAttendance.clock_in) ? 'Checked In' : (isLocating ? 'Locating...' : 'Check In Now') }}
                                </Button>
                                <p v-if="todayAttendance && todayAttendance.clock_in" class="text-xs text-center text-zinc-500">
                                    Clocked in at <span class="font-semibold text-zinc-900 dark:text-white">{{ todayAttendance.clock_in }}</span>
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Button 
                                    @click="checkOut" 
                                    :disabled="form.processing || !todayAttendance || !todayAttendance.clock_in || todayAttendance.clock_out"
                                    class="w-full h-14 text-base shadow-sm"
                                    :variant="(todayAttendance && todayAttendance.clock_out) ? 'secondary' : (!todayAttendance || !todayAttendance.clock_in ? 'outline' : 'destructive')"
                                >
                                    <CheckCircle2 v-if="todayAttendance && todayAttendance.clock_out" class="mr-2 h-5 w-5" />
                                    {{ (todayAttendance && todayAttendance.clock_out) ? 'Checked Out' : 'Check Out Now' }}
                                </Button>
                                <p v-if="todayAttendance && todayAttendance.clock_out" class="text-xs text-center text-zinc-500">
                                    Clocked out at <span class="font-semibold text-zinc-900 dark:text-white">{{ todayAttendance.clock_out }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Alert -->
                <div v-if="employee.work_shift && employee.work_shift.is_flexible" class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 rounded-lg p-4 flex gap-3 text-sm text-indigo-800 dark:text-indigo-300">
                    <AlertCircle class="h-5 w-5 shrink-0" />
                    <div>
                        <p class="font-semibold mb-1">Flexible Time Active</p>
                        <p>Your check-out time is calculated automatically based on your actual check-in time to fulfill your required daily hours.</p>
                        <p v-if="todayAttendance && todayAttendance.scheduled_end_time" class="mt-2 font-medium">
                            Scheduled Check-out Time: <span class="inline-block px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/50 tabular-nums">{{ todayAttendance.scheduled_end_time }}</span>
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
