<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import {
  ArrowLeft,
  Banknote,
  Building2,
  Calendar,
  CheckCircle2,
  ClipboardList,
  Hash,
  IdCard,
  Mail,
  MapPin,
  Pencil,
  Phone,
  PiggyBank,
  Power,
  PowerOff,
  Receipt,
  Send,
  Settings2,
  ShieldCheck,
  Trash2,
  TrendingDown,
  TrendingUp,
  UserCheck,
  UserCog,
  UserX,
  Wallet,
  WalletCards,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import type { Component } from "vue";
import GradientKpiCard from "@/components/dashboard/GradientKpiCard.vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import StatusPill from "@/components/dashboard/StatusPill.vue";
import EmptyState from "@/components/EmptyState.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
} from "@/components/ui/card";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCan } from "@/composables/useCan";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import {
  activate,
  deactivate,
  destroy,
  index,
  reject,
  requestRevision,
  show as showRoute,
  validate as validateMember,
  approveFinal as approveFinalRoute,
} from "@/routes/cooperative/members";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc";

const props = defineProps<{
  member: any;
  savingsSummary: {
    total_balance: number;
    by_category: Record<string, number>;
  };
  recentSavingsEntries: any[];
}>();

const { can } = useCan();
const canManageMember = computed(() => can("manage_cooperative_member"));
const canVerifyMember = computed(() =>
  can(["verify_cooperative_member", "validate_cooperative_member"]),
);
const canApproveMember = computed(() => can("approve_cooperative_member"));
const canReviewMember = computed(
  () => canVerifyMember.value || canApproveMember.value,
);

const reviewDialogOpen = ref(false);
const reviewAction = ref<"revision" | "reject" | null>(null);
const reviewNotes = ref("");
const reviewProcessing = ref(false);
const deleteDialogOpen = ref(false);

const savingCategories = [
  { key: "POKOK", label: "Simpanan Pokok", icon: WalletCards, tone: "emerald" as Tone },
  { key: "WAJIB", label: "Simpanan Wajib", icon: Wallet, tone: "sky" as Tone },
  { key: "SUKARELA", label: "Simpanan Sukarela", icon: PiggyBank, tone: "violet" as Tone },
  { key: "KHUSUS", label: "Simpanan Khusus", icon: Banknote, tone: "amber" as Tone },
];

const memberName = computed(
  () => props.member.nama_anggota_clean || props.member.name || "-",
);
const memberNumber = computed(
  () => props.member.no_anggota_display || props.member.member_no || "-",
);
const memberEmail = computed(() => props.member.email || "");
const memberPhone = computed(
  () => props.member.no_telp || props.member.phone || "",
);
const memberInitial = computed(() => {
  const name = memberName.value || "";
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return "??";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
});
const avatarTones: Tone[] = ["sky", "violet", "emerald", "amber", "rose"];
const avatarTone = computed<Tone>(() => {
  const sum = (memberName.value || "")
    .split("")
    .reduce((acc: number, ch: string) => acc + ch.charCodeAt(0), 0);
  return avatarTones[sum % avatarTones.length] ?? "sky";
});
const memberStatusLabel = computed(() => {
  switch (props.member.status) {
    case "ACTIVE":
      return "Aktif";
    case "INACTIVE":
      return "Non-aktif";
    case "RESIGNED":
      return "Resigned";
    default:
      return props.member.status_badge?.label || props.member.status || "-";
  }
});
const memberStatusTone = computed<Tone>(() => {
  switch (props.member.status) {
    case "ACTIVE":
      return "emerald";
    case "INACTIVE":
    case "RESIGNED":
      return "zinc";
    default:
      return "amber";
  }
});

const validationStatusLabel = computed(() => {
  const value = props.member.validation_status;
  if (!value) return "-";
  switch (value) {
    case "ACTIVE":
      return "Disetujui";
    case "PENDING":
      return "Verifikasi";
    case "PENDING_VALIDATION":
      return "Approval Final";
    case "REVISION":
      return "Revisi";
    case "REJECTED":
      return "Ditolak";
    default:
      return value;
  }
});
const validationStatusTone = computed<Tone>(() => {
  switch (props.member.validation_status) {
    case "ACTIVE":
      return "emerald";
    case "PENDING":
    case "PENDING_VALIDATION":
    case "REVISION":
      return "amber";
    case "REJECTED":
      return "rose";
    default:
      return "zinc";
  }
});

