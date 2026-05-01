<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
  Users,
  BriefcaseBusiness,
  FileText,
  Building2,
  DollarSign,
  TrendingUp,
  TrendingDown,
  Activity,
  CheckCircle,
  Clock,
  AlertTriangle
} from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: dashboard(),
  },
];

interface DashboardStats {
  totalEmployees: number;
  activeProjects: number;
  totalOrganizations: number;
  monthlyRevenue: number;
  pendingApprovals: number;
  payrollProcessed: number;
  attendanceRate: number;
  onLeaveToday: number;
}

interface UnitPerformance {
  unitName: string;
  headcount: number;
  revenue: number;
  profit: number;
  completion: number;
}

const stats = ref<DashboardStats>({
  totalEmployees: 0,
  activeProjects: 0,
  totalOrganizations: 0,
  monthlyRevenue: 0,
  pendingApprovals: 0,
  payrollProcessed: 0,
  attendanceRate: 0,
  onLeaveToday: 0,
});

const topPerformingUnits = ref<UnitPerformance[]>([]);
const loading = ref(true);

onMounted(async () => {
  // Simulate fetching dashboard stats
  // In production, this would be actual API calls
  setTimeout(() => {
    stats.value = {
      totalEmployees: 1247,
      activeProjects: 18,
      totalOrganizations: 12,
      monthlyRevenue: 2850000000,
      pendingApprovals: 23,
      payrollProcessed: 95,
      attendanceRate: 94.5,
      onLeaveToday: 47,
    };

    topPerformingUnits.value = [
      { unitName: 'Unit Jakarta Pusat', headcount: 245, revenue: 450000000, profit: 125000000, completion: 92 },
      { unitName: 'Unit Bandung', headcount: 178, revenue: 320000000, profit: 89000000, completion: 88 },
      { unitName: 'Unit Semarang', headcount: 156, revenue: 280000000, profit: 78000000, completion: 85 },
      { unitName: 'Unit Surabaya', headcount: 198, revenue: 390000000, profit: 95000000, completion: 90 },
    ];

    loading.value = false;
  }, 500);
});

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
};

const formatPercent = (value: number) => {
  return value.toFixed(1) + '%';
};
</script>

<template>
  <Head title="Dashboard Konsolidasi" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
            Dashboard Konsolidasi
          </h1>
          <p class="text-zinc-500 mt-1">
            Ringkasan performa seluruh unit cabang
          </p>
        </div>
        <div class="flex items-center gap-2 text-sm text-zinc-500">
          <Activity class="w-4 h-4" />
          <span>Last updated: {{ new Date().toLocaleDateString('id-ID') }}</span>
        </div>
      </div>

      <!-- Key Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Employees -->
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Karyawan</p>
              <p class="text-2xl font-bold mt-2">{{ stats.totalEmployees.toLocaleString('id-ID') }}</p>
              <div class="flex items-center mt-2 text-sm text-green-600">
                <TrendingUp class="w-4 h-4 mr-1" />
                <span>+5.2% dari bulan lalu</span>
              </div>
            </div>
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <Users class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
        </div>

        <!-- Active Projects -->
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Proyek Aktif</p>
              <p class="text-2xl font-bold mt-2">{{ stats.activeProjects }}</p>
              <div class="flex items-center mt-2 text-sm text-zinc-500">
                <BriefcaseBusiness class="w-4 h-4 mr-1" />
                <span>12 overhaul, 6 routine</span>
              </div>
            </div>
            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
              <FileText class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
          </div>
        </div>

        <!-- Monthly Revenue -->
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pendapatan Bulan Ini</p>
              <p class="text-2xl font-bold mt-2">{{ formatCurrency(stats.monthlyRevenue) }}</p>
              <div class="flex items-center mt-2 text-sm text-green-600">
                <TrendingUp class="w-4 h-4 mr-1" />
                <span>+12.5% vs target</span>
              </div>
            </div>
            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
              <DollarSign class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Menunggu Approval</p>
              <p class="text-2xl font-bold mt-2">{{ stats.pendingApprovals }}</p>
              <div class="flex items-center mt-2 text-sm text-orange-600">
                <AlertTriangle class="w-4 h-4 mr-1" />
                <span>8 payroll, 15 transfer</span>
              </div>
            </div>
            <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
              <Clock class="w-6 h-6 text-orange-600 dark:text-orange-400" />
            </div>
          </div>
        </div>
      </div>

      <!-- Secondary Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
              <CheckCircle class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Payroll Diproses</p>
              <p class="text-xl font-bold mt-1">{{ stats.payrollProcessed }}%</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <Activity class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Rata-rata Kehadiran</p>
              <p class="text-xl font-bold mt-1">{{ formatPercent(stats.attendanceRate) }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-sm">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
              <Building2 class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Unit</p>
              <p class="text-xl font-bold mt-1">{{ stats.totalOrganizations }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Unit Performance Table -->
      <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-800">
          <h2 class="text-lg font-semibold">Performa Unit Teratas</h2>
          <p class="text-sm text-zinc-500 mt-1">Unit dengan penyelesaian proyek dan profit tertinggi</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Unit</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Headcount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Pendapatan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Profit</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Completion</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
              <tr v-for="unit in topPerformingUnits" :key="unit.unitName" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <td class="px-6 py-4 text-sm font-medium">{{ unit.unitName }}</td>
                <td class="px-6 py-4 text-sm">{{ unit.headcount }}</td>
                <td class="px-6 py-4 text-sm">{{ formatCurrency(unit.revenue) }}</td>
                <td class="px-6 py-4 text-sm text-green-600 font-medium">{{ formatCurrency(unit.profit) }}</td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <div class="flex-1 bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 max-w-[100px]">
                      <div
                        class="bg-blue-600 h-2 rounded-full"
                        :style="{ width: unit.completion + '%' }"
                      ></div>
                    </div>
                    <span class="text-sm font-medium">{{ unit.completion }}%</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-900 dark:to-blue-800 rounded-xl p-6 text-white">
          <h3 class="text-lg font-semibold mb-2">Proses Payroll Bulan Ini</h3>
          <p class="text-blue-100 text-sm mb-4">Generate payroll untuk 1,247 karyawan</p>
          <button
            class="bg-white text-blue-600 px-4 py-2 rounded-lg font-medium hover:bg-blue-50 transition-colors"
          >
            Proses Sekarang
          </button>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-900 dark:to-purple-800 rounded-xl p-6 text-white">
          <h3 class="text-lg font-semibold mb-2">Laporan Konsolidasi</h3>
          <p class="text-purple-100 text-sm mb-4">Download laporan gabungan seluruh unit</p>
          <button
            class="bg-white text-purple-600 px-4 py-2 rounded-lg font-medium hover:bg-purple-50 transition-colors"
          >
            Download Reports
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
