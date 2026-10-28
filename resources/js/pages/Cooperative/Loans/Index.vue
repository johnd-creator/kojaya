<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { Calculator, CreditCard, Plus } from "lucide-vue-next";
import { computed, ref } from "vue";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import FilterBar from "@/components/FilterBar.vue";
import PageContainer from "@/components/PageContainer.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { useTableFilters } from "@/composables/useTableFilters";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { calculator, create, index, show } from "@/routes/cooperative/loans";

const props = defineProps<{
  loans: any;
  members: Array<{ id: number; member_no: string; name: string }>;
  loanTypes: Array<{ id: number; name: string }>;
  filters: { status?: string; cooperative_member_id?: string | number };
  stats: {
    applied: number;
    manager_approved: number;
    active: number;
    paid_off: number;
  };
}>();

const filters = ref({
  status: props.filters.status ?? "",
  cooperative_member_id: props.filters.cooperative_member_id ?? "",
});

const { resetFilters } = useTableFilters(filters, {
  route: index().url,
  debounceMs: 300,
  only: ["loans", "filters", "stats"],
});

const statusOptions = [
  { label: "Semua status", value: "" },
  { label: "Menunggu Manajer", value: "APPLIED" },
  { label: "Menunggu Pengurus", value: "MANAGER_APPROVED" },
  { label: "Disetujui", value: "APPROVED" },
  { label: "Aktif", value: "ACTIVE" },
  { label: "Lunas", value: "PAID_OFF" },
  { label: "Ditolak", value: "REJECTED" },
];

const memberOptions = computed(() => [
  { label: "Semua anggota", value: "" },
  ...props.members.map((member) => ({
    label: `${member.member_no} - ${member.name}`,
    value: member.id,
  })),
]);

const columns = [
  {
    header: "Pinjaman",
    key: "member.name",
    slot: "loan",
    class: "max-w-none! min-w-[180px] whitespace-normal",
  },
  {
    header: "Tipe",
    key: "loan_type.name",
    slot: "type",
    class: "max-w-none! min-w-[150px] whitespace-normal",
  },
  {
    header: "Status",
    key: "status",
    slot: "status",
    class: "max-w-none! whitespace-nowrap",
  },
  {
    header: "Pokok",
    key: "principal_amount",
    slot: "principal",
    align: "right" as const,
    class: "max-w-none! whitespace-nowrap",
  },
  {
    header: "Sisa",
    key: "outstanding_amount",
    slot: "outstanding",
    align: "right" as const,
    class: "max-w-none! whitespace-nowrap",
  },
  {
    header: "Jatuh Tempo",
    key: "first_due_date",
    slot: "first_due_date",
    class: "max-w-none! whitespace-nowrap",
  },
];

const breadcrumbs = [
  { title: "Koperasi", href: "#" },
  { title: "Pinjaman", href: index().url },
];
</script>

<template>
  <Head title="Pinjaman Koperasi" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Pinjaman Koperasi"
        description="Kelola pengajuan, persetujuan, pencairan, dan angsuran pinjaman anggota."
      >
        <Button as-child variant="outline" size="sm"
          ><Link :href="calculator().url"
            ><Calculator class="size-4" /> Kalkulator</Link
          ></Button
        >
        <Button as-child size="sm"
          ><Link :href="create().url"
            ><Plus class="size-4" /> Pengajuan Baru</Link
          ></Button
        >
      </WorkspaceHeader>
      <WorkspaceSummary
        :items="[
          { label: 'Menunggu Manajer', value: stats.applied },
          { label: 'Menunggu Pengurus', value: stats.manager_approved },
          { label: 'Pinjaman Aktif', value: stats.active, tone: 'emerald' },
          { label: 'Lunas', value: stats.paid_off },
        ]"
      />

      <FilterBar
        class="w-full rounded-xl"
        :show-search="false"
        @reset="resetFilters"
      >
        <SelectFilter
          v-model="filters.status"
          :options="statusOptions"
          placeholder="Semua status"
          class="w-full sm:max-w-[180px]"
        />
        <SelectFilter
          v-model="filters.cooperative_member_id"
          :options="memberOptions"
          placeholder="Semua anggota"
          class="w-full sm:max-w-[320px]"
        />
      </FilterBar>

      <Card
        data-testid="loans-list-card"
        class="gap-0 rounded-xl py-0 overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <div class="flex items-center justify-between border-b px-4 py-3">
          <h2 class="text-sm font-semibold">Daftar Pinjaman</h2>
          <span class="text-xs text-zinc-500"
            >{{ loans.total ?? 0 }} pinjaman</span
          >
        </div>
        <CardContent class="px-0 pb-0">
          <DataTable
            :columns="columns"
            :data="loans"
            :searchable="false"
            compact
            table-class="min-w-[850px] table-auto"
            empty-message="Belum ada pinjaman koperasi."
            :empty-icon="CreditCard"
          >
            <template #loan="{ row }">
              <Link
                class="font-semibold text-zinc-950 hover:text-indigo-600 dark:text-white"
                :href="show(row.id).url"
                >{{ row.member?.name }}</Link
              >
              <div class="text-xs text-zinc-500">
                {{ row.member?.member_no }} · {{ formatDate(row.applied_at) }}
              </div>
            </template>
            <template #type="{ row }">
              <div>{{ row.loan_type?.name }}</div>
              <div class="text-xs text-zinc-500">
                {{ row.term_months }} bulan
              </div>
            </template>
            <template #status="{ value }">
              <StatusBadge :status="value" />
            </template>
            <template #principal="{ value }">
              <span class="font-semibold tabular-nums">{{
                formatCurrency(value)
              }}</span>
            </template>
            <template #outstanding="{ value }">
              <span
                class="font-semibold tabular-nums text-amber-700 dark:text-amber-300"
                >{{ formatCurrency(value) }}</span
              >
            </template>
            <template #first_due_date="{ value }">
              <span class="tabular-nums">{{ formatDate(value) }}</span>
            </template>
          </DataTable>
        </CardContent>
      </Card>
    </PageContainer>
  </AppLayout>
</template>
