<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";
import {
  CheckCircle2,
  ChevronRight,
  ClipboardCheck,
  CreditCard,
  Headphones,
  ListChecks,
  ShieldCheck,
  Star,
  Wallet,
  WalletCards,
  LockKeyhole,
} from "lucide-vue-next";
import type { LucideIcon } from "lucide-vue-next";
import { computed, ref } from "vue";
import MidtransPaymentDialog from "@/components/Kojayaku/MidtransPaymentDialog.vue";
import PageContainer from "@/components/PageContainer.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { storeAccount as storeAccountRoute } from "@/routes/member";

const page = usePage();

const props = defineProps<{
  member: {
    name: string;
    member_no: string;
    validation_status?: string | null;
    onboarding_submitted_at?: string | null;
    organization?: { name: string } | null;
  };
  is_active_member: boolean;
  is_pending_review?: boolean;
  can_access_financial_features: boolean;
  can_preview_financial_summary: boolean;
  can_access_onboarding: boolean;
  onboarding_completeness: {
    progress_percent: number;
    completed_fields: number;
    total_fields: number;
    is_complete: boolean;
    missing: Array<{ key: string; label: string; description: string }>;
  };
  simpanan_pokok_invoice: {
    id: number;
    amount: number;
    paid_amount: number;
    due_date: string;
    status: string;
    period: string;
  } | null;
  simpanan_pokok_progress: {
    amount: number;
    paid: number;
    remaining: number;
    percent: number;
    is_paid: boolean;
  } | null;
  simpanan_wajib_pending: {
    count: number;
    total_amount: number;
    latest_due_date: string | null;
  } | null;
  simpanan_wajib_progress: {
    total_amount: number;
    total_paid: number;
    total_invoices: number;
    paid_invoices: number;
    percent: number;
  } | null;
  simpanan_wajib_invoice: {
    id: number;
    amount: number;
    paid_amount: number;
    due_date: string;
  } | null;
  summary: {
    savings_balance: number;
    active_loans: number;
    loan_outstanding: number;
    points_balance: number;
    member_tier: string;
  };
  recentTransactions: Array<{
    id: string;
    type: string;
    title: string;
    subtitle: string;
    total_amount?: number | string;
    amount?: number | string;
    status?: string | null;
    occurred_at: string | null;
  }>;
  store_account: {
    balance: number;
    balance_label: string;
  } | null;
  recentLoans: Array<{
    id: number;
    status: string;
    outstanding_amount: number | string;
    loan_type?: { name: string } | null;
    next_installment?: {
      id: number;
      installment_no: number;
      due_date: string | null;
      amount_due: number | string;
      amount_paid: number | string;
      remaining_amount: number | string;
      status: string;
    } | null;
  }>;
}>();

const flash = computed(
  () => (page.props.flash as Record<string, string>) ?? {},
);

type InvoiceForDialog = {
  id: number;
  amount: number;
  paid_amount: number;
  due_date: string;
};

const selectedInvoice = ref<InvoiceForDialog | null>(null);

function openPaymentDialog(invoice: InvoiceForDialog) {
  selectedInvoice.value = invoice;
}

function closePaymentDialog() {
  selectedInvoice.value = null;
}

const validationStatus = computed<string>(
  () => props.member.validation_status ?? "",
);

const memberInitials = computed(() =>
  props.member.name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join(""),
);

const memberStatusLabel = computed(() => {
  if (props.is_active_member && validationStatus.value === "ACTIVE") {
    return "Anggota Aktif";
  }

  if (props.is_pending_review) {
    return "Menunggu Review";
  }

  if (validationStatus.value === "REVISION") {
    return "Perlu Revisi";
  }

  if (validationStatus.value === "REJECTED") {
    return "Ditolak";
  }

  return "Calon Anggota";
});

