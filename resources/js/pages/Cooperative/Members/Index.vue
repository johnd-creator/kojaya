<script setup lang="ts">
import { Deferred, Head, Link, router, useForm } from "@inertiajs/vue3";
import {
  Download,
  Eye,
  Pencil,
  Plus,
  Trash2,
  UserCheck,
  UserRound,
  UserX,
  WalletCards,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import FilterBar from "@/components/FilterBar.vue";
import InputError from "@/components/InputError.vue";
import PageContainer from "@/components/PageContainer.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatsCard from "@/components/StatsCard.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
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
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { useCan } from "@/composables/useCan";
import { useTableFilters } from "@/composables/useTableFilters";
import AppLayout from "@/layouts/AppLayout.vue";
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
const editMemberForm = useForm({
  employee_id: "",
  user_id: "",
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
  user_id: "",
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
  editMemberForm.user_id = row.user_id ?? "";
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
  { header: "No Anggota", key: "no_anggota", slot: "memberNo" },
  { header: "Nama", key: "nama_anggota", slot: "member" },
  { header: "Status", key: "status", slot: "status" },
  { header: "Validasi", key: "validation_status", slot: "validationStatus" },
  { header: "Jenis Anggota", key: "jenis_anggota", slot: "jenisAnggota" },
  { header: "Jenis Kelamin", key: "jenis_kelamin", slot: "jenisKelamin" },
  { header: "Kategori", key: "kategori", slot: "kategori" },
  { header: "Autodebet", key: "autodebet" },
  { header: "No Rekening", key: "no_rekening", slot: "rekening" },
  { header: "Aksi", key: "actions", slot: "actions", align: "right" as const },
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

const getMemberStatusVariant = (
  status: string,
): "success" | "warning" | "secondary" | "destructive" => {
  switch (status) {
    case "ACTIVE":
      return "success";
    case "INACTIVE":
    case "RESIGNED":
      return "secondary";
    default:
      return "warning";
  }
};

const validationStatusLabel = (value: string | null | undefined): string => {
  if (!value) return "-";
  const match = (props.options.validationStatuses ?? []).find(
    (option) => option.value === value,
  );
  return match?.label ?? value;
};

const getValidationVariant = (
  value: string | null | undefined,
): "success" | "warning" | "secondary" | "destructive" => {
  switch (value) {
    case "ACTIVE":
      return "success";
    case "PENDING":
    case "PENDING_VALIDATION":
      return "warning";
    case "REVISION":
      return "secondary";
    case "REJECTED":
      return "destructive";
    default:
      return "secondary";
  }
};

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

const requestRevisionAction = (row: any): void => {
  const notes = window.prompt(
    "Tuliskan catatan revisi yang akan dikirim ke anggota:",
  );
  if (!notes) return;
  router.post(requestRevision(row.id).url, { notes }, { preserveScroll: true });
};

const rejectMemberAction = (row: any): void => {
  const notes = window.prompt(
    "Tuliskan alasan penolakan (wajib, minimal 5 karakter):",
  );
  if (!notes || notes.length < 5) return;
  router.post(reject(row.id).url, { notes }, { preserveScroll: true });
};

const statusLabel = (status: string) =>
  status === "ACTIVE" ? "AKTIF" : "NON-AKTIF";
const jenisAnggotaLabel = (value: string) =>
  props.options.jenisAnggota.find((option) => option.value === value)?.label ??
  value;
const jenisKelaminLabel = (value: string) =>
  value === "L" ? "Laki-laki" : value === "P" ? "Perempuan" : "-";
const kategoriLabel = (value: string) =>
  props.options.kategori.find((option) => option.value === value)?.label ??
  value ??
  "-";
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
</script>

<template>
  <Head title="Anggota Koperasi" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <PageContainer class="max-w-none">
      <div
        class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
      >
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Anggota Koperasi</h1>
          <p class="mt-1 text-sm text-zinc-500">
            Keanggotaan terpusat di Koperasi Utama.
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <a :href="exportUrl">
            <Button variant="outline">
              <Download class="mr-2 h-4 w-4" />Export Excel
            </Button>
          </a>
          <Button v-if="canManageMember" @click="createMemberDialogOpen = true">
            <Plus class="mr-2 h-4 w-4" />Tambah Anggota
          </Button>
        </div>
      </div>

      <Deferred data="stats">
        <template #fallback>
          <div aria-live="polite" class="sr-only">
            Memuat statistik anggota koperasi.
          </div>
          <div class="grid gap-4 md:grid-cols-3">
            <Skeleton
              v-for="card in 3"
              :key="card"
              class="h-24 rounded-lg border"
            />
          </div>
        </template>

        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
          <StatsCard
            label="Aktif"
            :value="stats?.active ?? 0"
            :icon="UserCheck"
          />
          <StatsCard
            label="Non-Aktif"
            :value="stats?.inactive ?? 0"
            :icon="UserX"
          />
          <StatsCard label="ALB" :value="stats?.alb ?? 0" :icon="WalletCards" />
          <StatsCard
            label="Menunggu Validasi"
            :value="stats?.pending_validation ?? 0"
            :icon="UserCheck"
          />
          <StatsCard
            label="Ditolak"
            :value="stats?.rejected ?? 0"
            :icon="UserX"
          />
        </div>
      </Deferred>

      <FilterBar
        v-model:search="filters.search"
        search-placeholder="Cari no anggota, nama, NPWP, telepon"
        @reset="resetFilters"
      >
        <SelectFilter
          v-model="filters.status"
          :options="statusOptions"
          placeholder="Semua status"
          class="w-full sm:max-w-[180px]"
        />
        <SelectFilter
          v-model="filters.validation_status"
          :options="validationStatusOptions"
          placeholder="Status validasi"
          class="w-full sm:max-w-[200px]"
        />
        <SelectFilter
          v-model="filters.jenis_anggota"
          :options="jenisAnggotaOptions"
          placeholder="Semua jenis"
          class="w-full sm:max-w-[220px]"
        />
        <SelectFilter
          v-model="filters.kategori"
          :options="kategoriOptions"
          placeholder="Semua kategori"
          class="w-full sm:max-w-[220px]"
        />
      </FilterBar>

      <DataTable
        :columns="columns"
        :data="tableData"
        :searchable="false"
        empty-message="Belum ada anggota koperasi."
        :empty-icon="UserRound"
      >
        <template #memberNo="{ row }">
          <div class="font-medium">{{ row.no_anggota || row.member_no }}</div>
        </template>

        <template #member="{ row }">
          <Link
            class="font-medium hover:text-indigo-600"
            :href="show(row.id).url"
            prefetch
            >{{ row.nama_anggota_clean || row.nama_anggota || row.name }}</Link
          >
          <div class="text-xs text-zinc-500">
            {{ row.no_telp || row.phone || "-" }}
          </div>
          <div v-if="row.email" class="text-xs text-zinc-500">
            {{ row.email }}
          </div>
        </template>

        <template #status="{ value }">
          <StatusBadge
            :status="statusLabel(value)"
            :variant="getMemberStatusVariant(value)"
          />
        </template>

        <template #validationStatus="{ row }">
          <StatusBadge
            :status="validationStatusLabel(row.validation_status)"
            :variant="getValidationVariant(row.validation_status)"
          />
        </template>

        <template #jenisAnggota="{ value }">
          {{ jenisAnggotaLabel(value) }}
        </template>

        <template #jenisKelamin="{ value }">
          {{ jenisKelaminLabel(value) }}
        </template>

        <template #kategori="{ value }">
          {{ kategoriLabel(value) }}
        </template>

        <template #rekening="{ row }">
          {{ row.no_rekening || "MANUAL" }}
        </template>

        <template #actions="{ row }">
          <TooltipProvider :delay-duration="300">
            <div class="flex flex-wrap justify-end gap-2">
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button
                    as-child
                    size="sm"
                    variant="outline"
                  >
                    <Link :href="show(row.id).url" prefetch>
                      <Eye class="h-4 w-4" />
                    </Link>
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Lihat detail</TooltipContent>
              </Tooltip>
              <Tooltip v-if="canVerifyMember && isAdminVerificationReady(row)">
                <TooltipTrigger as-child>
                  <Button
                    size="sm"
                    variant="default"
                    data-test="member-approve"
                    @click="verifyMemberAction(row)"
                  >
                    <UserCheck class="h-4 w-4" />
                    <span class="hidden sm:inline">Verifikasi</span>
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Verifikasi admin</TooltipContent>
              </Tooltip>
              <Tooltip v-if="canApproveMember && isFinalApprovalReady(row)">
                <TooltipTrigger as-child>
                  <Button
                    size="sm"
                    variant="default"
                    data-test="member-approve-final"
                    @click="approveFinalAction(row)"
                  >
                    <UserCheck class="h-4 w-4" />
                    <span class="hidden sm:inline">Approve Final</span>
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Approve final</TooltipContent>
              </Tooltip>
              <Tooltip v-if="canReviewMember && (isAdminVerificationReady(row) || isFinalApprovalReady(row))">
                <TooltipTrigger as-child>
                  <Button
                    size="sm"
                    variant="outline"
                    data-test="member-revision"
                    @click="requestRevisionAction(row)"
                  >
                    <Pencil class="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Minta revisi</TooltipContent>
              </Tooltip>
              <Tooltip v-if="canReviewMember && (isAdminVerificationReady(row) || isFinalApprovalReady(row))">
                <TooltipTrigger as-child>
                  <Button
                    size="sm"
                    variant="destructive"
                    data-test="member-reject"
                    @click="rejectMemberAction(row)"
                  >
                    <UserX class="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Tolak anggota</TooltipContent>
              </Tooltip>
              <Tooltip v-if="canManageMember">
                <TooltipTrigger as-child>
                  <Button
                    size="sm"
                    variant="outline"
                    @click="openEditDialog(row)"
                  >
                    <Pencil class="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Edit anggota</TooltipContent>
              </Tooltip>
              <Tooltip v-if="canManageMember && row.status !== 'ACTIVE'">
                <TooltipTrigger as-child>
                  <Button
                    size="sm"
                    variant="outline"
                    @click="router.post(activate(row.id).url)"
                  >
                    <UserCheck class="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Aktifkan anggota</TooltipContent>
              </Tooltip>
              <Tooltip v-if="canManageMember && row.status === 'ACTIVE'">
                <TooltipTrigger as-child>
                  <Button
                    size="sm"
                    variant="outline"
                    @click="deactivateMember(row)"
                  >
                    <UserX class="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Nonaktifkan anggota</TooltipContent>
              </Tooltip>
              <Tooltip v-if="canManageMember">
                <TooltipTrigger as-child>
                  <Button
                    size="sm"
                    variant="destructive"
                    @click="askDelete(row)"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Hapus anggota</TooltipContent>
              </Tooltip>
            </div>
          </TooltipProvider>
        </template>
      </DataTable>
    </PageContainer>

    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      title="Hapus Anggota"
      :message="`Tindakan ini tidak dapat dibatalkan. Anggota ${memberPendingDelete?.name ?? ''} akan dihapus permanen dari sistem.`"
      confirm-label="Hapus"
      variant="danger"
      @confirm="confirmDelete"
    />

    <Dialog v-model:open="createMemberDialogOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
        <DialogHeader>
          <DialogTitle>Tambah Anggota Baru</DialogTitle>
          <DialogDescription>
            Isi data utama anggota. Nomor anggota boleh dikosongkan agar dibuat
            otomatis.
          </DialogDescription>
        </DialogHeader>

        <form class="space-y-5" @submit.prevent="submitCreateMember">
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

            <div class="space-y-2">
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

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              @click="createMemberDialogOpen = false"
            >
              Batal
            </Button>
            <Button type="submit" :disabled="createMemberForm.processing">
              Simpan Anggota
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="editMemberDialogOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
        <DialogHeader>
          <DialogTitle>Edit Anggota</DialogTitle>
          <DialogDescription>
            Perbarui data anggota koperasi.
          </DialogDescription>
        </DialogHeader>

        <form class="space-y-5" @submit.prevent="submitEditMember">
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

          <div
            class="grid gap-4 rounded-xl border border-zinc-200/80 bg-white/95 p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900 md:grid-cols-2"
          >
            <div class="md:col-span-2">
              <h2 class="text-lg font-semibold">Akses Login & Simpanan</h2>
            </div>
            <div class="space-y-2">
              <Label for="edit-member-password">Password Login Baru</Label>
              <Input
                id="edit-member-password"
                v-model="editMemberForm.member_login_password"
                type="password"
                autocomplete="new-password"
                placeholder="Kosongkan jika tidak berubah"
              />
              <InputError :message="editMemberForm.errors.member_login_password" />
            </div>
            <div class="space-y-2">
              <Label for="edit-member-opening-balance">Saldo Awal Simpanan</Label>
              <Input
                id="edit-member-opening-balance"
                v-model="editMemberForm.opening_saving_balance"
                type="number"
                min="0"
                step="1000"
              />
              <InputError :message="editMemberForm.errors.opening_saving_balance" />
            </div>
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              @click="editMemberDialogOpen = false"
            >
              Batal
            </Button>
            <Button type="submit" :disabled="editMemberForm.processing">
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
