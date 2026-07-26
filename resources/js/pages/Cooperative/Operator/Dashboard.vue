<script setup lang="ts">
import { Deferred, Head, Link, router, usePage } from "@inertiajs/vue3";
import {
  AlertTriangle,
  ArrowDownNarrowWide,
  ArrowRight,
  Banknote,
  CalendarX2,
  ClipboardCheck,
  Coins,
  FileDown,
  FileSearch,
  FileText,
  HandCoins,
  Percent,
  PieChart,
  ShoppingBag,
  UserCheck,
  Users,
  WalletCards,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import EmptyState from "@/components/EmptyState.vue";
import StatsCard from "@/components/StatsCard.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { index as cooperativeLoansIndex } from "@/routes/cooperative/loans";
import { index as cooperativeMembersIndex } from "@/routes/cooperative/members";
import { approvalInbox, exceptions, exportMethod } from "@/routes/cooperative/operator";
import { index as cooperativeSavingsWithdrawalsIndex } from "@/routes/cooperative/savings/withdrawals";

const props = defineProps<{
  analytics?: {
    active_loan_outstanding?: number;
    overdue_installment_amount?: number;
    npl_ratio?: number;
    unpaid_dues_amount?: number;
    latest_shu_pool?: number;
  };
}>();

const page = usePage();
const userRoles = computed(() =>
  ((page.props.auth as { roles?: Array<{ name?: string } | string> } | undefined)?.roles ?? []).map(
    (role) => (typeof role === "string" ? role : role.name ?? ""),
  ),
);

const breadcrumbs = [{ title: "Operator Koperasi", href: "#" }, { title: "Dashboard", href: "#" }];

const loadingInbox = ref(true);
const inbox = ref<any>(null);
const exceptionData = ref<any>(null);

const defaultQuickActions = [
  { label: "Pembayaran", href: "/cooperative/payments", icon: WalletCards },
  { label: "Pinjaman", href: "/cooperative/loans", icon: HandCoins },
  { label: "Tutup Periode", href: "/cooperative/operator/closing", icon: ClipboardCheck },
  { label: "Anggota", href: "/cooperative/members", icon: Users },
  { label: "Produk POS", href: "/cooperative/pos-products", icon: ShoppingBag },
  { label: "Laporan", href: "/cooperative/reports", icon: FileText },
];

const roleMeta = computed(() => {
  if (userRoles.value.includes("Pengurus Koperasi")) {
    return {
      eyebrow: "Pengurus Koperasi",
      title: "Cockpit keputusan final",
      description: "Prioritaskan approval final, penarikan simpanan, dan risiko yang memerlukan keputusan Pengurus.",
      actions: [
        { label: "Approval Pinjaman", href: cooperativeLoansIndex({ query: { status: "MANAGER_APPROVED" } }).url, icon: HandCoins },
        { label: "Validasi Anggota", href: cooperativeMembersIndex({ query: { status: "PENDING" } }).url, icon: Users },
        { label: "Penarikan", href: cooperativeSavingsWithdrawalsIndex().url, icon: WalletCards },
      ],
    };
  }

  if (userRoles.value.includes("Manajer Koperasi")) {
    return {
      eyebrow: "Manajer Koperasi",
      title: "Cockpit review pinjaman",
      description: "Telaah pengajuan pinjaman, tandai risiko, lalu teruskan keputusan yang siap ke Pengurus.",
      actions: [
        { label: "Review Pinjaman", href: cooperativeLoansIndex({ query: { status: "APPLIED" } }).url, icon: HandCoins },
        { label: "Pinjaman Aktif", href: cooperativeLoansIndex({ query: { status: "ACTIVE" } }).url, icon: WalletCards },
        { label: "Laporan", href: "/cooperative/reports", icon: FileText },
      ],
    };
  }

  return {
    eyebrow: "Admin Koperasi",
    title: "Cockpit operasional harian",
    description: "Selesaikan verifikasi, tindak lanjut anomali, dan pekerjaan administrasi koperasi dari satu tempat.",
    actions: defaultQuickActions,
  };
});

const analyticsCards = computed(() => {
  const a = props.analytics;
  return [
    { label: "Outstanding Pinjaman", value: formatCurrency(a?.active_loan_outstanding ?? 0), icon: WalletCards },
    { label: "Tunggakan Angsuran", value: formatCurrency(a?.overdue_installment_amount ?? 0), icon: CalendarX2 },
    { label: "NPL Ratio", value: a?.npl_ratio != null ? `${(a.npl_ratio * 100).toFixed(2)}%` : "0%", icon: Percent },
    { label: "Iuran Belum Dibayar", value: formatCurrency(a?.unpaid_dues_amount ?? 0), icon: ArrowDownNarrowWide },
    { label: "SHU Pool Terakhir", value: formatCurrency(a?.latest_shu_pool ?? 0), icon: PieChart },
  ];
});

const loadInboxData = async () => {
  loadingInbox.value = true;
  try {
    const [inboxRes, exceptionsRes] = await Promise.all([
      fetch(approvalInbox().url, { headers: { Accept: "application/json" } }).then((r) => r.json()),
      fetch(exceptions().url, { headers: { Accept: "application/json" } }).then((r) => r.json()),
    ]);
    inbox.value = inboxRes.data;
    exceptionData.value = exceptionsRes.data;
  } finally {
    loadingInbox.value = false;
  }
};

loadInboxData();

const summaryCards = computed(() => {
  const s = inbox.value?.summary;
  return [
    { label: "Pembayaran Pending", value: s?.pending_payments ?? 0, icon: Banknote },
    { label: "Pinjaman Baru", value: s?.pending_loans ?? 0, icon: HandCoins },
    { label: "Penukaran Reward", value: s?.pending_redemptions ?? 0, icon: UserCheck },
    { label: "Approval Payroll", value: s?.pending_payroll_approvals ?? 0, icon: FileSearch },
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
      <div class="rounded-2xl border border-emerald-200/60 bg-gradient-to-br from-white via-emerald-50/60 to-sky-50/40 p-5 shadow-sm shadow-emerald-950/5 dark:border-emerald-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900 sm:p-6">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">{{ roleMeta.eyebrow }}</p>
          <h1 class="mt-1 text-3xl font-bold tracking-tight text-zinc-950 dark:text-white">{{ roleMeta.title }}</h1>
          <p class="mt-2 max-w-2xl text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ roleMeta.description }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button variant="outline" size="sm" @click="handleExport('members')">
            <FileDown class="mr-2 h-4 w-4" />Export Anggota
          </Button>
          <Button variant="outline" size="sm" @click="handleExport('payments')">
            <FileDown class="mr-2 h-4 w-4" />Export Pembayaran
          </Button>
        </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-3 gap-3 md:grid-cols-6">
        <Link
          v-for="action in roleMeta.actions"
          :key="action.label"
          :href="action.href"
          prefetch
          class="flex min-h-24 flex-col items-center justify-center gap-1.5 rounded-xl border border-zinc-200/80 bg-white p-4 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:border-zinc-800 dark:bg-zinc-900/80 dark:hover:border-emerald-700 dark:focus-visible:ring-offset-zinc-950"
        >
          <component :is="action.icon" class="size-5 text-zinc-600 dark:text-zinc-400" />
          <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ action.label }}</span>
        </Link>
      </div>

      <!-- Summary Cards -->
      <div v-if="loadingInbox" class="grid gap-4 md:grid-cols-4">
        <Skeleton v-for="n in 4" :key="n" class="h-24 rounded-lg border" />
      </div>
      <div v-else class="grid gap-4 md:grid-cols-4">
        <StatsCard v-for="card in summaryCards" :key="card.label" :label="card.label" :value="card.value" :icon="card.icon" />
      </div>

      <!-- Analytics Cards (Deferred) -->
      <Deferred data="analytics">
        <template #fallback>
          <div class="grid gap-4 md:grid-cols-5">
            <Skeleton v-for="n in 5" :key="n" class="h-24 rounded-lg border" />
          </div>
        </template>
        <div class="grid gap-4 md:grid-cols-5">
          <StatsCard v-for="card in analyticsCards" :key="card.label" :label="card.label" :value="card.value" :icon="card.icon" />
        </div>
      </Deferred>

      <!-- Approval Inbox -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
          <CardTitle>Inbox Approval</CardTitle>
          <Badge variant="secondary">{{ (inbox?.summary?.pending_payments ?? 0) + (inbox?.summary?.pending_loans ?? 0) + (inbox?.summary?.pending_redemptions ?? 0) }} menunggu</Badge>
        </CardHeader>
        <CardContent>
          <div v-if="loadingInbox" class="space-y-2">
            <Skeleton v-for="n in 4" :key="n" class="h-14 w-full rounded-lg" />
          </div>
          <div v-else-if="!inbox?.items?.payments?.length && !inbox?.items?.loans?.length && !inbox?.items?.redemptions?.length" class="py-4">
            <EmptyState :icon="Coins" description="Tidak ada item yang menunggu approval. Semua transaksi sudah diverifikasi." />
          </div>
          <div v-else class="space-y-4">
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
                    <Button size="sm" variant="outline">Review <ArrowRight class="ml-1 size-3" /></Button>
                  </Link>
                </div>
              </div>
            </div>

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
          <div v-if="loadingInbox" class="space-y-2">
            <Skeleton v-for="n in 4" :key="n" class="h-14 w-full rounded-lg" />
          </div>
          <div v-else-if="!exceptionData?.overdue_loans?.length && !exceptionData?.unpaid_dues?.length && !exceptionData?.low_stock?.length" class="py-4">
            <EmptyState :icon="Coins" description="Tidak ada pengecualian yang perlu ditindaklanjuti. Semua dalam batas normal." />
          </div>
          <div v-else class="space-y-4">
            <div v-if="exceptionData?.overdue_loans?.length">
              <h4 class="mb-2 text-sm font-semibold text-red-600">Angsuran Terlambat</h4>
              <div class="rounded-lg border border-red-200 divide-y">
                <div v-for="inst in exceptionData.overdue_loans" :key="inst.id" class="flex items-center justify-between p-3">
                  <div>
                    <span class="font-medium">{{ inst.loan?.member?.name ?? "N/A" }}</span>
                    <span class="ml-2 text-xs text-zinc-500">{{ formatCurrency(inst.amount_due) }}</span>
                    <div class="text-xs text-zinc-500">Jatuh tempo {{ formatDate(inst.due_date) }}</div>
                  </div>
                  <Link v-if="inst.loan?.id" :href="`/cooperative/loans/${inst.loan.id}`">
                    <Button size="sm" variant="outline">Lihat</Button>
                  </Link>
                </div>
              </div>
            </div>

            <div v-if="exceptionData?.unpaid_dues?.length">
              <h4 class="mb-2 text-sm font-semibold text-amber-600">Iuran Belum Dibayar</h4>
              <div class="rounded-lg border border-amber-200 divide-y">
                <div v-for="due in exceptionData.unpaid_dues" :key="due.id" class="flex items-center justify-between p-3">
                  <div>
                    <span class="font-medium">{{ due.member?.name ?? "N/A" }}</span>
                    <span class="ml-2 text-xs text-zinc-500">{{ formatCurrency(due.amount) }}</span>
                    <div class="text-xs text-zinc-500">Jatuh tempo {{ formatDate(due.due_date) }}</div>
                  </div>
                  <StatusBadge :status="due.status == 'UNPAID' ? 'OVERDUE' : 'WARNING'" />
                </div>
              </div>
            </div>

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
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
