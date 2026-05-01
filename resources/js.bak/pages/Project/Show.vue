<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft, Users, Calendar, DollarSign, CheckCircle, Plus, X, Search, Shield, Upload, FileText, Trash2, PieChart, Wallet } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import GanttChart from '@/components/project/GanttChart.vue';
import ProjectFinancials from '@/components/project/ProjectFinancials.vue';
import TeamCalendar from '@/components/project/TeamCalendar.vue';
import { Avatar, AvatarGroup } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/projects';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    project: any;
    rootTasks: any[];
    documents?: any[];
    availableAssets?: any[];
    availableEmployees?: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Operations', href: '#' },
    { title: 'Projects', href: index().url },
    { title: props.project.name, href: '#' },
];

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

const formatDateRange = (startDate: string, endDate: string) => {
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    const startFormatted = start.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
    
    const endFormatted = end.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
    
    return `${startFormatted} - ${endFormatted}`;
};

const statusColors: Record<string, string> = {
    PLANNING: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    ONGOING: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    ON_HOLD: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400',
    COMPLETED: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    CANCELLED: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    PENDING: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400',
    IN_PROGRESS: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
};

const showTaskDialog = ref(false);
const showTeamDialog = ref(false);
const showQuickTeamDialog = ref(false);
const showDocumentDialog = ref(false);
const showMobilizationDialog = ref(false);

const selectedTeamMember = ref<any | null>(null);
const availabilityLoading = ref(false);
const teamAvailability = ref<{ available: boolean; conflicts: any[] } | null>(null);

const activeTab = ref('tasks');
const viewMode = ref<'list' | 'gantt'>('list');

const taskForm = useForm({
    name: '',
    description: '',
    parent_task_id: null,
    start_date: '',
    end_date: '',
    assigned_to: '',
    estimated_hours: 0,
});

const teamForm = useForm({
    employee_id: '',
    role: '',
    start_date: '',
    end_date: '',
    daily_rate_cost: 0,
    notes: '',
});

const mobilizationForm = useForm({
    status: 'RECRUITMENT',
    has_ppe: false,
    has_uniform: false,
});

const documentForm = useForm({
    name: '',
    type: 'OTHER',
    file: null as File | null,
    expiry_date: '',
});

const openTaskDialog = () => {
    taskForm.reset();
    taskForm.start_date = props.project.start_date;
    taskForm.end_date = props.project.end_date;
    showTaskDialog.value = true;
};

const openTeamDialog = () => {
    teamForm.reset();
    teamForm.start_date = props.project.start_date;
    showTeamDialog.value = true;
};

const checkTeamAvailability = async () => {
    if (!teamForm.employee_id || !teamForm.start_date) {
        teamAvailability.value = null;
        return;
    }

    availabilityLoading.value = true;
    try {
        const { data } = await axios.get(`/projects/${props.project.id}/team/availability`, {
            params: {
                employee_id: teamForm.employee_id,
                start_date: teamForm.start_date,
                end_date: teamForm.end_date || undefined,
            },
        });
        teamAvailability.value = data;
    } finally {
        availabilityLoading.value = false;
    }
};

watch(
    () => [teamForm.employee_id, teamForm.start_date, teamForm.end_date],
    async () => {
        await checkTeamAvailability();
    },
);

const submitTask = () => {
    taskForm.post(`/projects/${props.project.id}/tasks`, {
        onSuccess: () => {
            showTaskDialog.value = false;
            taskForm.reset();
        },
    });
};

const submitTeamMember = () => {
    teamForm.post(`/projects/${props.project.id}/team`, {
        onSuccess: () => {
            showTeamDialog.value = false;
            teamForm.reset();
            teamAvailability.value = null;
        },
    });
};

const removeTeamMember = (teamMemberId: string) => {
    if (confirm('Are you sure you want to remove this team member?')) {
        router.delete(`/projects/${props.project.id}/team/${teamMemberId}`);
    }
};

const openDocumentDialog = () => {
    documentForm.reset();
    showDocumentDialog.value = true;
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        documentForm.file = target.files[0];
    }
};

