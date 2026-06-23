<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
  ArrowLeft,
  Banknote,
  Building2,
  Hash,
  IdCard,
  KeyRound,
  Mail,
  Pencil,
  Phone,
  Save,
  UserCog,
  Users,
} from "lucide-vue-next";
import { computed } from "vue";
import StatusPill from "@/components/dashboard/StatusPill.vue";
import InputError from "@/components/InputError.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import { index, show, update } from "@/routes/cooperative/members";

const props = defineProps<{
  member: any;
  employees: any[];
  users: any[];
  openingSavingBalance: number | string | null;
  options: {
    statuses: Array<{ value: string; label: string }>;
    jenisAnggota: Array<{ value: string; label: string }>;
    jenisKelamin: Array<{ value: string; label: string }>;
    kategori: Array<{ value: string; label: string }>;
    autodebet: Array<{ value: string; label: string }>;
  };
}>();

const form = useForm({
  employee_id: props.member.employee_id ?? "",
  user_id: props.member.user_id ?? "",
  no_anggota: props.member.no_anggota ?? props.member.member_no ?? "",
  tanggal_aktif: props.member.tanggal_aktif ?? props.member.joined_at ?? "",
  nama_anggota: props.member.nama_anggota ?? props.member.name ?? "",
  name: props.member.name ?? "",
  email: props.member.email ?? "",
  member_login_password: "",
  npwp: props.member.npwp ?? "",
  no_telp: props.member.no_telp ?? props.member.phone ?? "",
  phone: props.member.phone ?? "",
  identity_number: props.member.identity_number ?? "",
  address: props.member.address ?? "",
  joined_at: props.member.joined_at ?? "",
  status: props.member.status === "RESIGNED" ? "INACTIVE" : props.member.status ?? "ACTIVE",
  jenis_anggota: props.member.jenis_anggota ?? "AB",
  jenis_kelamin: props.member.jenis_kelamin ?? "L",
  kategori: props.member.kategori ?? "IP",
  autodebet: props.member.autodebet ?? "MANUAL",
  no_rekening: props.member.no_rekening ?? "",
  opening_saving_balance: props.openingSavingBalance ?? 0,
  notes: props.member.notes ?? "",
});

const memberName = computed(
  () => props.member.nama_anggota_clean || props.member.nama_anggota || props.member.name || "—",
);
const memberNumber = computed(
  () => props.member.no_anggota_display || props.member.member_no || "—",
);
const memberInitial = computed(() => {
  const name = memberName.value || "";
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return "??";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
});
const memberStatusLabel = computed(() => {
  switch (form.status) {
    case "ACTIVE":
      return "Aktif";
    case "INACTIVE":
      return "Non-aktif";
    case "RESIGNED":
      return "Resigned";
    default:
      return form.status || "—";
  }
});
const memberStatusTone = computed(() => {
  switch (form.status) {
    case "ACTIVE":
      return "emerald";
    case "INACTIVE":
    case "RESIGNED":
      return "zinc";
    default:
      return "amber";
  }
});

const submit = (): void => form.put(update(props.member.id).url);
</script>

