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
  X,
  type LucideIcon,
} from "lucide-vue-next";
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
    id: number;
    transaction_no: string;
    total_amount: number | string;
    sold_at: string;
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
  if (tone === "destructive") return "border-red-200 bg-red-50 text-red-900";
  if (tone === "warning") return "border-amber-200 bg-amber-50 text-amber-900";

  return "border-emerald-200 bg-emerald-50 text-emerald-900";
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

  return "bg-red-500";
};

const summaryCards = computed(() => [
  {
    label: "Saldo Simpanan",
    value: formatCurrency(props.summary.savings_balance),
    icon: WalletCards,
    iconClass: "bg-emerald-50 text-emerald-700",
    href: "/member/savings",
  },
  {
    label: "Pinjaman Aktif",
    value: formatCurrency(props.summary.loan_outstanding),
    icon: CreditCard,
    iconClass: "bg-blue-50 text-blue-700",
    href: "/member/loans",
  },
  {
    label: "Poin Saya",
    value: props.summary.points_balance,
    icon: Star,
    iconClass: "bg-amber-50 text-amber-600",
    href: "/member/points",
  },
]);

const quickLinks = computed(() => [
  {
    label: "Simpanan & Tagihan",
    href: "/member/savings",
    icon: WalletCards,
    iconClass: "bg-emerald-50 text-emerald-700",
    activeOnly: true,
  },
  {
    label: "Pinjaman",
    href: "/member/loans",
    icon: CreditCard,
    iconClass: "bg-blue-50 text-blue-700",
    activeOnly: true,
  },
  {
    label: "Poin",
    href: "/member/points",
    icon: Star,
    iconClass: "bg-amber-50 text-amber-600",
    activeOnly: true,
  },
  {
    label: "Transaksi POS",
    href: "/member/transactions",
    icon: ShoppingBag,
    iconClass: "bg-violet-50 text-violet-700",
    activeOnly: true,
  },
  {
    label: "Profil Saya",
    href: "/member/profile",
    icon: UserRound,
    iconClass: "bg-green-50 text-green-700",
    activeOnly: false,
  },
  {
    label: "Onboarding",
    href: "/member/onboarding",
    icon: ClipboardCheck,
    iconClass: "bg-teal-50 text-teal-700",
    activeOnly: false,
  },
]);

const visibleQuickLinks = computed(() =>
  quickLinks.value.filter((item) => props.is_active_member || !item.activeOnly),
);

const transactionRows = computed(() =>
  props.recentTransactions.slice(0, 3).map((transaction) => ({
    id: transaction.id,
    title: transaction.transaction_no,
    subtitle: "Transaksi POS",
    amount: formatCurrency(transaction.total_amount),
    date: new Intl.DateTimeFormat("id-ID", {
      day: "2-digit",
      month: "short",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    }).format(new Date(transaction.sold_at)),
  })),
);
</script>

