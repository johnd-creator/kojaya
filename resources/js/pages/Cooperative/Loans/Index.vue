<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { Calculator, CreditCard, HandCoins, Plus } from "lucide-vue-next";
import { computed, ref } from "vue";
import FilterBar from "@/components/FilterBar.vue";
import PageContainer from "@/components/PageContainer.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatsCard from "@/components/StatsCard.vue";
import { Button } from "@/components/ui/button";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import { useTableFilters } from "@/composables/useTableFilters";
import { formatCurrency, formatDate } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import { calculator, create, index, show } from "@/routes/cooperative/loans";

const props = defineProps<{
  loans: any;
  members: Array<{ id: number; member_no: string; name: string }>;
  loanTypes: Array<{ id: number; name: string }>;
  filters: { status?: string; cooperative_member_id?: string | number };
  stats: { applied: number; active: number; paid_off: number };
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
  { label: "APPLIED", value: "APPLIED" },
  { label: "APPROVED", value: "APPROVED" },
  { label: "ACTIVE", value: "ACTIVE" },
  { label: "PAID_OFF", value: "PAID_OFF" },
  { label: "REJECTED", value: "REJECTED" },
];

const memberOptions = computed(() => [
  { label: "Semua anggota", value: "" },
  ...props.members.map((member) => ({
    label: `${member.member_no} - ${member.name}`,
    value: member.id,
  })),
]);

const columns = [
  { header: "Pinjaman", key: "member.name", slot: "loan" },
  { header: "Tipe", key: "loan_type.name", slot: "type" },
  { header: "Status", key: "status", slot: "status" },
  { header: "Pokok", key: "principal_amount", slot: "principal", align: "right" as const },
  { header: "Sisa", key: "outstanding_amount", slot: "outstanding", align: "right" as const },
  { header: "Jatuh Tempo Awal", key: "first_due_date", slot: "first_due_date" },
];

const breadcrumbs = [
  { title: "Koperasi", href: "#" },
  { title: "Pinjaman", href: index().url },
];
</script>

<template>
  <Head title="Pinjaman Koperasi" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <PageContainer>
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Pinjaman Koperasi</h1>
          <p class="mt-1 text-sm text-zinc-500">
            Kelola pengajuan, approval, pencairan, dan angsuran pinjaman anggota.
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link :href="calculator().url">
            <Button type="button" variant="outline">
              <Calculator class="mr-2 h-4 w-4" />
              Kalkulator
            </Button>
          </Link>
          <Link :href="create().url">
            <Button type="button">
              <Plus class="mr-2 h-4 w-4" />
              Pengajuan Baru
            </Button>
          </Link>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-3">
        <StatsCard label="Menunggu Approval" :value="stats.applied" :icon="HandCoins" />
        <StatsCard label="Pinjaman Aktif" :value="stats.active" :icon="CreditCard" />
        <StatsCard label="Lunas" :value="stats.paid_off" :icon="Calculator" />
      </div>

      <FilterBar :show-search="false" @reset="resetFilters">
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

      <DataTable
        :columns="columns"
        :data="loans"
        :searchable="false"
        empty-message="Belum ada pinjaman koperasi."
        :empty-icon="CreditCard"
      >
        <template #loan="{ row }">
          <Link class="font-medium hover:text-indigo-600" :href="show(row.id).url">
            {{ row.member?.name }}
          </Link>
          <div class="text-xs text-zinc-500">
            {{ row.member?.member_no }} • {{ formatDate(row.applied_at) }}
          </div>
        </template>

        <template #type="{ row }">
          <div>
            <div>{{ row.loan_type?.name }}</div>
            <div class="text-xs text-zinc-500">{{ row.term_months }} bulan</div>
          </div>
        </template>

        <template #status="{ value }">
          <StatusBadge :status="value" />
        </template>

        <template #principal="{ value }">
          <span class="font-medium">{{ formatCurrency(value) }}</span>
        </template>

        <template #outstanding="{ value }">
          <span class="font-medium">{{ formatCurrency(value) }}</span>
        </template>

        <template #first_due_date="{ value }">
          {{ formatDate(value) }}
        </template>
      </DataTable>
    </PageContainer>
  </AppLayout>
</template>
