<script setup lang="ts">
import { ref, watch } from 'vue';
import type { AuditLogFilters } from '@/Actions/auditLogs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    modelValue: AuditLogFilters;
}

interface Emits {
    (e: 'update:modelValue', value: AuditLogFilters): void;
    (e: 'filter'): void;
    (e: 'reset'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const filters = ref<AuditLogFilters>({ ...props.modelValue });

watch(() => props.modelValue, (newValue) => {
    filters.value = { ...newValue };
}, { deep: true });

const applyFilters = () => {
    emit('update:modelValue', filters.value);
    emit('filter');
};

const resetFilters = () => {
    filters.value = {};
    emit('update:modelValue', filters.value);
    emit('reset');
};

const modules = [
    { value: 'employees', label: 'Employees' },
    { value: 'payrolls', label: 'Payrolls' },
    { value: 'invoices', label: 'Invoices' },
    { value: 'auth', label: 'Authentication' },
    { value: 'employeeCertificates', label: 'Certificates' },
    { value: 'medicalCheckups', label: 'Medical Checkups' },
];

const actions = [
    { value: 'CREATE', label: 'Create' },
    { value: 'UPDATE', label: 'Update' },
    { value: 'DELETE', label: 'Delete' },
    { value: 'LOGIN', label: 'Login' },
    { value: 'LOGOUT', label: 'Logout' },
    { value: 'FAILED_LOGIN', label: 'Failed Login' },
];
</script>

<template>
    <div class="space-y-4 rounded-lg border bg-card p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">Filters</h3>
            <Button variant="ghost" size="sm" @click="resetFilters">
                Reset
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-2">
                <Label for="module">Module</Label>
                <select
                    id="module"
                    v-model="filters.module"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <option value="">All Modules</option>
                    <option v-for="module in modules" :key="module.value" :value="module.value">
                        {{ module.label }}
                    </option>
                </select>
            </div>

            <div class="space-y-2">
                <Label for="action">Action</Label>
                <select
                    id="action"
                    v-model="filters.action"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <option value="">All Actions</option>
                    <option v-for="action in actions" :key="action.value" :value="action.value">
                        {{ action.label }}
                    </option>
                </select>
            </div>

            <div class="space-y-2">
                <Label for="date-from">Date From</Label>
                <Input
                    id="date-from"
                    v-model="filters.date_from"
                    type="date"
                    class="w-full"
                />
            </div>

            <div class="space-y-2">
                <Label for="date-to">Date To</Label>
                <Input
                    id="date-to"
                    v-model="filters.date_to"
                    type="date"
                    class="w-full"
                />
            </div>
        </div>

        <div class="flex justify-end">
            <Button @click="applyFilters">Apply Filters</Button>
        </div>
    </div>
</template>
