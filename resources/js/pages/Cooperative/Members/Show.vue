<script setup lang="ts">
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import { ArrowLeft } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";
import { index } from "@/routes/cooperative/members";

const props = defineProps<{
  member: any;
  savingsSummary: {
    total_balance: number;
    by_category: Record<string, number>;
  };
  recentSavingsEntries: any[];
}>();

const savingCategories = [
  { key: "POKOK", label: "Simpanan Pokok" },
  { key: "WAJIB", label: "Simpanan Wajib" },
  { key: "SUKARELA", label: "Simpanan Sukarela" },
  { key: "KHUSUS", label: "Simpanan Khusus" },
];

const memberName = computed(
  () => props.member.nama_anggota_clean || props.member.name || "-",
);
const memberNumber = computed(
  () => props.member.no_anggota_display || props.member.member_no || "-",
);
const memberStatusLabel = computed(
  () => props.member.status_badge?.label || props.member.status || "-",
);

const statusClassMap: Record<string, string> = {
  ACTIVE:
    "border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300",
  INACTIVE:
    "border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300",
  RESIGNED:
    "border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300",
};

const jenisKelaminMap: Record<string, string> = {
  L: "Laki-laki",
  P: "Perempuan",
};

const kategoriMap: Record<string, string> = {
  IP: "Indonesia Power",
  CDB: "Cogindo DayaBersama",
  KOP: "Koperasi",
};

const autodebetMap: Record<string, string> = {
  MANUAL: "Manual",
  BNI: "BNI",
  BRI: "BRI",
};

const savingEntryTypeMap: Record<string, string> = {
  OPENING_BALANCE: "Saldo Awal",
  SAVING_PAYMENT: "Setoran Simpanan",
  SAVING_WITHDRAWAL: "Penarikan Simpanan",
  POS_MEMBER_CREDIT: "Belanja Kredit POS",
  LOAN_DISBURSEMENT: "Pencairan Pinjaman",
  LOAN_PAYMENT: "Pembayaran Pinjaman",
};

const formatMemberDate = (value: string | null | undefined): string =>
  formatDate(value);
const formatJenisKelamin = (value: string | null | undefined): string =>
  (value && jenisKelaminMap[value]) || value || "-";
const formatKategori = (value: string | null | undefined): string =>
  (value && kategoriMap[value]) || value || "-";
const formatAutodebet = (value: string | null | undefined): string =>
  (value && autodebetMap[value]) || value || "Manual";
const formatSavingEntryType = (value: string | null | undefined): string =>
  (value && savingEntryTypeMap[value]) || value || "-";
const statusBadgeClass = (value: string | null | undefined): string =>
  (value && statusClassMap[value]) ||
  "border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300";
</script>

