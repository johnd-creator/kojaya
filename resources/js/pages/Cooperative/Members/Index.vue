<script setup lang="ts">
import { Deferred, Head, Link, router, useForm } from "@inertiajs/vue3";
import {
  ArrowRight,
  Banknote,
  Building2,
  CalendarClock,
  CheckCircle2,
  ClipboardCheck,
  Download,
  Eye,
  Hash,
  IdCard,
  KeyRound,
  Mail,
  MapPin,
  MoreHorizontal,
  Pencil,
  Phone,
  Plus,
  PowerOff,
  Power,
  Search,
  Send,
  Settings2,
  ShieldCheck,
  Trash2,
  UserCheck,
  UserCog,
  UserPlus,
  UserRound,
  UserX,
  Users,
  WalletCards,
  X,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import type { Component } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import GradientKpiCard from "@/components/dashboard/GradientKpiCard.vue";
import StatusPill from "@/components/dashboard/StatusPill.vue";
import InputError from "@/components/InputError.vue";
import PageContainer from "@/components/PageContainer.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
} from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { useCan } from "@/composables/useCan";
import { useTableFilters } from "@/composables/useTableFilters";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatNumber } from "@/lib/formatters";
import {
  activate,
  approveFinal,
  deactivate,
  destroy,
  exportMethod,
  index,
  reject,
  requestRevision,
  show,
  store,
  update,
  validate as validateMember,
} from "@/routes/cooperative/members";

type Tone = "emerald" | "amber" | "rose" | "sky" | "violet" | "zinc";

const props = defineProps<{
  members: any;
  filters: {
    search?: string;
    status?: string;
    jenis_anggota?: string;
    kategori?: string;
    validation_status?: string;
  };
  options: {
    statuses: Array<{ value: string; label: string }>;
    validationStatuses: Array<{ value: string; label: string }>;
    jenisAnggota: Array<{ value: string; label: string }>;
    jenisKelamin: Array<{ value: string; label: string }>;
    kategori: Array<{ value: string; label: string }>;
    autodebet: Array<{ value: string; label: string }>;
  };
  stats?: {
    active: number;
    inactive: number;
    alb: number;
    pending_validation?: number;
    rejected?: number;
  };
}>();

const filters = ref({
  search: props.filters.search ?? "",
  status: props.filters.status ?? "",
  jenis_anggota: props.filters.jenis_anggota ?? "",
  kategori: props.filters.kategori ?? "",
  validation_status: props.filters.validation_status ?? "",
});
const { can } = useCan();
const canManageMember = computed(() => can("manage_cooperative_member"));
const canVerifyMember = computed(() =>
  can(["verify_cooperative_member", "validate_cooperative_member"]),
);
const canApproveMember = computed(() => can("approve_cooperative_member"));
const canReviewMember = computed(
  () => canVerifyMember.value || canApproveMember.value,
);
const createMemberDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const memberPendingDelete = ref<{ id: string | number; name: string } | null>(
  null,
);
const editMemberDialogOpen = ref(false);
const editMemberId = ref<number | null>(null);
const reviewDialogOpen = ref(false);
const reviewAction = ref<"revision" | "reject" | null>(null);
const reviewRowId = ref<number | null>(null);
const reviewNotes = ref("");
const reviewProcessing = ref(false);

const editMemberForm = useForm({
  employee_id: "",
  no_anggota: "",
  tanggal_aktif: "",
  nama_anggota: "",
  name: "",
  email: "",
  member_login_password: "",
  npwp: "",
  no_telp: "",
  phone: "",
  identity_number: "",
  address: "",
  joined_at: "",
  status: "ACTIVE",
  jenis_anggota: "AB",
  jenis_kelamin: "L",
  kategori: "IP",
  autodebet: "MANUAL",
  no_rekening: "",
  opening_saving_balance: 0,
  notes: "",
});
const createMemberForm = useForm({
  employee_id: "",
  no_anggota: "",
  tanggal_aktif: new Date().toISOString().slice(0, 10),
  nama_anggota: "",
  name: "",
  email: "",
  member_login_password: "",
  npwp: "",
  no_telp: "",
  phone: "",
  identity_number: "",
  address: "",
  joined_at: new Date().toISOString().slice(0, 10),
  status: "ACTIVE",
  jenis_anggota: "AB",
  jenis_kelamin: "L",
  kategori: "IP",
  autodebet: "MANUAL",
  no_rekening: "",
  opening_saving_balance: 0,
  notes: "",
});

const resetCreateMemberForm = (): void => {
  createMemberForm.reset();
  createMemberForm.clearErrors();
  createMemberForm.tanggal_aktif = new Date().toISOString().slice(0, 10);
  createMemberForm.joined_at = createMemberForm.tanggal_aktif;
  createMemberForm.status = "ACTIVE";
  createMemberForm.jenis_anggota = "AB";
  createMemberForm.jenis_kelamin = "L";
  createMemberForm.kategori = "IP";
  createMemberForm.autodebet = "MANUAL";
  createMemberForm.opening_saving_balance = 0;
};

