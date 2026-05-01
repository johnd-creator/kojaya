<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index } from '@/routes/cooperative/reports';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{ summary: any }>();

const formatCurrency = (amount: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(amount ?? 0));
</script>

<template>
    <Head title="Laporan Koperasi" />
    <AppLayout :breadcrumbs="[{ title: 'Koperasi', href: '#' }, { title: 'Laporan', href: index().url }]">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Laporan Koperasi</h1>
                <p class="mt-1 text-sm text-zinc-500">Ringkasan anggota, simpanan, tunggakan, POS toko, dan SHU tahunan.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Anggota Aktif</div><div class="mt-2 text-2xl font-semibold">{{ summary.active_members }}</div></div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Saldo Simpanan</div><div class="mt-2 text-2xl font-semibold">{{ formatCurrency(summary.saving_balance) }}</div></div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Kredit Anggota</div><div class="mt-2 text-2xl font-semibold">{{ formatCurrency(summary.member_credit_balance) }}</div></div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Tunggakan</div><div class="mt-2 text-2xl font-semibold">{{ formatCurrency(summary.unpaid_dues) }}</div></div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Omzet Hari Ini</div><div class="mt-2 text-2xl font-semibold">{{ formatCurrency(summary.today_sales) }}</div></div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Omzet Bulan Ini</div><div class="mt-2 text-2xl font-semibold">{{ formatCurrency(summary.monthly_sales) }}</div></div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Produk Low Stock</div><div class="mt-2 text-2xl font-semibold">{{ summary.low_stock_products }}</div></div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Profit POS Tahun Ini</div><div class="mt-2 text-2xl font-semibold">{{ formatCurrency(summary.annual_pos_profit) }}</div></div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Poin POS Tahun Ini</div><div class="mt-2 text-2xl font-semibold">{{ summary.annual_pos_points }}</div></div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900"><div class="text-sm text-zinc-500">SHU Terakhir Ditutup</div><div class="mt-2 text-2xl font-semibold">{{ summary.latest_shu_year ?? '-' }}</div></div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900"><div class="text-sm text-zinc-500">Pool SHU Terakhir</div><div class="mt-2 text-2xl font-semibold">{{ formatCurrency(summary.latest_shu_total) }}</div></div>
            </div>
        </div>
    </AppLayout>
</template>
