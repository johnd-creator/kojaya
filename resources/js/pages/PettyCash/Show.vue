<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import type { User } from "lucide-vue-next";
import {
  ArrowLeft,
  Wallet,
  TrendingUp,
  TrendingDown,
  FileText,
  Calendar,
  Download,
} from "lucide-vue-next";
import { ref } from "vue";
import { index } from "@/actions/App/Http/Controllers/PettyCashAccountController";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import TransactionModal from "./TransactionModal.vue";

interface User {
  id: number;
  name: string;
}

interface Transaction {
  id: string;
  transaction_date: string;
  type: "DEBIT" | "CREDIT";
  amount: number;
  description: string;
  reference_no?: string;
  status: string;
  proof_file?: string;
  user: User;
  created_at: string;
}

interface PettyCashAccount {
  id: string;
  name: string;
  balance: number;
  limit: number;
  status: string;
  description: string;
  organization: {
    id: string;
    name: string;
  };
  transactions: Transaction[];
}

const props = defineProps<{
  account: PettyCashAccount;
}>();

const breadcrumbs = [
  { title: "Finance", href: "#" },
  { title: "Petty Cash", href: index().url },
  { title: props.account.name, href: "#" },
];

const isTransactionModalOpen = ref(false);
</script>

<template>
  <Head :title="account.name" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full"
    >
      <!-- Header -->
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div class="flex items-center gap-4">
          <Link :href="index().url">
            <Button variant="outline" size="icon" class="h-10 w-10">
              <ArrowLeft class="h-4 w-4" />
            </Button>
          </Link>
          <div>
            <h1
              class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"
            >
              {{ account.name }}
            </h1>
            <p class="text-zinc-500 text-sm flex items-center gap-2 mt-1">
              <span class="font-medium text-indigo-600 dark:text-indigo-400">{{
                account.organization.name
              }}</span>
              <span>•</span>
              <span
                :class="
                  account.status === 'ACTIVE'
                    ? 'text-emerald-600'
                    : 'text-zinc-500'
                "
                >{{ account.status }}</span
              >
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <Button
            @click="isTransactionModalOpen = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white"
          >
            <Wallet class="h-4 w-4 mr-2" />
            Record Transaction
          </Button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm"
        >
          <p class="text-sm font-medium text-zinc-500">Current Balance</p>
          <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">
            {{ formatCurrency(account.balance) }}
          </p>
        </div>
        <div
          class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm"
        >
          <p class="text-sm font-medium text-zinc-500">Limit</p>
          <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">
            {{ formatCurrency(account.limit) }}
          </p>
        </div>
        <div
          class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm"
        >
          <p class="text-sm font-medium text-zinc-500">Available Limit</p>
          <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">
            {{ formatCurrency(account.limit - account.balance) }}
          </p>
          <p class="text-xs text-zinc-400 mt-1">
            Note: This logic depends on policy
          </p>
        </div>
      </div>

      <!-- Transactions Table -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden"
      >
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-800">
          <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
            Transaction History
          </h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr
                class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800"
              >
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider"
                >
                  Date
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider"
                >
                  Type
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider"
                >
                  Description
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider"
                >
                  Reference
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider text-right"
                >
                  Amount
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider"
                >
                  By
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider text-right"
                >
                  Proof
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
              <tr v-if="account.transactions.length === 0">
                <td colspan="7" class="py-12 text-center text-zinc-500">
                  No transactions recorded yet.
                </td>
              </tr>
              <tr
                v-for="trx in account.transactions"
                :key="trx.id"
                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
              >
                <td class="py-4 px-6 text-sm text-zinc-600 dark:text-zinc-400">
                  {{ formatDate(trx.transaction_date) }}
                </td>
                <td class="py-4 px-6">
                  <Badge
                    :variant="trx.type === 'DEBIT' ? 'default' : 'destructive'"
                    class="capitalize"
                  >
                    {{ trx.type === "DEBIT" ? "Debit" : "Credit" }}
                  </Badge>
                </td>
                <td
                  class="py-4 px-6 text-sm text-zinc-900 dark:text-zinc-100 max-w-xs truncate"
                >
                  {{ trx.description }}
                </td>
                <td class="py-4 px-6 text-sm text-zinc-500 font-mono">
                  {{ trx.reference_no || "-" }}
                </td>
                <td
                  class="py-4 px-6 text-right font-medium"
                  :class="
                    trx.type === 'DEBIT' ? 'text-emerald-600' : 'text-red-600'
                  "
                >
                  {{ trx.type === "DEBIT" ? "+" : "-" }}
                  {{ formatCurrency(trx.amount) }}
                </td>
                <td class="py-4 px-6 text-sm text-zinc-500">
                  {{ trx.user?.name || "Unknown" }}
                </td>
                <td class="py-4 px-6 text-right">
                  <a
                    v-if="trx.proof_file"
                    :href="'/storage/' + trx.proof_file"
                    target="_blank"
                    class="text-indigo-600 hover:text-indigo-800 text-sm inline-flex items-center"
                  >
                    <Download class="h-4 w-4" />
                  </a>
                  <span v-else class="text-zinc-400">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <TransactionModal
      :is-open="isTransactionModalOpen"
      :account-id="account.id"
      @close="isTransactionModalOpen = false"
    />
  </AppLayout>
</template>
