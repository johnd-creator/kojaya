<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Save } from "lucide-vue-next";
import { computed, ref } from "vue";
import {
  index as invoicesIndex,
  store as invoicesStore,
} from "@/actions/App/Http/Controllers/InvoiceController";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  clients: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Finance", href: "#" },
  { title: "Invoices", href: invoicesIndex().url },
  { title: "New Invoice", href: "#" },
];

const form = useForm({
  client_id: "",
  invoice_no: "",
  invoice_date: new Date().toISOString().split("T")[0],
  due_date: "",
  amount: 0,
  tax_rate: 0.11,
  notes: "",
});

const calculatedTax = computed(() => {
  return form.amount * form.tax_rate;
});

const calculatedTotal = computed(() => {
  return form.amount + calculatedTax.value;
});

const submit = () => {
  form.post(invoicesStore().url);
};
</script>

<template>
  <Head title="New Invoice" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
      <div class="flex items-center gap-4">
        <Button variant="outline" size="icon" as-child>
          <Link :href="invoicesIndex().url">
            <ArrowLeft class="h-4 w-4" />
          </Link>
        </Button>
        <div>
          <h1
            class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            New Invoice
          </h1>
          <p class="text-sm text-zinc-500">
            Create a new invoice for a client.
          </p>
        </div>
      </div>

      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm p-6"
      >
        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <div class="grid gap-2">
                <Label for="invoice_no">Invoice Number</Label>
                <Input
                  id="invoice_no"
                  v-model="form.invoice_no"
                  placeholder="INV-2024-001"
                  required
                />
                <span
                  v-if="form.errors.invoice_no"
                  class="text-xs text-red-500"
                  >{{ form.errors.invoice_no }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="client_id">Client</Label>
                <select
                  id="client_id"
                  v-model="form.client_id"
                  required
                  class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option value="" disabled>Select client...</option>
                  <option
                    v-for="client in clients"
                    :key="client.id"
                    :value="client.id"
                  >
                    {{ client.name }}
                  </option>
                </select>
                <span
                  v-if="form.errors.client_id"
                  class="text-xs text-red-500"
                  >{{ form.errors.client_id }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="invoice_date">Invoice Date</Label>
                <Input
                  id="invoice_date"
                  type="date"
                  v-model="form.invoice_date"
                  required
                />
                <span
                  v-if="form.errors.invoice_date"
                  class="text-xs text-red-500"
                  >{{ form.errors.invoice_date }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label for="due_date">Due Date</Label>
                <Input
                  id="due_date"
                  type="date"
                  v-model="form.due_date"
                  required
                />
                <span
                  v-if="form.errors.due_date"
                  class="text-xs text-red-500"
                  >{{ form.errors.due_date }}</span
                >
              </div>
            </div>

            <div class="space-y-4">
              <div class="grid gap-2">
                <Label for="amount">Amount (IDR)</Label>
                <Input
                  id="amount"
                  type="number"
                  v-model="form.amount"
                  min="0"
                  step="1000"
                  required
                />
                <span v-if="form.errors.amount" class="text-xs text-red-500">{{
                  form.errors.amount
                }}</span>
              </div>

              <div class="grid gap-2">
                <Label for="tax_rate">Tax Rate (%)</Label>
                <Input
                  id="tax_rate"
                  type="number"
                  v-model="form.tax_rate"
                  min="0"
                  max="100"
                  step="0.1"
                  required
                />
                <span
                  v-if="form.errors.tax_rate"
                  class="text-xs text-red-500"
                  >{{ form.errors.tax_rate }}</span
                >
              </div>

              <div class="grid gap-2">
                <Label>Tax Amount</Label>
                <div class="text-lg font-medium text-zinc-900 dark:text-white">
                  {{ formatCurrency(calculatedTax) }}
                </div>
              </div>

              <div class="grid gap-2">
                <Label>Total Amount</Label>
                <div class="text-xl font-bold text-zinc-900 dark:text-white">
                  {{ formatCurrency(calculatedTotal) }}
                </div>
              </div>
            </div>
          </div>

          <div class="grid gap-2">
            <Label for="notes">Notes (Optional)</Label>
            <textarea
              id="notes"
              v-model="form.notes"
              rows="3"
              class="flex w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
              placeholder="Invoice notes..."
            ></textarea>
            <span v-if="form.errors.notes" class="text-xs text-red-500">{{
              form.errors.notes
            }}</span>
          </div>

          <div
            class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800"
          >
            <Button type="button" variant="outline" as-child>
              <Link :href="invoicesIndex().url">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
              <Save class="h-4 w-4 mr-2" />
              <span v-if="form.processing">Creating...</span>
              <span v-else>Create Invoice</span>
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
