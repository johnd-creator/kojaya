<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";

const props = defineProps<{
  pr: any;
  approvalLogs: any[];
  flashBudgetDetails?: any;
}>();

const submitForm = useForm({});
const submitDialogOpen = ref(false);
function submitPr() {
  submitForm.post(`/procurement/purchase-requests/${props.pr.id}/submit`, {
    onFinish: () => {
      submitDialogOpen.value = false;
    },
  });
}

const approveForm = useForm<{ level: number; note?: string }>({
  level: 1,
  note: "",
});
const approveDialogOpen = ref(false);
const approveLevel = ref<number | null>(null);
function openApproveDialog(level: number) {
  approveLevel.value = level;
  approveDialogOpen.value = true;
}

function approve() {
  if (approveLevel.value === null) return;

  approveForm.level = approveLevel.value;
  approveForm.post(`/procurement/purchase-requests/${props.pr.id}/approve`, {
    onFinish: () => {
      approveDialogOpen.value = false;
      approveLevel.value = null;
    },
  });
}

const rejectForm = useForm<{ note?: string }>({ note: "" });
const rejectDialogOpen = ref(false);

function openRejectDialog() {
  rejectForm.clearErrors();
  rejectForm.note = "";
  rejectDialogOpen.value = true;
}

function reject() {
  rejectForm.post(`/procurement/purchase-requests/${props.pr.id}/reject`, {
    onFinish: () => {
      if (!rejectForm.hasErrors) {
        rejectDialogOpen.value = false;
        rejectForm.reset();
      }
    },
  });
}

const poForm = useForm({});
const createPoDialogOpen = ref(false);
function createPo() {
  poForm.post(`/procurement/purchase-orders/from-pr/${props.pr.id}`, {
    onFinish: () => {
      createPoDialogOpen.value = false;
    },
  });
}

