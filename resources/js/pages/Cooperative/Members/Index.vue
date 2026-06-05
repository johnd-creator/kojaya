<script setup lang="ts">
import { Deferred, Head, Link, router } from "@inertiajs/vue3";
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
import PageContainer from "@/components/PageContainer.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatsCard from "@/components/StatsCard.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { useCan } from "@/composables/useCan";
import { useTableFilters } from "@/composables/useTableFilters";
import AppLayout from "@/layouts/AppLayout.vue";
import {
  activate,
  create,
  destroy,
  edit,
  exportMethod,
  index,
  resign,
  show,
} from "@/routes/cooperative/members";

const props = defineProps<{
  members: any;
  filters: {
    search?: string;
    status?: string;
    jenis_anggota?: string;
    kategori?: string;
  };
  options: {
    statuses: Array<{ value: string; label: string }>;
    jenisAnggota: Array<{ value: string; label: string }>;
    kategori: Array<{ value: string; label: string }>;
  };
  stats?: { active: number; inactive: number; alb: number };
}>();

const filters = ref({
  search: props.filters.search ?? "",
  status: props.filters.status ?? "",
  jenis_anggota: props.filters.jenis_anggota ?? "",
  kategori: props.filters.kategori ?? "",
});
const { can } = useCan();
const canManageMember = computed(() => can("manage_cooperative_member"));
const deleteDialogOpen = ref(false);
const memberPendingDelete = ref<{ id: string | number; name: string } | null>(null);

const askDelete = (row: any): void => {
  memberPendingDelete.value = {
    id: row.id,
    name: row.nama_anggota_clean || row.nama_anggota || row.name || "anggota ini",
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
const statusOptions = [
  { label: "Semua status", value: "" },
  ...props.options.statuses,
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

const statusLabel = (status: string) => (status === "ACTIVE" ? "AKTIF" : "NON-AKTIF");
const jenisAnggotaLabel = (value: string) =>
  props.options.jenisAnggota.find((option) => option.value === value)?.label ?? value;
const jenisKelaminLabel = (value: string) =>
  value === "L" ? "Laki-laki" : value === "P" ? "Perempuan" : "-";
const kategoriLabel = (value: string) =>
  props.options.kategori.find((option) => option.value === value)?.label ?? value ?? "-";
const exportUrl = computed(() => {
  const params = new URLSearchParams();

  Object.entries(filters.value).forEach(([key, value]) => {
    if (value) {
      params.set(key, value);
    }
  });

  const query = params.toString();

  return exportMethod.url(query ? { query: Object.fromEntries(params) } : undefined);
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
          <Link :href="create().url" prefetch>
            <Button v-can="'manage_cooperative_member'"><Plus class="mr-2 h-4 w-4" />Anggota Baru</Button>
          </Link>
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

        <div class="grid gap-4 md:grid-cols-3">
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
          <StatsCard
            label="ALB"
            :value="stats?.alb ?? 0"
            :icon="WalletCards"
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
          <div class="text-xs text-zinc-500">{{ row.no_telp || row.phone || "-" }}</div>
          <div v-if="row.email" class="text-xs text-zinc-500">{{ row.email }}</div>
        </template>

        <template #status="{ value }">
          <StatusBadge
            :status="statusLabel(value)"
            :variant="getMemberStatusVariant(value)"
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
          <div class="flex justify-end gap-2">
            <Button
              as-child
              size="sm"
              variant="outline"
              :aria-label="`Lihat detail anggota ${row.nama_anggota_clean || row.nama_anggota || row.name}`"
            >
              <Link :href="show(row.id).url" prefetch>
                <Eye class="h-4 w-4" />
                <span class="sr-only">Lihat</span>
              </Link>
            </Button>
            <Button
              v-if="canManageMember"
              as-child
              size="sm"
              variant="outline"
              :aria-label="`Edit anggota ${row.nama_anggota_clean || row.nama_anggota || row.name}`"
            >
              <Link :href="edit(row.id).url" prefetch>
                <Pencil class="h-4 w-4" />
                <span class="sr-only">Edit</span>
              </Link>
            </Button>
            <Button
              v-if="canManageMember && row.status !== 'ACTIVE'"
              size="sm"
              variant="outline"
              :aria-label="`Aktifkan anggota ${row.nama_anggota_clean || row.nama_anggota || row.name}`"
              @click="router.post(activate(row.id).url)"
            >
              <UserCheck class="h-4 w-4" />
            </Button>
            <Button
              v-if="canManageMember && row.status === 'ACTIVE'"
              size="sm"
              variant="outline"
              :aria-label="`Nonaktifkan anggota ${row.nama_anggota_clean || row.nama_anggota || row.name}`"
              @click="router.post(resign(row.id).url)"
            >
              <UserX class="h-4 w-4" />
            </Button>
            <Button
              v-if="canManageMember"
              size="sm"
              variant="destructive"
              :aria-label="`Hapus anggota ${row.nama_anggota_clean || row.nama_anggota || row.name}`"
              @click="askDelete(row)"
            >
              <Trash2 class="h-4 w-4" />
            </Button>
          </div>
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
  </AppLayout>
</template>
