<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { RefreshCw } from "lucide-vue-next";
import { computed, reactive } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { formatCurrency } from "@/lib/formatters";
import AppLayout from "@/layouts/AppLayout.vue";
import { index } from "@/routes/cooperative/pos/shu";

const props = defineProps<{ preview: any; closedPeriod: any; filters: any }>();

const filter = reactive({
  year: Number(props.filters?.year ?? new Date().getFullYear()),
});

const isClosed = computed(() => props.closedPeriod?.status === "CLOSED");
const allocations = computed(() =>
  (
    (isClosed.value
      ? props.closedPeriod?.allocations
      : props.preview?.allocations) ?? []
  ).filter((allocation: any) => Number(allocation.pos_points) > 0),
);
const totals = computed(() =>
  isClosed.value ? props.closedPeriod : props.preview,
);

const refreshPreview = () => {
  router.get(
    index().url,
    { year: filter.year },
    { preserveState: true, preserveScroll: true },
  );
};
</script>

<template>
  <Head title="SHU POS Tahunan" />
  <AppLayout
    :breadcrumbs="[
      { title: 'POS Toko', href: '#' },
      { title: 'SHU POS Tahunan', href: index().url },
    ]"
  >
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6">
      <div
        class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
      >
        <div>
          <h1 class="text-3xl font-bold tracking-tight">SHU POS Tahunan</h1>
          <p class="mt-1 text-sm text-zinc-500">
            Tambahan SHU dari profit toko koperasi, dibagi berdasarkan poin
            profit transaksi anggota.
          </p>
        </div>
        <div class="flex gap-3 rounded-lg border bg-white p-4 dark:bg-zinc-900">
          <Input
            v-model.number="filter.year"
            type="number"
            min="2020"
            class="w-32"
          />
          <Button type="button" variant="outline" @click="refreshPreview">
            <RefreshCw class="mr-2 h-4 w-4" />
            Preview
          </Button>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Profit POS Tahunan</div>
          <div class="mt-2 text-2xl font-semibold">
            {{ formatCurrency(totals.pos_profit_pool) }}
          </div>
        </div>
        <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Total Poin POS</div>
          <div class="mt-2 text-2xl font-semibold">
            {{ totals.total_pos_points }}
          </div>
        </div>
        <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Anggota Berpoin</div>
          <div class="mt-2 text-2xl font-semibold">
            {{ allocations.length }}
          </div>
        </div>
        <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900">
          <div class="text-sm text-zinc-500">Status Tahun</div>
          <div class="mt-2 text-2xl font-semibold">
            {{ isClosed ? "CLOSED" : "PREVIEW" }}
          </div>
        </div>
      </div>

      <div class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
        <table class="w-full text-sm">
          <thead
            class="border-b bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-zinc-950"
          >
            <tr>
              <th class="px-4 py-3">Anggota</th>
              <th class="px-4 py-3 text-right">Poin POS</th>
              <th class="px-4 py-3 text-right">Alokasi SHU POS</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="allocation in allocations" :key="allocation.member.id">
              <td class="px-4 py-3">
                <div class="font-medium">{{ allocation.member.member_no }}</div>
                <div class="text-xs text-zinc-500">
                  {{ allocation.member.name }}
                </div>
              </td>
              <td class="px-4 py-3 text-right">{{ allocation.pos_points }}</td>
              <td class="px-4 py-3 text-right font-medium">
                {{ formatCurrency(allocation.pos_shu_amount) }}
              </td>
            </tr>
            <tr v-if="allocations.length === 0">
              <td colspan="3" class="px-4 py-10 text-center text-zinc-500">
                Belum ada transaksi anggota yang menghasilkan poin POS pada
                tahun ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
