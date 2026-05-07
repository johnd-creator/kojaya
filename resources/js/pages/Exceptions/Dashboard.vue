<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  AlertTriangle,
  Clock,
  FileX,
  BookOpen,
  ShoppingCart,
  Users,
} from "lucide-vue-next";

interface ExceptionCounts {
  overdue_loan_count: number;
  unpaid_dues_count: number;
  pending_payment_count: number;
  pending_loan_count: number;
}

interface FinanceExceptionCounts {
  pending_reimbursement_count: number;
  pending_payroll_approval_count: number;
  unreconciled_payment_count: number;
}

interface ProcurementExceptionCounts {
  pr_without_po_count: number;
  po_overdue_count: number;
  pr_pending_approval_count: number;
}

interface Summary {
  cooperative: ExceptionCounts;
  finance: FinanceExceptionCounts;
  procurement: ProcurementExceptionCounts;
  hr: { pending_leave_count: number; pending_overtime_count: number };
}

interface AllExceptions {
  cooperative: Record<string, any[]>;
  finance: Record<string, any[]>;
  procurement: Record<string, any[]>;
  hr: Record<string, any[]>;
  summary: Summary;
}

const data = ref<AllExceptions | null>(null);
const activeTab = ref<"cooperative" | "finance" | "procurement" | "hr">("cooperative");
const loading = ref(false);

onMounted(fetchData);

async function fetchData() {
  loading.value = true;
  try {
    const res = await fetch("/exceptions/data");
    const json = await res.json();
    data.value = json.data;
  } finally {
    loading.value = false;
  }
}

const tabs = [
  { key: "cooperative", label: "Koperasi", icon: BookOpen },
  { key: "finance", label: "Keuangan", icon: Clock },
  { key: "procurement", label: "Procurement", icon: ShoppingCart },
  { key: "hr", label: "HR", icon: Users },
] as const;

const summary = computed(() => data.value?.summary);
const current = computed(() => data.value?.[activeTab.value] ?? {});
const currentSummary = computed(() => summary.value?.[activeTab.value] ?? {});

function totalExceptions(module: string): number {
  const s = summary.value?.[module as keyof Summary];
  if (!s) return 0;
  return Object.values(s).reduce((a, b) => a + (b as number), 0);
}

function formatRupiah(v: number): string {
  return "Rp " + v.toLocaleString("id-ID");
}
</script>

<template>
  <AppLayout>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Laporan Exception Lintas Modul</h1>
      <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
        Dashboard overdue, pending approval, dan anomali dari seluruh modul
      </p>

      <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card v-for="tab in tabs" :key="tab.key" class="cursor-pointer transition-shadow hover:shadow-md" :class="activeTab === tab.key ? 'ring-2 ring-blue-500' : ''" @click="activeTab = tab.key">
          <CardHeader class="pb-2">
            <CardTitle class="text-sm flex items-center gap-2">
              <component :is="tab.icon" class="h-4 w-4 text-zinc-400" />
              {{ tab.label }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-zinc-900 dark:text-white">
              {{ loading ? "..." : totalExceptions(tab.key) }}
            </div>
            <p class="text-xs text-zinc-500 mt-1">item perlu perhatian</p>
          </CardContent>
        </Card>
      </div>

      <Card class="mt-6" v-if="currentSummary">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <AlertTriangle class="h-5 w-5 text-amber-500" />
            {{ tabs.find(t => t.key === activeTab)?.label }} — Ringkasan
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="(v, k) in currentSummary" :key="k" class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
              <span class="text-sm text-zinc-600 dark:text-zinc-400 capitalize">{{ k.replace(/_/g, " ") }}</span>
              <Badge :variant="v > 0 ? 'destructive' : 'secondary'">{{ v }}</Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      <div v-if="loading" class="mt-6 text-center text-zinc-500">Memuat data...</div>
      <div v-else class="mt-6 text-sm text-zinc-500 dark:text-zinc-400 border-t border-zinc-200 dark:border-zinc-800 pt-4">
        Data di-refresh saat halaman dimuat. Gunakan endpoint <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1 rounded">/exceptions/{module}</code> untuk detail per modul.
      </div>
    </div>
  </AppLayout>
</template>
