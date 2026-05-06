<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import {
  ArrowLeft,
  CheckCircle2,
  XCircle,
  DollarSign,
  Download,
  Calendar,
  User as UserIcon,
} from "lucide-vue-next";
import { ref } from "vue";
import { index } from "@/actions/App/Http/Controllers/ReimbursementController";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";

interface User {
  id: number;
  name: string;
}

interface ReimbursementItem {
  id: string;
  category: string;
  description: string;
  amount: number;
  receipt_date: string;
  receipt_file_path: string | null;
}

interface Reimbursement {
  id: string;
  submission_date: string;
  total_amount: number;
  status: "DRAFT" | "SUBMITTED" | "APPROVED" | "REJECTED" | "PAID";
  description: string;
  rejection_reason: string | null;
  payment_date: string | null;
  user: User;
  approver: User | null;
  items: ReimbursementItem[];
}

interface Props {
  reimbursement: Reimbursement;
  approvalLogs: {
    from_status: string | null;
    to_status: string;
    approved_by: string | null;
    note: string | null;
    created_at: string;
  }[];
  can: {
    approve: boolean;
    reject: boolean;
    pay: boolean;
  };
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: "Finance", href: "#" },
  { title: "Reimbursements", href: index().url },
  { title: `#${props.reimbursement.id.substring(0, 8)}`, href: "#" },
];

const isRejectDialogOpen = ref(false);
const isApproveDialogOpen = ref(false);
const isPayDialogOpen = ref(false);
const rejectForm = useForm({
  rejection_reason: "",
});

const approve = () => {
  router.post(
    `/reimbursements/${props.reimbursement.id}/approve`,
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        isApproveDialogOpen.value = false;
      },
    },
  );
};

const reject = () => {
  rejectForm.post(`/reimbursements/${props.reimbursement.id}/reject`, {
    preserveScroll: true,
    onSuccess: () => {
      isRejectDialogOpen.value = false;
      rejectForm.reset();
    },
  });
};

const pay = () => {
  router.post(
    `/reimbursements/${props.reimbursement.id}/pay`,
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        isPayDialogOpen.value = false;
      },
    },
  );
};

const getReceiptUrl = (path: string) => {
  return `/storage/${path}`;
};
</script>

