<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { index } from '@/routes/cooperative/ledger';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{ entries: any; filters: any }>();
const memberSearch = ref(props.filters.member_search ?? '');
const entryType = ref(props.filters.entry_type ?? '');
const applyFilters = () => router.get(index().url, { member_search: memberSearch.value, entry_type: entryType.value }, { preserveState: true, replace: true });
const formatCurrency = (amount: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(amount ?? 0));
</script>

<template>
    <Head title="Ledger Simpanan" />
    <AppLayout :breadcrumbs="[{ title: 'Iuran & Simpanan', href: '#' }, { title: 'Ledger Simpanan', href: index().url }]">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Ledger Simpanan</h1>
                <p class="mt-1 text-sm text-zinc-500">Mutasi simpanan dan kredit anggota koperasi.</p>
            </div>
            <div class="flex flex-col gap-3 rounded-lg border bg-white p-4 dark:bg-zinc-900 md:flex-row">
                <Input v-model="memberSearch" class="md:w-80" placeholder="Cari anggota atau nomor anggota" @keyup.enter="applyFilters" />
                <select v-model="entryType" class="h-10 rounded-md border bg-white px-3 text-sm dark:bg-zinc-950">
                    <option value="">Semua tipe</option>
                    <option>SAVING_PAYMENT</option>
                    <option>POS_MEMBER_CREDIT</option>
                </select>
                <Button variant="outline" @click="applyFilters">Filter</Button>
            </div>
            <div class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900">
                        <tr><th class="px-4 py-3">Tanggal</th><th>Anggota</th><th>Tipe</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="px-4 py-3">Keterangan</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="entry in entries.data" :key="entry.id">
                            <td class="px-4 py-3">{{ entry.posted_at }}</td>
                            <td>{{ entry.member?.member_no }} - {{ entry.member?.name }}</td>
                            <td>{{ entry.entry_type }}</td>
                            <td class="text-right">{{ formatCurrency(entry.debit) }}</td>
                            <td class="text-right">{{ formatCurrency(entry.credit) }}</td>
                            <td class="px-4 py-3">{{ entry.description || '-' }}</td>
                        </tr>
                        <tr v-if="entries.data.length === 0"><td colspan="6" class="px-4 py-10 text-center text-zinc-500">Belum ada ledger.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
