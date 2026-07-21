<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ArrowLeft } from "lucide-vue-next";
import PageContainer from "@/components/PageContainer.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";

type Summary = {
    positive_deposit_liability: number;
    negative_receivable: number;
    positive_account_count: number;
    zero_account_count: number;
    negative_account_count: number;
    suspended_account_count: number;
    utilization_threshold: number;
    high_utilization_accounts: Array<{
        id: number;
        cooperative_member_id: number;
        balance: number;
        credit_limit: number;
        utilization: number;
    }>;
    oldest_uncovered_debt_date: string | null;
};

defineProps<{ summary: Summary }>();
</script>

<template>
    <Head title="Laporan Saldo Toko" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '#' },
            { title: 'Laporan Saldo Toko', href: '/cooperative/store-credit-report' },
        ]"
    >
        <PageContainer class="max-w-6xl">
            <Link href="/cooperative/store-credit" class="inline-flex items-center text-sm text-indigo-600 hover:underline">
                <ArrowLeft class="mr-1 size-4" /> Kembali
            </Link>
            <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Laporan Saldo Toko Anggota</h1>

            <div class="grid gap-3 md:grid-cols-3">
                <Card>
                    <CardContent class="p-4">
                        <div class="text-xs uppercase text-zinc-500">Total Kewajiban (Saldo +)</div>
                        <div class="text-2xl font-bold font-mono text-emerald-600">{{ formatCurrency(summary.positive_deposit_liability) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="text-xs uppercase text-zinc-500">Total Piutang (Saldo -)</div>
                        <div class="text-2xl font-bold font-mono text-rose-600">{{ formatCurrency(summary.negative_receivable) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="text-xs uppercase text-zinc-500">Utang Tertua Belum Tertutup</div>
                        <div class="text-2xl font-bold">{{ formatDate(summary.oldest_uncovered_debt_date) }}</div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-3 md:grid-cols-4">
                <Card><CardContent class="p-4"><div class="text-xs uppercase text-zinc-500">Akun Saldo Positif</div><div class="text-xl font-bold">{{ summary.positive_account_count }}</div></CardContent></Card>
                <Card><CardContent class="p-4"><div class="text-xs uppercase text-zinc-500">Akun Saldo Nol</div><div class="text-xl font-bold">{{ summary.zero_account_count }}</div></CardContent></Card>
                <Card><CardContent class="p-4"><div class="text-xs uppercase text-zinc-500">Akun Saldo Negatif</div><div class="text-xl font-bold">{{ summary.negative_account_count }}</div></CardContent></Card>
                <Card><CardContent class="p-4"><div class="text-xs uppercase text-zinc-500">Akun Ditangguhkan</div><div class="text-xl font-bold">{{ summary.suspended_account_count }}</div></CardContent></Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Akun Melebihi Utilisasi {{ Math.round(summary.utilization_threshold * 100) }}%</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <table class="w-full text-sm">
                        <thead class="border-b text-left text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="p-3">Anggota</th>
                                <th class="p-3 text-right">Saldo</th>
                                <th class="p-3 text-right">Limit</th>
                                <th class="p-3 text-right">Utilisasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="account in summary.high_utilization_accounts" :key="account.id" class="border-b last:border-0">
                                <td class="p-3 font-mono">#{{ account.cooperative_member_id }}</td>
                                <td class="p-3 text-right font-mono text-rose-600">{{ formatCurrency(account.balance) }}</td>
                                <td class="p-3 text-right font-mono">{{ formatCurrency(account.credit_limit) }}</td>
                                <td class="p-3 text-right font-mono">{{ (account.utilization * 100).toFixed(0) }}%</td>
                            </tr>
                            <tr v-if="summary.high_utilization_accounts.length === 0">
                                <td colspan="4" class="p-6 text-center text-zinc-500">Tidak ada akun melebihi ambang utilisasi.</td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </PageContainer>
    </AppLayout>
</template>
