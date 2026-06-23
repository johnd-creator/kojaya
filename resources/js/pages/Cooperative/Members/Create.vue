<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { index, store } from "@/routes/cooperative/members";

const props = defineProps<{
  employees: any[];
  users: any[];
  options: {
    statuses: Array<{ value: string; label: string }>;
    jenisAnggota: Array<{ value: string; label: string }>;
    jenisKelamin: Array<{ value: string; label: string }>;
    kategori: Array<{ value: string; label: string }>;
    autodebet: Array<{ value: string; label: string }>;
  };
}>();

const form = useForm({
  employee_id: "",
  user_id: "",
  no_anggota: "",
  tanggal_aktif: new Date().toISOString().slice(0, 10),
  nama_anggota: "",
  name: "",
  email: "",
  member_login_password: "",
  npwp: "",
  no_telp: "",
  phone: "",
  identity_number: "",
  address: "",
  joined_at: new Date().toISOString().slice(0, 10),
  status: "ACTIVE",
  jenis_anggota: "AB",
  jenis_kelamin: "L",
  kategori: "IP",
  autodebet: "MANUAL",
  no_rekening: "",
  opening_saving_balance: 0,
  notes: "",
});

const submit = () => form.post(store().url);
</script>

<template>
  <Head title="Anggota Baru" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Anggota', href: index().url },
      { title: 'Baru', href: '#' },
    ]"
  >
    <form
      class="flex w-full flex-col gap-6 p-6"
      @submit.prevent="submit"
    >
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Anggota Baru</h1>
        <p class="mt-1 text-sm text-zinc-500">
          Data anggota akan otomatis dicatat di Koperasi Utama.
        </p>
      </div>
      <div
        class="grid gap-4 rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900 md:grid-cols-2"
      >
        <label class="space-y-1"
          ><span class="text-sm">No Anggota</span
          ><Input v-model="form.no_anggota" maxlength="10" required
        /></label>
        <label class="space-y-1"
          ><span class="text-sm">Tanggal Aktif</span
          ><Input v-model="form.tanggal_aktif" type="date" required
        /></label>
        <label class="space-y-1"
          ><span class="text-sm">Tanggal Bergabung</span
          ><Input v-model="form.joined_at" type="date"
        /></label>
        <label class="space-y-1"
          ><span class="text-sm">Email</span
          ><Input
            v-model="form.email"
            type="email"
            maxlength="255"
            placeholder="nama@email.com"
        /></label>
        <label class="space-y-1">
          <span class="text-sm">Status</span>
          <select
            v-model="form.status"
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option
              v-for="option in props.options.statuses"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </label>
        <label class="space-y-1 md:col-span-2"
          ><span class="text-sm">Nama Anggota</span
          ><Input v-model="form.nama_anggota" maxlength="100" required
        /></label>
        <label class="space-y-1"
          ><span class="text-sm">NPWP</span
          ><Input v-model="form.npwp" maxlength="30"
        /></label>
        <label class="space-y-1"
          ><span class="text-sm">No Telp</span
          ><Input v-model="form.no_telp" maxlength="20"
        /></label>
        <label class="space-y-1">
          <span class="text-sm">Jenis Anggota</span>
          <select
            v-model="form.jenis_anggota"
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option
              v-for="option in props.options.jenisAnggota"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </label>
        <label class="space-y-1">
          <span class="text-sm">Jenis Kelamin</span>
          <select
            v-model="form.jenis_kelamin"
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option
              v-for="option in props.options.jenisKelamin"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </label>
        <label class="space-y-1">
          <span class="text-sm">Kategori</span>
          <select
            v-model="form.kategori"
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option
              v-for="option in props.options.kategori"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </label>
        <label class="space-y-1">
          <span class="text-sm">Autodebet</span>
          <select
            v-model="form.autodebet"
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option
              v-for="option in props.options.autodebet"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </label>
        <label class="space-y-1"
          ><span class="text-sm">No Rekening</span
          ><Input
            v-model="form.no_rekening"
            maxlength="30"
            :disabled="form.autodebet === 'MANUAL'"
            placeholder="Kosong untuk manual"
          />
        </label>
      </div>
      <div
        class="grid gap-4 rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 p-6 dark:bg-zinc-900 md:grid-cols-2"
      >
        <div class="md:col-span-2">
          <h2 class="text-lg font-semibold">Akses Login Anggota</h2>
        </div>
        <label class="space-y-1"
          ><span class="text-sm">Password Login</span
          ><Input
            v-model="form.member_login_password"
            type="password"
            autocomplete="new-password"
        /></label>
        <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
          <p class="font-medium">Saldo awal migrasi anggota lama?</p>
          <p class="mt-1 text-xs">
            Gunakan halaman
            <span class="font-semibold">Wizard Saldo Awal</span> setelah anggota
            dibuat untuk mencatat POKOK/WAJIB historis ke ledger simpanan.
            Angka di bawah hanya disimpan sebagai catatan ringkasan (legacy).
          </p>
          <label class="mt-2 block space-y-1"
            ><span class="text-sm">Saldo Awal (legacy)</span
            ><Input
              v-model="form.opening_saving_balance"
              type="number"
              min="0"
              step="1000"
          /></label>
        </div>
      </div>
      <div class="flex justify-end gap-2">
        <Link :href="index().url"
          ><Button type="button" variant="outline">Batal</Button></Link
        >
        <Button type="submit" :disabled="form.processing">Simpan</Button>
      </div>
    </form>
  </AppLayout>
</template>
