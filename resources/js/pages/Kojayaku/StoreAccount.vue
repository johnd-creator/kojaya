<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ArrowDownLeft, ArrowUpRight, RefreshCw, Wallet } from "lucide-vue-next";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDateTime } from "@/lib/formatters";
import { storeAccount as storeAccountRoute } from "@/routes/member";

type Account = {
  balance: number;
  balance_label: string;
  credit_limit: number;
  available_spending: number;
  status: string;
  status_label: string;
};

type LedgerEntry = {
  id: number;
  entry_type_label: string;
  amount: number;
  effect: "credit" | "debit";
  balance_after: number;
  purchaser_name: string | null;
  cashier_name: string | null;
  purchase_note: string | null;
  transaction_no: string | null;
  occurred_at: string | null;
  status: string | null;
  is_reversed: boolean;
};

const props = defineProps<{
  account: Account | null;
  ledger: { data: LedgerEntry[]; links: Array<{ url: string | null; label: string; active: boolean }> };
}>();

const balanceClass = (balance: number): string =>
  balance < 0 ? "text-rose-600 dark:text-rose-400" : "text-emerald-700 dark:text-emerald-400";

const statusVariant = (status: string): "default" | "secondary" | "destructive" =>
  status === "active" ? "default" : status === "closed" ? "destructive" : "secondary";

const entryLabel = (entry: LedgerEntry): string => {
  if (entry.status === "void") return "Void";
  if (entry.status === "refund") return "Refund";
  return entry.entry_type_label;
};
</script>

<template>
  <Head title="Saldo Toko" />
  <AppLayout :breadcrumbs="[{ title: 'Kojayaku', href: '/member' }, { title: 'Saldo Toko', href: storeAccountRoute().url }]">
    <PageContainer>
      <div class="flex flex-col gap-6">
        <section class="rounded-3xl border border-sky-200/70 bg-gradient-to-br from-sky-50 via-white to-emerald-50 p-5 shadow-sm dark:border-sky-900/40 dark:from-sky-950/30 dark:via-zinc-900 dark:to-emerald-950/20 sm:p-7">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700 dark:text-sky-300">Akun belanja anggota</p>
              <h1 class="mt-2 text-3xl font-black tracking-tight text-zinc-950 dark:text-white">Saldo Toko</h1>
              <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                Pantau setiap pembelian yang dicatat kasir atas nama Anda. Halaman ini hanya dapat melihat akun Saldo Toko milik Anda.
              </p>
            </div>
            <Wallet class="size-9 shrink-0 text-sky-600 dark:text-sky-300" />
          </div>
        </section>

        <section v-if="account" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">Saldo saat ini</p>
            <p class="mt-2 text-2xl font-black" :class="balanceClass(account.balance)">{{ formatCurrency(account.balance) }}</p>
            <p class="mt-1 text-xs font-semibold text-zinc-500">{{ account.balance_label }}</p>
          </div>
          <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">Limit</p>
            <p class="mt-2 text-2xl font-black text-zinc-900 dark:text-white">{{ formatCurrency(account.credit_limit) }}</p>
          </div>
          <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">Sisa yang dapat digunakan</p>
            <p class="mt-2 text-2xl font-black text-sky-700 dark:text-sky-300">{{ formatCurrency(account.available_spending) }}</p>
          </div>
          <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">Status akun</p>
            <Badge class="mt-3" :variant="statusVariant(account.status)">{{ account.status_label }}</Badge>
          </div>
        </section>

        <section v-if="!account" class="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
          <Wallet class="mx-auto size-10 text-zinc-400" />
          <h2 class="mt-3 text-lg font-bold text-zinc-900 dark:text-white">Akun Saldo Toko belum tersedia</h2>
          <p class="mx-auto mt-1 max-w-lg text-sm text-zinc-500">Hubungi koperasi untuk mengaktifkan akun Saldo Toko Anda.</p>
        </section>

        <section v-if="account" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
          <div class="border-b border-zinc-200 p-5 dark:border-zinc-800">
            <h2 class="text-lg font-black text-zinc-900 dark:text-white">Riwayat Saldo Toko</h2>
            <p class="mt-1 text-sm text-zinc-500">Nama pembeli, kasir, catatan, nominal, dan waktu tercatat apa adanya.</p>
          </div>
          <div v-if="ledger.data.length === 0" class="p-10 text-center">
            <RefreshCw class="mx-auto size-8 text-zinc-300 dark:text-zinc-700" />
            <p class="mt-3 text-sm font-semibold text-zinc-600 dark:text-zinc-300">Belum ada riwayat transaksi.</p>
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-sm">
              <thead class="bg-zinc-50 text-left text-xs font-bold uppercase tracking-wider text-zinc-500 dark:bg-zinc-950/60">
                <tr>
                  <th class="px-5 py-3">Tanggal & waktu</th>
                  <th class="px-5 py-3">Transaksi</th>
                  <th class="px-5 py-3">Yang berbelanja</th>
                  <th class="px-5 py-3">Kasir</th>
                  <th class="px-5 py-3">Catatan</th>
                  <th class="px-5 py-3 text-right">Nominal</th>
                  <th class="px-5 py-3 text-right">Saldo setelah</th>
                  <th class="px-5 py-3">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <tr v-for="entry in ledger.data" :key="entry.id">
                  <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ formatDateTime(entry.occurred_at) }}</td>
                  <td class="px-5 py-4">
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ entryLabel(entry) }}</span>
                    <span class="mt-1 block text-xs text-zinc-500">{{ entry.transaction_no ?? "—" }}</span>
                  </td>
                  <td class="px-5 py-4 font-medium text-zinc-900 dark:text-white">{{ entry.purchaser_name ?? "—" }}</td>
                  <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ entry.cashier_name ?? "—" }}</td>
                  <td class="max-w-[220px] px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ entry.purchase_note ?? "—" }}</td>
                  <td class="whitespace-nowrap px-5 py-4 text-right font-mono font-semibold" :class="entry.effect === 'credit' ? 'text-emerald-600' : 'text-rose-600'">
                    <span class="inline-flex items-center gap-1">
                      <ArrowDownLeft v-if="entry.effect === 'credit'" class="size-3.5" />
                      <ArrowUpRight v-else class="size-3.5" />
                      {{ entry.effect === "credit" ? "+" : "-" }}{{ formatCurrency(entry.amount) }}
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-5 py-4 text-right font-mono font-semibold text-zinc-900 dark:text-white">{{ formatCurrency(entry.balance_after) }}</td>
                  <td class="px-5 py-4">
                    <Badge v-if="entry.status" variant="secondary">{{ entryLabel(entry) }}</Badge>
                    <Badge v-if="entry.is_reversed" variant="destructive">Dibatalkan</Badge>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="ledger.links.length > 0" class="flex flex-wrap items-center justify-center gap-2 border-t border-zinc-200 p-4 dark:border-zinc-800">
            <Link v-for="(link, index) in ledger.links" :key="index" :href="link.url || '#'" :class="['rounded-lg px-3 py-1.5 text-sm', link.active ? 'bg-sky-600 text-white' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800', !link.url && 'pointer-events-none opacity-40']" v-html="link.label" />
          </div>
        </section>
      </div>
    </PageContainer>
  </AppLayout>
</template>
