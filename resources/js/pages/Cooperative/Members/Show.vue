<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { index } from '@/routes/cooperative/members';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{ member: any }>();

const formatCurrency = (amount: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(amount ?? 0));
</script>

<template>
    <Head title="Detail Anggota" />
    <AppLayout :breadcrumbs="[{ title: 'Koperasi', href: '#' }, { title: 'Anggota', href: index().url }, { title: member.member_no, href: '#' }]">
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-6">
            <div>
                <Link :href="index().url" class="text-sm text-indigo-600">Kembali</Link>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ member.name }}</h1>
                <p class="text-sm text-zinc-500">{{ member.member_no }} · {{ member.status }}</p>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Email</div>
                    <div class="font-medium">{{ member.email || '-' }}</div>
                </div>
                <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Telepon</div>
                    <div class="font-medium">{{ member.phone || '-' }}</div>
                </div>
                <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Organisasi</div>
                    <div class="font-medium">{{ member.organization?.name }}</div>
                </div>
            </div>
            <div class="rounded-lg border bg-white p-4 dark:bg-zinc-900">
                <h2 class="mb-3 text-lg font-semibold">Tagihan</h2>
                <table class="w-full text-left text-sm">
                    <tbody class="divide-y">
                        <tr v-for="invoice in member.invoices" :key="invoice.id">
                            <td class="py-2">{{ invoice.period }}</td>
                            <td>{{ invoice.contribution_type?.name }}</td>
                            <td>{{ invoice.status }}</td>
                            <td class="text-right">{{ formatCurrency(invoice.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
