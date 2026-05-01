<script setup lang="ts">
import { ChevronLeft, ChevronRight, Calendar, Users, AlertTriangle } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const props = withDefaults(defineProps<{
    team: any[];
  projectStart: string;
  projectEnd: string;
}>(), {
    team: () => [],
    projectStart: '',
    projectEnd: ''
});

const selectedMonth = ref(new Date().getMonth());
const selectedYear = ref(new Date().getFullYear());

const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const calendarDays = computed(() => {
    const days = [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const firstDayOfMonth = new Date(selectedYear.value, selectedMonth.value, 1);
    const lastDayOfMonth = new Date(selectedYear.value, selectedMonth.value + 1, 0);
    const startDayOfWeek = firstDayOfMonth.getDay();

    for (let i = 0; i < startDayOfWeek; i++) {
        days.push({
            date: null,
            dateObj: null,
            isEmpty: true,
            isWeekend: false,
            isPast: false,
            assignments: [],
            isOverloaded: false,
        });
    }

    for (let day = 1; day <= lastDayOfMonth.getDate(); day++) {
        const date = new Date(selectedYear.value, selectedMonth.value, day);
        const dayOfWeek = date.getDay();
        
        const assignments = (props.team || []).filter((member: any) => {
            const startDate = new Date(member.start_date);
            const endDate = member.end_date ? new Date(member.end_date) : new Date(props.projectEnd);
            return date >= startDate && date <= endDate;
        });

        days.push({
            date: day,
            dateObj: date,
            isEmpty: false,
            isWeekend: dayOfWeek === 0 || dayOfWeek === 6,
            isPast: date.getTime() < today.getTime(),
            assignments: assignments,
            isOverloaded: assignments.length > 5,
        });
    }

    return days;
});

const getAssignmentsForDay = (day: number) => {
    const date = new Date(selectedYear.value, selectedMonth.value, day);
    return (props.team || []).filter((member: any) => {
        const startDate = new Date(member.start_date);
        const endDate = member.end_date ? new Date(member.end_date) : new Date(props.projectEnd);
        return date >= startDate && date <= endDate;
    });
};

const previousMonth = () => {
    if (selectedMonth.value === 0) {
        selectedMonth.value = 11;
        selectedYear.value--;
    } else {
        selectedMonth.value--;
    }
};

const nextMonth = () => {
    if (selectedMonth.value === 11) {
        selectedMonth.value = 0;
        selectedYear.value++;
    } else {
        selectedMonth.value++;
    }
};
</script>

<template>
        <div class="team-calendar bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6">
            <div class="space-y-6">
                <!-- Calendar Header -->
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Team Availability Calendar</h3>
                    <div class="flex items-center gap-2">
                        <Button size="sm" variant="outline" @click="previousMonth">
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <span class="font-bold text-zinc-900 dark:text-white min-w-[140px] text-center">
                            {{ monthNames[selectedMonth] }} {{ selectedYear }}
                        </span>
                        <Button size="sm" variant="outline" @click="nextMonth">
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-1">
                    <!-- Day Headers -->
                    <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" class="text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 py-2">
                        {{ day }}
                    </div>

                    <!-- Calendar Days -->
                    <div
                        v-for="(day, index) in calendarDays"
                        :key="index"
                        class="calendar-day aspect-square rounded-md"
                        :class="{ 
                            'bg-zinc-50 dark:bg-zinc-800/50': day.isEmpty,
                            'bg-zinc-50 dark:bg-zinc-800/30': !day.isEmpty && day.isWeekend,
                            'bg-white dark:bg-zinc-900': !day.isEmpty && !day.isWeekend,
                            'opacity-50': day.isPast,
                        }"
                    >
                        <div v-if="!day.isEmpty" class="h-full flex flex-col p-1">
                            <span class="text-xs font-medium" :class="{
                                'text-zinc-400': day.isWeekend,
                                'text-zinc-700 dark:text-zinc-300': !day.isWeekend,
                            }">
                                {{ day.date }}
                            </span>

                            <!-- Team Avatars -->
                            <div v-if="day.assignments.length > 0" class="mt-1 flex items-center -space-x-2">
                                <Avatar 
                                    v-for="member in day.assignments.slice(0, 3)" 
                                    :key="member.id"
                                    size="xs"
                                    :title="`${member.employee?.first_name} ${member.employee?.last_name} - ${member.role}`"
                                    class="border-2 border-white dark:border-zinc-900 ring-2 ring-white dark:ring-zinc-900"
                                >
                                    {{ member.employee?.first_name?.charAt(0) }}
                                </Avatar>
                                <span 
                                    v-if="day.assignments.length > 3" 
                                    class="text-xs text-zinc-500"
                                    :title="`+${day.assignments.length - 3} more team members`"
                                >
                                    +{{ day.assignments.length - 3 }}
                                </span>
                            </div>

                            <!-- Overload Indicator -->
                            <div v-if="day.isOverloaded" class="mt-auto flex justify-center">
                                <AlertTriangle class="h-3 w-3 text-red-500" title="Team overloaded" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Legend -->
                <div class="border-t border-zinc-200 dark:border-zinc-800 pt-4">
                    <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Team Members</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        <div 
                            v-for="member in (team || [])" 
                            :key="member.id" 
                            class="flex items-center gap-2 text-sm p-2 bg-zinc-50 dark:bg-zinc-800 rounded-md"
                        >
                            <Avatar size="sm">
                                {{ member.employee?.first_name?.charAt(0) }}
                            </Avatar>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-zinc-900 dark:text-white truncate">
                                    {{ member.employee?.first_name }} {{ member.employee?.last_name }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <Badge size="sm" variant="secondary">{{ member.role }}</Badge>
                                    <span class="text-xs text-zinc-500">
                                        {{ new Date(member.start_date).toLocaleDateString() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar-day {
    @apply border border-transparent;
}
</style>

