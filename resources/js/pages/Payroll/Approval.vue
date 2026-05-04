<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { useForm } from "@inertiajs/vue3";
import { CheckCircle, XCircle, Clock, Building } from "lucide-vue-next";
import { ref, watch } from "vue";
import { index as payrollsIndex } from "@/actions/App/Http/Controllers/PayrollController";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { Textarea } from "@/components/ui/textarea";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{
  approvals: any;
  filters: Record<string, string>;
  stats: {
    pending_count: number;
    approved_count: number;
    rejected_count: number;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Core Modules", href: "#" },
  { title: "Payroll", href: payrollsIndex().url },
  { title: "Approvals", href: "#" },
];

const selectedStatus = ref(props.filters.status || "");
const selectedOrg = ref(props.filters.organization_id || "");
const selectedApproval = ref<any>(null);
const showApproveDialog = ref(false);
const showRejectDialog = ref(false);

let filterTimeout: ReturnType<typeof setTimeout>;
watch([selectedStatus, selectedOrg], () => {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    router.get(
      "/payroll-approvals",
      {
        status: selectedStatus.value,
        organization_id: selectedOrg.value,
      },
      { preserveState: true, replace: true },
    );
  }, 400);
});

const approveForm = useForm({
  notes: "",
});

const rejectForm = useForm({
  notes: "",
});

const openApproveDialog = (approval: any) => {
  selectedApproval.value = approval;
  showApproveDialog.value = true;
  approveForm.notes = "";
};

const openRejectDialog = (approval: any) => {
  selectedApproval.value = approval;
  showRejectDialog.value = true;
  rejectForm.notes = "";
};

const submitApprove = () => {
  if (!selectedApproval.value) return;

  approveForm.post(`/payroll-approvals/${selectedApproval.value.id}/approve`, {
    onSuccess: () => {
      showApproveDialog.value = false;
      selectedApproval.value = null;
    },
  });
};

const submitReject = () => {
  if (!selectedApproval.value) return;

  rejectForm.post(`/payroll-approvals/${selectedApproval.value.id}/reject`, {
    onSuccess: () => {
      showRejectDialog.value = false;
      selectedApproval.value = null;
    },
  });
};
</script>