<template>
  <Head title="Detail Anggota" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Anggota', href: index().url },
      { title: member.member_no, href: '#' },
    ]"
  >
    <div class="flex w-full flex-col gap-6 p-6">
      <Button as-child variant="outline" size="sm" class="w-fit">
        <Link :href="index().url">
          <ArrowLeft class="mr-2 h-4 w-4" />
          Kembali ke daftar anggota
        </Link>
      </Button>

      <div class="rounded-2xl border border-zinc-200/80 bg-white/95 p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/90">
        <div
          class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
        >
          <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-3">
              <h1 class="text-3xl font-bold tracking-tight">
                {{ memberName }}
              </h1>
              <span
                class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold"
                :class="statusBadgeClass(member.status)"
              >
                {{ memberStatusLabel }}
              </span>
            </div>

            <div class="flex flex-wrap gap-2 text-sm text-zinc-500">
              <span class="rounded-full bg-zinc-100 px-3 py-1 dark:bg-zinc-800">
                No. Anggota {{ memberNumber }}
              </span>
              <span class="rounded-full bg-zinc-100 px-3 py-1 dark:bg-zinc-800">
                {{ member.jenis_anggota_label || "Jenis anggota belum diisi" }}
              </span>
              <span class="rounded-full bg-zinc-100 px-3 py-1 dark:bg-zinc-800">
                {{ formatKategori(member.kategori) }}
              </span>
            </div>

            <p class="max-w-2xl text-sm text-zinc-500">
              Ringkasan profil anggota, informasi keanggotaan, dan aktivitas
              simpanan terbaru.
            </p>
          </div>

          <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[320px]">
            <div class="rounded-xl border border-zinc-200/70 bg-zinc-50/90 p-4 dark:border-zinc-800/70 dark:bg-zinc-950">
              <div class="text-xs uppercase tracking-wide text-zinc-500">
                Tanggal Aktif
              </div>
              <div class="mt-1 text-base font-semibold">
                {{ formatMemberDate(member.tanggal_aktif || member.joined_at) }}
              </div>
            </div>

            <div class="rounded-xl border border-zinc-200/70 bg-zinc-50/90 p-4 dark:border-zinc-800/70 dark:bg-zinc-950">
              <div class="text-xs uppercase tracking-wide text-zinc-500">
                Tanggal Bergabung
              </div>
              <div class="mt-1 text-base font-semibold">
                {{ formatMemberDate(member.joined_at) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div>
        <h2 class="text-lg font-semibold">Ringkasan Simpanan</h2>
        <div class="mt-3 grid gap-4 md:grid-cols-3 xl:grid-cols-5">
          <div class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500">Total Simpanan</div>
            <div class="mt-2 text-xl font-semibold">
              {{ formatCurrency(savingsSummary.total_balance) }}
            </div>
          </div>
          <div
            v-for="category in savingCategories"
            :key="category.key"
            class="rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-4 dark:bg-zinc-900"
          >
            <div class="text-sm text-zinc-500">{{ category.label }}</div>
            <div class="mt-2 text-xl font-semibold">
              {{
                formatCurrency(savingsSummary.by_category?.[category.key] ?? 0)
              }}
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-2xl border border-zinc-200/80 bg-white/95 p-5 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/90">
          <h2 class="text-lg font-semibold">Informasi Keanggotaan</h2>
          <dl class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Nomor Anggota
              </dt>
              <dd class="mt-1 font-medium">{{ memberNumber }}</dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Status
              </dt>
              <dd class="mt-1 font-medium">{{ memberStatusLabel }}</dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Tanggal Aktif
              </dt>
              <dd class="mt-1 font-medium">
                {{ formatMemberDate(member.tanggal_aktif || member.joined_at) }}
              </dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Tanggal Bergabung
              </dt>
              <dd class="mt-1 font-medium">
                {{ formatMemberDate(member.joined_at) }}
              </dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Jenis Anggota
              </dt>
              <dd class="mt-1 font-medium">
                {{ member.jenis_anggota_label || "-" }}
              </dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Kategori
              </dt>
              <dd class="mt-1 font-medium">
                {{ formatKategori(member.kategori) }}
              </dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Jenis Kelamin
              </dt>
              <dd class="mt-1 font-medium">
                {{ formatJenisKelamin(member.jenis_kelamin) }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="rounded-2xl border border-zinc-200/80 bg-white/95 p-5 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/90">
          <h2 class="text-lg font-semibold">Kontak & Administrasi</h2>
          <dl class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Email
              </dt>
              <dd class="mt-1 break-all font-medium">
                {{ member.email || "-" }}
              </dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Nomor Telepon
              </dt>
              <dd class="mt-1 font-medium">
                {{ member.no_telp || member.phone || "-" }}
              </dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                NIK
              </dt>
              <dd class="mt-1 font-medium">
                {{ member.identity_number || "-" }}
              </dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                NPWP
              </dt>
              <dd class="mt-1 font-medium">{{ member.npwp || "-" }}</dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Autodebet
              </dt>
              <dd class="mt-1 font-medium">
                {{ formatAutodebet(member.autodebet) }}
              </dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Nomor Rekening
              </dt>
              <dd class="mt-1 font-medium">
                {{ member.no_rekening || "-" }}
              </dd>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
              <dt class="text-xs uppercase tracking-wide text-zinc-500">
                Alamat
              </dt>
              <dd class="mt-1 whitespace-pre-line font-medium">
                {{ member.address || "-" }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <div class="rounded-2xl border border-zinc-200/80 bg-white/95 p-5 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/90">
        <h2 class="mb-3 text-lg font-semibold">Mutasi Simpanan Terbaru</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-b text-xs uppercase text-zinc-500">
              <tr>
                <th class="py-2">Tanggal</th>
                <th>Jenis Mutasi</th>
                <th>Kategori</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Kredit</th>
                <th class="pl-4">Keterangan</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="entry in recentSavingsEntries" :key="entry.id">
                <td class="py-3">{{ formatDate(entry.posted_at) }}</td>
                <td class="font-medium">
                  {{ formatSavingEntryType(entry.entry_type) }}
                </td>
                <td>
                  {{
                    entry.contribution_type?.category ||
                    entry.category_snapshot ||
                    "-"
                  }}
                </td>
                <td class="text-right">{{ formatCurrency(entry.debit) }}</td>
                <td class="text-right">{{ formatCurrency(entry.credit) }}</td>
                <td class="pl-4 text-zinc-600 dark:text-zinc-400">
                  {{ entry.description || "-" }}
                </td>
              </tr>
              <tr v-if="recentSavingsEntries.length === 0">
                <td colspan="6" class="py-8 text-center text-zinc-500">
                  Belum ada mutasi simpanan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="rounded-2xl border border-zinc-200/80 bg-white/95 p-5 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/90">
        <div
          class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
          <h2 class="text-lg font-semibold">Tagihan Simpanan</h2>
          <p class="text-sm text-zinc-500">
            Ringkasan tagihan yang sudah dibuat untuk anggota ini.
          </p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-b text-xs uppercase text-zinc-500">
              <tr>
                <th class="py-2">Periode</th>
                <th>Jenis Simpanan</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
                <th class="text-right">Nominal</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="invoice in member.invoices" :key="invoice.id">
                <td class="py-3">{{ invoice.period }}</td>
                <td class="font-medium">
                  {{ invoice.contribution_type?.name || "-" }}
                </td>
                <td>{{ formatDate(invoice.due_date) }}</td>
                <td>{{ invoice.status }}</td>
                <td class="text-right">{{ formatCurrency(invoice.amount) }}</td>
              </tr>
              <tr v-if="member.invoices.length === 0">
                <td colspan="5" class="py-8 text-center text-zinc-500">
                  Belum ada tagihan simpanan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
