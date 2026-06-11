<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import {
  ArrowLeft,
  FileText,
  Calendar,
  DollarSign,
  User,
} from "lucide-vue-next";
import {
  index as invoicesIndex,
  update as invoicesUpdate,
  exportEfakturCsv,
} from "@/actions/App/Http/Controllers/InvoiceController";
import { Button } from "@/components/ui/button";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  invoice: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Finance", href: "#" },
  { title: "Invoices", href: invoicesIndex().url },
  { title: props.invoice.invoice_no, href: "#" },
];
</script>

<template>
  <Head :title="`Invoice ${invoice.invoice_no}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="outline" size="icon" as-child>
            <Link :href="invoicesIndex().url">
              <ArrowLeft class="h-4 w-4" />
            </Link>
          </Button>
          <div>
            <div class="flex items-center gap-3">
              <h1
                class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"
              >
                {{ invoice.invoice_no }}
              </h1>
              <StatusBadge :status="invoice.status" />
            </div>
            <p class="text-sm text-zinc-500">Invoice details</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" as-child>
            <a
              :href="exportEfakturCsv({ invoice: invoice.id }).url"
              target="_blank"
            >
              Export e‑Faktur CSV
            </a>
          </Button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
          <div
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm p-6"
          >
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
              <FileText class="h-5 w-5" />
              Invoice Details
            </h2>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <div class="text-sm text-zinc-500">Invoice Number</div>
                <div class="font-medium">{{ invoice.invoice_no }}</div>
              </div>
              <div>
                <div class="text-sm text-zinc-500">Client</div>
                <div class="font-medium">{{ invoice.client?.name || "-" }}</div>
              </div>
              <div>
                <div class="text-sm text-zinc-500">Invoice Date</div>
                <div class="font-medium">{{ invoice.invoice_date }}</div>
              </div>
              <div>
                <div class="text-sm text-zinc-500">Due Date</div>
                <div class="font-medium">{{ invoice.due_date }}</div>
              </div>
            </div>

            <div
              class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-800"
            >
              <div class="text-sm text-zinc-500 mb-2">Notes</div>
              <div class="text-sm">{{ invoice.notes || "No notes" }}</div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm p-6"
          >
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
              <DollarSign class="h-5 w-5" />
              Payment Summary
            </h2>

            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-zinc-500">Subtotal</span>
                <span class="font-medium">{{
                  formatCurrency(invoice.amount)
                }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-zinc-500">Tax</span>
                <span class="font-medium">{{
                  formatCurrency(invoice.tax_amount)
                }}</span>
              </div>
              <div
                class="flex justify-between pt-3 border-t border-zinc-200 dark:border-zinc-800"
              >
                <span class="font-semibold">Total</span>
                <span class="font-bold text-lg">{{
                  formatCurrency(invoice.total_amount)
                }}</span>
              </div>
            </div>
          </div>

          <div
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm p-6"
          >
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
              <User class="h-5 w-5" />
              Organization
            </h2>

            <div class="space-y-3">
              <div>
                <div class="text-sm text-zinc-500">Unit</div>
                <div class="font-medium">
                  {{ invoice.unit?.name || invoice.organization?.name || "-" }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
