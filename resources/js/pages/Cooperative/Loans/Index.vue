<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import {
  Calculator,
  CreditCard,
  HandCoins,
  Plus,
  ShieldCheck,
  Sparkles,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import SectionHeader from "@/components/dashboard/SectionHeader.vue";
import FilterBar from "@/components/FilterBar.vue";
import PageContainer from "@/components/PageContainer.vue";
import SelectFilter from "@/components/SelectFilter.vue";
import StatsCard from "@/components/StatsCard.vue";
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
  stats: { applied: number; manager_approved: number; active: number; paid_off: number };
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
  { label: "MANAGER_APPROVED", value: "MANAGER_APPROVED" },
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
  {
    header: "Pokok",
    key: "principal_amount",
    slot: "principal",
    align: "right" as const,
  },
  {
    header: "Sisa",
    key: "outstanding_amount",
    slot: "outstanding",
    align: "right" as const,
  },
  { header: "Jatuh Tempo", key: "first_due_date", slot: "first_due_date" },
];

const breadcrumbs = [
  { title: "Koperasi", href: "#" },
  { title: "Pinjaman", href: index().url },
];
</script>

<template>
  <Head title="Pinjaman Koperasi" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <PageContainer class="max-w-none">
      <section
        class="relative overflow-hidden rounded-2xl border border-sky-200/60 bg-gradient-to-br from-white via-sky-50/60 to-emerald-50/40 p-6 shadow-sm shadow-sky-950/5 sm:p-7 dark:border-sky-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900"
      >
        <div
          class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10"
          aria-hidden="true"
        />
        <div
          class="pointer-events-none absolute -bottom-24 -left-12 size-64 rounded-full bg-emerald-300/15 blur-3xl dark:bg-emerald-500/10"
          aria-hidden="true"
        />
        <div
          class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
        >
          <div class="space-y-3">
            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-900/40 dark:text-sky-200 dark:ring-sky-800/60"
            >
              <Sparkles class="size-3.5" />
              Produk Pinjaman
            </span>
            <h1
              class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
            >
              Pinjaman Koperasi
            </h1>
            <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
              Kelola pengajuan, approval, pencairan, dan angsuran pinjaman
              anggota.
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <Link :href="calculator().url">
              <Button type="button" variant="outline">
                <Calculator class="mr-2 h-4 w-4" /> Kalkulator
              </Button>
            </Link>
            <Link :href="create().url">
              <Button type="button">
                <Plus class="mr-2 h-4 w-4" /> Pengajuan Baru
              </Button>
            </Link>
          </div>
        </div>
      </section>

      <div class="grid gap-4 md:grid-cols-4">
        <StatsCard
          label="Menunggu Manajer"
          :value="stats.applied"
          :icon="HandCoins"
        />
        <StatsCard
          label="Menunggu Pengurus"
          :value="stats.manager_approved"
          :icon="ShieldCheck"
        />
        <StatsCard
          label="Pinjaman Aktif"
          :value="stats.active"
          :icon="CreditCard"
        />
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

      <Card
        class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
      >
        <SectionHeader
          title="Daftar Pinjaman"
          :description="`${loans.total ?? 0} pinjaman tercatat`"
          :icon="CreditCard"
          tone="sky"
        />
        <CardContent class="px-0 pb-0">
          <DataTable
            :columns="columns"
            :data="loans"
            :searchable="false"
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