const isAdminVerificationReady = computed(
  () =>
    props.member.validation_status === "PENDING" ||
    props.member.validation_status === "REVISION",
);
const isFinalApprovalReady = computed(
  () => props.member.validation_status === "PENDING_VALIDATION",
);

const jenisKelaminMap: Record<string, string> = {
  L: "Laki-laki",
  P: "Perempuan",
};
const kategoriMap: Record<string, string> = {
  IP: "Indonesia Power",
  CDB: "Cogindo DayaBersama",
  KOP: "Koperasi",
};
const autodebetMap: Record<string, string> = {
  MANUAL: "Manual",
  BNI: "BNI",
  BRI: "BRI",
};
const savingEntryTypeMap: Record<string, string> = {
  OPENING_BALANCE: "Saldo Awal",
  SAVING_PAYMENT: "Setoran Simpanan",
  SAVING_WITHDRAWAL: "Penarikan Simpanan",
  POS_MEMBER_CREDIT: "Belanja Kredit POS",
  LOAN_DISBURSEMENT: "Pencairan Pinjaman",
  LOAN_PAYMENT: "Pembayaran Pinjaman",
};
const savingEntryTone: Record<string, Tone> = {
  OPENING_BALANCE: "sky",
  SAVING_PAYMENT: "emerald",
  SAVING_WITHDRAWAL: "rose",
  POS_MEMBER_CREDIT: "amber",
  LOAN_DISBURSEMENT: "violet",
  LOAN_PAYMENT: "sky",
};

const invoiceStatusTone: Record<string, Tone> = {
  PAID: "emerald",
  UNPAID: "rose",
  PARTIAL: "amber",
  PENDING: "amber",
  OVERDUE: "rose",
  CANCELLED: "zinc",
};

const formatMemberDate = (value: string | null | undefined): string =>
  formatDate(value);
const formatJenisKelamin = (value: string | null | undefined): string =>
  (value && jenisKelaminMap[value]) || value || "-";
const formatKategori = (value: string | null | undefined): string =>
  (value && kategoriMap[value]) || value || "-";
const formatAutodebet = (value: string | null | undefined): string =>
  (value && autodebetMap[value]) || value || "Manual";
const formatSavingEntryType = (value: string | null | undefined): string =>
  (value && savingEntryTypeMap[value]) || value || "-";
const entryTone = (value: string | null | undefined): Tone => {
  if (value && savingEntryTone[value]) {
    return savingEntryTone[value] as Tone;
  }
  return "zinc";
};
const invoiceTone = (status: string | null | undefined): Tone => {
  if (status && invoiceStatusTone[status]) {
    return invoiceStatusTone[status] as Tone;
  }
  return "zinc";
};

function sparklineFor(value: number, points = 8): number[] {
  const safeValue = Math.max(0, Number(value) || 0);
  const base = Math.min(1, Math.log10(safeValue + 1) / 7.5);
  const seed = Math.abs(Math.sin(safeValue * 12.9898) * 43758.5453);
  return Array.from({ length: points }, (_, i) => {
    const t = i / (points - 1);
    const noise = (Math.sin((seed + i) * 1.7) + 1) / 2;
    return Math.max(0.05, base * (0.35 + t * 0.85) + noise * 0.12);
  });
}

const openReviewDialog = (action: "revision" | "reject"): void => {
  reviewAction.value = action;
  reviewNotes.value = "";
  reviewDialogOpen.value = true;
};

const submitReview = (): void => {
  if (!reviewAction.value || reviewNotes.value.trim().length < 5) {
    return;
  }
  reviewProcessing.value = true;
  const url =
    reviewAction.value === "reject"
      ? reject(props.member.id).url
      : requestRevision(props.member.id).url;
  router.post(
    url,
    { notes: reviewNotes.value },
    {
      preserveScroll: true,
      onFinish: () => {
        reviewProcessing.value = false;
        reviewDialogOpen.value = false;
        reviewAction.value = null;
      },
    },
  );
};

