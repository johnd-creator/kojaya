<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { index, store } from '@/routes/cooperative/members';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{ employees: any[]; users: any[] }>();

const form = useForm({
    employee_id: '',
    user_id: '',
    name: '',
    email: '',
    phone: '',
    identity_number: '',
    address: '',
    joined_at: new Date().toISOString().slice(0, 10),
    status: 'PENDING',
    notes: '',
});

const submit = () => form.post(store().url);
</script>

<template>
    <Head title="Anggota Baru" />
    <AppLayout :breadcrumbs="[{ title: 'Koperasi', href: '#' }, { title: 'Anggota', href: index().url }, { title: 'Baru', href: '#' }]">
        <form class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6" @submit.prevent="submit">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Anggota Baru</h1>
                <p class="mt-1 text-sm text-zinc-500">Data anggota akan otomatis dicatat di Koperasi Utama.</p>
            </div>
            <div class="grid gap-4 rounded-lg border bg-white p-6 dark:bg-zinc-900 md:grid-cols-2">
                <label class="space-y-1"><span class="text-sm">Nama</span><Input v-model="form.name" required /></label>
                <label class="space-y-1"><span class="text-sm">Email</span><Input v-model="form.email" type="email" /></label>
                <label class="space-y-1"><span class="text-sm">Telepon</span><Input v-model="form.phone" /></label>
                <label class="space-y-1"><span class="text-sm">NIK</span><Input v-model="form.identity_number" /></label>
                <label class="space-y-1"><span class="text-sm">Tanggal Gabung</span><Input v-model="form.joined_at" type="date" /></label>
                <label class="space-y-1">
                    <span class="text-sm">Status</span>
                    <select v-model="form.status" class="h-10 w-full rounded-md border bg-white px-3 text-sm dark:bg-zinc-950">
                        <option>PENDING</option>
                        <option>ACTIVE</option>
                    </select>
                </label>
                <label class="space-y-1 md:col-span-2"><span class="text-sm">Alamat</span><textarea v-model="form.address" class="min-h-24 w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950" /></label>
                <label class="space-y-1 md:col-span-2"><span class="text-sm">Catatan</span><textarea v-model="form.notes" class="min-h-20 w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950" /></label>
            </div>
            <div class="flex justify-end gap-2">
                <Link :href="index().url"><Button type="button" variant="outline">Batal</Button></Link>
                <Button type="submit" :disabled="form.processing">Simpan</Button>
            </div>
        </form>
    </AppLayout>
</template>
