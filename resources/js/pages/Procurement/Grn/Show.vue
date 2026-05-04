<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import AppLayout from "@/layouts/AppLayout.vue";

const props = defineProps<{
  grn: any;
  poItems: any[];
  currentGrnItems: any[];
  allReceivedItems: any[];
}>();

const form = useForm({
  items: props.poItems.map((it) => ({
    po_item_id: it.id,
    received_qty: 0,
    condition: "Good",
  })),
});
const submitDialogOpen = ref(false);

const itemsWithRemaining = computed(() => {
  return props.poItems.map((item) => {
    // Total received from ALL GRNs (including this one if it's already processed)
    const totalReceived = props.allReceivedItems
      .filter((r) => r.purchase_order_item_id === item.id)
      .reduce((sum, r) => sum + r.received_qty, 0);

    // If this GRN is DRAFT, totalReceived doesn't include it yet.
    // If this GRN is PROCESSED, totalReceived includes it.

    return {
      ...item,
      received_total: totalReceived,
      remaining: Math.max(0, item.qty - totalReceived),
    };
  });
});

function submit() {
  form.post(`/procurement/grns/${props.grn.id}/receive`, {
    onFinish: () => {
      submitDialogOpen.value = false;
    },
  });
}

function receiveAll() {
  form.items.forEach((formItem) => {
    const originalItem = itemsWithRemaining.value.find(
      (i) => i.id === formItem.po_item_id,
    );
    if (originalItem) {
      formItem.received_qty = originalItem.remaining;
    }
  });
}
</script>

<template>
  <Head :title="`GRN ${props.grn.grn_no ?? props.grn.id}`" />
  <AppLayout>
    <div class="p-6 max-w-5xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold">Goods Receive Note</h1>
          <div class="text-sm text-zinc-500 mt-1">
            No: <span class="font-mono">{{ props.grn.grn_no ?? "-" }}</span> •
            Status:
            <span
              class="px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-700"
              >{{ props.grn.status }}</span
            >
          </div>
        </div>
      </div>

      <div
        v-if="props.grn.status !== 'RECEIVED_FULL'"
        class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 space-y-6"
      >
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-medium">Receive Items</h2>
          <button
            type="button"
            class="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
            @click="receiveAll"
          >
            Auto-fill Remaining
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead
              class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 font-medium border-b border-zinc-200 dark:border-zinc-800"
            >
              <tr>
                <th class="px-4 py-3">Item</th>
                <th class="px-4 py-3 text-right">Ordered</th>
                <th class="px-4 py-3 text-right">Total Received</th>
                <th class="px-4 py-3 text-right">Remaining</th>
                <th class="px-4 py-3 w-[150px]">Receive Qty</th>
                <th class="px-4 py-3 w-[200px]">Condition</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
              <tr v-for="formItem in form.items" :key="formItem.po_item_id">
                <td class="px-4 py-3 font-medium">
                  {{
                    itemsWithRemaining.find((x) => x.id === formItem.po_item_id)
                      ?.description
                  }}
                </td>
                <td class="px-4 py-3 text-right text-zinc-500">
                  {{
                    itemsWithRemaining.find((x) => x.id === formItem.po_item_id)
                      ?.qty
                  }}
                </td>
                <td class="px-4 py-3 text-right text-zinc-500">
                  {{
                    itemsWithRemaining.find((x) => x.id === formItem.po_item_id)
                      ?.received_total
                  }}
                </td>
                <td
                  class="px-4 py-3 text-right font-medium"
                  :class="{
                    'text-emerald-600':
                      itemsWithRemaining.find(
                        (x) => x.id === formItem.po_item_id,
                      )?.remaining === 0,
                  }"
                >
                  {{
                    itemsWithRemaining.find((x) => x.id === formItem.po_item_id)
                      ?.remaining
                  }}
                </td>
                <td class="px-4 py-3">
                  <input
                    type="number"
                    v-model.number="formItem.received_qty"
                    class="w-full border border-zinc-300 dark:border-zinc-700 rounded-md px-3 py-1.5 bg-transparent focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    min="0"
                    :max="
                      itemsWithRemaining.find(
                        (x) => x.id === formItem.po_item_id,
                      )?.remaining
                    "
                    :disabled="
                      itemsWithRemaining.find(
                        (x) => x.id === formItem.po_item_id,
                      )?.remaining === 0
                    "
                  />
                </td>
                <td class="px-4 py-3">
                  <select
                    v-model="formItem.condition"
                    class="w-full border border-zinc-300 dark:border-zinc-700 rounded-md px-3 py-1.5 bg-transparent focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    :disabled="formItem.received_qty === 0"
                  >
                    <option value="Good">Good</option>
                    <option value="Damaged">Damaged</option>
                    <option value="Wrong Item">Wrong Item</option>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-800"
        >
          <button
            class="px-6 py-2.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
            @click="submitDialogOpen = true"
            :disabled="form.processing"
          >
            <span v-if="form.processing">Processing...</span>
            <span v-else>Confirm Receipt</span>
          </button>
        </div>
      </div>

      <div
        class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6"
      >
        <h2 class="text-lg font-medium mb-4">
          Receipt History (All GRNs for this PO)
        </h2>
        <div
          v-if="props.allReceivedItems.length === 0"
          class="text-sm text-zinc-500 text-center py-8 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700"
        >
          No items received yet
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead
              class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 font-medium border-b border-zinc-200 dark:border-zinc-800"
            >
              <tr>
                <th class="px-4 py-3">Item</th>
                <th class="px-4 py-3 text-right">Received Qty</th>
                <th class="px-4 py-3">Condition</th>
                <th class="px-4 py-3 text-right">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
              <tr v-for="it in props.allReceivedItems" :key="it.id">
                <td class="px-4 py-3 font-medium">
                  {{
                    props.poItems.find(
                      (p) => p.id === it.purchase_order_item_id,
                    )?.description
                  }}
                </td>
                <td class="px-4 py-3 text-right font-medium">
                  {{ it.received_qty }}
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="{
                      'text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full text-xs':
                        it.condition === 'Good',
                      'text-red-600 bg-red-50 px-2 py-0.5 rounded-full text-xs':
                        it.condition !== 'Good',
                    }"
                  >
                    {{ it.condition }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right text-zinc-500">
                  {{ new Date(it.created_at).toLocaleDateString() }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <ConfirmDialog
        v-model:open="submitDialogOpen"
        title="Konfirmasi penerimaan barang"
        message="Apakah Anda yakin ingin memproses penerimaan barang pada GRN ini?"
        confirm-label="Konfirmasi"
        @confirm="submit"
      />
    </div>
  </AppLayout>
</template>
