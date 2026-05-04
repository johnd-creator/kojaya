<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
    members: {
        data: Array<{
            id: number;
            member_no: string;
            name: string;
            status: string;
            total_points: number;
            points_earned: number;
            points_redeemed: number;
            member_tier: string;
            next_tier: string | null;
            points_to_next_tier: number;
        }>;
    };
    stats: {
        active_members: number;
        total_balance: number;
    };
}>();
</script>

<template>
    <Head title="Member Points" />

    <AppLayout :breadcrumbs="[{ title: 'Cooperative', href: '/cooperative/members' }, { title: 'Points', href: '/cooperative/points' }]">
        <PageContainer>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">Points & Loyalty</h1>
                    <p class="text-sm text-muted-foreground">Ringkasan poin anggota koperasi dari transaksi dan penukaran reward.</p>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg border p-3">
                        <div class="text-muted-foreground">Active Members</div>
                        <div class="text-lg font-semibold">{{ stats.active_members }}</div>
                    </div>
                    <div class="rounded-lg border p-3">
                        <div class="text-muted-foreground">Total Balance</div>
                        <div class="text-lg font-semibold">{{ stats.total_balance }}</div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3">Member</th>
                            <th class="px-4 py-3">Tier</th>
                            <th class="px-4 py-3">Balance</th>
                            <th class="px-4 py-3">Earned</th>
                            <th class="px-4 py-3">Redeemed</th>
                            <th class="px-4 py-3">Next Tier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="member in members.data" :key="member.id" class="border-t">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ member.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ member.member_no }}</div>
                            </td>
                            <td class="px-4 py-3">{{ member.member_tier }}</td>
                            <td class="px-4 py-3 font-semibold">{{ member.total_points }}</td>
                            <td class="px-4 py-3">{{ member.points_earned }}</td>
                            <td class="px-4 py-3">{{ member.points_redeemed }}</td>
                            <td class="px-4 py-3">
                                <span v-if="member.next_tier">{{ member.next_tier }} ({{ member.points_to_next_tier }} pts)</span>
                                <span v-else>Highest tier</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </PageContainer>
    </AppLayout>
</template>
