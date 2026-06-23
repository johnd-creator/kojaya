<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";
import {
  Banknote,
  CheckCircle2,
  ChevronRight,
  ClipboardCheck,
  CreditCard,
  Headphones,
  History,
  ReceiptText,
  ShieldCheck,
  ShoppingBag,
  Star,
  UserRound,
  Wallet,
  WalletCards,
  X
  
} from "lucide-vue-next";
import type {LucideIcon} from "lucide-vue-next";
import { computed, ref } from "vue";
import PaymentProofDialog from "@/components/Kojayaku/PaymentProofDialog.vue";
import PageContainer from "@/components/PageContainer.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";

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
  recentLoans: Array<{
    id: number;
    status: string;
    outstanding_amount: number | string;
    loan_type?: { name: string } | null;
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
const dismissedAlerts = ref<Set<string>>(new Set());

function openPaymentDialog(invoice: InvoiceForDialog) {
  selectedInvoice.value = invoice;
}

function closePaymentDialog() {
  selectedInvoice.value = null;
}

function dismiss(key: string) {
  dismissedAlerts.value.add(key);
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
  if (tone === "destructive") return "border-red-200 dark:border-rose-900/50 bg-red-50 dark:bg-rose-950/20 text-red-900 dark:text-rose-300";
  if (tone === "warning") return "border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/20 text-amber-900 dark:text-amber-300";

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
  if (props.onboarding_completeness.is_complete) return false;

  return !dismissedAlerts.value.has("onboarding");
});

const showPokokAlert = computed(() => {
  if (!props.simpanan_pokok_progress || props.simpanan_pokok_progress.is_paid) {
    return false;
  }

  return !dismissedAlerts.value.has("pokok");
});

const showWajibAlert = computed(() => {
  if (
    !props.simpanan_wajib_progress ||
    props.simpanan_wajib_progress.percent >= 100
  ) {
    return false;
  }

  if (!props.simpanan_wajib_pending) return false;

  return !dismissedAlerts.value.has("wajib");
});

const progressColor = (percent: number) => {
  if (percent >= 100) return "bg-emerald-500";
  if (percent >= 50) return "bg-amber-500";

  return "bg-rose-500";
};

const summaryCards = computed(() => [
  {
    label: "Saldo Simpanan",
    value: formatCurrency(props.summary.savings_balance),
    icon: WalletCards,
    iconClass: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400",
    href: "/member/savings",
  },
  {
    label: "Pinjaman Aktif",
    value: formatCurrency(props.summary.loan_outstanding),
    icon: CreditCard,
    iconClass: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400",
    href: "/member/loans",
  },
  {
    label: "Poin Saya",
    value: props.summary.points_balance,
    icon: Star,
    iconClass: "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400",
    href: "/member/points",
  },
]);

const quickLinks = computed(() => [
  {
    label: "Simpanan & Tagihan",
    href: "/member/savings",
    icon: WalletCards,
    iconClass: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400",
    activeOnly: true,
  },
  {
    label: "Pinjaman",
    href: "/member/loans",
    icon: CreditCard,
    iconClass: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400",
    activeOnly: true,
  },
  {
    label: "Poin",
    href: "/member/points",
    icon: Star,
    iconClass: "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400",
    activeOnly: true,
  },
  {
    label: "Transaksi POS",
    href: "/member/transactions",
    icon: ShoppingBag,
    iconClass: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400",
    activeOnly: true,
  },
  {
    label: "Profil Saya",
    href: "/member/profile",
    icon: UserRound,
    iconClass: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400",
    activeOnly: false,
  },
  {
    label: "Onboarding",
    href: "/member/onboarding",
    icon: ClipboardCheck,
    iconClass: "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400",
    activeOnly: false,
  },
]);

const visibleQuickLinks = computed(() =>
  quickLinks.value.filter((item) => props.is_active_member || !item.activeOnly),
);