const accessBanner = computed<{
  tone: "warning" | "destructive" | "info";
  title: string;
  description: string;
  icon: LucideIcon;
  cta?: { href: string; label: string };
} | null>(() => {
  if (props.is_pending_review) {
    return {
      tone: "info",
      title: "Menunggu review pengurus",
      description:
        "Data onboarding Anda sudah terkirim. Pengurus koperasi akan memvalidasi data Anda.",
      icon: ShieldCheck,
    };
  }

  switch (validationStatus.value) {
    case "PENDING":
    case "PENDING_VALIDATION":
      return {
        tone: "warning",
        title: "Lengkapi onboarding Anda",
        description:
          "Simpanan, pinjaman, dan reward akan terbuka setelah pengurus menyetujui keanggotaan Anda.",
        icon: ClipboardCheck,
        cta: { href: "/member/onboarding", label: "Lanjutkan Onboarding" },
      };
    case "REVISION":
      return {
        tone: "warning",
        title: "Pengurus meminta revisi",
        description:
          "Perbarui data Anda, lalu submit ulang agar proses validasi dapat dilanjutkan.",
        icon: ClipboardCheck,
        cta: { href: "/member/onboarding", label: "Perbarui Data" },
      };
    case "REJECTED":
      return {
        tone: "destructive",
        title: "Pendaftaran ditolak",
        description:
          "Pengurus menolak pendaftaran Anda. Hubungi admin untuk informasi lebih lanjut.",
        icon: ShieldCheck,
      };
    default:
      return null;
  }
});

const bannerClass = computed(() => {
  const tone = accessBanner.value?.tone;
  if (tone === "destructive")
    return "border-red-200 dark:border-rose-900/50 bg-red-50 dark:bg-rose-950/20 text-red-900 dark:text-rose-300";
  if (tone === "warning")
    return "border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/20 text-amber-900 dark:text-amber-300";

  return "border-emerald-200 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-900 dark:text-emerald-300";
});

const pokokRemaining = computed(() => {
  if (!props.simpanan_pokok_invoice) return 0;

  return (
    props.simpanan_pokok_invoice.amount -
    props.simpanan_pokok_invoice.paid_amount
  );
});

const showOnboardingAlert = computed(() => {
  return (
    props.can_access_onboarding &&
    !props.is_pending_review &&
    !props.onboarding_completeness.is_complete
  );
});

const showPokokAlert = computed(() => {
  if (!props.can_access_financial_features && !props.can_access_onboarding) {
    return false;
  }

  if (!props.simpanan_pokok_progress || props.simpanan_pokok_progress.is_paid) {
    return false;
  }

  return true;
});

const showWajibAlert = computed(() => {
  if (
    !props.simpanan_wajib_progress ||
    props.simpanan_wajib_progress.percent >= 100
  ) {
    return false;
  }

  if (!props.simpanan_wajib_pending) return false;

  return true;
});

type MemberTask = {
  key: string;
  eyebrow: string;
  title: string;
  description: string;
  action: "pay-pokok" | "pay-wajib" | "pay-installment" | "onboarding";
  label: string;
  icon: LucideIcon;
  tone: "amber" | "emerald";
  invoice?: InvoiceForDialog;
};

const memberTasks = computed<MemberTask[]>(() => {
  const tasks: MemberTask[] = [];

  if (showPokokAlert.value && props.simpanan_pokok_invoice) {
    tasks.push({
      key: "simpanan-pokok",
      eyebrow: "Iuran awal",
      title: "Selesaikan Simpanan Pokok",
      description: `Sisa pembayaran ${formatCurrency(pokokRemaining.value)} sebelum layanan anggota aktif sepenuhnya.`,
      action: "pay-pokok",
      label: "Bayar sekarang",
      icon: WalletCards,
      tone: "amber",
      invoice: props.simpanan_pokok_invoice,
    });
  }

  if (
    props.can_access_financial_features &&
    showWajibAlert.value &&
    props.simpanan_wajib_pending
  ) {
    tasks.push({
      key: "simpanan-wajib",
      eyebrow: "Iuran berkala",
      title: "Bayar Simpanan Wajib",
      description: `${props.simpanan_wajib_pending.count} tagihan tertunda dengan total ${formatCurrency(props.simpanan_wajib_pending.total_amount)}.`,
      action: "pay-wajib",
      label: "Lihat tagihan",
      icon: WalletCards,
      tone: "amber",
      invoice: props.simpanan_wajib_invoice ?? undefined,
    });
  }

  if (props.can_access_financial_features) {
    props.recentLoans.forEach((loan) => {
      const installment = loan.next_installment;

      if (!installment || Number(installment.remaining_amount) <= 0) {
        return;
      }

      tasks.push({
        key: `loan-installment-${loan.id}-${installment.id}`,
        eyebrow: `Cicilan ke-${installment.installment_no}`,
        title: `Bayar cicilan ${loan.loan_type?.name ?? "pinjaman"}`,
        description: `${formatCurrency(installment.remaining_amount)} jatuh tempo ${formatDate(installment.due_date)}.`,
        action: "pay-installment",
        label: "Lihat cicilan",
        icon: CreditCard,
        tone: "amber",
      });
    });
  }

  if (showOnboardingAlert.value) {
    tasks.push({
      key: "onboarding",
      eyebrow: "Profil anggota",
      title: "Lengkapi data anggota",
      description: `${props.onboarding_completeness.completed_fields} dari ${props.onboarding_completeness.total_fields} data wajib sudah terisi.`,
      action: "onboarding",
      label: "Lanjutkan",
      icon: ClipboardCheck,
      tone: "emerald",
    });
  }

  return tasks;
});

