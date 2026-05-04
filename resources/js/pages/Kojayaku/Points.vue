<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate } from '@/lib/formatters';

defineProps<{
    summary: { total_points: number; points_earned: number; points_redeemed: number; member_tier: string; next_tier: string | null; points_to_next_tier: number };
    history: { data: Array<{ id: string; posted_at?: string | null; transaction_type: string; points: number; balance_after: number; description: string }> };
    redemptions: Array<{ id: string; status: string; redeemed_at: string; points_used: number; reward?: { name: string } | null }>;
}>();
</script>

<template>
    <Head title="Poin Saya" />
    <AppLayout :breadcrumbs="[{ title: 'Kojayaku', href: '/member' }, { title: 'Poin', href: '/member/points' }]">
        <PageContainer>
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Saldo Poin</div><div class="mt-2 text-2xl font-semibold">{{ summary.total_points }}</div></div>
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Poin Masuk</div><div class="mt-2 text-2xl font-semibold">{{ summary.points_earned }}</div></div>
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Poin Keluar</div><div class="mt-2 text-2xl font-semibold">{{ summary.points_redeemed }}</div></div>
                <div class="rounded-lg border p-4"><div class="text-sm text-muted-foreground">Tier</div><div class="mt-2 text-2xl font-semibold">{{ summary.member_tier }}</div></div>
            </div>
            <div v-if="summary.next_tier" class="rounded-lg border bg-primary/5 p-4 text-sm">
                {{ summary.points_to_next_tier }} poin lagi untuk naik ke tier {{ summary.next_tier }}.
            </div>
            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-lg border overflow-x-auto">
                    <div class="border-b px-4 py-3 font-semibold">Riwayat Poin</div>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3">Poin</th>
                                <th class="px-4 py-3">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in history.data" :key="item.id" class="border-t">
                                <td class="px-4 py-3">{{ formatDate(item.posted_at || null) }}</td>
                                <td class="px-4 py-3">{{ item.transaction_type }}</td>
                                <td class="px-4 py-3">{{ item.points }}</td>
                                <td class="px-4 py-3">{{ item.balance_after }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="rounded-lg border overflow-x-auto">
                    <div class="border-b px-4 py-3 font-semibold">Penukaran Terbaru</div>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">Reward</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Poin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="redemption in redemptions" :key="redemption.id" class="border-t">
                                <td class="px-4 py-3">{{ redemption.reward?.name || '-' }}</td>
                                <td class="px-4 py-3">{{ formatDate(redemption.redeemed_at) }}</td>
                                <td class="px-4 py-3">{{ redemption.status }}</td>
                                <td class="px-4 py-3">{{ redemption.points_used }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