const verifyMember = (): void => {
  router.post(validateMember(props.member.id).url);
};
const approveFinalAction = (): void => {
  router.post(approveFinalRoute(props.member.id).url);
};
const deactivateMember = (): void => {
  router.post(deactivate(props.member.id).url);
};
const activateMember = (): void => {
  router.post(activate(props.member.id).url);
};
const confirmDelete = (): void => {
  router.delete(destroy(props.member.id).url, {
    onFinish: () => (deleteDialogOpen.value = false),
  });
};

const kpiCards = computed(() => [
  {
    label: "Total Simpanan",
    value: props.savingsSummary.total_balance,
    icon: PiggyBank as Component,
    tone: "emerald" as Tone,
    href: showRoute(props.member.id).url,
    sparklinePoints: sparklineFor(props.savingsSummary.total_balance),
    meta: "Akumulasi seluruh kategori simpanan",
  },
  {
    label: "Simpanan Pokok",
    value: props.savingsSummary.by_category?.POKOK ?? 0,
    icon: WalletCards as Component,
    tone: "sky" as Tone,
    href: showRoute(props.member.id).url,
    sparklinePoints: sparklineFor(props.savingsSummary.by_category?.POKOK ?? 0),
    meta: "Setoran awal keanggotaan",
  },
  {
    label: "Simpanan Wajib",
    value: props.savingsSummary.by_category?.WAJIB ?? 0,
    icon: Wallet as Component,
    tone: "violet" as Tone,
    href: showRoute(props.member.id).url,
    sparklinePoints: sparklineFor(props.savingsSummary.by_category?.WAJIB ?? 0),
    meta: "Iuran rutin bulanan",
  },
  {
    label: "Simpanan Sukarela",
    value: props.savingsSummary.by_category?.SUKARELA ?? 0,
    icon: TrendingUp as Component,
    tone: "amber" as Tone,
    href: showRoute(props.member.id).url,
    sparklinePoints: sparklineFor(
      props.savingsSummary.by_category?.SUKARELA ?? 0,
    ),
    meta: "Tabungan sukarela anggota",
  },
]);

const showKategori = computed(() => formatKategori(props.member.kategori));
const showJenis = computed(
  () => props.member.jenis_anggota_label || "—",
);
</script>