const openCreateDialog = (): void => {
  resetCreateMemberForm();
  createMemberDialogOpen.value = true;
};

const submitCreateMember = (): void => {
  createMemberForm.post(store().url, {
    preserveScroll: true,
    onSuccess: () => {
      createMemberDialogOpen.value = false;
      resetCreateMemberForm();
    },
  });
};

const askDelete = (row: any): void => {
  memberPendingDelete.value = {
    id: row.id,
    name:
      row.nama_anggota_clean || row.nama_anggota || row.name || "anggota ini",
  };
  deleteDialogOpen.value = true;
};

const confirmDelete = (): void => {
  const target = memberPendingDelete.value;

  if (!target) {
    deleteDialogOpen.value = false;
    return;
  }

  router.delete(destroy(target.id).url, {
    onFinish: () => {
      deleteDialogOpen.value = false;
      memberPendingDelete.value = null;
    },
  });
};

const deactivateMember = (row: any): void => {
  router.post(deactivate(row.id).url, undefined, {
    preserveScroll: true,
  });
};

const openEditDialog = (row: any): void => {
  editMemberId.value = row.id;
  editMemberForm.reset();
  editMemberForm.clearErrors();
  editMemberForm.employee_id = row.employee_id ?? "";
  editMemberForm.no_anggota = row.no_anggota ?? row.member_no ?? "";
  editMemberForm.tanggal_aktif = (row.tanggal_aktif ?? row.joined_at ?? "").slice(0, 10);
  editMemberForm.nama_anggota = row.nama_anggota ?? row.name ?? "";
  editMemberForm.name = row.name ?? "";
  editMemberForm.email = row.email ?? "";
  editMemberForm.member_login_password = "";
  editMemberForm.npwp = row.npwp ?? "";
  editMemberForm.no_telp = row.no_telp ?? row.phone ?? "";
  editMemberForm.phone = row.phone ?? "";
  editMemberForm.identity_number = row.identity_number ?? "";
  editMemberForm.address = row.address ?? "";
  editMemberForm.joined_at = (row.joined_at ?? "").slice(0, 10);
  editMemberForm.status = row.status === "RESIGNED" ? "INACTIVE" : row.status ?? "ACTIVE";
  editMemberForm.jenis_anggota = row.jenis_anggota ?? "AB";
  editMemberForm.jenis_kelamin = row.jenis_kelamin ?? "L";
  editMemberForm.kategori = row.kategori ?? "IP";
  editMemberForm.autodebet = row.autodebet ?? "MANUAL";
  editMemberForm.no_rekening = row.no_rekening ?? "";
  editMemberForm.opening_saving_balance = 0;
  editMemberForm.notes = row.notes ?? "";
  editMemberDialogOpen.value = true;
};

const submitEditMember = (): void => {
  if (!editMemberId.value) return;
  editMemberForm.put(update(editMemberId.value).url, {
    preserveScroll: true,
    onSuccess: () => {
      editMemberDialogOpen.value = false;
      editMemberId.value = null;
    },
  });
};
const statusOptions = [
  { label: "Semua status", value: "" },
  ...props.options.statuses,
];
const validationStatusOptions = [
  { label: "Semua validasi", value: "" },
  ...(props.options.validationStatuses ?? []),
];
const jenisAnggotaOptions = [
  { label: "Semua jenis", value: "" },
  ...props.options.jenisAnggota,
];
const kategoriOptions = [
  { label: "Semua kategori", value: "" },
  ...props.options.kategori,
];

const breadcrumbs = [
  { title: "Koperasi", href: "#" },
  { title: "Anggota", href: index().url },
];

const { resetFilters } = useTableFilters(filters, {
  route: index().url,
  debounceMs: 400,
  only: ["members", "filters", "stats"],
});

const columns = [
  { header: "Anggota", key: "nama_anggota", slot: "member" },
  { header: "No. Anggota", key: "no_anggota", slot: "memberNo" },
  { header: "Status", key: "status", slot: "status" },
  { header: "Validasi", key: "validation_status", slot: "validationStatus" },
  { header: "Kategori", key: "kategori", slot: "kategori" },
  { header: "Autodebet", key: "autodebet", slot: "autodebet" },
  { header: "", key: "actions", slot: "actions", align: "right" as const },
];

const tableData = computed(() => {
  if (props.members?.meta) {
    return {
      ...props.members.meta,
      data: props.members.data ?? [],
      links: props.members.links ?? [],
    };
  }

  return props.members;
});

const memberName = (row: any): string =>
  row.nama_anggota_clean || row.nama_anggota || row.name || "Tanpa nama";

const memberEmail = (row: any): string => row.email || "";

const memberPhone = (row: any): string => row.no_telp || row.phone || "";

const memberStatusToTone = (status: string): Tone => {
  switch (status) {
    case "ACTIVE":
      return "emerald";
    case "INACTIVE":
    case "RESIGNED":
      return "zinc";
    default:
      return "amber";
  }
};