<template>
  <Head title="Payroll Approvals" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
      <!-- Header -->
      <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Payroll Approvals
          </h1>
          <p class="text-zinc-500 mt-1">
            Review and approve payroll submissions from HR units.
          </p>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between"
        >
          <div>
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
              Pending
            </p>
            <h2
              class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-1"
            >
              {{ stats.pending_count }}
            </h2>
          </div>
          <div
            class="h-12 w-12 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center"
          >
            <Clock class="h-6 w-6" />
          </div>
        </div>

        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between"
        >
          <div>
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
              Approved
            </p>
            <h2
              class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1"
            >
              {{ stats.approved_count }}
            </h2>
          </div>
          <div
            class="h-12 w-12 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center"
          >
            <CheckCircle class="h-6 w-6" />
          </div>
        </div>

        <div
          class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm flex items-center justify-between"
        >
          <div>
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
              Rejected
            </p>
            <h2 class="text-3xl font-bold text-red-600 dark:text-red-400 mt-1">
              {{ stats.rejected_count }}
            </h2>
          </div>
          <div
            class="h-12 w-12 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center"
          >
            <XCircle class="h-6 w-6" />
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-sm"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <Label>Status Filter</Label>
            <select
              v-model="selectedStatus"
              class="w-full flex h-10 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950"
            >
              <option value="">All Status</option>
              <option value="PENDING">Pending</option>
              <option value="APPROVED">Approved</option>
              <option value="REJECTED">Rejected</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden flex-1"
      >
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead
              class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800"
            >
              <tr>
                <th class="px-6 py-4 font-medium">Employee</th>
                <th class="px-6 py-4 font-medium">Organization</th>
                <th class="px-6 py-4 font-medium">Period</th>
                <th class="px-6 py-4 font-medium">Net Salary</th>
                <th class="px-6 py-4 font-medium">Requested By</th>
                <th class="px-6 py-4 font-medium">Status</th>
                <th class="px-6 py-4 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
              <tr
                v-for="approval in approvals.data"
                :key="approval.id"
                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div
                      class="h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500"
                    >
                      {{
                        approval.payroll?.employee?.first_name?.charAt(0) || "?"
                      }}
                    </div>
                    <div>
                      <div class="font-medium text-zinc-900 dark:text-white">
                        {{ approval.payroll?.employee?.first_name }}
                        {{ approval.payroll?.employee?.last_name }}
                      </div>
                      <div class="text-xs text-zinc-500">
                        {{ approval.payroll?.employee?.employee_code }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div
                    v-if="approval.payroll?.organization"
                    class="flex items-center gap-2"
                  >
                    <Building class="h-4 w-4 text-zinc-400" />
                    <span class="text-zinc-700 dark:text-zinc-300">{{
                      approval.payroll.organization.name
                    }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="text-zinc-700 dark:text-zinc-300">{{
                    approval.payroll?.period
                  }}</span>
                </td>
                <td class="px-6 py-4">
                  <span class="font-medium text-zinc-900 dark:text-white">{{
                    formatCurrency(approval.payroll?.net_salary || 0)
                  }}</span>
                </td>
                <td class="px-6 py-4">
                  <div class="text-xs text-zinc-500">
                    <div>{{ approval.requester?.name }}</div>
                    <div>
                      {{ new Date(approval.requested_at).toLocaleDateString() }}
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <StatusBadge :status="approval.status" />
                </td>
                <td class="px-6 py-4 text-right">
                  <div
                    v-if="approval.status === 'PENDING'"
                    class="flex justify-end gap-2"
                  >
                    <Button
                      size="sm"
                      variant="default"
                      @click="openApproveDialog(approval)"
                    >
                      <CheckCircle class="h-4 w-4 mr-1" />
                      Approve
                    </Button>
                    <Button
                      size="sm"
                      variant="destructive"
                      @click="openRejectDialog(approval)"
                    >
                      <XCircle class="h-4 w-4 mr-1" />
                      Reject
                    </Button>
                  </div>
                  <div v-else class="text-xs text-zinc-500">
                    {{ approval.approver_notes || "-" }}
                  </div>
                </td>
              </tr>
              <tr v-if="approvals.data.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                  <Clock
                    class="h-12 w-12 mx-auto text-zinc-300 dark:text-zinc-700 mb-3"
                  />
                  <p
                    class="text-base font-medium text-zinc-900 dark:text-zinc-100"
                  >
                    No approval requests found
                  </p>
                  <p class="text-sm mt-1">
                    Payroll submissions awaiting review will appear here.
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="approvals.links && approvals.links.length > 3"
          class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between"
        >
          <p class="text-sm text-zinc-500">
            Showing
            <span class="font-medium text-zinc-900 dark:text-white">{{
              approvals.from
            }}</span>
            to
            <span class="font-medium text-zinc-900 dark:text-white">{{
              approvals.to
            }}</span>
            of
            <span class="font-medium text-zinc-900 dark:text-white">{{
              approvals.total
            }}</span>
            results
          </p>
          <div class="flex gap-1">
            <a
              v-for="(link, i) in approvals.links"
              :key="i"
              :href="link.url || '#'"
              class="px-3 py-1 text-sm rounded-md transition-colors"
              :class="[
                link.active
                  ? 'bg-indigo-600 text-white'
                  : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800',
                !link.url ? 'opacity-50 cursor-not-allowed hidden' : '',
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>

      <!-- Approve Dialog -->
      <div
        v-if="showApproveDialog && selectedApproval"
        class="fixed inset-0 z-50 flex items-center justify-center"
      >
        <div
          class="fixed inset-0 bg-black/50"
          @click="showApproveDialog = false"
        ></div>
        <div
          class="relative bg-white dark:bg-zinc-900 rounded-lg shadow-xl max-w-md w-full mx-4 p-6"
        >
          <h3 class="text-lg font-semibold mb-4">Approve Payroll</h3>
          <p class="text-sm text-zinc-500 mb-4">
            Approve payroll for
            <strong
              >{{ selectedApproval.payroll?.employee?.first_name }}
              {{ selectedApproval.payroll?.employee?.last_name }}</strong
            >
            with net salary
            <strong>{{
              formatCurrency(selectedApproval.payroll?.net_salary || 0)
            }}</strong
            >?
          </p>
          <div class="grid gap-2 mb-4">
            <Label>Notes (Optional)</Label>
            <Textarea
              v-model="approveForm.notes"
              placeholder="Add approval notes..."
              :rows="3"
            />
            <span
              v-if="approveForm.errors.notes"
              class="text-xs text-red-500"
              >{{ approveForm.errors.notes }}</span
            >
          </div>
          <div class="flex justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              @click="showApproveDialog = false"
              >Cancel</Button
            >
            <Button
              type="button"
              @click="submitApprove"
              :disabled="approveForm.processing"
            >
              <span v-if="approveForm.processing">Approving...</span>
              <span v-else>Approve</span>
            </Button>
          </div>
        </div>
      </div>

      <!-- Reject Dialog -->
      <div
        v-if="showRejectDialog && selectedApproval"
        class="fixed inset-0 z-50 flex items-center justify-center"
      >
        <div
          class="fixed inset-0 bg-black/50"
          @click="showRejectDialog = false"
        ></div>
        <div
          class="relative bg-white dark:bg-zinc-900 rounded-lg shadow-xl max-w-md w-full mx-4 p-6"
        >
          <h3 class="text-lg font-semibold mb-4">Reject Payroll</h3>
          <p class="text-sm text-zinc-500 mb-4">
            Reject payroll for
            <strong
              >{{ selectedApproval.payroll?.employee?.first_name }}
              {{ selectedApproval.payroll?.employee?.last_name }}</strong
            >?
          </p>
          <div class="grid gap-2 mb-4">
            <Label>Reason (Required)</Label>
            <Textarea
              v-model="rejectForm.notes"
              placeholder="Provide reason for rejection..."
              :rows="3"
              required
            />
            <span v-if="rejectForm.errors.notes" class="text-xs text-red-500">{{
              rejectForm.errors.notes
            }}</span>
          </div>
          <div class="flex justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              @click="showRejectDialog = false"
              >Cancel</Button
            >
            <Button
              type="button"
              variant="destructive"
              @click="submitReject"
              :disabled="rejectForm.processing"
            >
              <span v-if="rejectForm.processing">Rejecting...</span>
              <span v-else>Reject</span>
            </Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