const submitDocument = () => {
    const formData = new FormData();
    formData.append('name', documentForm.name);
    formData.append('type', documentForm.type);
    if (documentForm.file) {
        formData.append('file', documentForm.file);
    }
    if (documentForm.expiry_date) {
        formData.append('expiry_date', documentForm.expiry_date);
    }

    axios.post(`/projects/${props.project.id}/documents`, formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    }).then(() => {
        showDocumentDialog.value = false;
        documentForm.reset();
        router.reload();
    }).catch(error => {
        console.error('Upload failed:', error);
    });
};

const deleteDocument = (documentId: string) => {
    if (confirm('Are you sure you want to delete this document?')) {
        router.delete(`/projects/${props.project.id}/documents/${documentId}`);
    }
};

const getDocumentUrl = (path: string) => {
    return `/storage/${path}`;
};

const openMobilizationDialog = (member: any) => {
    selectedTeamMember.value = member;
    mobilizationForm.status = member.status || 'RECRUITMENT';
    mobilizationForm.has_ppe = !!member.has_ppe;
    mobilizationForm.has_uniform = !!member.has_uniform;
    showMobilizationDialog.value = true;
};

const submitMobilization = () => {
    if (!selectedTeamMember.value) return;

    mobilizationForm.post(`/projects/${props.project.id}/team/${selectedTeamMember.value.id}/mobilization`, {
        onSuccess: () => {
            showMobilizationDialog.value = false;
            selectedTeamMember.value = null;
            mobilizationForm.reset();
            router.reload();
        },
    });
};
</script>