const memberStatusLabel = (status: string): string => {
  switch (status) {
    case "ACTIVE":
      return "Aktif";
    case "INACTIVE":
      return "Non-aktif";
    case "RESIGNED":
      return "Resigned";
    default:
      return status;
  }
};

const validationStatusLabel = (value: string | null | undefined): string => {
  if (!value) {
    return "-";
  }
  const match = (props.options.validationStatuses ?? []).find(
    (option) => option.value === value,
  );
  return match?.label ?? value;
};

const validationStatusShort = (value: string | null | undefined): string => {
  if (!value) {
    return "-";
  }
  switch (value) {
    case "ACTIVE":
      return "Disetujui";
    case "PENDING":
      return "Verifikasi";
    case "PENDING_VALIDATION":
      return "Approval";
    case "REVISION":
      return "Revisi";
    case "REJECTED":
      return "Ditolak";
    default:
      return value;
  }
};

const validationToTone = (value: string | null | undefined): Tone => {
  switch (value) {
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
};

const avatarTones: Tone[] = ["sky", "violet", "emerald", "amber", "rose"];
const avatarToneFor = (name: string): Tone => {
  const trimmed = (name || "").trim();
  if (!trimmed) {
    return "zinc";
  }
  let sum = 0;
  for (let i = 0; i < trimmed.length; i++) {
    sum += trimmed.charCodeAt(i);
  }
  return avatarTones[sum % avatarTones.length];
};

const initialsOf = (name: string): string => {
  const parts = (name || "").trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) {
    return "??";
  }
  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const avatarBgClass: Record<Tone, string> = {
  emerald:
    "bg-emerald-100 text-emerald-700 ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60",
  amber:
    "bg-amber-100 text-amber-700 ring-amber-200/70 dark:bg-amber-900/40 dark:text-amber-300 dark:ring-amber-900/60",
  rose: "bg-rose-100 text-rose-700 ring-rose-200/70 dark:bg-rose-900/40 dark:text-rose-300 dark:ring-rose-900/60",
  sky: "bg-sky-100 text-sky-700 ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60",
  violet:
    "bg-violet-100 text-violet-700 ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/60",
  zinc: "bg-zinc-100 text-zinc-700 ring-zinc-200/70 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700/60",
};

const toneBgClass: Record<Tone, string> = avatarBgClass;

const isAdminVerificationReady = (row: any): boolean =>
  ["PENDING", "REVISION"].includes(row.validation_status);

const isFinalApprovalReady = (row: any): boolean =>
  row.validation_status === "PENDING_VALIDATION";

const verifyMemberAction = (row: any): void => {
  router.post(validateMember(row.id).url, undefined, { preserveScroll: true });
};

const approveFinalAction = (row: any): void => {
  router.post(approveFinal(row.id).url, undefined, { preserveScroll: true });
};

const openReviewDialog = (
  row: any,
  action: "revision" | "reject",
): void => {
  reviewRowId.value = row.id;
  reviewAction.value = action;
  reviewNotes.value = "";
  reviewDialogOpen.value = true;
};

watch(reviewDialogOpen, (open) => {
  if (!open) {
    reviewNotes.value = "";
    reviewProcessing.value = false;
  }
});

const submitReview = (): void => {
  if (!reviewRowId.value || !reviewAction.value) {
    return;
  }
  if (!reviewNotes.value || reviewNotes.value.trim().length < 5) {
    return;
  }
  reviewProcessing.value = true;
  const url =
    reviewAction.value === "reject"
      ? reject(reviewRowId.value).url
      : requestRevision(reviewRowId.value).url;
  router.post(
    url,
    { notes: reviewNotes.value },
    {
      preserveScroll: true,
      onFinish: () => {
        reviewProcessing.value = false;
        reviewDialogOpen.value = false;
        reviewRowId.value = null;
        reviewAction.value = null;
      },
    },
  );
};

const jenisAnggotaLabel = (value: string) =>
  props.options.jenisAnggota.find((option) => option.value === value)?.label ??
  value;
const jenisKelaminLabel = (value: string) =>
  value === "L" ? "Laki-laki" : value === "P" ? "Perempuan" : "-";
const kategoriLabel = (value: string) =>
  props.options.kategori.find((option) => option.value === value)?.label ??
  value ??
  "-";
const autodebetLabel = (value: string) => {
  const match = props.options.autodebet.find(
    (option) => option.value === value,
  );
  return match?.label ?? value ?? "MANUAL";
};
const exportUrl = computed(() => {
  const params = new URLSearchParams();

  Object.entries(filters.value).forEach(([key, value]) => {
    if (value) {
      params.set(key, value);
    }
  });

  const query = params.toString();

  return exportMethod.url(
    query ? { query: Object.fromEntries(params) } : undefined,
  );
});

const activeFilterChips = computed(() => {
  const chips: Array<{ key: string; label: string; tone: Tone }> = [];
  if (filters.value.status) {
    const match = props.options.statuses.find(
      (option) => option.value === filters.value.status,
    );
    chips.push({
      key: "status",
      label: `Status: ${match?.label ?? filters.value.status}`,
      tone: memberStatusToTone(filters.value.status),
    });
  }
  if (filters.value.validation_status) {
    chips.push({
      key: "validation_status",
      label: `Validasi: ${validationStatusShort(filters.value.validation_status)}`,
      tone: validationToTone(filters.value.validation_status),
    });
  }
  if (filters.value.jenis_anggota) {
    chips.push({
      key: "jenis_anggota",
      label: `Jenis: ${jenisAnggotaLabel(filters.value.jenis_anggota)}`,
      tone: "sky",
    });
  }
  if (filters.value.kategori) {
    chips.push({
      key: "kategori",
      label: `Kategori: ${kategoriLabel(filters.value.kategori)}`,
      tone: "violet",
    });
  }
  return chips;
});

const clearFilter = (key: string): void => {
  (filters.value as Record<string, string>)[key] = "";
};

const stats = computed(() => props.stats ?? {
  active: 0,
  inactive: 0,
  alb: 0,
  pending_validation: 0,
  rejected: 0,
});

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

const totalMembers = computed(
  () =>
    stats.value.active +
    stats.value.inactive +
    stats.value.alb +
    (stats.value.pending_validation ?? 0) +
    (stats.value.rejected ?? 0),
);

const kpiCards = computed(() => [
  {
    label: "Anggota Aktif",
    value: stats.value.active,
    icon: UserCheck as Component,
    tone: "emerald" as Tone,
    href: index({ query: { status: "ACTIVE" } }).url,
    sparklinePoints: sparklineFor(stats.value.active),
    meta: "Status keanggotaan aktif",
  },
  {
    label: "Non-Aktif",
    value: stats.value.inactive,
    icon: UserX as Component,
    tone: "zinc" as Tone,
    href: index({ query: { status: "INACTIVE" } }).url,
    sparklinePoints: sparklineFor(stats.value.inactive),
    meta: "Belum aktif atau dinonaktifkan",
  },
  {
    label: "ALB",
    value: stats.value.alb,
    icon: WalletCards as Component,
    tone: "violet" as Tone,
    href: index({ query: { kategori: "ALB" } }).url,
    sparklinePoints: sparklineFor(stats.value.alb),
    meta: "Anggota Luar Biasa",
  },
  {
    label: "Menunggu Validasi",
    value: stats.value.pending_validation ?? 0,
    icon: ClipboardCheck as Component,
    tone: "amber" as Tone,
    href: index({ query: { validation_status: "PENDING" } }).url,
    sparklinePoints: sparklineFor(stats.value.pending_validation ?? 0),
    meta: "Verifikasi admin / final",
  },
  {
    label: "Ditolak",
    value: stats.value.rejected ?? 0,
    icon: ShieldCheck as Component,
    tone: "rose" as Tone,
    href: index({ query: { validation_status: "REJECTED" } }).url,
    sparklinePoints: sparklineFor(stats.value.rejected ?? 0),
    meta: "Pengajuan ditolak",
  },
]);
</script>

<template>
  <Head title="Anggota Koperasi" />
  <AppLayout :breadcrumbs="breadcrumbs">
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
        <div
          class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
        >
          <div class="space-y-3">
            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-800/60"
            >
              <Users class="size-3.5" />
              Manajemen Keanggotaan
            </span>
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
            >
              Anggota Koperasi
            </h1>
            <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
              Verifikasi, aktivasi, dan kelola data keanggotaan koperasi dalam
              satu tempat. Total
              <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{
                formatNumber(totalMembers)
              }}</span>
              anggota terdaftar.
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <a
              :href="exportUrl"
              class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white/80 px-4 py-2.5 text-sm font-semibold text-zinc-700 shadow-sm shadow-zinc-950/5 backdrop-blur transition hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950/40 dark:text-zinc-200 dark:hover:bg-zinc-900"
            >
              <Download class="size-4" />
              Export Excel
            </a>
            <Button
              v-if="canManageMember"
              size="lg"
              class="bg-emerald-700 shadow-emerald-950/20 hover:bg-emerald-800"
              @click="openCreateDialog"
            >
              <Plus class="mr-2 size-4" />
              Tambah Anggota
            </Button>
          </div>
        </div>
      </section>

      <!-- KPI BAND -->
      <Deferred data="stats">
        <template #fallback>
          <div aria-live="polite" class="sr-only">
            Memuat statistik anggota koperasi.
          </div>
          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <Skeleton
              v-for="i in 5"
              :key="i"
              class="h-36 rounded-2xl"
            />
          </div>
        </template>

        <section
          class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5"
          aria-label="Ringkasan anggota"
        >
          <GradientKpiCard
            v-for="card in kpiCards"
            :key="card.label"
            :label="card.label"
            :value="formatNumber(card.value)"
            :meta="card.meta"
            :icon="card.icon"
            :tone="card.tone"
            :href="card.href"
            :sparkline-points="card.sparklinePoints"
          />
        </section>
      </Deferred>

      <!-- FILTER + TABLE -->
      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <div
          class="flex flex-col gap-3 border-b border-zinc-200/70 px-5 py-4 lg:flex-row lg:items-center lg:justify-between dark:border-zinc-800/70"
        >
          <div class="flex items-start gap-3">
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60"
            >
              <UserCog class="size-4" />
            </span>
            <div>
              <h2
                class="text-base font-semibold tracking-tight text-zinc-950 dark:text-white"
              >
                Daftar Anggota
              </h2>
              <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                Cari, filter, dan kelola anggota koperasi.
              </p>
            </div>
          </div>
          <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
            <div class="relative w-full sm:max-w-xs">
              <Search
                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"
                aria-hidden="true"
              />
              <Input
                v-model="filters.search"
                type="search"
                placeholder="Cari nama, no anggota, NPWP, telepon…"
                class="pl-9"
                aria-label="Cari anggota"
              />
            </div>
            <SelectFilter
              v-model="filters.status"
              :options="statusOptions"
              placeholder="Status"
              class="w-full sm:w-40"
            />
            <SelectFilter
              v-model="filters.validation_status"
              :options="validationStatusOptions"
              placeholder="Validasi"
              class="w-full sm:w-44"
            />
            <SelectFilter
              v-model="filters.jenis_anggota"
              :options="jenisAnggotaOptions"
              placeholder="Jenis"
              class="w-full sm:w-40"
            />
            <SelectFilter
              v-model="filters.kategori"
              :options="kategoriOptions"
              placeholder="Kategori"
              class="w-full sm:w-40"
            />
            <Button
              variant="ghost"
              size="sm"
              class="shrink-0 text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-200"
              type="button"
              @click="resetFilters"
            >
              Reset
            </Button>
          </div>
        </div>
        <div
          v-if="activeFilterChips.length > 0"
          class="flex flex-wrap items-center gap-2 border-b border-zinc-200/70 bg-zinc-50/60 px-5 py-2.5 dark:border-zinc-800/70 dark:bg-zinc-950/40"
        >
          <span
            class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
          >
            Filter aktif
          </span>
          <button
            v-for="chip in activeFilterChips"
            :key="chip.key"
            type="button"
            class="group inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset transition hover:opacity-80"
            :class="toneBgClass[chip.tone]"
            @click="clearFilter(chip.key)"
          >
            {{ chip.label }}
            <X class="size-3 transition-transform group-hover:scale-110" />
          </button>
        </div>
        <CardContent class="p-0">
          <DataTable
            :columns="columns"
            :data="tableData"
            :searchable="false"
            empty-message="Belum ada anggota koperasi."
            :empty-icon="UserRound"
          >
            <template #memberNo="{ row }">
              <div class="space-y-0.5">
                <span
                  class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                >
                  <Hash class="size-3" />
                  {{ row.no_anggota || row.member_no || "-" }}
                </span>
              </div>
            </template>

            <template #member="{ row }">
              <Link
                :href="show(row.id).url"
                prefetch
                class="group flex items-center gap-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-zinc-900"
              >
                <span
                  :class="[
                    'inline-flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold ring-2 ring-white transition-transform duration-200 group-hover:scale-105 dark:ring-zinc-900',
                    avatarBgClass[avatarToneFor(memberName(row))],
                  ]"
                  aria-hidden="true"
                >
                  {{ initialsOf(memberName(row)) }}
                </span>
                <span class="min-w-0 space-y-0.5">
                  <span
                    class="block truncate text-sm font-semibold text-zinc-950 group-hover:text-emerald-700 dark:text-white dark:group-hover:text-emerald-300"
                  >
                    {{ memberName(row) }}
                  </span>
                  <span
                    v-if="memberEmail(row)"
                    class="flex items-center gap-1 truncate text-xs text-zinc-500 dark:text-zinc-400"
                  >
                    <Mail class="size-3 shrink-0" />
                    {{ memberEmail(row) }}
                  </span>
                  <span
                    v-if="memberPhone(row)"
                    class="flex items-center gap-1 truncate text-xs text-zinc-500 dark:text-zinc-400"
                  >
                    <Phone class="size-3 shrink-0" />
                    {{ memberPhone(row) }}
                  </span>
                </span>
              </Link>
            </template>

            <template #status="{ row }">
              <StatusPill
                :tone="memberStatusToTone(row.status)"
                :label="memberStatusLabel(row.status)"
              />
            </template>

            <template #validationStatus="{ row }">
              <StatusPill
                v-if="row.validation_status"
                :tone="validationToTone(row.validation_status)"
                :label="validationStatusShort(row.validation_status)"
              />
              <span v-else class="text-xs text-zinc-400">-</span>
            </template>

            <template #kategori="{ value }">
              <span
                class="inline-flex items-center rounded-md bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/60"
              >
                {{ kategoriLabel(value) }}
              </span>
            </template>

            <template #autodebet="{ row }">
              <span
                v-if="row.autodebet === 'AUTODEBET'"
                class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300"
              >
                <Banknote class="size-3.5" />
                {{ autodebetLabel(row.autodebet) }}
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-600 dark:text-zinc-400"
              >
                <Settings2 class="size-3.5" />
                {{ autodebetLabel(row.autodebet) }}
              </span>
              <div
                v-if="row.no_rekening"
                class="mt-0.5 font-mono text-[10px] text-zinc-500"
              >
                {{ row.no_rekening }}
              </div>
            </template>

            <template #actions="{ row }">
              <div class="flex items-center justify-end gap-1.5">
                <Button
                  v-if="
                    canVerifyMember && isAdminVerificationReady(row)
                  "
                  size="sm"
                  variant="default"
                  class="bg-emerald-700 hover:bg-emerald-800"
                  data-test="member-approve"
                  @click="verifyMemberAction(row)"
                >
                  <CheckCircle2 class="size-3.5" />
                  <span class="hidden sm:inline">Verifikasi</span>
                </Button>
                <Button
                  v-else-if="canApproveMember && isFinalApprovalReady(row)"
                  size="sm"
                  variant="default"
                  class="bg-emerald-700 hover:bg-emerald-800"
                  data-test="member-approve-final"
                  @click="approveFinalAction(row)"
                >
                  <CheckCircle2 class="size-3.5" />
                  <span class="hidden sm:inline">Approve</span>
                </Button>

                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button
                      size="icon-sm"
                      variant="outline"
                      :aria-label="`Aksi lain untuk ${memberName(row)}`"
                    >
                      <MoreHorizontal class="size-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" class="w-52">
                    <DropdownMenuLabel class="text-xs">
                      {{ memberName(row) }}
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem as-child>
                      <Link :href="show(row.id).url" prefetch>
                        <Eye class="mr-2 size-4" />
                        Lihat detail
                      </Link>
                    </DropdownMenuItem>
                    <template v-if="canManageMember">
                      <DropdownMenuItem @click="openEditDialog(row)">
                        <Pencil class="mr-2 size-4" />
                        Edit anggota
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        v-if="row.status === 'ACTIVE'"
                        @click="deactivateMember(row)"
                      >
                        <PowerOff class="mr-2 size-4" />
                        Nonaktifkan
                      </DropdownMenuItem>
                      <DropdownMenuItem v-else @click="router.post(activate(row.id).url)">
                        <Power class="mr-2 size-4 text-emerald-600" />
                        Aktifkan
                      </DropdownMenuItem>
                    </template>
                    <template
                      v-if="
                        canReviewMember &&
                        (isAdminVerificationReady(row) ||
                          isFinalApprovalReady(row))
                      "
                    >
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        data-test="member-revision"
                        @click="openReviewDialog(row, 'revision')"
                      >
                        <Send class="mr-2 size-4" />
                        Minta revisi
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        variant="destructive"
                        data-test="member-reject"
                        @click="openReviewDialog(row, 'reject')"
                      >
                        <UserX class="mr-2 size-4" />
                        Tolak anggota
                      </DropdownMenuItem>
                    </template>
                    <template v-if="canManageMember">
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        variant="destructive"
                        @click="askDelete(row)"
                      >
                        <Trash2 class="mr-2 size-4" />
                        Hapus anggota
                      </DropdownMenuItem>
                    </template>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </template>
          </DataTable>
        </CardContent>
      </Card>
    </PageContainer>

    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      title="Hapus Anggota"
      :message="`Tindakan ini tidak dapat dibatalkan. Anggota ${memberPendingDelete?.name ?? ''} akan dihapus permanen dari sistem.`"
      confirm-label="Hapus"
      variant="danger"
      @confirm="confirmDelete"
    />

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
          <Label for="review-notes">Catatan</Label>
          <textarea
            id="review-notes"
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

    <!-- CREATE DIALOG -->
    <Dialog v-model:open="createMemberDialogOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
        <DialogHeader>
          <div class="flex items-start gap-3">
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60"
            >
              <UserPlus class="size-4" />
            </span>
            <div>
              <DialogTitle>Tambah Anggota Baru</DialogTitle>
              <DialogDescription>
                Isi data utama anggota. Nomor anggota boleh dikosongkan agar
                dibuat otomatis.
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>

        <form class="space-y-6" @submit.prevent="submitCreateMember">
          <!-- Identitas -->
          <section class="space-y-3">
            <header class="flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
              <IdCard class="size-4 text-emerald-600" />
              Identitas
            </header>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="member-no">No Anggota</Label>
                <Input
                  id="member-no"
                  v-model="createMemberForm.no_anggota"
                  maxlength="10"
                  placeholder="Otomatis jika kosong"
                />
                <InputError :message="createMemberForm.errors.no_anggota" />
              </div>

              <div class="space-y-2">
                <Label for="member-active-date">Tanggal Aktif</Label>
                <Input
                  id="member-active-date"
                  v-model="createMemberForm.tanggal_aktif"
                  type="date"
                  required
                />
                <InputError :message="createMemberForm.errors.tanggal_aktif" />
              </div>

              <div class="space-y-2 md:col-span-2">
                <Label for="member-name">Nama Anggota</Label>
                <Input
                  id="member-name"
                  v-model="createMemberForm.nama_anggota"
                  maxlength="100"
                  required
                />
                <InputError :message="createMemberForm.errors.nama_anggota" />
              </div>

              <div class="space-y-2">
                <Label for="member-email">Email</Label>
                <Input
                  id="member-email"
                  v-model="createMemberForm.email"
                  type="email"
                  maxlength="255"
                  placeholder="nama@email.com"
                />
                <InputError :message="createMemberForm.errors.email" />
              </div>

              <div class="space-y-2">
                <Label for="member-phone">No Telp</Label>
                <Input
                  id="member-phone"
                  v-model="createMemberForm.no_telp"
                  maxlength="20"
                />
                <InputError :message="createMemberForm.errors.no_telp" />
              </div>
            </div>
          </section>

          <!-- Keanggotaan -->
          <section class="space-y-3">
            <header class="flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
              <Building2 class="size-4 text-sky-600" />
              Keanggotaan
            </header>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="member-type">Jenis Anggota</Label>
                <select
                  id="member-type"
                  v-model="createMemberForm.jenis_anggota"
                  class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.jenisAnggota"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="createMemberForm.errors.jenis_anggota" />
              </div>

              <div class="space-y-2">
                <Label for="member-gender">Jenis Kelamin</Label>
                <select
                  id="member-gender"
                  v-model="createMemberForm.jenis_kelamin"
                  class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
                >
                  <option value="L">Laki-laki</option>
                  <option value="P">Perempuan</option>
                </select>
                <InputError :message="createMemberForm.errors.jenis_kelamin" />
              </div>

              <div class="space-y-2">
                <Label for="member-category">Kategori</Label>
                <select
                  id="member-category"
                  v-model="createMemberForm.kategori"
                  class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.kategori"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="createMemberForm.errors.kategori" />
              </div>
            </div>
          </section>

          <!-- Pembayaran -->
          <section class="space-y-3">
            <header class="flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
              <Banknote class="size-4 text-violet-600" />
              Pembayaran
            </header>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="member-autodebet">Autodebet</Label>
                <select
                  id="member-autodebet"
                  v-model="createMemberForm.autodebet"
                  class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.autodebet"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="createMemberForm.errors.autodebet" />
              </div>

              <div class="space-y-2">
                <Label for="member-account">No Rekening</Label>
                <Input
                  id="member-account"
                  v-model="createMemberForm.no_rekening"
                  maxlength="30"
                  :disabled="createMemberForm.autodebet === 'MANUAL'"
                  placeholder="Kosong untuk manual"
                />
                <InputError :message="createMemberForm.errors.no_rekening" />
              </div>

              <div class="space-y-2 md:col-span-2">
                <Label for="member-opening-balance">Saldo Awal Simpanan</Label>
                <Input
                  id="member-opening-balance"
                  v-model="createMemberForm.opening_saving_balance"
                  type="number"
                  min="0"
                  step="1000"
                />
                <InputError
                  :message="createMemberForm.errors.opening_saving_balance"
                />
              </div>
            </div>
          </section>

          <DialogFooter class="gap-2">
            <Button
              type="button"
              variant="outline"
              @click="createMemberDialogOpen = false"
            >
              Batal
            </Button>
            <Button type="submit" :disabled="createMemberForm.processing">
              <Plus class="mr-2 size-4" />
              Simpan Anggota
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- EDIT DIALOG -->
    <Dialog v-model:open="editMemberDialogOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
        <DialogHeader>
          <div class="flex items-start gap-3">
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60"
            >
              <Pencil class="size-4" />
            </span>
            <div>
              <DialogTitle>Edit Anggota</DialogTitle>
              <DialogDescription>
                Perbarui data anggota koperasi.
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>

        <form class="space-y-6" @submit.prevent="submitEditMember">
          <!-- Identitas -->
          <section class="space-y-3">
            <header class="flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
              <IdCard class="size-4 text-emerald-600" />
              Identitas
            </header>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="edit-member-no">No Anggota</Label>
                <Input
                  id="edit-member-no"
                  v-model="editMemberForm.no_anggota"
                  maxlength="10"
                  required
                />
                <InputError :message="editMemberForm.errors.no_anggota" />
              </div>

              <div class="space-y-2">
                <Label for="edit-member-active-date">Tanggal Aktif</Label>
                <Input
                  id="edit-member-active-date"
                  v-model="editMemberForm.tanggal_aktif"
                  type="date"
                  required
                />
                <InputError :message="editMemberForm.errors.tanggal_aktif" />
              </div>

              <div class="space-y-2">
                <Label for="edit-member-join-date">Tanggal Bergabung</Label>
                <Input
                  id="edit-member-join-date"
                  v-model="editMemberForm.joined_at"
                  type="date"
                />
                <InputError :message="editMemberForm.errors.joined_at" />
              </div>

              <div class="space-y-2">
                <Label for="edit-member-email">Email</Label>
                <Input
                  id="edit-member-email"
                  v-model="editMemberForm.email"
                  type="email"
                  maxlength="255"
                  placeholder="nama@email.com"
                />
                <InputError :message="editMemberForm.errors.email" />
              </div>

              <div class="space-y-2">
                <Label for="edit-member-status">Status</Label>
                <select
                  id="edit-member-status"
                  v-model="editMemberForm.status"
                  class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.statuses"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="editMemberForm.errors.status" />
              </div>

              <div class="space-y-2 md:col-span-2">
                <Label for="edit-member-name">Nama Anggota</Label>
                <Input
                  id="edit-member-name"
                  v-model="editMemberForm.nama_anggota"
                  maxlength="100"
                  required
                />
                <InputError :message="editMemberForm.errors.nama_anggota" />
              </div>

              <div class="space-y-2">
                <Label for="edit-member-npwp">NPWP</Label>
                <Input
                  id="edit-member-npwp"
                  v-model="editMemberForm.npwp"
                  maxlength="30"
                />
                <InputError :message="editMemberForm.errors.npwp" />
              </div>

              <div class="space-y-2">
                <Label for="edit-member-phone">No Telp</Label>
                <Input
                  id="edit-member-phone"
                  v-model="editMemberForm.no_telp"
                  maxlength="20"
                />
                <InputError :message="editMemberForm.errors.no_telp" />
              </div>
            </div>
          </section>

          <!-- Keanggotaan -->
          <section class="space-y-3">
            <header class="flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
              <Building2 class="size-4 text-sky-600" />
              Keanggotaan
            </header>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="edit-member-type">Jenis Anggota</Label>
                <select
                  id="edit-member-type"
                  v-model="editMemberForm.jenis_anggota"
                  class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.jenisAnggota"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="editMemberForm.errors.jenis_anggota" />
              </div>

              <div class="space-y-2">
                <Label for="edit-member-gender">Jenis Kelamin</Label>
                <select
                  id="edit-member-gender"
                  v-model="editMemberForm.jenis_kelamin"
                  class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.jenisKelamin"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="editMemberForm.errors.jenis_kelamin" />
              </div>

              <div class="space-y-2">
                <Label for="edit-member-category">Kategori</Label>
                <select
                  id="edit-member-category"
                  v-model="editMemberForm.kategori"
                  class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.kategori"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="editMemberForm.errors.kategori" />
              </div>
            </div>
          </section>

          <!-- Pembayaran -->
          <section class="space-y-3">
            <header class="flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
              <Banknote class="size-4 text-violet-600" />
              Pembayaran
            </header>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="edit-member-autodebet">Autodebet</Label>
                <select
                  id="edit-member-autodebet"
                  v-model="editMemberForm.autodebet"
                  class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.autodebet"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="editMemberForm.errors.autodebet" />
              </div>

              <div class="space-y-2">
                <Label for="edit-member-account">No Rekening</Label>
                <Input
                  id="edit-member-account"
                  v-model="editMemberForm.no_rekening"
                  maxlength="30"
                  :disabled="editMemberForm.autodebet === 'MANUAL'"
                  placeholder="Kosong untuk manual"
                />
                <InputError :message="editMemberForm.errors.no_rekening" />
              </div>
            </div>
          </section>

          <!-- Akses -->
          <section
            class="space-y-3 rounded-xl border border-zinc-200/70 bg-zinc-50/50 p-4 dark:border-zinc-800/70 dark:bg-zinc-950/40"
          >
            <header class="flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
              <KeyRound class="size-4 text-amber-600" />
              Akses Login & Simpanan
            </header>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="edit-member-password">Password Login Baru</Label>
                <Input
                  id="edit-member-password"
                  v-model="editMemberForm.member_login_password"
                  type="password"
                  autocomplete="new-password"
                  placeholder="Kosongkan jika tidak berubah"
                />
                <InputError
                  :message="editMemberForm.errors.member_login_password"
                />
              </div>
              <div class="space-y-2">
                <Label for="edit-member-opening-balance">
                  Saldo Awal Simpanan
                </Label>
                <Input
                  id="edit-member-opening-balance"
                  v-model="editMemberForm.opening_saving_balance"
                  type="number"
                  min="0"
                  step="1000"
                />
                <InputError
                  :message="editMemberForm.errors.opening_saving_balance"
                />
              </div>
            </div>
          </section>

          <DialogFooter class="gap-2">
            <Button
              type="button"
              variant="outline"
              @click="editMemberDialogOpen = false"
            >
              Batal
            </Button>
            <Button type="submit" :disabled="editMemberForm.processing">
              Simpan Perubahan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
