<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, UserCheck, UserX } from 'lucide-vue-next';
import { ref } from 'vue';
import { activate, create, edit, index, resign, show } from '@/routes/cooperative/members';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    members: any;
    filters: { search?: string; status?: string };
    stats: { active: number; pending: number };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const breadcrumbs = [
    { title: 'Koperasi', href: '#' },
    { title: 'Anggota', href: index().url },
];

const applyFilters = () => {
    router.get(index().url, { search: search.value, status: status.value }, { preserveState: true, replace: true });
};

const formatCurrency = (amount: number | null) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(amount ?? 0));
</script>

<template>
    <Head title="Anggota Koperasi" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Anggota Koperasi</h1>
                    <p class="mt-1 text-sm text-zinc-500">Keanggotaan terpusat di Koperasi Utama.</p>
                </div>
                <Link :href="create().url">
                    <Button><Plus class="mr-2 h-4 w-4" />Anggota Baru</Button>
                </Link>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Aktif</div>
                    <div class="text-2xl font-semibold">{{ stats.active }}</div>
                </div>
                <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Pending</div>
                    <div class="text-2xl font-semibold">{{ stats.pending }}</div>
                </div>
                <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Total Terdata</div>
                    <div class="text-2xl font-semibold">{{ members.total }}</div>
                </div>
            </div>

            <div class="flex flex-col gap-3 rounded-lg border bg-white p-4 dark:bg-zinc-900 md:flex-row">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                    <Input v-model="search" class="pl-9" placeholder="Cari nomor, nama, email, NIK" @keyup.enter="applyFilters" />
                </div>
                <select v-model="status" class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950">
                    <option value="">Semua status</option>
                    <option value="PENDING">PENDING</option>
                    <option value="ACTIVE">ACTIVE</option>
                    <option value="INACTIVE">INACTIVE</option>
                    <option value="RESIGNED">RESIGNED</option>
                </select>
                <Button variant="outline" @click="applyFilters">Filter</Button>
            </div>

            <div class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3">Anggota</th>
                            <th class="px-4 py-3">Kontak</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Simpanan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="member in members.data" :key="member.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <Link class="font-medium hover:text-indigo-600" :href="show(member.id).url">{{ member.name }}</Link>
                                <div class="text-xs text-zinc-500">{{ member.member_no }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-600">
                                <div>{{ member.email || '-' }}</div>
                                <div class="text-xs">{{ member.phone || '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full border px-2 py-1 text-xs">{{ member.status }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(member.saving_balance) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <Link :href="edit(member.id).url"><Button size="sm" variant="outline">Edit</Button></Link>
                                    <Button v-if="member.status !== 'ACTIVE'" size="sm" variant="outline" @click="router.post(activate(member.id).url)">
                                        <UserCheck class="h-4 w-4" />
                                    </Button>
                                    <Button v-if="member.status === 'ACTIVE'" size="sm" variant="outline" @click="router.post(resign(member.id).url)">
                                        <UserX class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="members.data.length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-zinc-500">Belum ada anggota koperasi.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
