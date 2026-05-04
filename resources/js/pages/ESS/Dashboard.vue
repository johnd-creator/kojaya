<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
    employee: {
        first_name: string;
        last_name: string;
        employee_code: string;
        department?: { name: string } | null;
        position?: { name: string } | null;
        organization?: { name: string } | null;
    };
    stats: {
        attendance_this_month: number;
        pending_leaves: number;
        approved_leaves_this_year: number;
        latest_payroll_period: string | null;
        latest_net_salary: number | null;
        expiring_certificates: number;
        due_medical_checkups: number;
    };
}>();
</script>

<template>
    <Head title="ESS Dashboard" />

    <AppLayout :breadcrumbs="[{ title: 'ESS', href: '/ess' }]">
        <PageContainer>
            <div class="rounded-xl border bg-gradient-to-r from-primary/10 to-transparent p-6">
                <h1 class="text-2xl font-semibold">{{ employee.first_name }} {{ employee.last_name }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ employee.employee_code }} · {{ employee.position?.name || '-' }} · {{ employee.department?.name || '-' }}
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Attendance This Month</div><div class="text-2xl font-semibold">{{ stats.attendance_this_month }}</div></div>
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Pending Leaves</div><div class="text-2xl font-semibold">{{ stats.pending_leaves }}</div></div>
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Approved Leaves This Year</div><div class="text-2xl font-semibold">{{ stats.approved_leaves_this_year }}</div></div>
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Latest Payslip</div><div class="text-lg font-semibold">{{ stats.latest_payroll_period || '-' }}</div></div>
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Latest Net Salary</div><div class="text-lg font-semibold">{{ stats.latest_net_salary ?? '-' }}</div></div>
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Compliance Alerts</div><div class="text-lg font-semibold">{{ stats.expiring_certificates + stats.due_medical_checkups }}</div></div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Link href="/attendance/self-service" class="rounded-lg border p-4 hover:bg-muted/40">Attendance Self Service</Link>
                <Link href="/leaves/self-service" class="rounded-lg border p-4 hover:bg-muted/40">Leave Requests</Link>
                <Link href="/ess/payslips" class="rounded-lg border p-4 hover:bg-muted/40">Payslips</Link>
                <Link href="/ess/compliance" class="rounded-lg border p-4 hover:bg-muted/40">Certificates & MCU</Link>
            </div>
        </PageContainer>
    </AppLayout>
</template>