<template>
  <Head title="Edit Anggota" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Anggota', href: index().url },
      { title: memberNumber, href: show(props.member.id).url },
      { title: 'Edit', href: '#' },
    ]"
  >
    <PageContainer class="max-w-none">
      <form class="flex flex-col gap-6" @submit.prevent="submit">
        <!-- HERO -->
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
            <div class="flex items-start gap-4">
              <span
                class="inline-flex size-16 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-lg font-bold text-sky-700 ring-2 ring-white shadow-sm shadow-zinc-950/5 dark:bg-sky-950/60 dark:text-sky-300 dark:ring-zinc-900"
              >
                {{ memberInitial }}
              </span>
              <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-900/40 dark:text-sky-200 dark:ring-sky-800/60"
                  >
                    <Pencil class="size-3.5" />
                    Edit Anggota
                  </span>
                  <StatusPill
                    :tone="memberStatusTone"
                    :label="memberStatusLabel"
                  />
                </div>
                <h1
                  class="text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white"
                >
                  {{ memberName }}
                </h1>
                <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                  <span
                    class="inline-flex items-center gap-1 rounded-md bg-white/80 px-2 py-1 font-mono text-xs font-semibold text-zinc-700 ring-1 ring-inset ring-zinc-200/70 dark:bg-zinc-950/40 dark:text-zinc-200 dark:ring-zinc-800/60"
                  >
                    <Hash class="size-3" />
                    {{ memberNumber }}
                  </span>
                </div>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <Button
                as-child
                variant="outline"
              >
                <Link :href="show(props.member.id).url" prefetch>
                  <ArrowLeft class="mr-2 size-4" />
                  Lihat detail
                </Link>
              </Button>
            </div>
          </div>
        </section>

        <!-- IDENTITAS -->
        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <div
            class="flex items-start gap-3 border-b border-zinc-200/70 px-6 py-5 dark:border-zinc-800/70"
          >
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/60"
            >
              <IdCard class="size-4" />
            </span>
            <div>
              <h2
                class="text-base font-semibold tracking-tight text-zinc-950 dark:text-white"
              >
                Identitas
              </h2>
              <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                Data utama keanggotaan.
              </p>
            </div>
          </div>
          <CardContent class="px-6 py-5">
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="edit-member-no">No Anggota</Label>
                <Input
                  id="edit-member-no"
                  v-model="form.no_anggota"
                  maxlength="10"
                  required
                />
                <InputError :message="form.errors.no_anggota" />
              </div>
              <div class="space-y-2">
                <Label for="edit-member-active-date">Tanggal Aktif</Label>
                <Input
                  id="edit-member-active-date"
                  v-model="form.tanggal_aktif"
                  type="date"
                  required
                />
                <InputError :message="form.errors.tanggal_aktif" />
              </div>
              <div class="space-y-2 md:col-span-2">
                <Label for="edit-member-name">Nama Anggota</Label>
                <Input
                  id="edit-member-name"
                  v-model="form.nama_anggota"
                  maxlength="100"
                  required
                />
                <InputError :message="form.errors.nama_anggota" />
              </div>
              <div class="space-y-2">
                <Label for="edit-member-join-date">Tanggal Bergabung</Label>
                <Input
                  id="edit-member-join-date"
                  v-model="form.joined_at"
                  type="date"
                />
                <InputError :message="form.errors.joined_at" />
              </div>
              <div class="space-y-2">
                <Label for="edit-member-status">Status</Label>
                <select
                  id="edit-member-status"
                  v-model="form.status"
                  class="h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.statuses"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="form.errors.status" />
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- KONTAK -->
        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <div
            class="flex items-start gap-3 border-b border-zinc-200/70 px-6 py-5 dark:border-zinc-800/70"
          >
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60"
            >
              <Mail class="size-4" />
            </span>
            <div>
              <h2
                class="text-base font-semibold tracking-tight text-zinc-950 dark:text-white"
              >
                Kontak & Administrasi
              </h2>
              <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                Saluran komunikasi dan data administratif.
              </p>
            </div>
          </div>
          <CardContent class="px-6 py-5">
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="edit-member-email">Email</Label>
                <div class="relative">
                  <Mail
                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"
                    aria-hidden="true"
                  />
                  <Input
                    id="edit-member-email"
                    v-model="form.email"
                    type="email"
                    maxlength="255"
                    placeholder="nama@email.com"
                    class="pl-9"
                  />
                </div>
                <InputError :message="form.errors.email" />
              </div>
              <div class="space-y-2">
                <Label for="edit-member-phone">No Telp</Label>
                <div class="relative">
                  <Phone
                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"
                    aria-hidden="true"
                  />
                  <Input
                    id="edit-member-phone"
                    v-model="form.no_telp"
                    maxlength="20"
                    class="pl-9"
                  />
                </div>
                <InputError :message="form.errors.no_telp" />
              </div>
              <div class="space-y-2">
                <Label for="edit-member-npwp">NPWP</Label>
                <Input
                  id="edit-member-npwp"
                  v-model="form.npwp"
                  maxlength="30"
                />
                <InputError :message="form.errors.npwp" />
              </div>
              <div class="space-y-2">
                <Label for="edit-member-identity">NIK</Label>
                <Input
                  id="edit-member-identity"
                  v-model="form.identity_number"
                  maxlength="20"
                />
                <InputError :message="form.errors.identity_number" />
              </div>
              <div class="space-y-2 md:col-span-2">
                <Label for="edit-member-address">Alamat</Label>
                <textarea
                  id="edit-member-address"
                  v-model="form.address"
                  rows="2"
                  placeholder="Alamat lengkap"
                  class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-800 dark:bg-zinc-950"
                />
                <InputError :message="form.errors.address" />
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- KEANGGOTAAN -->
        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <div
            class="flex items-start gap-3 border-b border-zinc-200/70 px-6 py-5 dark:border-zinc-800/70"
          >
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/60"
            >
              <Building2 class="size-4" />
            </span>
            <div>
              <h2
                class="text-base font-semibold tracking-tight text-zinc-950 dark:text-white"
              >
                Keanggotaan
              </h2>
              <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                Klasifikasi anggota di koperasi.
              </p>
            </div>
          </div>
          <CardContent class="px-6 py-5">
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="edit-member-type">Jenis Anggota</Label>
                <select
                  id="edit-member-type"
                  v-model="form.jenis_anggota"
                  class="h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.jenisAnggota"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="form.errors.jenis_anggota" />
              </div>
              <div class="space-y-2">
                <Label for="edit-member-gender">Jenis Kelamin</Label>
                <select
                  id="edit-member-gender"
                  v-model="form.jenis_kelamin"
                  class="h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.jenisKelamin"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="form.errors.jenis_kelamin" />
              </div>
              <div class="space-y-2 md:col-span-2">
                <Label for="edit-member-category">Kategori</Label>
                <select
                  id="edit-member-category"
                  v-model="form.kategori"
                  class="h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.kategori"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="form.errors.kategori" />
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- PEMBAYARAN -->
        <Card
          class="overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80"
        >
          <div
            class="flex items-start gap-3 border-b border-zinc-200/70 px-6 py-5 dark:border-zinc-800/70"
          >
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 ring-1 ring-inset ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/60"
            >
              <Banknote class="size-4" />
            </span>
            <div>
              <h2
                class="text-base font-semibold tracking-tight text-zinc-950 dark:text-white"
              >
                Pembayaran
              </h2>
              <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                Metode autodebet dan rekening anggota.
              </p>
            </div>
          </div>
          <CardContent class="px-6 py-5">
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="edit-member-autodebet">Autodebet</Label>
                <select
                  id="edit-member-autodebet"
                  v-model="form.autodebet"
                  class="h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <option
                    v-for="option in props.options.autodebet"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <InputError :message="form.errors.autodebet" />
              </div>
              <div class="space-y-2">
                <Label for="edit-member-account">No Rekening</Label>
                <Input
                  id="edit-member-account"
                  v-model="form.no_rekening"
                  maxlength="30"
                  :disabled="form.autodebet === 'MANUAL'"
                  placeholder="Kosong untuk manual"
                />
                <InputError :message="form.errors.no_rekening" />
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- AKSES LOGIN & SIMPANAN -->
        <Card
          class="overflow-hidden border-amber-200/60 bg-gradient-to-br from-amber-50/30 via-white to-white shadow-sm shadow-amber-950/5 dark:border-amber-900/40 dark:from-amber-950/10 dark:via-zinc-900 dark:to-zinc-900"
        >
          <div
            class="flex items-start gap-3 border-b border-amber-200/60 px-6 py-5 dark:border-amber-900/40"
          >
            <span
              class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200/70 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900/60"
            >
              <KeyRound class="size-4" />
            </span>
            <div>
              <h2
                class="text-base font-semibold tracking-tight text-zinc-950 dark:text-white"
              >
                Akses Login & Simpanan
              </h2>
              <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                Ubah password anggota dan saldo awal simpanan.
              </p>
            </div>
          </div>
          <CardContent class="px-6 py-5">
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="edit-member-password">Password Login Baru</Label>
                <Input
                  id="edit-member-password"
                  v-model="form.member_login_password"
                  type="password"
                  autocomplete="new-password"
                  placeholder="Kosongkan jika tidak berubah"
                />
                <InputError :message="form.errors.member_login_password" />
              </div>
              <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                <p class="font-medium">Saldo awal migrasi anggota lama?</p>
                <p class="mt-1 text-xs">
                  Gunakan
                  <Link
                    :href="`${index().url}/${props.member.id}/opening-balance`"
                    class="font-semibold underline"
                    prefetch
                  >
                    Wizard Saldo Awal
                  </Link>
                  untuk mencatat POKOK/WAJIB historis ke ledger simpanan.
                  Angka di bawah hanya disimpan sebagai catatan ringkasan (legacy).
                </p>
                <div class="mt-2 space-y-1">
                  <Label for="edit-member-opening-balance">
                    Saldo Awal (legacy)
                  </Label>
                  <Input
                    id="edit-member-opening-balance"
                    v-model="form.opening_saving_balance"
                    type="number"
                    min="0"
                    step="1000"
                  />
                  <InputError :message="form.errors.opening_saving_balance" />
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- STICKY FOOTER ACTIONS -->
        <div
          class="sticky bottom-4 z-10 flex flex-col gap-3 rounded-2xl border border-zinc-200/80 bg-white/95 p-3 shadow-lg shadow-zinc-950/10 backdrop-blur sm:flex-row sm:items-center sm:justify-end dark:border-zinc-800/80 dark:bg-zinc-900/95"
        >
          <p class="mr-auto text-sm text-zinc-500 dark:text-zinc-400">
            Pastikan perubahan sudah benar sebelum disimpan.
          </p>
          <Button
            as-child
            type="button"
            variant="outline"
          >
            <Link :href="show(props.member.id).url" prefetch>
              <ArrowLeft class="mr-2 size-4" />
              Batal
            </Link>
          </Button>
          <Button type="submit" :disabled="form.processing">
            <Save class="mr-2 size-4" />
            Simpan Perubahan
          </Button>
        </div>
      </form>
    </PageContainer>
  </AppLayout>
</template>