<template>
  <Head title="Reimbursement Details" />

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
              Reimbursement #{{ reimbursement.id.substring(0, 8) }}
            </h1>
            <p class="text-zinc-500 text-sm flex items-center gap-2 mt-1">
              <UserIcon class="h-3 w-3" />
              <span>{{ reimbursement.user.name }}</span>
              <span>•</span>
              <Calendar class="h-3 w-3" />
              <span>{{ formatDate(reimbursement.submission_date) }}</span>
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <template v-if="can.approve">
            <Button
              variant="default"
              @click="isApproveDialogOpen = true"
              class="bg-emerald-600 hover:bg-emerald-700 text-white"
            >
              <CheckCircle2 class="mr-2 h-4 w-4" />
              Approve
            </Button>
            <Dialog v-model:open="isRejectDialogOpen">
              <DialogTrigger as-child>
                <Button variant="destructive">
                  <XCircle class="mr-2 h-4 w-4" />
                  Reject
                </Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Reject Reimbursement</DialogTitle>
                  <DialogDescription>
                    Please provide a reason for rejecting this request.
                  </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                  <div class="grid gap-2">
                    <Label htmlFor="reason">Reason</Label>
                    <Textarea
                      id="reason"
                      v-model="rejectForm.rejection_reason"
                      placeholder="Reason for rejection..."
                    />
                    <p
                      v-if="rejectForm.errors.rejection_reason"
                      class="text-sm text-destructive"
                    >
                      {{ rejectForm.errors.rejection_reason }}
                    </p>
                  </div>
                </div>
                <DialogFooter>
                  <Button variant="outline" @click="isRejectDialogOpen = false"
                    >Cancel</Button
                  >
                  <Button
                    variant="destructive"
                    @click="reject"
                    :disabled="rejectForm.processing"
                    >Confirm Rejection</Button
                  >
                </DialogFooter>
              </DialogContent>
            </Dialog>
          </template>

          <template v-if="can.pay">
            <Button
              variant="default"
              class="bg-green-600 hover:bg-green-700 text-white"
              @click="isPayDialogOpen = true"
            >
              <DollarSign class="mr-2 h-4 w-4" />
              Mark as Paid
            </Button>
          </template>
        </div>
      </div>

      <!-- Overview Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm"
        >
          <p class="text-sm font-medium text-zinc-500">Status</p>
          <div class="mt-2 flex items-center gap-2">
            <Badge
              variant="outline"
              class="text-lg px-3 py-1 uppercase tracking-wide"
            >
              {{ reimbursement.status }}
            </Badge>
          </div>
          <div
            v-if="reimbursement.rejection_reason"
            class="mt-3 text-sm text-destructive bg-destructive/10 p-2 rounded"
          >
            <span class="font-semibold block">Rejection Reason:</span>
            {{ reimbursement.rejection_reason }}
          </div>
          <div
            v-if="reimbursement.approver"
            class="mt-3 text-sm text-zinc-600 dark:text-zinc-400"
          >
            <span class="font-semibold">Approved by:</span>
            {{ reimbursement.approver.name }}
          </div>
        </div>

        <div
          class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm"
        >
          <p class="text-sm font-medium text-zinc-500">Total Amount</p>
          <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">
            {{ formatCurrency(reimbursement.total_amount) }}
          </p>
          <p
            v-if="reimbursement.payment_date"
            class="text-xs text-emerald-600 mt-1 flex items-center gap-1"
          >
            <CheckCircle2 class="h-3 w-3" />
            Paid on {{ formatDate(reimbursement.payment_date) }}
          </p>
        </div>

        <div
          class="bg-white dark:bg-zinc-900 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm"
        >
          <p class="text-sm font-medium text-zinc-500">Description</p>
          <p class="text-zinc-900 dark:text-white mt-2 text-sm leading-relaxed">
            {{ reimbursement.description || "No description provided." }}
          </p>
        </div>
      </div>

      <!-- Items Table -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden"
      >
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-800">
          <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
            Approval Timeline
          </h2>
        </div>
        <div class="p-6">
          <div class="relative pl-8 border-l-2 border-zinc-200 dark:border-zinc-700 space-y-6">
            <div
              v-for="(log, i) in approvalLogs"
              :key="i"
              class="relative"
            >
              <div
                class="absolute -left-[calc(2rem+5px)] top-0 w-3 h-3 rounded-full border-2"
                :class="{
                  'bg-blue-500 border-blue-500': log.to_status === 'SUBMITTED',
                  'bg-emerald-500 border-emerald-500': log.to_status === 'APPROVED',
                  'bg-red-500 border-red-500': log.to_status === 'REJECTED',
                  'bg-green-600 border-green-600': log.to_status === 'PAID',
                  'bg-zinc-400 border-zinc-400': !['SUBMITTED','APPROVED','REJECTED','PAID'].includes(log.to_status),
                }"
              />
              <div>
                <div class="flex items-center gap-2 text-sm">
                  <Badge variant="outline" class="text-xs px-2 py-0.5">
                    {{ log.to_status }}
                  </Badge>
                  <span v-if="log.from_status" class="text-zinc-400 text-xs">
                    dari {{ log.from_status }}
                  </span>
                </div>
                <p v-if="log.note" class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                  {{ log.note }}
                </p>
                <p class="mt-1 text-xs text-zinc-400">
                  {{ new Date(log.created_at).toLocaleString('id-ID') }}
                </p>
              </div>
            </div>
          </div>
          <div v-if="approvalLogs.length === 0" class="text-sm text-zinc-400 text-center py-4">
            Belum ada riwayat approval.
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden"
      >
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-800">
          <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
            Expense Items
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
                  Category
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider"
                >
                  Description
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider"
                >
                  Receipt
                </th>
                <th
                  class="py-4 px-6 font-medium text-sm text-zinc-500 uppercase tracking-wider text-right"
                >
                  Amount
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
              <tr v-if="reimbursement.items.length === 0">
                <td colspan="5" class="py-12 text-center text-zinc-500">
                  No items found.
                </td>
              </tr>
              <tr
                v-for="item in reimbursement.items"
                :key="item.id"
                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
              >
                <td class="py-4 px-6 text-sm text-zinc-600 dark:text-zinc-400">
                  {{ formatDate(item.receipt_date) }}
                </td>
                <td class="py-4 px-6">
                  <Badge variant="outline">{{ item.category }}</Badge>
                </td>
                <td
                  class="py-4 px-6 text-sm text-zinc-900 dark:text-zinc-100 max-w-xs truncate"
                >
                  {{ item.description }}
                </td>
                <td class="py-4 px-6">
                  <a
                    v-if="item.receipt_file_path"
                    :href="getReceiptUrl(item.receipt_file_path)"
                    target="_blank"
                    class="text-indigo-600 hover:text-indigo-800 text-sm inline-flex items-center gap-1"
                  >
                    <Download class="h-4 w-4" />
                    View Receipt
                  </a>
                  <span v-else class="text-zinc-400 text-sm">-</span>
                </td>
                <td
                  class="py-4 px-6 text-right font-medium text-zinc-900 dark:text-zinc-100"
                >
                  {{ formatCurrency(item.amount) }}
                </td>
              </tr>
            </tbody>
            <tfoot
              class="bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-800"
            >
              <tr>
                <td
                  colspan="4"
                  class="py-4 px-6 text-right font-bold text-sm text-zinc-900 dark:text-white uppercase tracking-wider"
                >
                  Total
                </td>
                <td
                  class="py-4 px-6 text-right font-bold text-zinc-900 dark:text-white"
                >
                  {{ formatCurrency(reimbursement.total_amount) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <ConfirmDialog
      v-model:open="isApproveDialogOpen"
      title="Approve reimbursement"
      message="Apakah Anda yakin ingin menyetujui reimbursement ini?"
      confirm-label="Approve"
      @confirm="approve"
    />

    <ConfirmDialog
      v-model:open="isPayDialogOpen"
      title="Tandai sebagai dibayar"
      message="Apakah Anda yakin ingin menandai reimbursement ini sebagai PAID?"
      confirm-label="Tandai Dibayar"
      @confirm="pay"
    />
  </AppLayout>
</template>
