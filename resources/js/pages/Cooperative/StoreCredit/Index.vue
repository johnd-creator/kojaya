<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { Search, Wallet, ArrowRight, Plus, ChevronDown } from "lucide-vue-next";
import { ref } from "vue";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import { index, show, store, report } from "@/routes/cooperative/store-credit";
import type { PaginatedResource } from "@/types";
type Account = {
  id: number;
  balance: number;
  credit_limit: number;
  available_credit: number;
  status: string;
  status_label: string;
  is_negative: boolean;
  member: {
    id: number;
    full_name: string | null;
    name: string | null;
    member_no: string | null;
  } | null;
};
type EligibleMember = {
  id: number;
  organization_id: string;
  organization_code: string | null;
  organization_name: string | null;
  member_no: string | null;
  name: string | null;
};
const props = defineProps<{
  accounts: PaginatedResource<Account>;
  filters: { q?: string; filter?: string };
  eligibleMembers: EligibleMember[];
  canManage: boolean;
}>();
const balanceTone = (account: Account): string =>
  account.balance > 0
    ? "text-emerald-700"
    : account.balance < 0
      ? "text-rose-600"
      : "text-zinc-500";
const filters = ref({
  q: props.filters.q ?? "",
  filter: props.filters.filter ?? "",
});
function applyFilter(): void {
  router.get(index.url(), filters.value, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}
const showOpenDialog = ref(false);
const openForm = useForm({
  cooperative_member_id: "",
  credit_limit: 0,
  opening_balance: 0,
  reason: "",
});
function submitOpenAccount(): void {
  openForm.post(store().url, {
    preserveScroll: true,
    onSuccess: () => {
      showOpenDialog.value = false;
      openForm.reset();
    },
  });
}
</script>
<template>
  <Head title="Saldo Toko Anggota" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Saldo Toko Anggota', href: index.url() },
    ]"
  >
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Saldo Toko Anggota"
        description="Kelola saldo, limit kredit, dan status akun toko anggota. Saldo negatif menunjukkan piutang anggota."
      >
        <Button as-child variant="outline" size="sm"
          ><Link :href="report().url">Laporan Saldo Toko</Link></Button
        >
        <Dialog v-if="canManage" v-model:open="showOpenDialog">
          <DialogTrigger as-child>
            <Button size="sm"><Plus class="mr-2 size-4" /> Buka Akun</Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-md">
            <DialogHeader>
              <DialogTitle>Buka Akun Saldo Toko</DialogTitle>
              <DialogDescription>
                Pilih anggota untuk mengaktifkan akun Saldo Toko. Setelah
                dibuka, anggota dapat menerima setoran dan membayar transaksi
                menggunakan saldo.
              </DialogDescription>
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="submitOpenAccount">
              <div class="space-y-2">
                <Label>Anggota</Label>
                <select
                  aria-label="Anggota"
                  v-model="openForm.cooperative_member_id"
                  required
                  class="h-9 w-full rounded-md border bg-white px-2 text-sm dark:bg-zinc-950"
                >
                  <option value="">Pilih anggota</option>
                  <option
                    v-for="member in eligibleMembers"
                    :key="member.id"
                    :value="member.id"
                  >
                    {{
                      member.organization_code ??
                      member.organization_name ??
                      "Koperasi"
                    }}
                    —
                    {{ member.member_no ?? "-" }} —
                    {{ member.name ?? "Anggota" }}
                  </option>
                </select>
                <p
                  v-if="eligibleMembers.length === 0"
                  class="text-xs text-zinc-500"
                >
                  Semua anggota aktif sudah memiliki akun Saldo Toko.
                </p>
                <p
                  v-if="openForm.errors.cooperative_member_id"
                  class="text-xs text-red-500"
                >
                  {{ openForm.errors.cooperative_member_id }}
                </p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div class="space-y-2">
                  <Label>Limit Kredit (opsional)</Label>
                  <Input
                    aria-label="Limit Kredit (opsional)"
                    v-model.number="openForm.credit_limit"
                    type="number"
                    min="0"
                  />
                </div>
                <div class="space-y-2">
                  <Label>Saldo Awal (opsional)</Label>
                  <Input
                    aria-label="Saldo Awal (opsional)"
                    v-model.number="openForm.opening_balance"
                    type="number"
                    min="0"
                  />
                </div>
              </div>
              <div class="space-y-2">
                <Label>Keterangan (opsional)</Label>
                <Textarea
                  aria-label="Keterangan (opsional)"
                  v-model="openForm.reason"
                />
              </div>
              <DialogFooter>
                <Button
                  type="button"
                  variant="outline"
                  @click="showOpenDialog = false"
                >
                  Batal
                </Button>
                <Button type="submit" :disabled="openForm.processing"
                  >Buka Akun</Button
                >
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </WorkspaceHeader>
      <WorkspaceSummary
        :items="[
          { label: 'Akun pada Filter', value: accounts.meta.total },
          { label: 'Ditampilkan', value: accounts.data.length },
          {
            label: 'Saldo Negatif di Halaman Ini',
            value: accounts.data.filter((account) => account.balance < 0)
              .length,
            tone: 'rose',
          },
          {
            label: 'Ditangguhkan di Halaman Ini',
            value: accounts.data.filter(
              (account) => account.status === 'suspended',
            ).length,
          },
        ]"
      />
      <Card class="gap-0 rounded-xl py-0 overflow-hidden">
        <CardContent class="p-3">
          <div class="flex flex-wrap items-center gap-2">
            <div class="relative min-w-0 basis-full sm:basis-auto sm:flex-1">
              <Search class="absolute left-2 top-2.5 size-4 text-zinc-400" />
              <Input
                v-model="filters.q"
                placeholder="Cari nama / nomor anggota"
                aria-label="Cari akun anggota"
                class="pl-8"
                @keyup.enter="applyFilter"
              />
            </div>
            <div class="relative">
              <select
                aria-label="Filter saldo akun"
                v-model="filters.filter"
                class="h-9 w-40 appearance-none rounded-md border border-zinc-200 bg-white py-1 pl-3 pr-8 text-sm dark:border-zinc-800 dark:bg-zinc-950"
                @change="applyFilter"
              >
                <option value="">Semua</option>
                <option value="positive">Saldo Positif</option>
                <option value="negative">Saldo Negatif</option>
                <option value="zero">Saldo Nol</option>
                <option value="suspended">Ditangguhkan</option>
              </select>
              <ChevronDown
                aria-hidden="true"
                class="pointer-events-none absolute right-2 top-1/2 size-4 -translate-y-1/2 text-zinc-500 dark:text-zinc-400"
              />
            </div>
            <Button variant="outline" size="sm" @click="applyFilter"
              >Terapkan</Button
            >
          </div>
        </CardContent>
      </Card>
      <Card class="gap-0 rounded-xl py-0 overflow-hidden">
        <div class="flex items-center justify-between border-b px-4 py-3">
          <h2 class="text-sm font-semibold">Daftar Akun</h2>
          <span class="text-xs text-zinc-500"
            >{{ accounts.meta.total }} akun pada filter</span
          >
        </div>
        <CardContent
          class="overflow-x-auto p-0"
          role="region"
          aria-label="Daftar akun saldo toko, geser untuk melihat kolom lainnya"
          tabindex="0"
        >
          <table class="w-full min-w-[760px] text-sm">
            <thead class="border-b text-left text-xs uppercase text-zinc-500">
              <tr>
                <th class="p-3">Anggota</th>
                <th class="p-3 text-right">Saldo</th>
                <th class="p-3 text-right">Limit Kredit</th>
                <th class="p-3">Status</th>
                <th class="p-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="account in accounts.data"
                :key="account.id"
                class="border-b last:border-0"
              >
                <td class="p-3 font-medium">
                  <span>{{
                    account.member?.full_name ??
                    account.member?.name ??
                    "Anggota tidak tersedia"
                  }}</span>
                  <div class="mt-0.5 text-xs font-normal text-zinc-500">
                    {{ account.member?.member_no ?? "—" }}
                  </div>
                </td>
                <td
                  class="p-3 whitespace-nowrap text-right tabular-nums font-semibold"
                  :class="balanceTone(account)"
                >
                  {{ formatCurrency(account.balance) }}
                </td>
                <td
                  class="p-3 whitespace-nowrap text-right tabular-nums text-zinc-600"
                >
                  {{ formatCurrency(account.credit_limit) }}
                </td>
                <td class="p-3">
                  <Badge
                    variant="outline"
                    :class="
                      account.status === 'active'
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300'
                        : 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300'
                    "
                  >
                    {{ account.status_label }}
                  </Badge>
                </td>
                <td class="p-3 text-right">
                  <Link
                    :href="show({ account: account.id }).url"
                    class="inline-flex items-center text-emerald-700 dark:text-emerald-400 hover:underline"
                  >
                    Detail <ArrowRight class="ml-1 size-4" />
                  </Link>
                </td>
              </tr>
              <tr v-if="accounts.data.length === 0">
                <td colspan="5" class="p-8 text-center text-zinc-500">
                  <Wallet class="mx-auto mb-2 size-6 text-zinc-400" />
                  <p class="text-sm font-medium">
                    Belum ada akun saldo toko pada filter ini.
                  </p>
                  <p class="mt-1 text-xs">
                    {{
                      canManage
                        ? "Buka akun untuk anggota aktif atau sesuaikan filter pencarian."
                        : "Sesuaikan filter untuk melihat akun anggota."
                    }}
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
      <div
        v-if="accounts.meta.last_page > 1"
        class="flex flex-wrap items-center justify-center gap-2 py-2"
      >
        <Link
          v-for="(link, i) in accounts.meta.links"
          :key="i"
          :as="link.url ? 'a' : 'span'"
          :aria-disabled="!link.url"
          :href="link.url || '#'"
          :class="[
            'rounded px-3 py-1 text-sm',
            link.active ? 'bg-emerald-700 text-white' : 'hover:bg-zinc-100',
            !link.url && 'pointer-events-none text-zinc-500',
          ]"
          v-html="link.label"
        />
      </div>
    </PageContainer>
  </AppLayout>
</template>
