<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";

const props = defineProps<{ po: any }>();

const grnForm = useForm({});
const createGrnDialogOpen = ref(false);
function createGrn() {
  grnForm.post(`/procurement/grns/from-po/${props.po.id}`, {
    onFinish: () => {
      createGrnDialogOpen.value = false;
    },
  });
}

function printPreview() {
  window.print();
}
</script>

<template>
  <Head :title="`PO ${props.po.po_no ?? props.po.id}`" />
  <AppLayout>
    <div class="p-6 max-w-5xl mx-auto space-y-6">
      <!-- Toolbar -->
      <div class="flex items-center justify-between print:hidden">
        <h1 class="text-2xl font-semibold">Purchase Order Details</h1>
        <div class="flex gap-2">
          <button
            class="px-4 py-2 rounded-md bg-white border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-200 font-medium transition-colors shadow-sm flex items-center gap-2"
            @click="printPreview"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="lucide lucide-printer"
            >
              <polyline points="6 9 6 2 18 2 18 9" />
              <path
                d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"
              />
              <rect width="12" height="8" x="6" y="14" />
            </svg>
            Print
          </button>
          <button
            v-if="props.po.status === 'ISSUED'"
            class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition-colors shadow-sm"
            @click="createGrnDialogOpen = true"
            :disabled="grnForm.processing"
          >
            Receive Goods (GRN)
          </button>
        </div>
      </div>

      <!-- PO Document -->
      <div
        class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-8 shadow-sm print:shadow-none print:border-0"
      >
        <!-- Header -->
        <div
          class="flex justify-between items-start border-b border-zinc-200 dark:border-zinc-800 pb-6 mb-6"
        >
          <div>
            <div
              class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 mb-1"
            >
              PURCHASE ORDER
            </div>
            <div class="text-sm text-zinc-500">
              <span class="font-medium">Date:</span>
              {{ formatDate(props.po.issued_at) }}<br />
              <span class="font-medium">PO #:</span>
              {{ props.po.po_no ?? "DRAFT" }}
            </div>
          </div>
          <div class="text-right">
            <div class="font-bold text-lg">ERP Company Inc.</div>
            <div class="text-sm text-zinc-500">
              123 Business Park<br />
              Jakarta, Indonesia 12000<br />
              NPWP: 01.234.567.8-901.000
            </div>
          </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-2 gap-8 mb-8">
          <div>
            <div
              class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1"
            >
              Vendor
            </div>
            <div class="font-medium">PT. Vendor Sejahtera (Mock)</div>
            <div class="text-sm text-zinc-500">
              Jl. Supplier No. 88<br />
              Surabaya, Indonesia
            </div>
          </div>
          <div>
            <div
              class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1"
            >
              Ship To
            </div>
            <div class="font-medium">Warehouse Pusat</div>
            <div class="text-sm text-zinc-500">
              Kawasan Industri Pulogadung<br />
              Jakarta Timur
            </div>
          </div>
        </div>

        <!-- Items Table -->
        <table class="w-full text-sm text-left mb-8">
          <thead
            class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 font-medium border-y border-zinc-200 dark:border-zinc-800"
          >
            <tr>
              <th class="px-4 py-3">Description</th>
              <th class="px-4 py-3 text-right">Qty</th>
              <th class="px-4 py-3 text-right">Unit Price</th>
              <th class="px-4 py-3 text-right">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
            <tr v-for="it in props.po.items" :key="it.id">
              <td class="px-4 py-3">{{ it.description }}</td>
              <td class="px-4 py-3 text-right">{{ it.qty }}</td>
              <td class="px-4 py-3 text-right">
                {{ formatCurrency(it.price) }}
              </td>
              <td class="px-4 py-3 text-right font-medium">
                {{ formatCurrency(it.amount) }}
              </td>
            </tr>
          </tbody>
          <tfoot class="border-t border-zinc-200 dark:border-zinc-800">
            <tr>
              <td colspan="3" class="px-4 py-3 text-right font-bold">
                Subtotal
              </td>
              <td class="px-4 py-3 text-right font-medium">
                {{ formatCurrency(props.po.total_amount) }}
              </td>
            </tr>
            <tr>
              <td colspan="3" class="px-4 py-3 text-right font-bold text-lg">
                Total
              </td>
              <td
                class="px-4 py-3 text-right font-bold text-lg text-indigo-600"
              >
                {{ formatCurrency(props.po.total_amount) }}
              </td>
            </tr>
          </tfoot>
        </table>

        <!-- Footer -->
        <div
          class="grid grid-cols-2 gap-8 mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-800"
        >
          <div>
            <div
              class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2"
            >
              Notes
            </div>
            <p class="text-sm text-zinc-500 italic">
              Please include PO number on all invoices and shipping documents.
            </p>
          </div>
          <div>
            <div
              class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-8"
            >
              Authorized Signature
            </div>
            <div
              class="border-b border-zinc-300 dark:border-zinc-700 w-3/4"
            ></div>
          </div>
        </div>
      </div>

      <ConfirmDialog
        v-model:open="createGrnDialogOpen"
        title="Buat GRN"
        message="Apakah Anda yakin ingin membuat Goods Receive Note dari purchase order ini?"
        confirm-label="Buat GRN"
        @confirm="createGrn"
      />
    </div>
  </AppLayout>
</template>
