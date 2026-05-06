<script setup lang="ts">
import { Deferred, Head, Link, router } from "@inertiajs/vue3";
import {
  Plus,
  UserCheck,
  UserRound,
  UserX,
  WalletCards,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import {
  activate,
  create,
  edit,
  index,
  resign,
  show,
} from "@/routes/cooperative/members";
import FilterBar from "@/components/FilterBar.vue";
import PageContainer from "@/components/PageContainer.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatsCard from "@/components/StatsCard.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { useTableFilters } from "@/composables/useTableFilters";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";

const props = defineProps<{
  members: any;
  filters: { search?: string; status?: string };
  stats?: { active: number; pending: number };
}>();

const filters = ref({
  search: props.filters.search ?? "",
  status: props.filters.status ?? "",
});
const statusOptions = [
  { label: "Semua status", value: "" },
  { label: "PENDING", value: "PENDING" },
  { label: "ACTIVE", value: "ACTIVE" },
  { label: "INACTIVE", value: "INACTIVE" },
  { label: "RESIGNED", value: "RESIGNED" },
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
  { header: "Anggota", key: "name", slot: "member" },
  { header: "Kontak", key: "email", slot: "contact" },
  { header: "Status", key: "status", slot: "status" },
  { header: "Akun", key: "user", slot: "account" },
  {
    header: "Simpanan",
    key: "saving_balance",
    slot: "balance",
    align: "right" as const,
  },
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

const totalMembers = computed(() => {
  if (props.members?.total) {
    return props.members.total;
  }

  return props.members?.data?.length ?? 0;
});

const getMemberStatusVariant = (
  status: string,
): "success" | "warning" | "secondary" | "destructive" => {
  switch (status) {
    case "ACTIVE":
      return "success";
    case "PENDING":
      return "warning";
    case "INACTIVE":
      return "secondary";
    case "RESIGNED":
      return "destructive";
    default:
      return "secondary";
  }
};
</script>

<template>
  <Head title="Anggota Koperasi" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <PageContainer>
      <div
        class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
      >
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Anggota Koperasi</h1>
          <p class="mt-1 text-sm text-zinc-500">
            Keanggotaan terpusat di Koperasi Utama.
          </p>
        </div>
        <Link :href="create().url" prefetch>
          <Button v-can="'manage_cooperative_member'"><Plus class="mr-2 h-4 w-4" />Anggota Baru</Button>
        </Link>
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
            label="Pending"
            :value="stats?.pending ?? 0"
            :icon="UserRound"
          />
          <StatsCard
            label="Total Terdata"
            :value="totalMembers"
            :icon="WalletCards"
          />
        </div>
      </Deferred>

      <FilterBar
        v-model:search="filters.search"
        search-placeholder="Cari nomor, nama, email, NIK"
        @reset="resetFilters"
      >
        <SelectFilter
          v-model="filters.status"
          :options="statusOptions"
          placeholder="Semua status"
          class="w-full sm:max-w-[180px]"
        />
      </FilterBar>

      <DataTable
        :columns="columns"
        :data="tableData"
        :searchable="false"
        empty-message="Belum ada anggota koperasi."
        :empty-icon="UserRound"
      >
        <template #member="{ row }">
          <Link
            class="font-medium hover:text-indigo-600"
            :href="show(row.id).url"
            prefetch
            >{{ row.name }}</Link
          >
          <div class="text-xs text-zinc-500">{{ row.member_no }}</div>
        </template>

        <template #contact="{ row }">
          <div class="text-zinc-600">
            <div>{{ row.email || "-" }}</div>
            <div class="text-xs">{{ row.phone || "-" }}</div>
          </div>
        </template>

        <template #status="{ value }">
          <StatusBadge
            :status="value"
            :variant="getMemberStatusVariant(value)"
          />
        </template>

        <template #account="{ row }">
          <StatusBadge
            :status="row.user ? 'LINKED' : 'UNLINKED'"
            :label="row.user ? 'User aktif' : 'Belum tertaut'"
            :variant="row.user ? 'success' : 'secondary'"
          />
        </template>

        <template #balance="{ value }">
          <span class="font-medium text-zinc-900 dark:text-zinc-100">{{
            formatCurrency(value)
          }}</span>
        </template>

        <template #actions="{ row }">
          <div class="flex justify-end gap-2">
            <Link :href="edit(row.id).url" prefetch
              ><Button v-can="'manage_cooperative_member'" size="sm" variant="outline">Edit</Button></Link
            >
            <Button
              v-if="row.status !== 'ACTIVE'"
              v-can="'manage_cooperative_member'"
              size="sm"
              variant="outline"
              :aria-label="`Aktifkan anggota ${row.name}`"
              @click="router.post(activate(row.id).url)"
            >
              <UserCheck class="h-4 w-4" />
            </Button>
            <Button
              v-if="row.status === 'ACTIVE'"
              v-can="'manage_cooperative_member'"
              size="sm"
              variant="outline"
              :aria-label="`Nonaktifkan anggota ${row.name}`"
              @click="router.post(resign(row.id).url)"
            >
              <UserX class="h-4 w-4" />
            </Button>
          </div>
        </template>
      </DataTable>
    </PageContainer>
  </AppLayout>
</template>