<template>
  <Head title="Beranda Kojayaku" />
  <AppLayout :breadcrumbs="[{ title: 'Beranda', href: '/member' }]">
    <PageContainer class="bg-white">
      <div class="flex flex-col gap-6">
        <div
          v-if="flash.success"
          class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"
        >
          {{ flash.success }}
        </div>

        <section
          class="grid gap-6 rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-white p-5 shadow-sm shadow-emerald-950/5 lg:grid-cols-[1fr_0.65fr] lg:p-7"
        >
          <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            <div
              class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full border border-emerald-100 bg-white text-3xl font-bold text-emerald-700 shadow-sm"
            >
              {{ memberInitials }}
            </div>
            <div class="min-w-0">
              <p class="text-sm text-zinc-500">Selamat datang kembali,</p>
              <div class="mt-2 flex flex-wrap items-center gap-3">
                <h2 class="text-3xl font-bold text-zinc-950">
                  {{ member.name }}
                </h2>
                <span
                  class="rounded-lg bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-800"
                >
                  {{ memberStatusLabel }}
                </span>
              </div>
              <p class="mt-3 max-w-2xl text-sm text-zinc-600">
                Terima kasih telah menjadi bagian dari
                {{ member.organization?.name || "Koperasi Kajaya Bersama" }}.
                No. anggota {{ member.member_no }}.
              </p>
            </div>
          </div>

          <div
            class="flex items-center gap-6 border-t border-emerald-100 pt-5 lg:border-l lg:border-t-0 lg:pl-8 lg:pt-0"
          >
            <div class="min-w-0 flex-1">
              <p class="font-bold text-emerald-800">
                Ajukan pinjaman dengan mudah
              </p>
              <p class="mt-2 text-sm text-zinc-600">
                Proses cepat dan transparan untuk kebutuhan Anda.
              </p>
              <Link
                href="/member/loans"
                class="mt-5 inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:border-emerald-300 hover:bg-emerald-50"
              >
                Ajukan Sekarang
                <ChevronRight class="h-4 w-4" />
              </Link>
            </div>
            <div
              class="hidden h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 sm:flex"
            >
              <Wallet class="h-12 w-12" />
            </div>
          </div>
        </section>

        <div
          v-if="accessBanner"
          data-test="member-access-banner"
          class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
          :class="bannerClass"
        >
          <div class="flex items-start gap-3">
            <component
              :is="accessBanner.icon"
              class="mt-0.5 h-5 w-5 shrink-0"
            />
            <div>
              <p class="font-semibold">{{ accessBanner.title }}</p>
              <p class="text-sm">{{ accessBanner.description }}</p>
            </div>
          </div>
          <Link
            v-if="accessBanner.cta"
            :href="accessBanner.cta.href"
            class="inline-flex items-center justify-center rounded-md bg-current/10 px-3 py-1.5 text-sm font-semibold underline-offset-2 hover:underline"
          >
            {{ accessBanner.cta.label }}
          </Link>
        </div>

        <div
          v-if="showOnboardingAlert"
          class="relative rounded-xl border border-amber-200 bg-amber-50 p-5"
        >
          <button
            type="button"
            class="absolute right-3 top-3 z-10 rounded p-1 text-amber-400 transition hover:bg-amber-100 hover:text-amber-600"
            @click="dismiss('onboarding')"
          >
            <X class="h-4 w-4" />
          </button>
          <div class="flex items-start gap-3 pr-8">
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100"
            >
              <ClipboardCheck class="h-5 w-5 text-amber-700" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-4">
                <h3 class="font-semibold text-amber-900">
                  Kelengkapan Profil
                </h3>
                <span class="text-sm font-semibold text-amber-700">
                  {{ onboarding_completeness.progress_percent }}%
                </span>
              </div>
              <p class="mt-1 text-sm text-amber-700">
                {{ onboarding_completeness.completed_fields }} dari
                {{ onboarding_completeness.total_fields }} data sudah terisi.
              </p>
              <div class="mt-3 h-2 overflow-hidden rounded-full bg-amber-200">
                <div
                  class="h-full rounded-full transition-all duration-500"
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

        <div
          v-if="showPokokAlert && simpanan_pokok_invoice"
          class="relative rounded-xl border border-amber-200 bg-amber-50 p-5"
        >
          <button
            type="button"
            class="absolute right-3 top-3 z-10 rounded p-1 text-amber-400 transition hover:bg-amber-100 hover:text-amber-600"
            @click="dismiss('pokok')"
          >
            <X class="h-4 w-4" />
          </button>
          <div class="flex items-start gap-3 pr-8">
            <Banknote class="h-5 w-5 text-amber-700" />
            <div class="min-w-0 flex-1">
              <h3 class="font-semibold text-amber-900">Simpanan Pokok</h3>
              <p class="mt-1 text-sm text-amber-700">
                Sisa pembayaran: {{ formatCurrency(pokokRemaining) }}.
              </p>
              <button
                type="button"
                class="mt-3 inline-flex items-center rounded-md bg-amber-700 px-4 py-1.5 text-sm font-semibold text-white transition hover:bg-amber-800"
                @click="openPaymentDialog(simpanan_pokok_invoice!)"
              >
                Bayar Sekarang
              </button>
            </div>
          </div>
        </div>

        <div
          v-if="
            showWajibAlert && simpanan_wajib_pending && simpanan_wajib_progress
          "
          class="relative rounded-xl border border-blue-200 bg-blue-50 p-5"
        >
          <button
            type="button"
            class="absolute right-3 top-3 z-10 rounded p-1 text-blue-400 transition hover:bg-blue-100 hover:text-blue-600"
            @click="dismiss('wajib')"
          >
            <X class="h-4 w-4" />
          </button>
          <div class="flex items-start gap-3 pr-8">
            <WalletCards class="h-5 w-5 text-blue-700" />
            <div class="min-w-0 flex-1">
              <h3 class="font-semibold text-blue-900">Simpanan Wajib</h3>
              <p class="mt-1 text-sm text-blue-700">
                {{ simpanan_wajib_pending.count }} tagihan belum dibayar,
                total {{ formatCurrency(simpanan_wajib_pending.total_amount) }}.
              </p>
              <button
                v-if="simpanan_wajib_invoice"
                type="button"
                class="mt-3 inline-flex items-center rounded-md bg-blue-700 px-4 py-1.5 text-sm font-semibold text-white transition hover:bg-blue-800"
                @click="openPaymentDialog(simpanan_wajib_invoice)"
              >
                Bayar Sekarang
              </button>
              <Link
                v-else
                href="/member/savings"
                class="mt-3 inline-flex items-center rounded-md bg-blue-700 px-4 py-1.5 text-sm font-semibold text-white transition hover:bg-blue-800"
              >
                Lihat Tagihan
              </Link>
            </div>
          </div>
        </div>

        <section class="grid gap-5 lg:grid-cols-3">
          <Link
            v-for="card in summaryCards"
            :key="card.label"
            :href="card.href"
            class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm shadow-zinc-950/5 transition hover:border-emerald-200 hover:shadow-md"
          >
            <div class="flex items-center gap-5 p-5">
              <div
                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl"
                :class="card.iconClass"
              >
                <component :is="card.icon" class="h-7 w-7" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm text-zinc-500">{{ card.label }}</p>
                <p class="mt-1 text-2xl font-bold text-zinc-950">
                  {{ card.value }}
                </p>
              </div>
            </div>
            <div
              class="flex items-center justify-between border-t border-zinc-100 px-5 py-3 text-sm font-semibold text-emerald-700"
            >
              Lihat Detail
              <ChevronRight
                class="h-4 w-4 transition group-hover:translate-x-0.5"
              />
            </div>
          </Link>
        </section>

        <section
          class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5 shadow-sm shadow-emerald-950/5"
        >
          <h2 class="text-base font-bold text-zinc-950">Akses Cepat</h2>
          <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            <Link
              v-for="item in visibleQuickLinks"
              :key="item.label"
              :href="item.href"
              class="flex min-h-16 items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-900 shadow-sm shadow-zinc-950/5 transition hover:border-emerald-200 hover:bg-emerald-50"
            >
              <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                :class="item.iconClass"
              >
                <component :is="item.icon" class="h-5 w-5" />
              </span>
              <span class="min-w-0">{{ item.label }}</span>
            </Link>
          </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[0.95fr_1fr]">
          <div
            class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm shadow-zinc-950/5"
          >
            <div class="flex items-center justify-between gap-4">
              <h2 class="font-bold text-zinc-950">Transaksi Terbaru</h2>
              <Link
                href="/member/transactions"
                class="text-sm font-semibold text-emerald-700 hover:text-emerald-800"
              >
                Lihat semua
              </Link>
            </div>
            <div class="mt-4 overflow-hidden rounded-xl border border-zinc-100">
              <div
                v-for="transaction in transactionRows"
                :key="transaction.id"
                class="flex items-center justify-between gap-4 border-b border-zinc-100 p-4 last:border-b-0"
              >
                <div class="flex min-w-0 items-center gap-3">
                  <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"
                  >
                    <ReceiptText class="h-5 w-5" />
                  </div>
                  <div class="min-w-0">
                    <p class="truncate font-medium text-zinc-950">
                      {{ transaction.title }}
                    </p>
                    <p class="text-sm text-zinc-500">
                      {{ transaction.subtitle }}
                    </p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-bold text-emerald-700">
                    {{ transaction.amount }}
                  </p>
                  <p class="text-xs text-zinc-500">{{ transaction.date }}</p>
                </div>
              </div>
              <div
                v-if="transactionRows.length === 0"
                class="flex flex-col items-center justify-center gap-2 p-8 text-center text-sm text-zinc-500"
              >
                <History class="h-8 w-8 text-zinc-300" />
                Belum ada transaksi terbaru.
              </div>
            </div>
            <Link
              href="/member/transactions"
              class="mt-4 flex items-center justify-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800"
            >
              Lihat Semua Transaksi
              <ChevronRight class="h-4 w-4" />
            </Link>
          </div>

          <div
            class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm shadow-zinc-950/5"
          >
            <div class="flex items-center justify-between gap-4">
              <h2 class="font-bold text-zinc-950">Status Pinjaman</h2>
              <Link
                href="/member/loans"
                class="text-sm font-semibold text-emerald-700 hover:text-emerald-800"
              >
                Lihat semua
              </Link>
            </div>

            <div
              v-if="recentLoans.length === 0"
              class="mt-4 flex min-h-40 flex-col items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50/70 p-6 text-center"
            >
              <div
                class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-600 text-white shadow-lg shadow-emerald-600/20"
              >
                <CheckCircle2 class="h-6 w-6" />
              </div>
              <p class="mt-4 font-bold text-emerald-800">
                Belum ada pinjaman aktif
              </p>
              <p class="mt-1 text-sm text-zinc-600">
                Anda belum memiliki pinjaman aktif saat ini.
              </p>
              <Link
                href="/member/loans"
                class="mt-5 inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50"
              >
                Ajukan Pinjaman
                <ChevronRight class="h-4 w-4" />
              </Link>
            </div>

            <div v-else class="mt-4 space-y-3">
              <div
                v-for="loan in recentLoans"
                :key="loan.id"
                class="flex items-center justify-between gap-4 rounded-xl border border-zinc-100 p-4"
              >
                <div class="min-w-0">
                  <p class="font-medium text-zinc-950">
                    {{ loan.loan_type?.name || "Pinjaman" }}
                  </p>
                  <p class="text-sm text-zinc-500">{{ loan.status }}</p>
                </div>
                <p class="font-bold text-zinc-950">
                  {{ formatCurrency(loan.outstanding_amount) }}
                </p>
              </div>
            </div>
          </div>
        </section>

        <section
          class="flex flex-col gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-5 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex items-center gap-4">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-700 shadow-sm"
            >
              <Headphones class="h-6 w-6" />
            </div>
            <div>
              <p class="font-bold text-emerald-800">Butuh bantuan?</p>
              <p class="text-sm text-zinc-600">
                Kami siap membantu Anda. Hubungi layanan anggota untuk
                informasi lebih lanjut.
              </p>
            </div>
          </div>
          <Link
            href="/member/profile"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50"
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
</template>
