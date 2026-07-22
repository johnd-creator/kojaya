<script setup lang="ts">
import { Chart as ChartJS } from "chart.js/auto";
import {
  TrendingUp,
  TrendingDown,
  AlertTriangle,
  CheckCircle,
  RefreshCw,
} from "lucide-vue-next";
import { computed, onMounted, ref, watch } from "vue";
import {
  fetchCertificateCompliance,
  fetchMcuCompliance,
} from "@/api/compliance";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { CertificateComplianceReport, McuComplianceReport } from "@/types";

type Props = {
  organizationId?: string;
  refreshInterval?: number;
};

const props = withDefaults(defineProps<Props>(), {
  refreshInterval: 300000, // 5 minutes default
});

const certificateReport = ref<CertificateComplianceReport | null>(null);
const mcuReport = ref<McuComplianceReport | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const lastRefresh = ref(new Date());

const certificateRate = computed(() => {
  if (!certificateReport.value) return 0;
  return certificateReport.value.compliance_rate;
});

const mcuRate = computed(() => {
  if (!mcuReport.value) return 0;
  return mcuReport.value.compliance_rate;
});

const overallCompliance = computed(() => {
  if (!certificateReport.value || !mcuReport.value) return 0;
  const total =
    certificateReport.value.total_employees + mcuReport.value.total_employees;
  const compliant =
    certificateReport.value.compliant_employees +
    mcuReport.value.compliant_employees;
  return total > 0 ? Math.round((compliant / total) * 100) : 0;
});

const getComplianceColor = (rate: number): string => {
  if (rate >= 90) return "text-green-600 bg-green-50 dark:bg-green-950";
  if (rate >= 70) return "text-yellow-600 bg-yellow-50 dark:bg-yellow-950";
  return "text-red-600 bg-red-50 dark:bg-red-950";
};

const getComplianceIcon = (rate: number) => {
  if (rate >= 90) return CheckCircle;
  if (rate >= 70) return AlertTriangle;
  return TrendingDown;
};

const certificatesChartRef = ref<HTMLCanvasElement | null>(null);
const mcuChartRef = ref<HTMLCanvasElement | null>(null);

let certificatesChart: ChartJS | null = null;
let mcuChart: ChartJS | null = null;

const fetchReports = async () => {
  loading.value = true;
  error.value = null;
  try {
    const [certResponse, mcuResponse] = await Promise.all([
      fetchCertificateCompliance({ organization_id: props.organizationId }),
      fetchMcuCompliance({ organization_id: props.organizationId }),
    ]);

    certificateReport.value = certResponse;
    mcuReport.value = mcuResponse;
    lastRefresh.value = new Date();

    if (certificatesChartRef.value && certificatesChart) {
      updateCertificatesChart();
    }

    if (mcuChartRef.value && mcuChart) {
      updateMcuChart();
    }
  } catch (err) {
    console.error("Failed to fetch compliance reports:", err);
    error.value = "Failed to load compliance data";
  } finally {
    loading.value = false;
  }
};

const initCharts = () => {
  // Initialize Certificate Compliance Chart
  if (certificatesChartRef.value) {
    const ctx = certificatesChartRef.value.getContext("2d");
    if (!ctx) return;
    certificatesChart = new ChartJS(ctx, {
      type: "doughnut",
      data: {
        labels: ["Valid", "Expiring", "Expired"],
        datasets: [
          {
            data: [0, 0, 0],
            backgroundColor: [
              "#10b981", // green
              "#f59e0b", // yellow
              "#ef4444", // red
            ],
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom" as const,
          },
        },
      },
    });
  }

  // Initialize MCU Compliance Chart
  if (mcuChartRef.value) {
    const ctx = mcuChartRef.value.getContext("2d");
    if (!ctx) return;
    mcuChart = new ChartJS(ctx, {
      type: "doughnut",
      data: {
        labels: ["Fit", "Fit with Restriction", "Unfit", "No MCU"],
        datasets: [
          {
            data: [0, 0, 0, 0],
            backgroundColor: [
              "#10b981", // green
              "#f59e0b", // yellow
              "#ef4444", // red
              "#9ca3af", // gray
            ],
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom" as const,
          },
        },
      },
    });
  }
};

const updateCertificatesChart = () => {
  if (!certificatesChart || !certificateReport.value) return;

  const valid = certificateReport.value.certificates.filter(
    (c) => c.status === "VALID",
  ).length;
  const expired = certificateReport.value.certificates.filter(
    (c) => c.status === "EXPIRED" || c.status === "REVOKED",
  ).length;
  const expiring = certificateReport.value.certificates.filter((c) => {
    if (c.status !== "VALID") return false;
    if (!c.expiry_date) return false;
    const expiryDate = new Date(c.expiry_date);
    const today = new Date();
    const daysUntilExpiry = Math.ceil(
      (expiryDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24),
    );
    return daysUntilExpiry > 0 && daysUntilExpiry <= 60;
  }).length;

  certificatesChart.data.datasets[0].data = [valid, expiring, expired];
  certificatesChart.update();
};

const updateMcuChart = () => {
  if (!mcuChart || !mcuReport.value) return;

  const fit = mcuReport.value.medical_checkups.filter(
    (m) => m.result === "FIT",
  ).length;
  const withRestriction = mcuReport.value.medical_checkups.filter(
    (m) => m.result === "FIT_WITH_RESTRICTION",
  ).length;
  const unfit = mcuReport.value.medical_checkups.filter(
    (m) => m.result === "UNFIT",
  ).length;
  const totalEmployees =
    certificateReport.value?.total_employees ?? mcuReport.value.total_employees;
  const noMcu = totalEmployees - mcuReport.value.total_employees;

  mcuChart.data.datasets[0].data = [fit, withRestriction, unfit, noMcu];
  mcuChart.update();
};

