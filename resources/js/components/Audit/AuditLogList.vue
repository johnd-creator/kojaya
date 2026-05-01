<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { auditLogsApi   } from '@/Actions/auditLogs';
import type {AuditLog, AuditLogFilters} from '@/Actions/auditLogs';
import { Button } from '@/components/ui/button';
import AuditLogDetail from './AuditLogDetail.vue';
import AuditLogFilter from './AuditLogFilter.vue';

const logs = ref<AuditLog[]>([]);
const loading = ref(false);
const selectedLog = ref<AuditLog | null>(null);
const detailOpen = ref(false);
const filterOpen = ref(false);

const filters = ref<AuditLogFilters>({
    per_page: 15,
    page: 1,
});

const fetchLogs = async () => {
    loading.value = true;
    try {
        const response = await auditLogsApi.list(filters.value);
        logs.value = response.data;
    } catch (error) {
        console.error('Failed to fetch audit logs:', error);
    } finally {
        loading.value = false;
    }
};

const viewDetail = (log: AuditLog) => {
    selectedLog.value = log;
    detailOpen.value = true;
};

const applyFilters = () => {
    filters.value.page = 1;
    fetchLogs();
    filterOpen.value = false;
};

const resetFilters = () => {
    filters.value = {
        per_page: 15,
        page: 1,
    };
    fetchLogs();
};

const exportLogs = async () => {
    try {
        const response = await auditLogsApi.export(filters.value);
        const dataStr = JSON.stringify(response.data, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `audit-logs-${new Date().toISOString()}.json`;
        link.click();
        URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to export logs:', error);
    }
};

const getActionBadgeColor = (action: string): string => {
    const colors: Record<string, string> = {
        CREATE: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        UPDATE: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        DELETE: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        LOGIN: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        LOGOUT: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        FAILED_LOGIN: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    };
    return colors[action] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleString();
};

onMounted(() => {
    fetchLogs();
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Audit Logs</h1>
                <p class="text-muted-foreground text-sm">
                    View and track all system activities and changes
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" @click="filterOpen = !filterOpen">
                    {{ filterOpen ? 'Hide' : 'Show' }} Filters
                </Button>
                <Button variant="outline" size="sm" @click="exportLogs">
                    Export
                </Button>
                <Button size="sm" @click="fetchLogs">Refresh</Button>
            </div>
        </div>

        <AuditLogFilter
            v-if="filterOpen"
            v-model="filters"
            @filter="applyFilters"
            @reset="resetFilters"
        />

        <div class="rounded-lg border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium">Timestamp</th>
                            <th class="px-4 py-3 text-left text-sm font-medium">User</th>
                            <th class="px-4 py-3 text-left text-sm font-medium">Action</th>
                            <th class="px-4 py-3 text-left text-sm font-medium">Module</th>
                            <th class="px-4 py-3 text-left text-sm font-medium">Subject</th>
                            <th class="px-4 py-3 text-left text-sm font-medium">IP Address</th>
                            <th class="px-4 py-3 text-left text-sm font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                Loading...
                            </td>
                        </tr>
                        <tr v-else-if="logs.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                No audit logs found
                            </td>
                        </tr>
                        <tr
                            v-for="log in logs"
                            :key="log.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="px-4 py-3 text-sm">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ log.user ? log.user.name : 'System' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                    :class="getActionBadgeColor(log.action)"
                                >
                                    {{ log.action }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm capitalize">
                                {{ log.module.replace('_', ' ') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span v-if="log.subject_type">
                                    {{ log.subject_type.split('\\').pop() }} #{{ log.subject_id }}
                                </span>
                                <span v-else class="text-muted-foreground">N/A</span>
                            </td>
                            <td class="px-4 py-3 text-sm font-mono text-xs">
                                {{ log.ip_address || 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="viewDetail(log)"
                                >
                                    View
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <AuditLogDetail
            v-if="selectedLog"
            v-model:open="detailOpen"
            :log="selectedLog"
        />
    </div>
</template>
