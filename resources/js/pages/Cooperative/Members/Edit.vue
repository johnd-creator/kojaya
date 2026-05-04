<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { index, update } from "@/routes/cooperative/members";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";

const props = defineProps<{
  member: any;
  employees: any[];
  users: any[];
  openingSavingBalance: number | string | null;
}>();

const form = useForm({
  employee_id: props.member.employee_id ?? "",
  user_id: props.member.user_id ?? "",
  name: props.member.name ?? "",
  email: props.member.email ?? "",
  member_login_password: "",
  phone: props.member.phone ?? "",
  identity_number: props.member.identity_number ?? "",
  address: props.member.address ?? "",
  joined_at: props.member.joined_at ?? "",
  status: props.member.status ?? "PENDING",
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
          ><span class="text-sm">Nama</span><Input v-model="form.name" required
        /></label>
        <label class="space-y-1"
          ><span class="text-sm">Email</span
          ><Input v-model="form.email" type="email"
        /></label>
        <label class="space-y-1"
          ><span class="text-sm">Telepon</span><Input v-model="form.phone"
        /></label>
        <label class="space-y-1"
          ><span class="text-sm">NIK</span
          ><Input v-model="form.identity_number"
        /></label>
        <label class="space-y-1"
          ><span class="text-sm">Tanggal Gabung</span
          ><Input v-model="form.joined_at" type="date"
        /></label>
        <label class="space-y-1">
          <span class="text-sm">Status</span>
          <select
            v-model="form.status"
            class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950"
          >
            <option>PENDING</option>
            <option>ACTIVE</option>
            <option>INACTIVE</option>
            <option>RESIGNED</option>
          </select>
        </label>
        <label class="space-y-1 md:col-span-2"
          ><span class="text-sm">Alamat</span
          ><textarea
            v-model="form.address"
            class="min-h-24 w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950"
          />
        </label>
        <label class="space-y-1 md:col-span-2"
          ><span class="text-sm">Catatan</span
          ><textarea
            v-model="form.notes"
            class="min-h-20 w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950"
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
