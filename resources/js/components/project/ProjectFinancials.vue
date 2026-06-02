<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import {
  DollarSign,
  TrendingUp,
  TrendingDown,
  Wallet,
  CreditCard,
  PieChart,
  AlertCircle,
  ArrowUpRight,
  ArrowDownRight,
  Users,
  Plus,
} from "lucide-vue-next";
import { ref, onMounted } from "vue";
import { fetchProjectFinancials } from "@/api/projectFinancials";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { formatCurrency } from "@/lib/formatters";

const props = defineProps<{
  projectId: string;
}>();

const loading = ref(true);
const summary = ref<any>(null);
const budgetAnalysis = ref<any>(null);
const transactions = ref<any[]>([]);

const fetchData = async () => {
  loading.value = true;
  try {
    const data = await fetchProjectFinancials(props.projectId);
    summary.value = data.summary;
    budgetAnalysis.value = data.budgetAnalysis;
    transactions.value = data.transactions;
  } catch (error) {
    console.error("Failed to fetch financial data", error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div v-if="loading" class="flex justify-center items-center py-12">
    <div
      class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"
    ></div>
  </div>

  <div v-else class="space-y-6">
    <!-- Top Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <Card class="border-l-4 border-l-emerald-500">
        <CardContent class="p-6">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Total Revenue
              </p>
              <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mt-2">
                {{ formatCurrency(summary?.revenue || 0) }}
              </h3>
            </div>
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
              <TrendingUp
                class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
              />
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm">
            <span class="text-zinc-500">From paid invoices</span>
          </div>
        </CardContent>
      </Card>

      <Card class="border-l-4 border-l-rose-500">
        <CardContent class="p-6">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Total Cost
              </p>
              <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mt-2">
                {{ formatCurrency(summary?.cogs || 0) }}
              </h3>
            </div>
            <div class="p-2 bg-rose-100 dark:bg-rose-900/30 rounded-lg">
              <TrendingDown class="h-5 w-5 text-rose-600 dark:text-rose-400" />
            </div>
          </div>
          <div class="mt-4 flex items-center gap-2 text-sm text-zinc-500">
            <span class="flex items-center gap-1"
              ><Wallet class="h-3 w-3" />
              {{
                formatCurrency(
                  summary?.cost_breakdown?.reimbursements +
                    summary?.cost_breakdown?.petty_cash,
                )
              }}
              Exp</span
            >
            <span class="flex items-center gap-1"
              ><Users class="h-3 w-3" />
              {{ formatCurrency(summary?.cost_breakdown?.labor) }} Labor</span
            >
          </div>
        </CardContent>
      </Card>

      <Card class="border-l-4 border-l-indigo-500">
        <CardContent class="p-6">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Gross Profit
              </p>
              <h3
                class="text-2xl font-bold text-zinc-900 dark:text-white mt-2"
                :class="
                  summary?.gross_profit >= 0
                    ? 'text-emerald-600'
                    : 'text-rose-600'
                "
              >
                {{ formatCurrency(summary?.gross_profit || 0) }}
              </h3>
            </div>
            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
              <DollarSign
                class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
              />
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm">
            <Badge
              :variant="
                summary?.gross_margin >= 20
                  ? 'default'
                  : summary?.gross_margin > 0
                    ? 'secondary'
                    : 'destructive'
              "
            >
              {{ summary?.gross_margin }}% Margin
            </Badge>
          </div>
        </CardContent>
      </Card>

      <Card class="border-l-4 border-l-amber-500">
        <CardContent class="p-6">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                Budget Usage
              </p>
              <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mt-2">
                {{ Math.round(budgetAnalysis?.percentage_used || 0) }}%
              </h3>
            </div>
            <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
              <PieChart class="h-5 w-5 text-amber-600 dark:text-amber-400" />
            </div>
          </div>
          <div class="mt-4">
            <Progress
              :model-value="budgetAnalysis?.percentage_used || 0"
              class="h-2"
              :class="{
                'bg-rose-200': (budgetAnalysis?.percentage_used || 0) > 100,
              }"
            />
            <p class="text-xs text-zinc-500 mt-1">
              {{ formatCurrency(budgetAnalysis?.total_actual || 0) }} /
              {{ formatCurrency(budgetAnalysis?.total_budget || 0) }}
            </p>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Budget vs Actual Table -->
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle>Budget Breakdown</CardTitle>
          <CardDescription
            >Planned vs Actual expenses per category</CardDescription
          >
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead
                class="text-zinc-500 bg-zinc-50 dark:bg-zinc-800/50 uppercase text-xs"
              >
                <tr>
                  <th class="px-4 py-3 rounded-l-lg">Category</th>
                  <th class="px-4 py-3 text-right">Planned</th>
                  <th class="px-4 py-3 text-right">Actual</th>
                  <th class="px-4 py-3 text-right rounded-r-lg">Variance</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <tr v-for="item in budgetAnalysis?.items" :key="item.id">
                  <td class="px-4 py-3 font-medium">{{ item.category }}</td>
                  <td class="px-4 py-3 text-right">
                    {{ formatCurrency(item.planned_amount) }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    {{ formatCurrency(item.actual_amount) }}
                  </td>
                  <td
                    class="px-4 py-3 text-right"
                    :class="
                      item.planned_amount - item.actual_amount >= 0
                        ? 'text-emerald-600'
                        : 'text-rose-600'
                    "
                  >
                    {{
                      formatCurrency(item.planned_amount - item.actual_amount)
                    }}
                  </td>
                </tr>
                <tr v-if="!budgetAnalysis?.items?.length">
                  <td colspan="4" class="px-4 py-8 text-center text-zinc-500">
                    No budget items defined yet.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Recent Transactions -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>Recent Transactions</CardTitle>
              <CardDescription>Latest financial activities</CardDescription>
            </div>
            <div class="flex gap-2">
              <Button size="sm" variant="outline" as-child>
                <Link :href="`/invoices/create?project_id=${projectId}`">
                  <Plus class="h-3 w-3 mr-1" />
                  Invoice
                </Link>
              </Button>
              <Button size="sm" variant="outline" as-child>
                <Link :href="`/reimbursements/create?project_id=${projectId}`">
                  <Plus class="h-3 w-3 mr-1" />
                  Reimbursement
                </Link>
              </Button>
              <Button size="sm" variant="outline" as-child>
                <Link :href="`/petty-cash`">
                  <Plus class="h-3 w-3 mr-1" />
                  Petty Cash
                </Link>
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div
              v-for="(tx, index) in transactions"
              :key="index"
              class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-100 dark:border-zinc-800"
            >
              <div class="flex items-center gap-3">
                <div
                  class="p-2 rounded-full"
                  :class="
                    tx.category === 'REVENUE'
                      ? 'bg-emerald-100 text-emerald-600'
                      : 'bg-rose-100 text-rose-600'
                  "
                >
                  <ArrowDownRight
                    v-if="tx.category === 'REVENUE'"
                    class="h-4 w-4"
                  />
                  <ArrowUpRight v-else class="h-4 w-4" />
                </div>
                <div>
                  <p
                    class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate max-w-[120px]"
                    :title="tx.description"
                  >
                    {{ tx.description || tx.type }}
                  </p>
                  <p class="text-xs text-zinc-500">
                    {{ tx.date }} • {{ tx.reference }}
                  </p>
                </div>
              </div>
              <div class="text-right">
                <p
                  class="text-sm font-bold"
                  :class="
                    tx.category === 'REVENUE'
                      ? 'text-emerald-600'
                      : 'text-rose-600'
                  "
                >
                  {{ tx.category === "REVENUE" ? "+" : "-"
                  }}{{ formatCurrency(tx.amount) }}
                </p>
                <Badge variant="outline" class="text-[10px] h-5 px-1.5">{{
                  tx.status
                }}</Badge>
              </div>
            </div>

            <div
              v-if="!transactions.length"
              class="text-center py-8 text-zinc-500"
            >
              No transactions found.
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