<template>
  <Head :title="`Detail ${memberName}`" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Anggota', href: index().url },
      { title: memberNumber, href: '#' },
    ]"
  >
    <PageContainer class="max-w-none">
      <!-- HERO -->
      <section
        class="relative overflow-hidden rounded-2xl border border-emerald-200/60 bg-gradient-to-br from-white via-emerald-50/60 to-sky-50/40 p-6 shadow-sm shadow-emerald-950/5 sm:p-7 dark:border-emerald-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      >
        <div
          class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"
          aria-hidden="true"
        />
        <div
          class="pointer-events-none absolute -bottom-24 -left-12 size-64 rounded-full bg-sky-300/15 blur-3xl dark:bg-sky-500/10"
          aria-hidden="true"
        />
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="flex items-start gap-4">
            <span
              :class="[
                'inline-flex size-16 shrink-0 items-center justify-center rounded-2xl text-lg font-bold ring-2 ring-white shadow-sm shadow-zinc-950/5 dark:ring-zinc-900',
                memberStatusTone === 'emerald' &&
                  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                memberStatusTone === 'zinc' &&
                  'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200',
                memberStatusTone === 'amber' &&
                  'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                avatarTone === 'rose' &&
                  memberStatusTone !== 'emerald' &&
                  memberStatusTone !== 'zinc' &&
                  memberStatusTone !== 'amber' &&
                  'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
              ]"
            >
              {{ memberInitial }}
            </span>
            <div class="space-y-3">
              <div class="flex flex-wrap items-center gap-2">
                <span
                  class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-800/60"
                >
                  <UserCog class="size-3.5" />
                  Detail Anggota
                </span>
                <StatusPill
                  :tone="memberStatusTone"
                  :label="memberStatusLabel"
                />
                <StatusPill
                  v-if="props.member.validation_status"
                  :tone="validationStatusTone"
                  :label="validationStatusLabel"
                />
              </div>
              <h1
                class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
              >
                {{ memberName }}
              </h1>
              <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <span
                  class="inline-flex items-center gap-1 rounded-md bg-white/80 px-2 py-1 font-mono text-xs font-semibold text-zinc-700 ring-1 ring-inset ring-zinc-200/70 dark:bg-zinc-950/40 dark:text-zinc-200 dark:ring-zinc-800/60"
                >
                  <Hash class="size-3" />
                  {{ memberNumber }}
                </span>
                <span
                  class="inline-flex items-center gap-1 rounded-md bg-white/80 px-2 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60"
                >
                  <Building2 class="size-3" />
                  {{ showKategori }}
                </span>
                <span
                  class="inline-flex items-center gap-1 rounded-md bg-white/80 px-2 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/60"
                >
                  <IdCard class="size-3" />
                  {{ showJenis }}
                </span>
              </div>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <Button
              v-if="canVerifyMember && isAdminVerificationReady"
              class="bg-emerald-700 hover:bg-emerald-800"
              data-test="member-approve"
              @click="verifyMember"
            >
              <CheckCircle2 class="mr-2 size-4" />
              Verifikasi
            </Button>
            <Button
              v-else-if="canApproveMember && isFinalApprovalReady"
              class="bg-emerald-700 hover:bg-emerald-800"
              data-test="member-approve-final"
              @click="approveFinalAction"
            >
              <CheckCircle2 class="mr-2 size-4" />
              Approve Final
            </Button>
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="outline">
                  <UserCog class="mr-2 size-4" />
                  Aksi
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-56">
                <DropdownMenuLabel class="text-xs">
                  {{ memberName }}
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem as-child>
                  <Link :href="index().url" prefetch>
                    <ArrowLeft class="mr-2 size-4" />
                    Kembali ke daftar
                  </Link>
                </DropdownMenuItem>
                <template v-if="canManageMember">
                  <DropdownMenuSeparator />
                  <DropdownMenuItem as-child>
                    <Link :href="`${index().url}/${props.member.id}/edit`" prefetch>
                      <Pencil class="mr-2 size-4" />
                      Edit anggota
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    v-if="props.member.status === 'ACTIVE'"
                    @click="deactivateMember"
                  >
                    <PowerOff class="mr-2 size-4" />
                    Nonaktifkan
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    v-else
                    @click="activateMember"
                  >
                    <Power class="mr-2 size-4 text-emerald-600" />
                    Aktifkan
                  </DropdownMenuItem>
                </template>
                <template
                  v-if="
                    canReviewMember &&
                    (isAdminVerificationReady || isFinalApprovalReady)
                  "
                >
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    data-test="member-revision"
                    @click="openReviewDialog('revision')"
                  >
                    <Send class="mr-2 size-4" />
                    Minta revisi
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    variant="destructive"
                    data-test="member-reject"
                    @click="openReviewDialog('reject')"
                  >
                    <UserX class="mr-2 size-4" />
                    Tolak anggota
                  </DropdownMenuItem>
                </template>
                <template v-if="canManageMember">
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    variant="destructive"
                    @click="deleteDialogOpen = true"
                  >
                    <Trash2 class="mr-2 size-4" />
                    Hapus anggota
                  </DropdownMenuItem>
                </template>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </section>

      <!-- KPI BAND -->
      <section
        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        aria-label="Ringkasan simpanan"
      >
        <GradientKpiCard
          v-for="card in kpiCards"
          :key="card.label"
          :label="card.label"
          :value="formatCurrency(card.value)"
          :meta="card.meta"
          :icon="card.icon"
          :tone="card.tone"
          :href="card.href"
          :sparkline-points="card.sparklinePoints"
        />
      </section>

      <!-- INFORMASI KEAKTIFAN & KONTAK -->
      <div class="grid gap-6 xl:grid-cols-3">
        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 xl:col-span-2 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <SectionHeader
            title="Informasi Keanggotaan"
            description="Data utama keanggotaan koperasi."
            :icon="IdCard"
            tone="emerald"
          />
          <CardContent class="px-6 py-5">
            <dl class="grid gap-4 sm:grid-cols-2">
              <div
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/60 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/40"
              >
                <dt
                  class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  <Hash class="size-3" /> Nomor Anggota
                </dt>
                <dd
                  class="mt-1.5 font-mono text-sm font-semibold text-zinc-950 dark:text-white"
                >
                  {{ memberNumber }}
                </dd>
              </div>
              <div
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/60 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/40"
              >
                <dt
                  class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  <ShieldCheck class="size-3" /> Status
                </dt>
                <dd class="mt-1.5">
                  <StatusPill
                    :tone="memberStatusTone"
                    :label="memberStatusLabel"
                  />
                </dd>
              </div>
              <div
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/60 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/40"
              >
                <dt
                  class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  <Calendar class="size-3" /> Tanggal Aktif
                </dt>
                <dd
                  class="mt-1.5 text-sm font-semibold text-zinc-950 dark:text-white"
                >
                  {{ formatMemberDate(member.tanggal_aktif || member.joined_at) }}
                </dd>
              </div>
              <div
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/60 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/40"
              >
                <dt
                  class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  <Calendar class="size-3" /> Tanggal Bergabung
                </dt>
                <dd
                  class="mt-1.5 text-sm font-semibold text-zinc-950 dark:text-white"
                >
                  {{ formatMemberDate(member.joined_at) }}
                </dd>
              </div>
              <div
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/60 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/40"
              >
                <dt
                  class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  <IdCard class="size-3" /> Jenis Anggota
                </dt>
                <dd
                  class="mt-1.5 text-sm font-semibold text-zinc-950 dark:text-white"
                >
                  {{ props.member.jenis_anggota_label || "—" }}
                </dd>
              </div>
              <div
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/60 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/40"
              >
                <dt
                  class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  <Building2 class="size-3" /> Kategori
                </dt>
                <dd class="mt-1.5">
                  <span
                    class="inline-flex items-center gap-1 rounded-md bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/60"
                  >
                    {{ showKategori }}
                  </span>
                </dd>
              </div>
              <div
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/60 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/40"
              >
                <dt
                  class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  <UserCheck class="size-3" /> Jenis Kelamin
                </dt>
                <dd
                  class="mt-1.5 text-sm font-semibold text-zinc-950 dark:text-white"
                >
                  {{ formatJenisKelamin(member.jenis_kelamin) }}
                </dd>
              </div>
            </dl>
          </CardContent>
        </Card>

        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <SectionHeader
            title="Kontak & Pembayaran"
            description="Saluran komunikasi dan administrasi."
            :icon="Mail"
            tone="sky"
          />
          <CardContent class="space-y-3 px-6 py-5">
            <div
              v-if="memberEmail"
              class="flex items-start gap-3 rounded-xl border border-zinc-200/70 bg-white/80 p-3.5 dark:border-zinc-800/70 dark:bg-zinc-950/40"
            >
              <span
                class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60"
              >
                <Mail class="size-4" />
              </span>
              <div class="min-w-0 flex-1">
                <p
                  class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  Email
                </p>
                <p
                  class="mt-0.5 truncate text-sm font-semibold text-zinc-950 dark:text-white"
                >
                  {{ memberEmail }}
                </p>
              </div>
            </div>
            <div
              v-if="memberPhone"
              class="flex items-start gap-3 rounded-xl border border-zinc-200/70 bg-white/80 p-3.5 dark:border-zinc-800/70 dark:bg-zinc-950/40"
            >
              <span
                class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60"
              >
                <Phone class="size-4" />
              </span>
              <div class="min-w-0 flex-1">
                <p
                  class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  No. Telepon
                </p>
                <p
                  class="mt-0.5 text-sm font-semibold text-zinc-950 dark:text-white"
                >
                  {{ memberPhone }}
                </p>
              </div>
            </div>
            <div
              v-if="member.address"
              class="flex items-start gap-3 rounded-xl border border-zinc-200/70 bg-white/80 p-3.5 dark:border-zinc-800/70 dark:bg-zinc-950/40"
            >
              <span
                class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60"
              >
                <MapPin class="size-4" />
              </span>
              <div class="min-w-0 flex-1">
                <p
                  class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  Alamat
                </p>
                <p
                  class="mt-0.5 whitespace-pre-line text-sm font-medium text-zinc-950 dark:text-white"
                >
                  {{ member.address }}
                </p>
              </div>
            </div>
            <div
              class="grid grid-cols-2 gap-3"
            >
              <div
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/60 p-3.5 dark:border-zinc-800/70 dark:bg-zinc-950/40"
              >
                <p
                  class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  NIK
                </p>
                <p
                  class="mt-1 font-mono text-sm font-semibold text-zinc-950 dark:text-white"
                >
                  {{ member.identity_number || "—" }}
                </p>
              </div>
              <div
                class="rounded-xl border border-zinc-200/70 bg-zinc-50/60 p-3.5 dark:border-zinc-800/70 dark:bg-zinc-950/40"
              >
                <p
                  class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                >
                  NPWP
                </p>
                <p
                  class="mt-1 font-mono text-sm font-semibold text-zinc-950 dark:text-white"
                >
                  {{ member.npwp || "—" }}
                </p>
              </div>
            </div>
            <div
              class="rounded-xl border border-zinc-200/70 bg-zinc-50/60 p-3.5 dark:border-zinc-800/70 dark:bg-zinc-950/40"
            >
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p
                    class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                  >
                    Autodebet
                  </p>
                  <p
                    class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white"
                  >
                    {{ formatAutodebet(member.autodebet) }}
                  </p>
                </div>
                <div class="text-right">
                  <p
                    class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
                  >
                    No. Rekening
                  </p>
                  <p
                    class="mt-1 font-mono text-sm font-semibold text-zinc-950 dark:text-white"
                  >
                    {{ member.no_rekening || "—" }}
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- RINGKASAN SIMPANAN DETAIL -->
      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Ringkasan Simpanan"
          description="Saldo per kategori simpanan anggota."
          :icon="PiggyBank"
          tone="emerald"
        />
        <CardContent class="px-6 py-5">
          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div
              v-for="category in savingCategories"
              :key="category.key"
              :class="[
                'group relative overflow-hidden rounded-xl border p-4 transition-all hover:-translate-y-0.5 hover:shadow-md hover:shadow-zinc-950/5',
                category.tone === 'emerald' &&
                  'border-emerald-200/60 bg-gradient-to-br from-emerald-50/80 to-white dark:border-emerald-900/40 dark:from-emerald-950/20 dark:to-zinc-900',
                category.tone === 'sky' &&
                  'border-sky-200/60 bg-gradient-to-br from-sky-50/80 to-white dark:border-sky-900/40 dark:from-sky-950/20 dark:to-zinc-900',
                category.tone === 'violet' &&
                  'border-violet-200/60 bg-gradient-to-br from-violet-50/80 to-white dark:border-violet-900/40 dark:from-violet-950/20 dark:to-zinc-900',
                category.tone === 'amber' &&
                  'border-amber-200/60 bg-gradient-to-br from-amber-50/80 to-white dark:border-amber-900/40 dark:from-amber-950/20 dark:to-zinc-900',
              ]"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                  <p
                    class="flex items-center gap-1 text-xs font-medium text-zinc-500 dark:text-zinc-400"
                  >
                    <component
                      :is="category.icon"
                      :class="[
                        'size-3',
                        category.tone === 'emerald' && 'text-emerald-600',
                        category.tone === 'sky' && 'text-sky-600',
                        category.tone === 'violet' && 'text-violet-600',
                        category.tone === 'amber' && 'text-amber-600',
                      ]"
                    />
                    {{ category.label }}
                  </p>
                  <p
                    class="text-xl font-bold tabular-nums text-zinc-950 sm:text-2xl dark:text-white"
                  >
                    {{
                      formatCurrency(
                        savingsSummary.by_category?.[category.key] ?? 0,
                      )
                    }}
                  </p>
                </div>
                <span
                  :class="[
                    'inline-flex size-9 items-center justify-center rounded-lg ring-1 ring-inset transition-transform duration-300 group-hover:scale-110',
                    category.tone === 'emerald' &&
                      'bg-emerald-100 text-emerald-700 ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60',
                    category.tone === 'sky' &&
                      'bg-sky-100 text-sky-700 ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60',
                    category.tone === 'violet' &&
                      'bg-violet-100 text-violet-700 ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/60',
                    category.tone === 'amber' &&
                      'bg-amber-100 text-amber-700 ring-amber-200/70 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900/60',
                  ]"
                >
                  <component :is="category.icon" class="size-4" />
                </span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- MUTASI SIMPANAN -->
      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Mutasi Simpanan Terbaru"
          description="Aktivitas setoran dan penarikan simpanan terakhir."
          :icon="ClipboardList"
          tone="violet"
        />
        <CardContent class="p-0">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead
                class="border-b border-zinc-200/70 bg-zinc-50/60 text-left text-[11px] uppercase tracking-wide text-zinc-500 dark:border-zinc-800/70 dark:bg-zinc-900/60"
              >
                <tr>
                  <th class="px-6 py-3 font-medium">Tanggal</th>
                  <th class="px-6 py-3 font-medium">Jenis Mutasi</th>
                  <th class="px-6 py-3 font-medium">Kategori</th>
                  <th class="px-6 py-3 text-right font-medium">Debit</th>
                  <th class="px-6 py-3 text-right font-medium">Kredit</th>
                  <th class="px-6 py-3 font-medium">Keterangan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70">
                <tr
                  v-for="entry in recentSavingsEntries"
                  :key="entry.id"
                  class="transition-colors hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40"
                >
                  <td
                    class="whitespace-nowrap px-6 py-3 text-zinc-600 dark:text-zinc-400"
                  >
                    {{ formatDate(entry.posted_at) }}
                  </td>
                  <td class="px-6 py-3">
                    <StatusPill
                      :tone="entryTone(entry.entry_type)"
                      :label="formatSavingEntryType(entry.entry_type)"
                    />
                  </td>
                  <td
                    class="px-6 py-3 text-zinc-600 dark:text-zinc-400"
                  >
                    {{
                      entry.contribution_type?.category ||
                      entry.category_snapshot ||
                      "—"
                    }}
                  </td>
                  <td class="px-6 py-3 text-right tabular-nums">
                    <span
                      v-if="Number(entry.debit) > 0"
                      class="inline-flex items-center gap-1 font-semibold text-rose-700 dark:text-rose-300"
                    >
                      <TrendingDown class="size-3.5" />
                      {{ formatCurrency(entry.debit) }}
                    </span>
                    <span v-else class="text-zinc-400">—</span>
                  </td>
                  <td class="px-6 py-3 text-right tabular-nums">
                    <span
                      v-if="Number(entry.credit) > 0"
                      class="inline-flex items-center gap-1 font-semibold text-emerald-700 dark:text-emerald-300"
                    >
                      <TrendingUp class="size-3.5" />
                      {{ formatCurrency(entry.credit) }}
                    </span>
                    <span v-else class="text-zinc-400">—</span>
                  </td>
                  <td
                    class="px-6 py-3 text-zinc-600 dark:text-zinc-400"
                  >
                    {{ entry.description || "—" }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <EmptyState
            v-if="recentSavingsEntries.length === 0"
            :icon="Receipt"
            title="Belum ada mutasi simpanan"
            description="Aktivitas setoran dan penarikan akan muncul di sini."
            class="py-10"
          />
        </CardContent>
      </Card>

      <!-- TAGIHAN SIMPANAN -->
      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Tagihan Simpanan"
          description="Ringkasan tagihan yang sudah dibuat untuk anggota ini."
          :icon="Receipt"
          tone="amber"
        />
        <CardContent class="p-0">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead
                class="border-b border-zinc-200/70 bg-zinc-50/60 text-left text-[11px] uppercase tracking-wide text-zinc-500 dark:border-zinc-800/70 dark:bg-zinc-900/60"
              >
                <tr>
                  <th class="px-6 py-3 font-medium">Periode</th>
                  <th class="px-6 py-3 font-medium">Jenis Simpanan</th>
                  <th class="px-6 py-3 font-medium">Jatuh Tempo</th>
                  <th class="px-6 py-3 font-medium">Status</th>
                  <th class="px-6 py-3 text-right font-medium">Nominal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70">
                <tr
                  v-for="invoice in member.invoices"
                  :key="invoice.id"
                  class="transition-colors hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40"
                >
                  <td
                    class="whitespace-nowrap px-6 py-3 font-medium text-zinc-950 dark:text-white"
                  >
                    {{ invoice.period }}
                  </td>
                  <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400">
                    {{ invoice.contribution_type?.name || "—" }}
                  </td>
                  <td
                    class="whitespace-nowrap px-6 py-3 text-zinc-600 dark:text-zinc-400"
                  >
                    {{ formatDate(invoice.due_date) }}
                  </td>
                  <td class="px-6 py-3">
                    <StatusPill
                      :tone="invoiceTone(invoice.status)"
                      :label="invoice.status"
                    />
                  </td>
                  <td
                    class="px-6 py-3 text-right font-semibold tabular-nums text-zinc-950 dark:text-white"
                  >
                    {{ formatCurrency(invoice.amount) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <EmptyState
            v-if="member.invoices.length === 0"
            :icon="Receipt"
            title="Belum ada tagihan simpanan"
            description="Tagihan akan muncul setelah dibuat oleh sistem."
            class="py-10"
          />
        </CardContent>
      </Card>
    </PageContainer>

    <!-- Review dialog (replaces window.prompt) -->
    <Dialog v-model:open="reviewDialogOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <div class="flex items-start gap-3">
            <span
              :class="[
                'inline-flex size-9 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset',
                reviewAction === 'reject'
                  ? 'bg-rose-100 text-rose-700 ring-rose-200/70 dark:bg-rose-900/40 dark:text-rose-300 dark:ring-rose-900/60'
                  : 'bg-amber-100 text-amber-700 ring-amber-200/70 dark:bg-amber-900/40 dark:text-amber-300 dark:ring-amber-900/60',
              ]"
            >
              <Send v-if="reviewAction === 'revision'" class="size-4" />
              <UserX v-else class="size-4" />
            </span>
            <div>
              <DialogTitle>
                {{
                  reviewAction === "reject"
                    ? "Tolak anggota"
                    : "Minta revisi"
                }}
              </DialogTitle>
              <DialogDescription>
                {{
                  reviewAction === "reject"
                    ? "Tuliskan alasan penolakan. Pesan akan dikirim ke anggota."
                    : "Tuliskan catatan revisi yang akan dikirim ke anggota."
                }}
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>
        <div class="space-y-2">
          <Label for="show-review-notes">Catatan</Label>
          <textarea
            id="show-review-notes"
            v-model="reviewNotes"
            rows="4"
            placeholder="Tulis pesan untuk anggota…"
            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-800 dark:bg-zinc-950"
          />
          <p class="text-xs text-zinc-500">
            Minimal 5 karakter. ({{ reviewNotes.length }} / 5+)
          </p>
        </div>
        <DialogFooter class="gap-2">
          <Button
            type="button"
            variant="outline"
            @click="reviewDialogOpen = false"
          >
            Batal
          </Button>
          <Button
            type="button"
            :variant="reviewAction === 'reject' ? 'destructive' : 'default'"
            :disabled="reviewNotes.trim().length < 5 || reviewProcessing"
            @click="submitReview"
          >
            {{
              reviewAction === "reject" ? "Tolak anggota" : "Kirim revisi"
            }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Delete confirm -->
    <Dialog v-model:open="deleteDialogOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <div class="flex items-start gap-3">
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200/70 dark:bg-rose-900/40 dark:text-rose-300 dark:ring-rose-900/60"
            >
              <Trash2 class="size-4" />
            </span>
            <div>
              <DialogTitle>Hapus Anggota</DialogTitle>
              <DialogDescription>
                Tindakan ini tidak dapat dibatalkan. Anggota
                <span class="font-semibold">{{ memberName }}</span>
                akan dihapus permanen dari sistem.
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>
        <DialogFooter class="gap-2">
          <Button variant="outline" @click="deleteDialogOpen = false">
            Batal
          </Button>
          <Button variant="destructive" @click="confirmDelete">
            Hapus permanen
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
