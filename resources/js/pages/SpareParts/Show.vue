<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { useForm } from "@inertiajs/vue3";
import {
  ArrowLeft,
  Package,
  AlertTriangle,
  Home,
  TrendingUp,
} from "lucide-vue-next";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

interface Stock {
  id: string;
  warehouse: {
    id: string;
    name: string;
    code: string;
  };
  quantity: number;
  reserved_quantity: number;
}

interface SparePart {
  id: string;
  code: string;
  name: string;
  specification: string | null;
  unit: string;
  category: string | null;
  min_stock: number;
  max_stock: number;
  reorder_level: number;
  total_stock: number;
  available_stock: number;
  is_below_min: boolean;
  is_below_reorder: boolean;
  organization?: {
    id: string;
    name: string;
    code: string;
  };
  stocks: Stock[];
}

interface Props {
  sparePart: SparePart;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Storage", href: "#" },
  { title: "Spare Parts", href: "/spare-parts" },
  { title: props.sparePart.name, href: `/spare-parts/${props.sparePart.id}` },
];

const stockForm = useForm({
  warehouse_id: "",
  quantity: 0,
  type: "IN",
  notes: "",
});

const submitStockUpdate = () => {
  stockForm.post(`/spare-parts/${props.sparePart.id}/stock`, {
    preserveScroll: true,
    onSuccess: () => {
      stockForm.reset();
    },
  });
};

const getStockColor = () => {
  if (props.sparePart.is_below_min) {
    return "bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400";
  }
  if (props.sparePart.is_below_reorder) {
    return "bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400";
  }
  return "bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400";
};

const getStockBadge = () => {
  if (props.sparePart.is_below_min) {
    return "Low Stock";
  }
  if (props.sparePart.is_below_reorder) {
    return "Reorder";
  }
  return "OK";
};
</script>

