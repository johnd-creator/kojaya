<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import {
  AlertTriangle,
  ArrowDownNarrowWide,
  Banknote,
  CalendarX2,
  FileDown,
  FileSearch,
  HandCoins,
  ListChecks,
  Percent,
  PieChart,
  UserCheck,
  WalletCards,
} from "lucide-vue-next";
import { computed, onMounted, ref } from "vue";
import StatsCard from "@/components/StatsCard.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { Badge } from "@/components/ui/badge";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { approvalInbox, exceptions, analytics, exportMethod } from "@/routes/cooperative/operator";

defineProps<{
  initialInbox?: any;
  initialExceptions?: any;
  initialAnalytics?: any;
}>();

type InboxItem = {
  id: number;
  amount?: number;
  member?: { name?: string; member_no?: string };
  member_no?: string;
  created_at?: string;
  paid_at?: string;
  reference_no?: string;
};

const breadcrumbs = [{ title: "Operator Koperasi", href: "#" }, { title: "Dashboard", href: "#" }];

const loading = ref(true);
const inbox = ref<any>(null);
const exceptionData = ref<any>(null);
const analyticData = ref<any>(null);

onMounted(async () => {
  try {
    const [inboxRes, exceptionsRes, analyticsRes] = await Promise.all([
      fetch(approvalInbox().url, { headers: { Accept: "application/json" } }).then((r) => r.json()),
      fetch(exceptions().url, { headers: { Accept: "application/json" } }).then((r) => r.json()),
      fetch(analytics().url, { headers: { Accept: "application/json" } }).then((r) => r.json()),
    ]);
    inbox.value = inboxRes.data;
    exceptionData.value = exceptionsRes.data;
    analyticData.value = analyticsRes.data;
  } finally {
    loading.value = false;
  }
});

const summaryCards = computed(() => {
  const s = inbox.value?.summary;
  return [
    { label: "Pembayaran Pending", value: s?.pending_payments ?? 0, icon: Banknote },
    { label: "Pinjaman Baru", value: s?.pending_loans ?? 0, icon: HandCoins },
    { label: "Penukaran Reward", value: s?.pending_redemptions ?? 0, icon: UserCheck },
    { label: "Approval Payroll", value: s?.pending_payroll_approvals ?? 0, icon: FileSearch },
  ];
});

const analyticsCards = computed(() => {
  const a = analyticData.value;
  return [
    { label: "Outstanding Pinjaman", value: formatCurrency(a?.active_loan_outstanding ?? 0), icon: WalletCards },
    { label: "Tunggakan Angsuran", value: formatCurrency(a?.overdue_installment_amount ?? 0), icon: CalendarX2 },
    { label: "NPL Ratio", value: `${((a?.npl_ratio ?? 0) * 100).toFixed(2)}%`, icon: Percent },
    { label: "Iuran Belum Dibayar", value: formatCurrency(a?.unpaid_dues_amount ?? 0), icon: ArrowDownNarrowWide },
    { label: "SHU Pool Terakhir", value: formatCurrency(a?.latest_shu_pool ?? 0), icon: PieChart },
  ];
});

const handleExport = (type: string) => {
  window.open(exportMethod({ query: { type } }).url, "_blank");
};
</script>