const summaryCards = computed(() => [
  {
    label: "Saldo Toko",
    value:
      props.can_preview_financial_summary && props.store_account
        ? formatCurrency(props.store_account.balance)
        : props.can_preview_financial_summary
          ? "Belum tersedia"
          : "Terkunci",
    icon: Wallet,
    iconClass: "bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400",
    href: props.can_access_financial_features ? storeAccountRoute().url : null,
    locked: !props.can_access_financial_features,
    lockedMessage: "Aktif setelah verifikasi",
  },
  {
    label: "Saldo Simpanan",
    value: props.can_preview_financial_summary
      ? formatCurrency(props.summary.savings_balance)
      : "Terkunci",
    icon: WalletCards,
    iconClass:
      "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400",
    href: props.can_access_financial_features ? "/member/savings" : null,
    locked: !props.can_access_financial_features,
    lockedMessage: "Aktif setelah verifikasi",
  },
  {
    label: "Pinjaman Aktif",
    value: props.can_preview_financial_summary
      ? formatCurrency(props.summary.loan_outstanding)
      : "Terkunci",
    icon: CreditCard,
    iconClass:
      "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400",
    href: props.can_access_financial_features ? "/member/loans" : null,
    locked: !props.can_access_financial_features,
    lockedMessage: "Aktif setelah verifikasi",
  },
  {
    label: "Poin Saya",
    value: props.can_preview_financial_summary
      ? props.summary.points_balance
      : "Terkunci",
    icon: Star,
    iconClass:
      "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400",
    href: props.can_access_financial_features ? "/member/points" : null,
    locked: !props.can_access_financial_features,
    lockedMessage: "Aktif setelah verifikasi",
  },
]);

function taskToneClass(tone: MemberTask["tone"]): string {
  return tone === "amber"
    ? "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
    : "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300";
}

function taskHref(task: MemberTask): string {
  if (task.action === "onboarding") {
    return "/member/onboarding";
  }

  return task.action === "pay-installment"
    ? "/member/loans"
    : "/member/savings";
}
</script>