const transactionRows = computed(() =>
  props.recentTransactions.slice(0, 3).map((transaction) => ({
    id: transaction.id,
    title: transaction.title,
    subtitle:
      transaction.type === "SAVINGS_PAYMENT" && transaction.status
        ? `${transaction.subtitle} · ${transaction.status}`
        : transaction.subtitle,
    amount: formatCurrency(transaction.amount ?? transaction.total_amount),
    date: transaction.occurred_at
      ? new Intl.DateTimeFormat("id-ID", {
          day: "2-digit",
          month: "short",
          year: "numeric",
          hour: "2-digit",
          minute: "2-digit",
        }).format(new Date(transaction.occurred_at))
      : "-",
  })),
);
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
          <span class="flex h-2 w-2 rounded-full bg-emerald-600 animate-ping"></span>
          {{ flash.success }}
        </div>

        <section
          class="grid gap-6 rounded-3xl border border-emerald-800/10 bg-gradient-to-br from-emerald-800 to-emerald-950 p-4 text-white shadow-xl shadow-emerald-950/20 sm:p-6 lg:grid-cols-[1.1fr_0.9fr] lg:p-8 relative overflow-hidden"
        >
          <!-- Decorative background glow -->
          <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute -left-10 -top-10 w-40 h-40 bg-emerald-400/10 rounded-full blur-3xl pointer-events-none"></div>

          <div class="flex flex-col gap-6 sm:flex-row sm:items-center relative z-10">
            <div
              class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border-2 border-white/20 bg-white/10 text-xl font-bold text-white shadow-inner backdrop-blur-md sm:h-24 sm:w-24 sm:rounded-full sm:text-3xl"
            >
              {{ memberInitials }}
            </div>
            <div class="min-w-0">
              <p class="text-xs font-medium uppercase tracking-wider text-emerald-300">Selamat datang kembali,</p>
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
              <p class="mt-3 max-w-2xl text-sm text-emerald-100/90 leading-relaxed">
                Terima kasih telah menjadi bagian dari
                <span class="font-semibold text-emerald-300">{{ member.organization?.name || "Koperasi Kajaya Bersama" }}</span>.
                <br />No. Anggota: <span class="font-mono bg-white/10 px-1.5 py-0.5 rounded text-xs">{{ member.member_no }}</span>
              </p>
            </div>
          </div>

          <div
            class="flex items-center gap-6 border-t border-white/10 pt-6 lg:border-l lg:border-t-0 lg:pl-8 lg:pt-0 relative z-10"
          >
            <div class="min-w-0 flex-1">
              <p class="font-bold text-lg text-white">
                Ajukan pinjaman dengan mudah
              </p>
              <p class="mt-1 text-sm text-emerald-200/90">
                Proses cepat dan transparan untuk kebutuhan finansial Anda.
              </p>
              <Link
                href="/member/loans"
                class="mt-5 inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-emerald-950 transition hover:bg-emerald-50 hover:scale-105 active:scale-95 shadow-lg shadow-emerald-950/35"
              >
                Ajukan Sekarang
                <ChevronRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
              </Link>
            </div>
            <div
              class="hidden h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-white/10 border border-white/10 text-white sm:flex shadow-inner backdrop-blur-sm"
            >
              <Wallet class="h-12 w-12 text-emerald-300" />
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
              <component
                :is="accessBanner.icon"
                class="h-5 w-5 shrink-0"
              />
            </div>
            <div>
              <p class="font-bold text-sm sm:text-base">{{ accessBanner.title }}</p>
              <p class="text-xs sm:text-sm mt-0.5 opacity-90">{{ accessBanner.description }}</p>
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

        <!-- Onboarding Progress Alert -->
        <div
          v-if="showOnboardingAlert"
          class="relative rounded-2xl border border-amber-200 dark:border-amber-900/30 bg-amber-50/50 dark:bg-amber-950/10 backdrop-blur-sm p-4 shadow-sm sm:p-6"
        >
          <button
            type="button"
            class="absolute right-4 top-4 z-10 rounded-lg p-1.5 text-amber-500 hover:bg-amber-100/50 hover:text-amber-800 dark:hover:bg-amber-900/20 dark:hover:text-amber-400 transition"
            @click="dismiss('onboarding')"
          >
            <X class="h-4 w-4" />
          </button>
          <div class="flex items-start gap-4 pr-8">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 shadow-inner"
            >
              <ClipboardCheck class="h-6 w-6" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-4">
                <h3 class="font-bold text-amber-900 dark:text-amber-400 text-base">
                  Lengkapi Profil Onboarding Anda
                </h3>
                <span class="text-sm font-extrabold text-amber-800 dark:text-amber-400 bg-amber-100/50 dark:bg-amber-900/20 px-2.5 py-0.5 rounded-full border border-amber-200/50 dark:border-amber-900/30">
                  {{ onboarding_completeness.progress_percent }}%
                </span>
              </div>
              <p class="mt-1 text-sm text-amber-800/90 dark:text-amber-300 leading-relaxed">
                {{ onboarding_completeness.completed_fields }} dari
                {{ onboarding_completeness.total_fields }} data wajib telah diisi. Silakan lengkapi sisa kolom untuk mengaktifkan seluruh fitur.
              </p>
              <Link
                href="/member/onboarding"
                class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-amber-700 px-4 py-2 text-sm font-bold text-white shadow-sm shadow-amber-900/10 transition hover:bg-amber-800"
              >
                Lanjutkan Onboarding
                <ChevronRight class="h-4 w-4" />
              </Link>
              <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-amber-100 dark:bg-zinc-800 border border-amber-200/30 dark:border-zinc-700/50">
                <div
                  class="h-full rounded-full transition-all duration-500 shadow-sm"
                  :class="
                    progressColor(onboarding_completeness.progress_percent)
                  "
                  :style="{
                    width: onboarding_completeness.progress_percent + '%',
                  }"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Simpanan Pokok Alert -->
        <div
          v-if="showPokokAlert && simpanan_pokok_invoice"
          class="relative rounded-2xl border border-amber-200 dark:border-amber-900/30 bg-amber-50/50 dark:bg-amber-950/10 backdrop-blur-sm p-4 shadow-sm animate-fade-in sm:p-6"
        >
          <button
            type="button"
            class="absolute right-4 top-4 z-10 rounded-lg p-1.5 text-amber-500 hover:bg-amber-100/50 hover:text-amber-800 dark:hover:bg-amber-900/20 dark:hover:text-amber-400 transition"
            @click="dismiss('pokok')"
          >
            <X class="h-4 w-4" />
          </button>
          <div class="flex items-start gap-4 pr-8">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 shadow-inner"
            >
              <Banknote class="h-6 w-6" />
            </div>
            <div class="min-w-0 flex-1">
              <h3 class="font-bold text-amber-900 dark:text-amber-400 text-base">Pembayaran Simpanan Pokok</h3>
              <p class="mt-1 text-sm text-amber-800/90 dark:text-amber-300 leading-relaxed">
                Harap lunasi Simpanan Pokok Anda. Sisa pembayaran: <span class="font-bold text-amber-950 dark:text-white">{{ formatCurrency(pokokRemaining) }}</span>.
              </p>
              <button
                type="button"
                class="mt-4 inline-flex items-center gap-2 rounded-xl bg-amber-800 dark:bg-amber-700 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-amber-800/20 dark:shadow-amber-950/20 transition hover:bg-amber-950 dark:hover:bg-amber-600 hover:scale-105 active:scale-95"
                @click="openPaymentDialog(simpanan_pokok_invoice!)"
              >
                Bayar Sekarang
              </button>
            </div>
          </div>
        </div>

        <!-- Simpanan Wajib Alert (Emerald-themed to match Savings color) -->
        <div
          v-if="
            showWajibAlert && simpanan_wajib_pending && simpanan_wajib_progress
          "
          class="relative rounded-2xl border border-emerald-200 dark:border-emerald-900/30 bg-emerald-50/50 dark:bg-emerald-950/10 backdrop-blur-sm p-4 shadow-sm animate-fade-in sm:p-6"
        >
          <button
            type="button"
            class="absolute right-4 top-4 z-10 rounded-lg p-1.5 text-emerald-500 hover:bg-emerald-100/50 hover:text-emerald-700 dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400 transition"
            @click="dismiss('wajib')"
          >
            <X class="h-4 w-4" />
          </button>
          <div class="flex items-start gap-4 pr-8">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 shadow-inner"
            >
              <WalletCards class="h-6 w-6" />
            </div>
            <div class="min-w-0 flex-1">
              <h3 class="font-bold text-emerald-900 dark:text-emerald-400 text-base">Pembayaran Simpanan Wajib</h3>
              <p class="mt-1 text-sm text-emerald-800/90 dark:text-emerald-300 leading-relaxed">
                Terdapat <span class="font-bold">{{ simpanan_wajib_pending.count }} tagihan</span> simpanan wajib belum terbayar, total tagihan sebesar <span class="font-bold text-emerald-950 dark:text-white">{{ formatCurrency(simpanan_wajib_pending.total_amount) }}</span>.
              </p>
              <button
                v-if="simpanan_wajib_invoice"
                type="button"
                class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-800 dark:bg-emerald-700 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-emerald-800/20 dark:shadow-emerald-950/20 transition hover:bg-emerald-950 dark:hover:bg-emerald-600 hover:scale-105 active:scale-95"
                @click="openPaymentDialog(simpanan_wajib_invoice)"
              >
                Bayar Sekarang
              </button>
              <Link
                v-else
                href="/member/savings"
                class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-800 dark:bg-emerald-700 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-emerald-800/20 dark:shadow-emerald-950/20 transition hover:bg-emerald-950 dark:hover:bg-emerald-600 hover:scale-105 active:scale-95"
              >
                Lihat Semua Tagihan
              </Link>
            </div>
          </div>
        </div>

        <!-- Summary Cards Section -->
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
          <Link
            v-for="card in summaryCards"
            :key="card.label"
            :href="card.href"
            class="group overflow-hidden rounded-2xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-xl hover:-translate-y-1.5 hover:border-emerald-300 dark:hover:border-emerald-700 transition-all duration-300"
          >
            <div class="flex items-center gap-4 p-4 sm:gap-5 sm:p-6">
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-sm group-hover:scale-110 transition-transform duration-300 sm:h-16 sm:w-16"
                :class="card.iconClass"
              >
                <component :is="card.icon" class="h-8 w-8" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ card.label }}</p>
                <p class="mt-1.5 text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight sm:text-2xl">
                  {{ card.value }}
                </p>
              </div>
            </div>
            <div
              class="flex items-center justify-between border-t border-zinc-50 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/40 group-hover:bg-emerald-50/30 dark:group-hover:bg-emerald-950/20 px-4 py-3 sm:px-6 sm:py-3.5 text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400 transition-colors"
            >
              Lihat Detail
              <ChevronRight
                class="h-4 w-4 transition-transform group-hover:translate-x-1"
              />
            </div>
          </Link>
        </section>

        <!-- Quick Links Section -->
        <section
          class="rounded-3xl border border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/30 p-4 shadow-sm sm:p-6"
        >
          <h2 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight">Akses Cepat</h2>
          <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-6">
            <Link
              v-for="item in visibleQuickLinks"
              :key="item.label"
              :href="item.href"
              class="group flex min-h-16 items-center gap-3 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 text-sm font-semibold text-zinc-800 dark:text-zinc-200 shadow-sm transition-all duration-300 hover:border-emerald-300 dark:hover:border-emerald-700 hover:bg-emerald-50/20 dark:hover:bg-emerald-950/10 hover:-translate-y-1 hover:shadow-md"
            >
              <span
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-all duration-300 group-hover:scale-110 shadow-sm"
                :class="item.iconClass"
              >
                <component :is="item.icon" class="h-5 w-5" />
              </span>
              <span class="min-w-0 leading-tight group-hover:text-emerald-900 dark:group-hover:text-emerald-400 transition-colors">{{ item.label }}</span>
            </Link>
          </div>
        </section>

        <!-- Two Column Content Grid -->
        <section class="grid gap-6 xl:grid-cols-[0.95fr_1fr]">
          <!-- Transaksi Terbaru Card -->
          <div
            class="rounded-3xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm flex flex-col justify-between sm:p-6"
          >
            <div>
              <div class="flex items-center justify-between gap-4">
                <h2 class="font-bold text-zinc-900 dark:text-white tracking-tight">Transaksi Terbaru</h2>
                <Link
                  href="/member/transactions"
                  class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-300 hover:underline"
                >
                  Lihat semua
                </Link>
              </div>
              <div class="mt-5 space-y-3">
                <div
                  v-for="transaction in transactionRows"
                  :key="transaction.id"
                  class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-50 dark:border-zinc-800 p-4 transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30"
                >
                  <div class="flex min-w-0 items-center gap-3">
                    <div
                      class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-900/35 text-emerald-700 dark:text-emerald-400 shadow-sm"
                    >
                      <ReceiptText class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                      <p class="truncate font-bold text-zinc-800 dark:text-zinc-200 text-sm">
                        {{ transaction.title }}
                      </p>
                      <p class="text-xs text-zinc-400 dark:text-zinc-500">
                        {{ transaction.subtitle }}
                      </p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="font-extrabold text-emerald-800 dark:text-emerald-400 text-sm">
                      {{ transaction.amount }}
                    </p>
                    <p class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 mt-0.5">{{ transaction.date }}</p>
                  </div>
                </div>
                <div
                  v-if="transactionRows.length === 0"
                  class="flex flex-col items-center justify-center gap-3 py-12 text-center text-sm text-zinc-400 dark:text-zinc-500 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl bg-zinc-50/30 dark:bg-zinc-900/50"
                >
                  <History class="h-8 w-8 text-zinc-300 dark:text-zinc-700" />
                  <span>Belum ada transaksi terbaru.</span>
                </div>
              </div>
            </div>
            <Link
              href="/member/transactions"
              class="mt-6 flex items-center justify-center gap-2 rounded-xl bg-zinc-50 dark:bg-zinc-950 py-3 text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors"
            >
              Lihat Semua Transaksi
              <ChevronRight class="h-4 w-4" />
            </Link>
          </div>

          <!-- Status Pinjaman Card -->
          <div
            class="rounded-3xl border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm flex flex-col justify-between sm:p-6"
          >
            <div>
              <div class="flex items-center justify-between gap-4">
                <h2 class="font-bold text-zinc-900 dark:text-white tracking-tight">Status Pinjaman</h2>
                <Link
                  href="/member/loans"
                  class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-300 hover:underline"
                >
                  Lihat semua
                </Link>
              </div>

              <div
                v-if="recentLoans.length === 0"
                class="mt-5 flex min-h-[260px] flex-col items-center justify-center rounded-2xl border border-dashed border-emerald-200 dark:border-emerald-900/30 bg-emerald-50/20 dark:bg-emerald-950/10 p-6 text-center"
              >
                <div
                  class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-800 dark:bg-emerald-700 text-white shadow-lg shadow-emerald-800/20 dark:shadow-emerald-950/30"
                >
                  <CheckCircle2 class="h-7 w-7" />
                </div>
                <p class="mt-4 font-bold text-emerald-950 dark:text-emerald-400">
                  Belum ada pinjaman aktif
                </p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 max-w-xs leading-relaxed">
                  Anda tidak memiliki pengajuan pinjaman aktif saat ini. Ajukan sekarang jika Anda butuh modal usaha atau dana darurat.
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
                    <p class="font-bold text-zinc-800 dark:text-zinc-200 text-sm">
                      {{ loan.loan_type?.name || "Pinjaman" }}
                    </p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                      Status: <span class="font-semibold text-emerald-800 dark:text-emerald-400">{{ loan.status }}</span>
                    </p>
                  </div>
                  <p class="font-extrabold text-zinc-900 dark:text-white text-sm">
                    {{ formatCurrency(loan.outstanding_amount) }}
                  </p>
                </div>
              </div>
            </div>
            <Link
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
              <p class="font-bold text-emerald-900 dark:text-emerald-300">Butuh bantuan atau informasi tambahan?</p>
              <p class="text-sm text-emerald-900/70 dark:text-zinc-400 mt-1 max-w-xl">
                Kami siap membantu Anda. Hubungi tim layanan anggota kami untuk bantuan cepat mengenai keanggotaan, simpanan, atau pinjaman.
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

      <PaymentProofDialog
        :open="selectedInvoice !== null"
        :invoice="selectedInvoice"
        @update:open="closePaymentDialog"
      />
    </PageContainer>
  </AppLayout>
</template>>
