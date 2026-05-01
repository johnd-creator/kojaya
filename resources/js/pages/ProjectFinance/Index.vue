<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Chart from 'chart.js/auto';
import { Wallet, ArrowLeft, TrendingUp, TrendingDown, DollarSign, PieChart, Receipt, CreditCard, Users } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    project: any;
    financialSummary: {
        budget: number;
        revenue: number;
        cost: number;
        profit: number;
        margin: number;
    };
    costBreakdown: {
        labor: number;
        materials: number;
        equipment: number;
        subcontractors: number;
        reimbursements: number;
        petty_cash: number;
        other: number;
    };
    recentTransactions: any[];
    sCurveData: {
        labels: string[];
        planned: number[];
        actual: number[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: props.project.name, href: `/projects/${props.project.id}` },
    { title: 'Financials', href: `/projects/${props.project.id}/financials` },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);
};

const profitColor = computed(() => {
    return props.financialSummary.profit >= 0 ? 'text-green-600' : 'text-red-600';
});

const profitBg = computed(() => {
    return props.financialSummary.profit >= 0 ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20';
});

onMounted(() => {
    const ctx = document.getElementById('sCurveChart') as HTMLCanvasElement;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.sCurveData.labels,
            datasets: [
                {
                    label: 'Planned Progress (%)',
                    data: props.sCurveData.planned,
                    borderColor: '#4f46e5', // Indigo 600
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Actual Progress (%)',
                    data: props.sCurveData.actual,
                    borderColor: '#22c55e', // Green 500
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                title: {
                    display: true,
                    text: 'S-Curve: Planned vs Actual Progress'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Progress (%)'
                    }
                }
            }
        }
    });
});
</script>

<template>
    <Head title="Project Financials" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-4">
                <Link :href="`/projects/${props.project.id}`">
                    <Button variant="ghost" size="icon" class="rounded-full">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                        <Wallet class="h-6 w-6 text-indigo-600" />
                        Project Financials
                    </h1>
                    <p class="text-sm text-zinc-500 mt-1">Real-time budget control, profitability analysis, and S-Curve</p>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-zinc-500">Total Budget</h3>
                        <PieChart class="h-4 w-4 text-zinc-400" />
                    </div>
                    <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">
                        {{ formatCurrency(financialSummary.budget) }}
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">Contract Value</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-zinc-500">Recognized Revenue</h3>
                        <TrendingUp class="h-4 w-4 text-green-500" />
                    </div>
                    <div class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ formatCurrency(financialSummary.revenue) }}
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">Based on Paid Invoices</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-zinc-500">Actual Cost</h3>
                        <TrendingDown class="h-4 w-4 text-red-500" />
                    </div>
                    <div class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ formatCurrency(financialSummary.cost) }}
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">Total Direct Costs</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm" :class="profitBg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-zinc-500">Net Profit</h3>
                        <DollarSign class="h-4 w-4 text-zinc-400" />
                    </div>
                    <div class="mt-2 text-2xl font-bold" :class="profitColor">
                        {{ formatCurrency(financialSummary.profit) }}
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">Margin: {{ financialSummary.margin }}%</p>
                </div>
            </div>

            <!-- S-Curve Chart -->
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Project S-Curve</h3>
                <div class="h-80 w-full relative">
                    <canvas id="sCurveChart"></canvas>
                </div>
            </div>

            <!-- Cost Breakdown & Transactions -->
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Cost Breakdown</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Users class="h-4 w-4 text-zinc-400" />
                                <span class="text-sm font-medium">Labor Cost</span>
                            </div>
                            <span class="text-sm font-bold">{{ formatCurrency(costBreakdown.labor) }}</span>
                        </div>
                        <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
                            <div class="bg-indigo-500 h-2 rounded-full" :style="{ width: financialSummary.cost > 0 ? `${(costBreakdown.labor / financialSummary.cost) * 100}%` : '0%' }"></div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Receipt class="h-4 w-4 text-zinc-400" />
                                <span class="text-sm font-medium">Reimbursements</span>
                            </div>
                            <span class="text-sm font-bold">{{ formatCurrency(costBreakdown.reimbursements) }}</span>
                        </div>
                        <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
                            <div class="bg-pink-500 h-2 rounded-full" :style="{ width: financialSummary.cost > 0 ? `${(costBreakdown.reimbursements / financialSummary.cost) * 100}%` : '0%' }"></div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <CreditCard class="h-4 w-4 text-zinc-400" />
                                <span class="text-sm font-medium">Petty Cash Expenses</span>
                            </div>
                            <span class="text-sm font-bold">{{ formatCurrency(costBreakdown.petty_cash) }}</span>
                        </div>
                        <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2">
                            <div class="bg-orange-500 h-2 rounded-full" :style="{ width: financialSummary.cost > 0 ? `${(costBreakdown.petty_cash / financialSummary.cost) * 100}%` : '0%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Recent Transactions</h3>
                    <div class="space-y-4">
                        <div v-if="recentTransactions.length === 0" class="text-center py-8 text-zinc-500">
                            No recent transactions found.
                        </div>
                        <div v-for="tx in recentTransactions" :key="tx.id" class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-full" :class="tx.type === 'revenue' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'">
                                    <TrendingUp v-if="tx.type === 'revenue'" class="h-4 w-4" />
                                    <TrendingDown v-else class="h-4 w-4" />
                                </div>
                                <div>
                                    <div class="font-medium text-sm text-zinc-900 dark:text-white">{{ tx.description }}</div>
                                    <div class="text-xs text-zinc-500">{{ tx.date }}</div>
                                </div>
                            </div>
                            <div class="font-bold text-sm" :class="tx.type === 'revenue' ? 'text-green-600' : 'text-red-600'">
                                {{ tx.type === 'revenue' ? '+' : '-' }} {{ formatCurrency(tx.amount) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