<template>
  <Head title="Beranda Kojayaku" />
  <AppLayout :breadcrumbs="[{ title: 'Beranda', href: '/member' }]">
    <PageContainer>
      <div class="flex flex-col gap-6">
        <div
          v-if="flash.success"
          class="rounded-2xl border border-emerald-200 dark:border-emerald-800/30 bg-emerald-50 dark:bg-emerald-950/20 p-4 text-sm font-semibold text-emerald-900 dark:text-emerald-300 shadow-sm flex items-center gap-3 animate-fade-in"
        >
          <span
            class="flex h-2 w-2 rounded-full bg-emerald-600 animate-ping"
          ></span>
          {{ flash.success }}
        </div>

        <section
          class="relative overflow-hidden rounded-3xl border border-emerald-800/10 bg-gradient-to-br from-emerald-800 to-emerald-950 p-5 text-white shadow-xl shadow-emerald-950/20 sm:p-6 lg:p-7"
        >
          <div
            class="pointer-events-none absolute -right-10 -bottom-10 size-44 rounded-full bg-emerald-400/10 blur-3xl"
            aria-hidden="true"
          />
          <div
            class="pointer-events-none absolute -left-10 -top-10 size-44 rounded-full bg-emerald-300/10 blur-3xl"
            aria-hidden="true"
          />

          <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center">
            <div
              class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border-2 border-white/20 bg-white/10 text-xl font-bold text-white shadow-inner backdrop-blur-md sm:h-24 sm:w-24 sm:rounded-full sm:text-3xl"
            >
              {{ memberInitials }}
            </div>
            <div class="min-w-0">
              <p
                class="text-xs font-medium uppercase tracking-wider text-emerald-300"
              >
                Selamat datang kembali,
              </p>
              <div class="mt-1 flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">
                  {{ member.name }}
                </h2>
                <span
                  class="rounded-full bg-white/20 px-3.5 py-0.5 text-xs font-semibold border border-white/30 backdrop-blur-md text-white shadow-sm"
                >
                  {{ memberStatusLabel }}
                </span>
              </div>
              <p
                class="mt-3 max-w-2xl text-sm leading-relaxed text-emerald-100/90"
              >
                Terima kasih telah menjadi bagian dari
                <span class="font-semibold text-emerald-300">{{
                  member.organization?.name || "Koperasi Jaya Bersama"
                }}</span
                >. <br />No. Anggota:
                <span
                  class="font-mono bg-white/10 px-1.5 py-0.5 rounded text-xs"
                  >{{ member.member_no }}</span
                >
              </p>
            </div>
          </div>
        </section>

        <div
          v-if="accessBanner"
          data-test="member-access-banner"
          class="flex flex-col gap-4 rounded-2xl border p-5 sm:flex-row sm:items-center sm:justify-between shadow-sm transition-all dark:border-zinc-800"
          :class="bannerClass"
        >
          <div class="flex items-start gap-3.5">
            <div class="p-2 bg-current/10 rounded-xl">
              <component :is="accessBanner.icon" class="h-5 w-5 shrink-0" />
            </div>
            <div>
              <p class="font-bold text-sm sm:text-base">
                {{ accessBanner.title }}
              </p>
              <p class="text-xs sm:text-sm mt-0.5 opacity-90">
                {{ accessBanner.description }}
              </p>
            </div>
          </div>
          <Link
            v-if="accessBanner.cta"
            :href="accessBanner.cta.href"
            class="inline-flex items-center justify-center rounded-xl bg-current/15 px-4 py-2 text-xs sm:text-sm font-bold transition-all hover:bg-current/25"
          >
            {{ accessBanner.cta.label }}
          </Link>
        </div>

        <section
          class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4"
          aria-label="Ringkasan keuangan anggota"
        >
          <component
            :is="card.href ? Link : 'div'"
            v-for="card in summaryCards"
            :key="card.label"
            :href="card.href ?? undefined"
            :aria-disabled="card.href ? undefined : 'true'"
            :class="[
              'group rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm transition-all duration-200 dark:border-zinc-800 dark:bg-zinc-900 sm:p-5',
              card.href
                ? 'hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:hover:border-emerald-700 dark:focus-visible:ring-offset-zinc-950'
                : 'cursor-not-allowed opacity-80',
            ]"
          >
            <div class="flex flex-col gap-3 sm:gap-4">
              <div
                class="flex size-10 items-center justify-center rounded-xl shadow-sm transition-transform duration-200 group-hover:scale-105 sm:size-11"
                :class="card.iconClass"
              >
                <component :is="card.icon" class="size-5" aria-hidden="true" />
              </div>
              <div class="min-w-0">
                <p
                  class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400"
                >
                  {{ card.label }}
                </p>
                <p
                  class="mt-1 text-lg font-extrabold tracking-tight text-zinc-950 dark:text-white sm:text-xl"
                >
                  {{ card.value }}
                </p>
              </div>
            </div>
            <span
              class="mt-4 flex items-center gap-1 text-xs font-semibold text-emerald-800 dark:text-emerald-300"
              :class="{ 'text-zinc-500 dark:text-zinc-400': card.locked }"
              >{{ card.href ? "Lihat detail" : card.lockedMessage }}
              <LockKeyhole
                v-if="card.locked"
                class="size-3.5"
                aria-hidden="true"
              />
              <ChevronRight
                v-else
                class="size-3.5 transition-transform group-hover:translate-x-0.5"
                aria-hidden="true"
              />
            </span>
          </component>
        </section>

        <!-- Member Tasks and Loan Status -->
        <section class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
          <!-- Member Tasks Card -->
          <div
            class="flex flex-col justify-between rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-5"
          >
            <div>
              <div class="flex items-center justify-between gap-4">
                <h2
                  class="font-bold text-zinc-900 dark:text-white tracking-tight"
                >
                  Tugas Anggota
                </h2>
                <Link
                  v-if="can_access_financial_features"
                  href="/member/savings"
                  class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-300 hover:underline"
                >
                  Kelola simpanan
                </Link>
              </div>
              <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Hal yang perlu Anda selesaikan untuk menjaga layanan anggota
                tetap aktif.
              </p>
              <div v-if="memberTasks.length > 0" class="mt-5 space-y-3">
                <div
                  v-for="task in memberTasks"
                  :key="task.key"
                  class="flex flex-col gap-4 rounded-2xl border border-zinc-100 p-4 transition-colors hover:bg-zinc-50/60 dark:border-zinc-800 dark:hover:bg-zinc-800/30 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div class="flex min-w-0 items-start gap-3">
                    <div
                      class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl shadow-sm"
                      :class="taskToneClass(task.tone)"
                    >
                      <component :is="task.icon" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                      <p
                        class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                      >
                        {{ task.eyebrow }}
                      </p>
                      <p
                        class="mt-1 font-bold text-zinc-800 dark:text-zinc-200 text-sm"
                      >
                        {{ task.title }}
                      </p>
                      <p
                        class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400"
                      >
                        {{ task.description }}
                      </p>
                    </div>
                  </div>
                  <button
                    v-if="task.action === 'pay-pokok' && task.invoice"
                    type="button"
                    class="inline-flex min-h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-emerald-800 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:bg-emerald-700 dark:hover:bg-emerald-600 dark:focus-visible:ring-offset-zinc-900"
                    @click="openPaymentDialog(task.invoice)"
                  >
                    {{ task.label }} <ChevronRight class="size-4" />
                  </button>
                  <button
                    v-else-if="task.action === 'pay-wajib' && task.invoice"
                    type="button"
                    class="inline-flex min-h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-emerald-800 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:bg-emerald-700 dark:hover:bg-emerald-600 dark:focus-visible:ring-offset-zinc-900"
                    @click="openPaymentDialog(task.invoice)"
                  >
                    {{ task.label }} <ChevronRight class="size-4" />
                  </button>
                  <Link
                    v-else
                    :href="taskHref(task)"
                    class="inline-flex min-h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-emerald-200 px-4 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 dark:border-emerald-800 dark:text-emerald-300 dark:hover:bg-emerald-950/30 dark:focus-visible:ring-offset-zinc-900"
                  >
                    {{ task.label }} <ChevronRight class="size-4" />
                  </Link>
                </div>
              </div>
              <div
                v-else
                class="mt-5 flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20"
              >
                <div
                  class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                >
                  <ListChecks class="size-5" />
                </div>
                <div>
                  <p
                    class="text-sm font-bold text-emerald-900 dark:text-emerald-300"
                  >
                    Semua tugas sudah selesai
                  </p>
                  <p
                    class="mt-0.5 text-xs text-emerald-800 dark:text-emerald-200/70"
                  >
                    Terima kasih sudah menjaga data dan kewajiban anggota Anda.
                  </p>
                </div>
              </div>
            </div>
            <Link
              v-if="can_access_financial_features"
              href="/member/transactions"
              class="mt-6 flex items-center justify-center gap-2 rounded-xl bg-zinc-50 dark:bg-zinc-950 py-3 text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors"
            >
              Buka Riwayat Transaksi
              <ChevronRight class="h-4 w-4" />
            </Link>
          </div>

          <!-- Status Pinjaman Card -->
          <div
            class="flex flex-col justify-between rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-5"
          >
            <div>
              <div class="flex items-center justify-between gap-4">
                <h2
                  class="font-bold text-zinc-900 dark:text-white tracking-tight"
                >
                  Status Pinjaman
                </h2>
                <Link
                  v-if="can_access_financial_features"
                  href="/member/loans"
                  class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-300 hover:underline"
                >
                  Lihat semua
                </Link>
              </div>

              <div
                v-if="recentLoans.length === 0"
                class="mt-5 flex min-h-52 flex-col items-center justify-center rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/20 p-5 text-center dark:border-emerald-900/30 dark:bg-emerald-950/10"
              >
                <div
                  class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-800 dark:bg-emerald-700 text-white shadow-lg shadow-emerald-800/20 dark:shadow-emerald-950/30"
                >
                  <CheckCircle2 class="h-7 w-7" />
                </div>
                <p
                  class="mt-4 font-bold text-emerald-950 dark:text-emerald-400"
                >
                  Belum ada pinjaman aktif
                </p>
                <p
                  class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 max-w-xs leading-relaxed"
                >
                  Anda tidak memiliki pengajuan pinjaman aktif saat ini. Ajukan
                  sekarang jika Anda butuh modal usaha atau dana darurat.
                </p>
                <Link
                  href="/member/loans"
                  class="mt-6 inline-flex items-center gap-2 rounded-xl bg-emerald-800 dark:bg-emerald-700 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-emerald-800/20 dark:shadow-emerald-950/35 transition hover:bg-emerald-900 dark:hover:bg-emerald-600 hover:scale-105 active:scale-95"
                >
                  Ajukan Pinjaman
                  <ChevronRight class="h-4 w-4" />
                </Link>
              </div>

              <div v-else class="mt-5 space-y-3">
                <div
                  v-for="loan in recentLoans"
                  :key="loan.id"
                  class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-50 dark:border-zinc-800 p-4 transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30"
                >
                  <div class="min-w-0">
                    <p
                      class="font-bold text-zinc-800 dark:text-zinc-200 text-sm"
                    >
                      {{ loan.loan_type?.name || "Pinjaman" }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                      Status:
                      <span
                        class="font-semibold text-emerald-800 dark:text-emerald-400"
                        >{{ loan.status }}</span
                      >
                    </p>
                  </div>
                  <p
                    class="font-extrabold text-zinc-900 dark:text-white text-sm"
                  >
                    {{ formatCurrency(loan.outstanding_amount) }}
                  </p>
                </div>
              </div>
            </div>
            <Link
              v-if="can_access_financial_features"
              href="/member/loans"
              class="mt-6 flex items-center justify-center gap-2 rounded-xl bg-zinc-50 dark:bg-zinc-950 py-3 text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors"
            >
              Kelola Pinjaman
              <ChevronRight class="h-4 w-4" />
            </Link>
          </div>
        </section>

        <!-- Help Section Banner -->
        <section
          class="flex flex-col gap-4 rounded-3xl border border-emerald-100 dark:border-emerald-900/35 bg-gradient-to-br from-emerald-50/60 to-teal-50/30 dark:from-emerald-950/20 dark:to-teal-950/10 p-6 sm:flex-row sm:items-center sm:justify-between shadow-sm"
        >
          <div class="flex items-center gap-4">
            <div
              class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white dark:bg-zinc-900 text-emerald-800 dark:text-emerald-400 shadow-sm border border-emerald-50 dark:border-zinc-800"
            >
              <Headphones class="h-7 w-7" />
            </div>
            <div>
              <p class="font-bold text-emerald-900 dark:text-emerald-300">
                Butuh bantuan atau informasi tambahan?
              </p>
              <p
                class="text-sm text-emerald-800 dark:text-zinc-400 mt-1 max-w-xl"
              >
                Kami siap membantu Anda. Hubungi tim layanan anggota kami untuk
                bantuan cepat mengenai keanggotaan, simpanan, atau pinjaman.
              </p>
            </div>
          </div>
          <Link
            href="/member/profile"
            class="inline-flex items-center justify-center gap-2.5 rounded-xl border border-emerald-200 dark:border-emerald-800/40 bg-white dark:bg-zinc-900 px-5 py-2.5 text-sm font-bold text-emerald-800 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-zinc-800 transition hover:shadow-sm"
          >
            <Headphones class="h-4 w-4" />
            Hubungi Kami
          </Link>
        </section>
      </div>

      <MidtransPaymentDialog
        :open="selectedInvoice !== null"
        :invoice="selectedInvoice"
        @update:open="closePaymentDialog"
      />
    </PageContainer>
  </AppLayout>
</template>
>