<template>
  <Head :title="sparePart.name" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full"
    >
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link href="/spare-parts">
          <Button variant="ghost" size="icon" class="h-8 w-8">
            <ArrowLeft class="h-4 w-4" />
          </Button>
        </Link>
        <div class="flex-1">
          <div class="flex items-center gap-3">
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
            >
              {{ sparePart.name }}
            </h1>
            <div class="flex items-center gap-2">
              <div
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border"
                :class="getStockColor()"
              >
                {{ getStockBadge() }}
              </div>
              <AlertTriangle
                v-if="sparePart.is_below_min"
                class="h-4 w-4 text-red-500"
              />
            </div>
          </div>
          <p class="text-zinc-500 mt-1">
            Code: <span class="font-mono">{{ sparePart.code }}</span>
            <span v-if="sparePart.specification" class="ml-3"
              >| {{ sparePart.specification }}</span
            >
          </p>
        </div>
      </div>

      <!-- Details Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Stock Overview -->
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
        >
          <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
              <Package class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
            </div>
            <h3 class="font-semibold text-zinc-900 dark:text-white">
              Stock Overview
            </h3>
          </div>
          <div class="space-y-4">
            <div>
              <p class="text-sm text-zinc-500">Current Stock</p>
              <p class="text-2xl font-bold text-zinc-900 dark:text-white">
                {{ sparePart.available_stock }} {{ sparePart.unit }}
              </p>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-xs text-zinc-500">Min Stock</p>
                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                  {{ sparePart.min_stock }}
                </p>
              </div>
              <div>
                <p class="text-xs text-zinc-500">Max Stock</p>
                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                  {{ sparePart.max_stock }}
                </p>
              </div>
              <div>
                <p class="text-xs text-zinc-500">Reorder Level</p>
                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                  {{ sparePart.reorder_level }}
                </p>
              </div>
              <div>
                <p class="text-xs text-zinc-500">Total Stock</p>
                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                  {{ sparePart.total_stock }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Part Details -->
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
        >
          <h3 class="font-semibold text-zinc-900 dark:text-white mb-4">
            Part Details
          </h3>
          <div class="space-y-3">
            <div>
              <p class="text-xs text-zinc-500">Category</p>
              <p class="text-sm font-medium text-zinc-900 dark:text-white">
                {{ sparePart.category || "-" }}
              </p>
            </div>
            <div>
              <p class="text-xs text-zinc-500">Unit of Measure</p>
              <p class="text-sm font-medium text-zinc-900 dark:text-white">
                {{ sparePart.unit }}
              </p>
            </div>
            <div v-if="sparePart.organization">
              <p class="text-xs text-zinc-500">Organization</p>
              <p class="text-sm font-medium text-zinc-900 dark:text-white">
                {{ sparePart.organization.name }} ({{
                  sparePart.organization.code
                }})
              </p>
            </div>
          </div>
        </div>

        <!-- Stock Update -->
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
        >
          <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
              <TrendingUp
                class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
              />
            </div>
            <h3 class="font-semibold text-zinc-900 dark:text-white">
              Update Stock
            </h3>
          </div>
          <form @submit.prevent="submitStockUpdate" class="space-y-4">
            <div class="space-y-2">
              <Label for="warehouse">Warehouse</Label>
              <select
                id="warehouse"
                v-model="stockForm.warehouse_id"
                :disabled="stockForm.processing"
                class="flex h-10 w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm"
                :class="{ 'border-red-500': stockForm.errors.warehouse_id }"
                required
              >
                <option value="">Select Warehouse</option>
                <option
                  v-for="stock in sparePart.stocks"
                  :key="stock.warehouse.id"
                  :value="stock.warehouse.id"
                >
                  {{ stock.warehouse.name }} ({{ stock.warehouse.code }})
                </option>
              </select>
              <p
                v-if="stockForm.errors.warehouse_id"
                class="text-sm text-red-500"
              >
                {{ stockForm.errors.warehouse_id }}
              </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="quantity">Quantity</Label>
                <Input
                  id="quantity"
                  v-model.number="stockForm.quantity"
                  type="number"
                  min="0"
                  :disabled="stockForm.processing"
                  :class="{ 'border-red-500': stockForm.errors.quantity }"
                  required
                />
                <p
                  v-if="stockForm.errors.quantity"
                  class="text-sm text-red-500"
                >
                  {{ stockForm.errors.quantity }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="type">Type</Label>
                <select
                  id="type"
                  v-model="stockForm.type"
                  :disabled="stockForm.processing"
                  class="flex h-10 w-full rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-3 py-2 text-sm"
                  :class="{ 'border-red-500': stockForm.errors.type }"
                  required
                >
                  <option value="IN">In</option>
                  <option value="OUT">Out</option>
                  <option value="ADJUST">Adjust</option>
                </select>
                <p v-if="stockForm.errors.type" class="text-sm text-red-500">
                  {{ stockForm.errors.type }}
                </p>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="notes">Notes</Label>
              <Input
                id="notes"
                v-model="stockForm.notes"
                placeholder="Optional notes"
                :disabled="stockForm.processing"
              />
            </div>

            <Button
              type="submit"
              :disabled="stockForm.processing"
              class="w-full bg-emerald-600 hover:bg-emerald-700 text-white"
            >
              {{ stockForm.processing ? "Updating..." : "Update Stock" }}
            </Button>
          </form>
        </div>
      </div>

      <!-- Warehouse Stock -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
      >
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <Home class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          </div>
          <h3 class="font-semibold text-zinc-900 dark:text-white">
            Warehouse Stock
          </h3>
        </div>
        <div
          v-if="sparePart.stocks.length === 0"
          class="text-center py-8 text-zinc-500"
        >
          No stock records found in any warehouse.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-zinc-200 dark:border-zinc-800">
                <th class="py-3 px-4 font-medium text-sm text-zinc-500">
                  Warehouse
                </th>
                <th class="py-3 px-4 font-medium text-sm text-zinc-500">
                  Available
                </th>
                <th class="py-3 px-4 font-medium text-sm text-zinc-500">
                  Reserved
                </th>
                <th class="py-3 px-4 font-medium text-sm text-zinc-500">
                  Total
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
              <tr v-for="stock in sparePart.stocks" :key="stock.id">
                <td class="py-3 px-4">
                  <span class="font-medium text-zinc-900 dark:text-white">
                    {{ stock.warehouse.name }}
                  </span>
                  <span class="text-xs text-zinc-500 ml-2"
                    >({{ stock.warehouse.code }})</span
                  >
                </td>
                <td class="py-3 px-4">
                  <span
                    class="text-emerald-600 dark:text-emerald-400 font-medium"
                  >
                    {{ stock.quantity }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-amber-600 dark:text-amber-400 font-medium">
                    {{ stock.reserved_quantity }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  <span class="font-medium text-zinc-900 dark:text-white">
                    {{ stock.quantity + stock.reserved_quantity }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
