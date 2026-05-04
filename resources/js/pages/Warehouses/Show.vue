<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ArrowLeft, Home, Package, MapPin } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

interface Stock {
  id: string;
  spare_part: {
    id: string;
    code: string;
    name: string;
    unit: string;
  };
  quantity: number;
  reserved_quantity: number;
}

interface Warehouse {
  id: string;
  code: string;
  name: string;
  location: string | null;
  type: "STORAGE" | "REPAIR" | "DISPOSAL";
  organization?: {
    id: string;
    name: string;
    code: string;
  };
  stocks: Stock[];
}

interface Props {
  warehouse: Warehouse;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Storage", href: "#" },
  { title: "Warehouses", href: "/warehouses" },
  { title: props.warehouse.name, href: `/warehouses/${props.warehouse.id}` },
];

const getTypeColor = (type: string) => {
  const colors = {
    STORAGE:
      "bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400",
    REPAIR:
      "bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400",
    DISPOSAL:
      "bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400",
  };
  return (
    colors[type as keyof typeof colors] ||
    "bg-zinc-50 text-zinc-700 border-zinc-200"
  );
};

const getTypeBadge = (type: string) => {
  const badges = {
    STORAGE: "Storage",
    REPAIR: "Repair",
    DISPOSAL: "Disposal",
  };
  return badges[type as keyof typeof badges] || type;
};

const totalItems = () => {
  return props.warehouse.stocks.length;
};

const totalQuantity = () => {
  return props.warehouse.stocks.reduce((sum, stock) => sum + stock.quantity, 0);
};
</script>

<template>
  <Head :title="warehouse.name" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full"
    >
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link href="/warehouses">
          <Button variant="ghost" size="icon" class="h-8 w-8">
            <ArrowLeft class="h-4 w-4" />
          </Button>
        </Link>
        <div class="flex-1">
          <div class="flex items-center gap-3">
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
            >
              {{ warehouse.name }}
            </h1>
            <div
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border"
              :class="getTypeColor(warehouse.type)"
            >
              {{ getTypeBadge(warehouse.type) }}
            </div>
          </div>
          <p class="text-zinc-500 mt-1">
            Code: <span class="font-mono">{{ warehouse.code }}</span>
            <span
              v-if="warehouse.location"
              class="ml-3 flex items-center gap-1"
            >
              <MapPin class="h-3 w-3" />
              {{ warehouse.location }}
            </span>
          </p>
        </div>
      </div>

      <!-- Details Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Warehouse Overview -->
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
        >
          <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
              <Home class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
            </div>
            <h3 class="font-semibold text-zinc-900 dark:text-white">
              Overview
            </h3>
          </div>
          <div class="space-y-4">
            <div>
              <p class="text-sm text-zinc-500">Total Items</p>
              <p class="text-2xl font-bold text-zinc-900 dark:text-white">
                {{ totalItems() }}
              </p>
            </div>
            <div>
              <p class="text-sm text-zinc-500">Total Quantity</p>
              <p class="text-2xl font-bold text-zinc-900 dark:text-white">
                {{ totalQuantity() }}
              </p>
            </div>
            <div v-if="warehouse.organization">
              <p class="text-sm text-zinc-500">Organization</p>
              <p class="text-sm font-medium text-zinc-900 dark:text-white">
                {{ warehouse.organization.name }}
              </p>
              <p class="text-xs text-zinc-500">
                {{ warehouse.organization.code }}
              </p>
            </div>
          </div>
        </div>

        <!-- Warehouse Details -->
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
        >
          <h3 class="font-semibold text-zinc-900 dark:text-white mb-4">
            Warehouse Details
          </h3>
          <div class="space-y-3">
            <div>
              <p class="text-xs text-zinc-500">Warehouse Code</p>
              <p
                class="text-sm font-medium text-zinc-900 dark:text-white font-mono"
              >
                {{ warehouse.code }}
              </p>
            </div>
            <div>
              <p class="text-xs text-zinc-500">Warehouse Name</p>
              <p class="text-sm font-medium text-zinc-900 dark:text-white">
                {{ warehouse.name }}
              </p>
            </div>
            <div>
              <p class="text-xs text-zinc-500">Type</p>
              <div
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border mt-1"
                :class="getTypeColor(warehouse.type)"
              >
                {{ getTypeBadge(warehouse.type) }}
              </div>
            </div>
            <div v-if="warehouse.location">
              <p class="text-xs text-zinc-500">Location</p>
              <p class="text-sm font-medium text-zinc-900 dark:text-white">
                {{ warehouse.location }}
              </p>
            </div>
          </div>
        </div>

        <!-- Statistics -->
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
        >
          <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
              <Package class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <h3 class="font-semibold text-zinc-900 dark:text-white">
              Inventory Stats
            </h3>
          </div>
          <div class="space-y-4">
            <div>
              <p class="text-sm text-zinc-500">Unique Parts</p>
              <p class="text-2xl font-bold text-zinc-900 dark:text-white">
                {{ warehouse.stocks.length }}
              </p>
            </div>
            <div>
              <p class="text-sm text-zinc-500">Reserved Items</p>
              <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                {{
                  warehouse.stocks.reduce(
                    (sum, stock) => sum + stock.reserved_quantity,
                    0,
                  )
                }}
              </p>
            </div>
            <div>
              <p class="text-sm text-zinc-500">Available Items</p>
              <p
                class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"
              >
                {{ totalQuantity() }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Stock Inventory -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-6"
      >
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <Package class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          </div>
          <h3 class="font-semibold text-zinc-900 dark:text-white">
            Current Inventory
          </h3>
        </div>
        <div
          v-if="warehouse.stocks.length === 0"
          class="text-center py-8 text-zinc-500"
        >
          No spare parts found in this warehouse.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-zinc-200 dark:border-zinc-800">
                <th class="py-3 px-4 font-medium text-sm text-zinc-500">
                  Part Code
                </th>
                <th class="py-3 px-4 font-medium text-sm text-zinc-500">
                  Part Name
                </th>
                <th class="py-3 px-4 font-medium text-sm text-zinc-500">
                  Unit
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
              <tr v-for="stock in warehouse.stocks" :key="stock.id">
                <td class="py-3 px-4">
                  <Link
                    :href="`/spare-parts/${stock.spare_part.id}`"
                    class="font-mono text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
                  >
                    {{ stock.spare_part.code }}
                  </Link>
                </td>
                <td class="py-3 px-4">
                  <Link
                    :href="`/spare-parts/${stock.spare_part.id}`"
                    class="text-sm font-medium text-zinc-900 dark:text-white hover:underline"
                  >
                    {{ stock.spare_part.name }}
                  </Link>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ stock.spare_part.unit }}
                  </span>
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
