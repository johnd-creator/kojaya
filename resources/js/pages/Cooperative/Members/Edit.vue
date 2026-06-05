<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import { index, update } from "@/routes/cooperative/members";

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

const submit = () => form.put(update(props.member.id).url);
</script>

<template>
  <Head title="Edit Anggota" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Koperasi', href: '#' },
      { title: 'Anggota', href: index().url },
      { title: member.member_no, href: '#' },
    ]"
  >
    <form
      class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6"
      @submit.prevent="submit"
    >
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Edit Anggota</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ member.member_no }}</p>
      </div>
      <div
        class="grid gap-4 rounded-lg border bg-white p-6 dark:bg-zinc-900 md:grid-cols-2"
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
        class="grid gap-4 rounded-lg border bg-white p-6 dark:bg-zinc-900 md:grid-cols-2"
      >
        <div class="md:col-span-2">
          <h2 class="text-lg font-semibold">Akses Login Anggota</h2>
        </div>
        <label class="space-y-1"
          ><span class="text-sm">Password Login Baru</span
          ><Input
            v-model="form.member_login_password"
            type="password"
            autocomplete="new-password"
        /></label>
        <label class="space-y-1"
          ><span class="text-sm">Saldo Awal Simpanan</span
          ><Input
            v-model="form.opening_saving_balance"
            type="number"
            min="0"
            step="1000"
        /></label>
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
