<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { index } from "@/routes/cooperative/ledger";

const props = defineProps<{
  entries: any;
  filters: any;
  summary: {
    total_balance: number;
    by_category: Record<string, number>;
    uncategorized: number;
  };
  contributionTypes: any[];
  categories: string[];
  entryTypes: string[];
}>();
const memberSearch = ref(props.filters.member_search ?? "");
const entryType = ref(props.filters.entry_type ?? "");
const ledgerScope = ref(props.filters.ledger_scope ?? "SAVINGS");
const category = ref(props.filters.category ?? "");
const contributionTypeId = ref(props.filters.contribution_type_id ?? "");
const startDate = ref(props.filters.start_date ?? "");
const endDate = ref(props.filters.end_date ?? "");
const applyFilters = () =>
  router.get(
    index().url,
    {
      member_search: memberSearch.value,
      entry_type: entryType.value,
      ledger_scope: ledgerScope.value,
      category: category.value,
      contribution_type_id: contributionTypeId.value,
      start_date: startDate.value,
      end_date: endDate.value,
    },
    { preserveState: true, replace: true },
  );
</script>

<template>
  <Head title="Ledger Simpanan" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Iuran & Simpanan', href: '#' },
      { title: 'Ledger Simpanan', href: index().url },
    ]"
  >
    <div class="flex w-full flex-col gap-6 p-6">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Ledger Simpanan</h1>
        <p class="mt-1 text-sm text-zinc-500">
          Monitoring mutasi simpanan anggota berdasarkan kategori.
        </p>
      </div>
      <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Total Simpanan</div>
          <div class="mt-2 text-xl font-semibold">
            {{ formatCurrency(summary.total_balance) }}
          </div>
        </div>
        <div
          v-for="key in ['POKOK', 'WAJIB', 'SUKARELA', 'KHUSUS']"
          :key="key"
          class="rounded-lg border bg-white p-4 dark:bg-zinc-900"
        >
          <div class="text-sm text-zinc-500">Simpanan {{ key }}</div>
          <div class="mt-2 text-xl font-semibold">
            {{ formatCurrency(summary.by_category?.[key] ?? 0) }}
          </div>
        </div>
        <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Belum Dikategorikan</div>
          <div class="mt-2 text-xl font-semibold">
            {{ formatCurrency(summary.uncategorized) }}
          </div>
        </div>
      </div>
      <div
        class="grid gap-3 rounded-lg border bg-white p-4 dark:bg-zinc-900 md:grid-cols-3 xl:grid-cols-7"
      >
        <Input
          v-model="memberSearch"
          placeholder="Cari anggota atau nomor anggota"
          @keyup.enter="applyFilters"
        />
        <select
          v-model="ledgerScope"
          class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
        >
          <option value="SAVINGS">Simpanan</option>
          <option value="LOAN">Pinjaman</option>
          <option value="POS">POS</option>
          <option value="">Semua scope</option>
        </select>
        <select
          v-model="category"
          class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
        >
          <option value="">Semua kategori</option>
          <option v-for="item in categories" :key="item" :value="item">
            {{ item }}
          </option>
        </select>
        <select
          v-model="contributionTypeId"
          class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
        >
          <option value="">Semua jenis</option>
          <option
            v-for="type in contributionTypes"
            :key="type.id"
            :value="type.id"
          >
            {{ type.name }}
          </option>
        </select>
        <select
          v-model="entryType"
          class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
        >
          <option value="">Semua tipe</option>
          <option v-for="item in entryTypes" :key="item" :value="item">
            {{ item }}
          </option>
        </select>
        <Input v-model="startDate" type="date" />
        <Input v-model="endDate" type="date" />
        <Button variant="outline" @click="applyFilters">Filter</Button>
      </div>
      <div class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
        <table class="w-full text-left text-sm">
          <thead
            class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"
          >
            <tr>
              <th class="px-4 py-3">Tanggal</th>
              <th>Anggota</th>
              <th>Tipe</th>
              <th>Scope</th>
              <th>Kategori</th>
              <th class="text-right">Debit</th>
              <th class="text-right">Credit</th>
              <th class="px-4 py-3">Keterangan</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="entry in entries.data" :key="entry.id">
              <td class="px-4 py-3">{{ formatDate(entry.posted_at) }}</td>
              <td>{{ entry.member?.member_no }} - {{ entry.member?.name }}</td>
              <td>{{ entry.entry_type }}</td>
              <td>{{ entry.ledger_scope || "-" }}</td>
              <td>
                {{
                  entry.contribution_type?.category ||
                  entry.category_snapshot ||
                  "-"
                }}
              </td>
              <td class="text-right">{{ formatCurrency(entry.debit) }}</td>
              <td class="text-right">{{ formatCurrency(entry.credit) }}</td>
              <td class="px-4 py-3">{{ entry.description || "-" }}</td>
            </tr>
            <tr v-if="entries.data.length === 0">
              <td colspan="8" class="px-4 py-10 text-center text-zinc-500">
                Belum ada ledger.
              </td>
            </tr>
          </tbody>
        </table>
        <div
          v-if="entries.links?.length > 3"
          class="flex flex-col gap-3 border-t px-4 py-3 text-sm text-zinc-500 md:flex-row md:items-center md:justify-between"
        >
          <div>
            Menampilkan {{ entries.from }}-{{ entries.to }} dari
            {{ entries.total }} mutasi
          </div>
          <div class="flex flex-wrap gap-1">
            <template v-for="(link, index) in entries.links" :key="index">
              <Button
                v-if="link.url"
                as-child
                size="sm"
                :variant="link.active ? 'default' : 'outline'"
              >
                <Link :href="link.url" preserve-scroll preserve-state>
                  <span v-html="link.label" />
                </Link>
              </Button>
              <span
                v-else
                class="rounded-md border px-3 py-1.5 text-zinc-400"
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