onMounted(() => {
  fetchReports();
  initCharts();

  if (props.refreshInterval > 0) {
    setInterval(fetchReports, props.refreshInterval);
  }
});

watch(
  () => props.organizationId,
  () => {
    fetchReports();
  },
);
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h3
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-100"
        >
          Compliance Dashboard
        </h3>
        <p class="text-sm text-neutral-500 dark:text-neutral-400">
          Track certificate and MCU compliance across the organization
        </p>
      </div>
      <Button
        variant="outline"
        size="sm"
        :disabled="loading"
        @click="fetchReports"
      >
        <RefreshCw :class="{ 'animate-spin': loading }" class="mr-2 h-4 w-4" />
        Refresh
      </Button>
    </div>

    <div
      v-if="error"
      class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-900/20"
    >
      <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    </div>

    <div v-else class="grid gap-6 md:grid-cols-2">
      <!-- Certificate Compliance Card -->
      <Card>
        <CardHeader>
          <CardTitle class="text-base">Certificate Compliance</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="loading" class="flex items-center justify-center py-8">
            <div
              class="h-8 w-8 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-neutral-100"
            />
          </div>

          <div v-else-if="certificateReport">
            <div class="flex items-center justify-center mb-6">
              <div class="text-center">
                <div
                  class="inline-flex items-center justify-center rounded-full p-3 mb-2"
                  :class="getComplianceColor(certificateRate)"
                >
                  <span class="contents"><component
                    :is="getComplianceIcon(certificateRate)"
                    class="h-8 w-8"
                  /></span>
                </div>
                <div
                  class="text-3xl font-bold text-neutral-900 dark:text-neutral-100"
                >
                  {{ certificateRate }}%
                </div>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                  Certificate Compliance
                </p>
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-neutral-600 dark:text-neutral-400"
                  >Total Employees:</span
                >
                <span
                  class="font-semibold text-neutral-900 dark:text-neutral-100"
                >
                  {{ certificateReport.total_employees }}
                </span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-neutral-600 dark:text-neutral-400"
                  >Compliant:</span
                >
                <span class="font-semibold text-green-600 dark:text-green-400">
                  {{ certificateReport.compliant_employees }}
                </span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-neutral-600 dark:text-neutral-400"
                  >Non-Compliant:</span
                >
                <span class="font-semibold text-red-600 dark:text-red-400">
                  {{ certificateReport.non_compliant_employees }}
                </span>
              </div>
            </div>

            <div
              class="mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-800"
            >
              <canvas ref="certificatesChartRef" class="h-48" />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- MCU Compliance Card -->
      <Card>
        <CardHeader>
          <CardTitle class="text-base">MCU Compliance</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="loading" class="flex items-center justify-center py-8">
            <div
              class="h-8 w-8 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-neutral-100"
            />
          </div>

          <div v-else-if="mcuReport">
            <div class="flex items-center justify-center mb-6">
              <div class="text-center">
                <div
                  class="inline-flex items-center justify-center rounded-full p-3 mb-2"
                  :class="getComplianceColor(mcuRate)"
                >
                  <span class="contents"><component :is="getComplianceIcon(mcuRate)" class="h-8 w-8" /></span>
                </div>
                <div
                  class="text-3xl font-bold text-neutral-900 dark:text-neutral-100"
                >
                  {{ mcuRate }}%
                </div>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                  MCU Compliance
                </p>
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-neutral-600 dark:text-neutral-400"
                  >Total Employees:</span
                >
                <span
                  class="font-semibold text-neutral-900 dark:text-neutral-100"
                >
                  {{ mcuReport.total_employees }}
                </span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-neutral-600 dark:text-neutral-400"
                  >Compliant:</span
                >
                <span class="font-semibold text-green-600 dark:text-green-400">
                  {{ mcuReport.compliant_employees }}
                </span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-neutral-600 dark:text-neutral-400"
                  >Non-Compliant:</span
                >
                <span class="font-semibold text-red-600 dark:text-red-400">
                  {{ mcuReport.non_compliant_employees }}
                </span>
              </div>
            </div>

            <div
              class="mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-800"
            >
              <canvas ref="mcuChartRef" class="h-48" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Overall Compliance Summary -->
    <Card>
      <CardHeader>
        <CardTitle class="text-base">Overall Compliance Summary</CardTitle>
      </CardHeader>
      <CardContent>
        <div v-if="loading" class="flex items-center justify-center py-4">
          <div
            class="h-6 w-6 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-900 dark:border-neutral-700 dark:border-t-neutral-100"
          />
        </div>

        <div v-else class="text-center">
          <div
            class="inline-flex items-center justify-center rounded-full p-4 mb-3"
            :class="getComplianceColor(overallCompliance)"
          >
            <span class="contents"><component
              :is="getComplianceIcon(overallCompliance)"
              class="h-10 w-10"
            /></span>
          </div>
          <div
            class="text-4xl font-bold text-neutral-900 dark:text-neutral-100"
          >
            {{ overallCompliance }}%
          </div>
          <p class="text-sm text-neutral-500 dark:text-neutral-400">
            Combined Certificate & MCU Compliance
          </p>
        </div>
      </CardContent>
    </Card>

    <div class="text-xs text-neutral-400 dark:text-neutral-600">
      Last updated: {{ lastRefresh.toLocaleTimeString() }}
    </div>
  </div>
</template>