<template>
    <Head :title="project.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col">
            <!-- Header -->
            <div class="border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-6 py-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <Link :href="index().url">
                            <Button variant="ghost" size="icon" class="rounded-full">
                                <ArrowLeft class="h-5 w-5" />
                            </Button>
                        </Link>
                        <div>
                            <div class="flex items-center gap-3">
                                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ project.name }}</h1>
                                <Badge :class="statusColors[project.status] || 'bg-zinc-100'">{{ project.status }}</Badge>
                            </div>
                            <div class="flex items-center gap-4 mt-1 text-sm text-zinc-500">
                                <span class="flex items-center gap-1">
                                    <Calendar class="h-4 w-4" />
                                    {{ formatDateRange(project.start_date, project.end_date) }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <DollarSign class="h-4 w-4" />
                                    Budget: {{ formatCurrency(project.budget) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Actions -->
                        <Button variant="outline" as-child>
                            <Link :href="`/projects/${project.id}/team`">
                                <Users class="h-4 w-4 mr-2" /> Manage Team & Mobilization
                            </Link>
                        </Button>
                        <Button variant="outline" as-child>
                            <Link :href="`/projects/${project.id}/financials`">
                                <Wallet class="h-4 w-4 mr-2" /> Financial Dashboard
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="px-6 mt-4">
                <Tabs v-model="activeTab" class="w-full">
                    <TabsList class="grid w-full grid-cols-5 lg:w-[520px]">
                        <TabsTrigger value="tasks">Tasks</TabsTrigger>
                        <TabsTrigger value="manpower">Manpower</TabsTrigger>
                        <TabsTrigger value="team">Team</TabsTrigger>
                        <TabsTrigger value="documents">Docs</TabsTrigger>
                        <TabsTrigger value="financials">Financials</TabsTrigger>
                    </TabsList>

                    <div class="mt-6 pb-10">
                        <TabsContent value="tasks" class="mt-0">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-medium">Project Tasks</h3>
                                        <div class="flex items-center rounded-md border border-zinc-200 dark:border-zinc-800 p-1">
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                class="h-7 px-2" 
                                                :class="{'bg-zinc-100 dark:bg-zinc-800': viewMode === 'list'}"
                                                @click="viewMode = 'list'"
                                            >
                                                List
                                            </Button>
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                class="h-7 px-2" 
                                                :class="{'bg-zinc-100 dark:bg-zinc-800': viewMode === 'gantt'}"
                                                @click="viewMode = 'gantt'"
                                            >
                                                Gantt
                                            </Button>
                                        </div>
                                    </div>
                                    <Button size="sm" @click="openTaskDialog">
                                        <Plus class="h-4 w-4 mr-2" />
                                        New Task
                                    </Button>
                                </div>

                                <div v-if="viewMode === 'list'">
                                    <div v-if="rootTasks && rootTasks.length > 0" class="space-y-2">
                                        <div v-for="task in rootTasks" :key="task.id" class="p-4 border rounded-lg bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="font-medium text-zinc-900 dark:text-white">{{ task.name }}</h4>
                                                        <Badge :variant="task.status === 'COMPLETED' ? 'default' : 'secondary'">{{ task.status }}</Badge>
                                                    </div>
                                                    <p v-if="task.description" class="text-sm text-zinc-500 mt-1">{{ task.description }}</p>
                                                    <div class="flex items-center gap-4 mt-2 text-xs text-zinc-500">
                                                        <span v-if="task.assignee">Assigned to: {{ task.assignee.first_name }} {{ task.assignee.last_name }}</span>
                                                        <span>Due: {{ formatDate(task.end_date) }}</span>
                                                        <span>Est. {{ task.estimated_hours }}h</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <div class="text-right">
                                                        <div class="text-sm font-medium">{{ task.progress_percentage }}%</div>
                                                        <div class="h-2 w-20 bg-zinc-200 rounded-full overflow-hidden">
                                                            <div class="h-full bg-indigo-600 rounded-full" :style="{ width: task.progress_percentage + '%' }"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else class="p-8 text-center border rounded-lg bg-white dark:bg-zinc-900">
                                        <p class="text-zinc-500">No tasks yet. Create your first task to get started.</p>
                                    </div>
                                </div>

                                <div v-else-if="viewMode === 'gantt'">
                                    <GanttChart :project-id="project.id" :tasks="rootTasks" />
                                </div>
                            </div>

                            <Dialog v-model:open="showTaskDialog">
                                <DialogContent class="max-w-2xl">
                                    <DialogHeader>
                                        <DialogTitle>Create New Task</DialogTitle>
                                    </DialogHeader>
                                    <form @submit.prevent="submitTask" class="space-y-4">
                                        <div class="grid gap-2">
                                            <Label for="task_name">Task Name</Label>
                                            <Input id="task_name" v-model="taskForm.name" placeholder="Enter task name" required />
                                            <span v-if="taskForm.errors.name" class="text-xs text-red-500">{{ taskForm.errors.name }}</span>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="task_description">Description</Label>
                                            <Textarea id="task_description" v-model="taskForm.description" placeholder="Task description..." :rows="3" />
                                            <span v-if="taskForm.errors.description" class="text-xs text-red-500">{{ taskForm.errors.description }}</span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="grid gap-2">
                                                <Label for="task_start_date">Start Date</Label>
                                                <Input id="task_start_date" type="date" v-model="taskForm.start_date" required />
                                                <span v-if="taskForm.errors.start_date" class="text-xs text-red-500">{{ taskForm.errors.start_date }}</span>
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="task_end_date">End Date</Label>
                                                <Input id="task_end_date" type="date" v-model="taskForm.end_date" required />
                                                <span v-if="taskForm.errors.end_date" class="text-xs text-red-500">{{ taskForm.errors.end_date }}</span>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="grid gap-2">
                                                <Label for="task_assigned_to">Assign To</Label>
                                                <select id="task_assigned_to" v-model="taskForm.assigned_to" class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                                    <option value="">Unassigned</option>
                                                    <option v-for="member in (project.team || [])" :key="member.id" :value="member.employee_id">
                                                        {{ member.employee?.first_name }} {{ member.employee?.last_name }} ({{ member.role }})
                                                    </option>
                                                </select>
                                                <span v-if="taskForm.errors.assigned_to" class="text-xs text-red-500">{{ taskForm.errors.assigned_to }}</span>
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="task_estimated_hours">Estimated Hours</Label>
                                                <Input id="task_estimated_hours" type="number" v-model="taskForm.estimated_hours" min="0" required />
                                                <span v-if="taskForm.errors.estimated_hours" class="text-xs text-red-500">{{ taskForm.errors.estimated_hours }}</span>
                                            </div>
                                        </div>

                                        <div class="flex justify-end gap-3 pt-4">
                                            <Button type="button" variant="outline" @click="showTaskDialog = false">Cancel</Button>
                                            <Button type="submit" :disabled="taskForm.processing">
                                                <span v-if="taskForm.processing">Creating...</span>
                                                <span v-else>Create Task</span>
                                            </Button>
                                        </div>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </TabsContent>

                        <TabsContent value="manpower" class="mt-0">
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-lg font-medium">Manpower Planning</h3>
                                    <p class="text-sm text-zinc-500">Track mobilization readiness and detect conflicts</p>
                                </div>

                                <div v-if="project.team && project.team.length > 0" class="space-y-2">
                                    <div v-for="member in project.team" :key="member.id" class="flex flex-col gap-3 rounded-lg border bg-white p-4 dark:bg-zinc-900">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="font-medium text-zinc-900 dark:text-white">
                                                    {{ member.employee?.first_name }} {{ member.employee?.last_name }}
                                                </div>
                                                <div class="text-sm text-zinc-500">{{ member.role }}</div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Badge variant="outline">{{ member.status }}</Badge>
                                                <Button size="sm" variant="outline" @click="openMobilizationDialog(member)">Update</Button>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                            <div>Start: {{ formatDate(member.start_date) }}</div>
                                            <div>End: {{ member.end_date ? formatDate(member.end_date) : 'open' }}</div>
                                            <div>PPE: {{ member.has_ppe ? 'Yes' : 'No' }}</div>
                                            <div>Uniform: {{ member.has_uniform ? 'Yes' : 'No' }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="p-8 text-center border rounded-lg bg-white dark:bg-zinc-900">
                                    <p class="text-zinc-500">No team members assigned yet.</p>
                                </div>
                            </div>

                            <Dialog v-model:open="showMobilizationDialog">
                                <DialogContent class="max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Update Mobilization</DialogTitle>
                                    </DialogHeader>
                                    <form @submit.prevent="submitMobilization" class="space-y-4">
                                        <div class="grid gap-2">
                                            <Label>Status</Label>
                                            <select v-model="mobilizationForm.status" class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                                <option value="RECRUITMENT">RECRUITMENT</option>
                                                <option value="SCREENING">SCREENING</option>
                                                <option value="MCU">MCU</option>
                                                <option value="ONBOARDING">ONBOARDING</option>
                                                <option value="PLACED">PLACED</option>
                                            </select>
                                            <span v-if="mobilizationForm.errors.status" class="text-xs text-red-500">{{ mobilizationForm.errors.status }}</span>
                                        </div>

                                        <div class="flex items-center justify-between rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                                            <div class="text-zinc-700 dark:text-zinc-200">Has PPE</div>
                                            <input type="checkbox" v-model="mobilizationForm.has_ppe" class="h-4 w-4" />
                                        </div>

                                        <div class="flex items-center justify-between rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                                            <div class="text-zinc-700 dark:text-zinc-200">Has Uniform</div>
                                            <input type="checkbox" v-model="mobilizationForm.has_uniform" class="h-4 w-4" />
                                        </div>

                                        <div class="flex justify-end gap-3 pt-2">
                                            <Button type="button" variant="outline" @click="showMobilizationDialog = false">Cancel</Button>
                                            <Button type="submit" :disabled="mobilizationForm.processing">
                                                <span v-if="mobilizationForm.processing">Saving...</span>
                                                <span v-else>Save</span>
                                            </Button>
                                        </div>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </TabsContent>

                        <TabsContent value="team" class="mt-0">
                            <div class="space-y-6">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h3 class="text-lg font-medium">Team Members</h3>
                                        <p class="text-sm text-zinc-500">{{ (project.team || []).length }} members assigned</p>
                                    </div>
                                    <Button size="sm" @click="openTeamDialog">
                                        <Plus class="h-4 w-4 mr-2" />
                                        Add Member
                                    </Button>
                                </div>

                                <div v-if="project.team && project.team.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div v-for="member in project.team" :key="member.id" class="p-4 border rounded-lg bg-white dark:bg-zinc-900">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-3">
                                                <Avatar>
                                                    {{ member.employee?.first_name?.charAt(0) }}
                                                </Avatar>
                                                <div>
                                                    <h4 class="font-medium text-zinc-900 dark:text-white">
                                                        {{ member.employee?.first_name }} {{ member.employee?.last_name }}
                                                    </h4>
                                                    <p class="text-sm text-zinc-500">{{ member.role }}</p>
                                                </div>
                                            </div>
                                            <Button variant="ghost" size="icon" @click="removeTeamMember(member.id)">
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                            </Button>
                                        </div>
                                        <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                                            <div class="grid grid-cols-2 gap-2 text-xs text-zinc-500">
                                                <div>
                                                    <span class="font-medium">Start:</span> {{ formatDate(member.start_date) }}
                                                </div>
                                                <div v-if="member.end_date">
                                                    <span class="font-medium">End:</span> {{ formatDate(member.end_date) }}
                                                </div>
                                                <div>
                                                    <span class="font-medium">Rate:</span> {{ formatCurrency(member.daily_rate_cost) }}/day
                                                </div>
                                                <div>
                                                    <Badge :variant="member.status === 'PLACED' ? 'default' : 'secondary'">{{ member.status }}</Badge>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="p-8 text-center border rounded-lg bg-white dark:bg-zinc-900">
                                    <p class="text-zinc-500">No team members assigned yet.</p>
                                </div>

                                <TeamCalendar :team="project.team || []" :project-start="project.start_date" :project-end="project.end_date" />
                            </div>

                            <Dialog v-model:open="showTeamDialog">
                                <DialogContent class="max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Add Team Member</DialogTitle>
                                    </DialogHeader>
                                    <form @submit.prevent="submitTeamMember" class="space-y-4">
                                        <div class="grid gap-2">
                                            <Label for="team_employee">Employee</Label>
                                            <select id="team_employee" v-model="teamForm.employee_id" required class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                                <option value="">Select employee...</option>
                                                <option v-for="employee in (availableEmployees || [])" :key="employee.id" :value="employee.id">
                                                    {{ employee.first_name }} {{ employee.last_name }}
                                                </option>
                                            </select>
                                            <span v-if="teamForm.errors.employee_id" class="text-xs text-red-500">{{ teamForm.errors.employee_id }}</span>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="team_role">Role</Label>
                                            <Input id="team_role" v-model="teamForm.role" placeholder="e.g., Engineer, Supervisor" required />
                                            <span v-if="teamForm.errors.role" class="text-xs text-red-500">{{ teamForm.errors.role }}</span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="grid gap-2">
                                                <Label for="team_start_date">Start Date</Label>
                                                <Input id="team_start_date" type="date" v-model="teamForm.start_date" required />
                                                <span v-if="teamForm.errors.start_date" class="text-xs text-red-500">{{ teamForm.errors.start_date }}</span>
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="team_end_date">End Date</Label>
                                                <Input id="team_end_date" type="date" v-model="teamForm.end_date" />
                                                <span v-if="teamForm.errors.end_date" class="text-xs text-red-500">{{ teamForm.errors.end_date }}</span>
                                            </div>
                                        </div>

                                        <div v-if="availabilityLoading" class="text-xs text-zinc-500">
                                            Checking availability...
                                        </div>
                                        <div v-else-if="teamAvailability && !teamAvailability.available" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/10 dark:text-red-200">
                                            <div class="font-medium">Schedule conflict</div>
                                            <div class="mt-1 space-y-1">
                                                <div v-for="conflict in teamAvailability.conflicts" :key="conflict.project_id">
                                                    {{ conflict.project_code }} - {{ conflict.project_name }} ({{ conflict.start_date }} - {{ conflict.end_date || 'open' }})
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="team_daily_rate">Daily Rate (IDR)</Label>
                                            <Input id="team_daily_rate" type="number" v-model="teamForm.daily_rate_cost" min="0" step="10000" required />
                                            <span v-if="teamForm.errors.daily_rate_cost" class="text-xs text-red-500">{{ teamForm.errors.daily_rate_cost }}</span>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="team_notes">Notes</Label>
                                            <Textarea id="team_notes" v-model="teamForm.notes" placeholder="Additional notes..." :rows="2" />
                                        </div>

                                        <div class="flex justify-end gap-3 pt-4">
                                            <Button type="button" variant="outline" @click="showTeamDialog = false">Cancel</Button>
                                            <Button type="submit" :disabled="teamForm.processing">
                                                <span v-if="teamForm.processing">Adding...</span>
                                                <span v-else>Add Member</span>
                                            </Button>
                                        </div>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </TabsContent>

                        <TabsContent value="documents" class="mt-0">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h3 class="text-lg font-medium">Project Documents</h3>
                                        <p class="text-sm text-zinc-500">{{ (documents || []).length }} documents uploaded</p>
                                    </div>
                                    <Button size="sm" @click="openDocumentDialog">
                                        <Upload class="h-4 w-4 mr-2" />
                                        Upload Document
                                    </Button>
                                </div>

                                <div v-if="documents && documents.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div v-for="doc in documents" :key="doc.id" class="p-4 border rounded-lg bg-white dark:bg-zinc-900 hover:shadow-md transition-shadow">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                                    <FileText class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-medium text-zinc-900 dark:text-white truncate">{{ doc.name }}</h4>
                                                    <p class="text-xs text-zinc-500">{{ doc.type }}</p>
                                                </div>
                                            </div>
                                            <Button variant="ghost" size="icon" @click="deleteDocument(doc.id)">
                                                <Trash2 class="h-4 w-4 text-red-500" />
                                            </Button>
                                        </div>
                                        <div class="mt-3 flex items-center justify-between">
                                            <Badge :variant="doc.status === 'VALID' ? 'default' : 'destructive'">{{ doc.status }}</Badge>
                                            <a v-if="doc.file_path" :href="getDocumentUrl(doc.file_path)" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800">
                                                View
                                            </a>
                                        </div>
                                        <div v-if="doc.expiry_date" class="mt-2 text-xs text-zinc-500">
                                            Exp: {{ formatDate(doc.expiry_date) }}
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="p-8 text-center border rounded-lg bg-white dark:bg-zinc-900">
                                    <p class="text-zinc-500">No documents uploaded yet.</p>
                                </div>
                            </div>
                            
                            <Dialog v-model:open="showDocumentDialog">
                                <DialogContent class="max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Upload Document</DialogTitle>
                                    </DialogHeader>
                                    <form @submit.prevent="submitDocument" class="space-y-4">
                                        <div class="grid gap-2">
                                            <Label for="doc_name">Document Name</Label>
                                            <Input id="doc_name" v-model="documentForm.name" placeholder="e.g., Contract Agreement" required />
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="doc_type">Type</Label>
                                            <select id="doc_type" v-model="documentForm.type" class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950">
                                                <option value="CONTRACT">Contract</option>
                                                <option value="INVOICE">Invoice</option>
                                                <option value="REPORT">Report</option>
                                                <option value="PERMIT">Permit</option>
                                                <option value="OTHER">Other</option>
                                            </select>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="doc_expiry">Expiry Date (Optional)</Label>
                                            <Input id="doc_expiry" type="date" v-model="documentForm.expiry_date" />
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="doc_file">File</Label>
                                            <Input id="doc_file" type="file" @change="handleFileChange" required />
                                        </div>

                                        <div class="flex justify-end gap-3 pt-4">
                                            <Button type="button" variant="outline" @click="showDocumentDialog = false">Cancel</Button>
                                            <Button type="submit" :disabled="documentForm.processing">
                                                <span v-if="documentForm.processing">Uploading...</span>
                                                <span v-else>Upload</span>
                                            </Button>
                                        </div>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </TabsContent>

                        <TabsContent value="financials" class="mt-0">
                            <ProjectFinancials :project-id="project.id" />
                        </TabsContent>
                    </div>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