function formatDate(date: string) {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

const steps = [
  "DRAFT",
  "SUBMITTED",
  "APPROVAL_L1",
  "APPROVAL_L2",
  "APPROVAL_L3",
  "APPROVED",
  "PO_CREATED",
];
const currentStepIndex = computed(() => {
  if (props.pr.status === "REJECTED") return -1;
  return steps.indexOf(props.pr.status);
});
</script>

<template>
  <Head :title="`PR ${props.pr.title}`" />
  <AppLayout>
    <div class="p-6 max-w-5xl mx-auto space-y-6">
      <!-- Header -->
      <div
        class="flex flex-col md:flex-row md:items-center justify-between gap-4"
      >
        <div>
          <h1 class="text-2xl font-semibold flex items-center gap-2">
            {{ props.pr.title }}
            <span
              :class="{
                'bg-zinc-100 text-zinc-700': props.pr.status === 'DRAFT',
                'bg-blue-100 text-blue-700': props.pr.status === 'SUBMITTED',
                'bg-amber-100 text-amber-700': String(
                  props.pr.status,
                ).startsWith('APPROVAL_'),
                'bg-emerald-100 text-emerald-700':
                  props.pr.status === 'APPROVED' ||
                  props.pr.status === 'PO_CREATED',
                'bg-red-100 text-red-700': props.pr.status === 'REJECTED',
              }"
              class="px-2.5 py-0.5 rounded-full text-xs font-medium"
            >
              {{ props.pr.status }}
            </span>
          </h1>
          <div class="text-sm text-zinc-500 mt-1">
            Created on {{ formatDate(props.pr.created_at) }} • Total
            {{ formatCurrency(props.pr.total_amount) }}
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap gap-2">
          <button
            v-if="props.pr.status === 'DRAFT'"
            v-can="'create_pr'"
            class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition-colors shadow-sm text-sm"
            @click="submitDialogOpen = true"
            :disabled="submitForm.processing"
          >
            Submit Request
          </button>

          <template
            v-if="
              props.pr.status === 'SUBMITTED' ||
              String(props.pr.status).startsWith('APPROVAL_')
            "
          >
            <button
              v-can="'approve_pr'"
              class="px-3 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-medium transition-colors shadow-sm text-sm"
              @click="openApproveDialog(1)"
              :disabled="approveForm.processing"
            >
              Approve L1
            </button>
            <button
              v-can="'approve_pr'"
              class="px-3 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-medium transition-colors shadow-sm text-sm"
              @click="openApproveDialog(2)"
              :disabled="approveForm.processing"
            >
              Approve L2
            </button>
            <button
              v-can="'approve_pr'"
              class="px-3 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-medium transition-colors shadow-sm text-sm"
              @click="openApproveDialog(3)"
              :disabled="approveForm.processing"
            >
              Approve L3
            </button>
          </template>

          <button
            v-if="
              props.pr.status !== 'REJECTED' &&
              props.pr.status !== 'PO_CREATED' &&
              props.pr.status !== 'DRAFT'
            "
            v-can="'approve_pr'"
            class="px-4 py-2 rounded-md bg-white border border-red-200 text-red-600 hover:bg-red-50 font-medium transition-colors shadow-sm text-sm"
            @click="openRejectDialog"
            :disabled="rejectForm.processing"
          >
            Reject
          </button>

          <button
            v-if="props.pr.status === 'APPROVED'"
            v-can="'create_po'"
            class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition-colors shadow-sm text-sm"
            @click="createPoDialogOpen = true"
            :disabled="poForm.processing"
          >
            Generate PO
          </button>
        </div>
      </div>

      <!-- Timeline -->
      <div
        v-if="props.pr.status !== 'REJECTED'"
        class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6"
      >
        <div class="flex items-center justify-between w-full">
          <div
            v-for="(step, index) in [
              'Draft',
              'Submitted',
              'Approval',
              'Approved',
              'PO Created',
            ]"
            :key="step"
            class="flex flex-col items-center relative z-10 w-full"
          >
            <div
              :class="[
                'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors duration-200',
                index <=
                (currentStepIndex > 4
                  ? 4
                  : currentStepIndex === -1
                    ? 0
                    : Math.min(currentStepIndex, 4))
                  ? 'bg-indigo-600 text-white'
                  : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800',
              ]"
            >
              {{ index + 1 }}
            </div>
            <div
              class="text-xs mt-2 font-medium"
              :class="
                index <= currentStepIndex ? 'text-indigo-600' : 'text-zinc-500'
              "
            >
              {{ step }}
            </div>
            <!-- Line connector -->
            <div
              v-if="index < 4"
              class="absolute top-4 left-1/2 w-full h-[2px] -z-10"
              :class="
                index <
                (currentStepIndex > 4 ? 4 : Math.min(currentStepIndex, 4))
                  ? 'bg-indigo-600'
                  : 'bg-zinc-100 dark:bg-zinc-800'
              "
            ></div>
          </div>
        </div>
      </div>

      <!-- Budget Flash Message -->
      <div
        v-if="props.flashBudgetDetails"
        class="bg-amber-50 dark:bg-amber-900/20 text-amber-900 dark:text-amber-200 border border-amber-200 dark:border-amber-800 rounded-xl p-4"
      >
        <div class="font-medium flex items-center gap-2">
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
            class="lucide lucide-alert-triangle"
          >
            <path
              d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"
            />
            <path d="M12 9v4" />
            <path d="M12 17h.01" />
          </svg>
          Budget Check Failed
        </div>
        <div class="mt-2 text-sm space-y-1">
          <div
            v-for="d in props.flashBudgetDetails"
            :key="d.gl_account"
            class="flex justify-between border-b border-amber-200/50 last:border-0 py-1"
          >
            <span>GL {{ d.gl_account }}</span>
            <span class="font-mono"
              >Req: {{ formatCurrency(d.requested) }} / Avail:
              {{ formatCurrency(d.available) }}</span
            >
            <span
              :class="
                d.enough
                  ? 'text-emerald-600 font-bold'
                  : 'text-red-600 font-bold'
              "
              >{{ d.enough ? "OK" : "INSUFFICIENT" }}</span
            >
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Items List -->
        <div
          class="lg:col-span-2 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden"
        >
          <div
            class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 font-medium"
          >
            Items
          </div>
          <table class="w-full text-sm text-left">
            <thead
              class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 font-medium border-b border-zinc-200 dark:border-zinc-800"
            >
              <tr>
                <th class="px-6 py-3">Description</th>
                <th class="px-6 py-3">GL Account</th>
                <th class="px-6 py-3 text-right">Qty</th>
                <th class="px-6 py-3 text-right">Price</th>
                <th class="px-6 py-3 text-right">Amount</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
              <tr v-for="it in props.pr.items" :key="it.id">
                <td class="px-6 py-3">{{ it.description }}</td>
                <td class="px-6 py-3 font-mono text-xs">{{ it.gl_account }}</td>
                <td class="px-6 py-3 text-right">{{ it.qty }}</td>
                <td class="px-6 py-3 text-right">
                  {{ formatCurrency(it.price) }}
                </td>
                <td class="px-6 py-3 text-right font-medium">
                  {{ formatCurrency(it.amount) }}
                </td>
              </tr>
            </tbody>
            <tfoot
              class="bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-800"
            >
              <tr>
                <td colspan="4" class="px-6 py-3 text-right font-medium">
                  Total Amount
                </td>
                <td class="px-6 py-3 text-right font-bold text-indigo-600">
                  {{ formatCurrency(props.pr.total_amount) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Approval Logs -->
        <div
          class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 h-fit"
        >
          <div
            class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 font-medium"
          >
            Approval History
          </div>
          <div
            v-if="props.approvalLogs.length === 0"
            class="p-6 text-sm text-zinc-500 text-center"
          >
            No logs available
          </div>
          <div v-else class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <div
              v-for="(l, i) in props.approvalLogs"
              :key="i"
              class="p-4 text-sm"
            >
              <div class="flex justify-between items-start mb-1">
                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{
                  l.to_status
                }}</span>
                <span class="text-xs text-zinc-500">{{
                  formatDate(l.created_at)
                }}</span>
              </div>
              <div class="text-zinc-500 text-xs">
                From: {{ l.from_status ?? "N/A" }}
              </div>
              <div
                v-if="l.note"
                class="mt-2 p-2 bg-zinc-50 dark:bg-zinc-800 rounded text-zinc-600 dark:text-zinc-400 italic text-xs border border-zinc-100 dark:border-zinc-700"
              >
                "{{ l.note }}"
              </div>
            </div>
          </div>
        </div>
      </div>

      <ConfirmDialog
        v-model:open="submitDialogOpen"
        title="Submit purchase request"
        message="Apakah Anda yakin ingin mengirim purchase request ini?"
        confirm-label="Submit"
        @confirm="submitPr"
      />

      <ConfirmDialog
        v-model:open="approveDialogOpen"
        title="Approve purchase request"
        :message="`Apakah Anda yakin ingin approve sebagai Level ${approveLevel ?? ''}?`"
        confirm-label="Approve"
        @confirm="approve"
      />

      <ConfirmDialog
        v-model:open="createPoDialogOpen"
        title="Generate purchase order"
        message="Apakah Anda yakin ingin membuat purchase order dari request ini?"
        confirm-label="Generate PO"
        @confirm="createPo"
      />

      <Dialog v-model:open="rejectDialogOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Reject purchase request</DialogTitle>
          </DialogHeader>

          <form @submit.prevent="reject" class="space-y-4">
            <div class="grid gap-2">
              <Label for="reject-note">Reason for rejection</Label>
              <textarea
                id="reject-note"
                v-model="rejectForm.note"
                rows="4"
                required
                class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-xs outline-none transition-colors focus-visible:border-zinc-400 focus-visible:ring-2 focus-visible:ring-zinc-950/10 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
                placeholder="Please provide a reason for rejection"
              />
              <span
                v-if="rejectForm.errors.note"
                class="text-xs text-red-500"
                >{{ rejectForm.errors.note }}</span
              >
            </div>

            <div class="flex justify-end gap-2">
              <Button
                type="button"
                variant="outline"
                :disabled="rejectForm.processing"
                @click="rejectDialogOpen = false"
                >Cancel</Button
              >
              <Button
                type="submit"
                variant="destructive"
                :disabled="rejectForm.processing"
                >Reject</Button
              >
            </div>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