<template>
  <Head title="Operator Dashboard" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-6">
      <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Cockpit Operator Koperasi</h1>
          <p class="mt-1 text-sm text-zinc-500">Approval, pengecekan anomali, dan overview operasional koperasi harian.</p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" size="sm" @click="handleExport('members')">
            <FileDown class="mr-2 h-4 w-4" />Export Anggota
          </Button>
          <Button variant="outline" size="sm" @click="handleExport('payments')">
            <FileDown class="mr-2 h-4 w-4" />Export Pembayaran
          </Button>
        </div>
      </div>

      <!-- Summary Cards -->
      <div v-if="loading" class="grid gap-4 md:grid-cols-4">
        <Skeleton v-for="n in 4" :key="n" class="h-24 rounded-lg border" />
      </div>
      <div v-else class="grid gap-4 md:grid-cols-4">
        <StatsCard v-for="card in summaryCards" :key="card.label" :label="card.label" :value="card.value" :icon="card.icon" />
      </div>

      <!-- Analytics Cards -->
      <div v-if="!loading" class="grid gap-4 md:grid-cols-5">
        <StatsCard v-for="card in analyticsCards" :key="card.label" :label="card.label" :value="card.value" :icon="card.icon" />
      </div>

      <!-- Approval Inbox -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
          <CardTitle>Inbox Approval</CardTitle>
          <Badge variant="secondary">{{ (inbox?.summary?.pending_payments ?? 0) + (inbox?.summary?.pending_loans ?? 0) + (inbox?.summary?.pending_redemptions ?? 0) }} menunggu</Badge>
        </CardHeader>
        <CardContent>
          <div v-if="loading" class="space-y-2">
            <Skeleton v-for="n in 4" :key="n" class="h-14 w-full rounded-lg" />
          </div>
          <div v-else class="space-y-4">
            <!-- Pending Payments -->
            <div v-if="inbox?.items?.payments?.length">
              <h4 class="mb-2 text-sm font-semibold text-zinc-600">Pembayaran Menunggu Verifikasi</h4>
              <div class="rounded-lg border divide-y">
                <div v-for="payment in inbox.items.payments" :key="payment.id" class="flex items-center justify-between p-3">
                  <div>
                    <span class="font-medium">{{ payment.member?.name ?? "N/A" }}</span>
                    <span class="ml-2 text-xs text-zinc-500">{{ payment.member?.member_no }}</span>
                    <div class="text-sm text-zinc-600">{{ formatCurrency(payment.amount) }}</div>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-zinc-500">{{ formatDate(payment.paid_at) }}</span>
                    <Link :href="`/cooperative/payments/${payment.id}`">
                      <Button size="sm" variant="outline">Verifikasi</Button>
                    </Link>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pending Loans -->
            <div v-if="inbox?.items?.loans?.length">
              <h4 class="mb-2 text-sm font-semibold text-zinc-600">Pinjaman Menunggu Approval</h4>
              <div class="rounded-lg border divide-y">
                <div v-for="loan in inbox.items.loans" :key="loan.id" class="flex items-center justify-between p-3">
                  <div>
                    <span class="font-medium">{{ loan.member?.name ?? "N/A" }}</span>
                    <span class="ml-2 text-xs text-zinc-500">{{ loan.reference_no }}</span>
                    <div class="text-sm text-zinc-600">{{ formatCurrency(loan.principal_amount) }}</div>
                  </div>
                  <Link :href="`/cooperative/loans/${loan.id}`">
                    <Button size="sm" variant="outline">Review</Button>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Pending Redemptions -->
            <div v-if="inbox?.items?.redemptions?.length">
              <h4 class="mb-2 text-sm font-semibold text-zinc-600">Penukaran Reward</h4>
              <div class="rounded-lg border divide-y">
                <div v-for="r in inbox.items.redemptions" :key="r.id" class="flex items-center justify-between p-3">
                  <div>
                    <span class="font-medium">{{ r.member?.name ?? "N/A" }}</span>
                    <span class="ml-2 text-xs text-zinc-500">{{ r.reward?.name ?? "-" }}</span>
                  </div>
                  <Link :href="`/cooperative/redemptions/${r.id}`">
                    <Button size="sm" variant="outline">Proses</Button>
                  </Link>
                </div>
              </div>
            </div>

            <div v-if="!inbox?.items?.payments?.length && !inbox?.items?.loans?.length && !inbox?.items?.redemptions?.length" class="py-8 text-center text-sm text-zinc-500">
              Tidak ada item yang menunggu approval.
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Exceptions -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <AlertTriangle class="h-5 w-5 text-amber-500" />Pengecualian & Anomali
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="loading" class="space-y-2">
            <Skeleton v-for="n in 4" :key="n" class="h-14 w-full rounded-lg" />
          </div>
          <div v-else class="space-y-4">
            <!-- Overdue Loans -->
            <div v-if="exceptionData?.overdue_loans?.length">
              <h4 class="mb-2 text-sm font-semibold text-red-600">Angsuran Terlambat</h4>
              <div class="rounded-lg border border-red-200 divide-y">
                <div v-for="inst in exceptionData.overdue_loans" :key="inst.id" class="flex items-center justify-between p-3">
                  <div>
                    <span class="font-medium">{{ inst.loan?.member?.name ?? "N/A" }}</span>
                    <span class="ml-2 text-xs text-zinc-500">{{ formatCurrency(inst.amount_due) }} — jatuh tempo {{ formatDate(inst.due_date) }}</span>
                  </div>
                  <Link v-if="inst.loan?.id" :href="`/cooperative/loans/${inst.loan.id}`">
                    <Button size="sm" variant="outline">Lihat</Button>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Unpaid Dues -->
            <div v-if="exceptionData?.unpaid_dues?.length">
              <h4 class="mb-2 text-sm font-semibold text-amber-600">Iuran Belum Dibayar</h4>
              <div class="rounded-lg border border-amber-200 divide-y">
                <div v-for="due in exceptionData.unpaid_dues" :key="due.id" class="flex items-center justify-between p-3">
                  <div>
                    <span class="font-medium">{{ due.member?.name ?? "N/A" }}</span>
                    <span class="ml-2 text-xs text-zinc-500">{{ formatCurrency(due.amount) }} — jatuh tempo {{ formatDate(due.due_date) }}</span>
                  </div>
                  <StatusBadge :status="due.status" :variant="due.status === 'UNPAID' ? 'destructive' : 'warning'" />
                </div>
              </div>
            </div>

            <!-- Low Stock -->
            <div v-if="exceptionData?.low_stock?.length">
              <h4 class="mb-2 text-sm font-semibold text-orange-600">Stok Rendah</h4>
              <div class="rounded-lg border border-orange-200 divide-y">
                <div v-for="product in exceptionData.low_stock" :key="product.id" class="flex items-center justify-between p-3">
                  <div>
                    <span class="font-medium">{{ product.name }}</span>
                    <span class="ml-2 text-xs text-zinc-500">Stok: {{ product.stock }} / Min: {{ product.minimum_stock }}</span>
                  </div>
                  <Link :href="`/cooperative/pos-products/${product.id}`">
                    <Button size="sm" variant="outline">Lihat</Button>
                  </Link>
                </div>
              </div>
            </div>

            <div v-if="!exceptionData?.overdue_loans?.length && !exceptionData?.unpaid_dues?.length && !exceptionData?.low_stock?.length" class="py-8 text-center text-sm text-zinc-500">
              Tidak ada pengecualian yang perlu ditindaklanjuti.
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
