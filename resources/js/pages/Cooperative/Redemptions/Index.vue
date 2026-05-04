<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
    redemptions: {
        data: Array<{
            id: string;
            status: string;
            quantity: number;
            points_used: number;
            redeemed_at: string;
            reward: { name: string };
            member: { name: string; member_no: string };
        }>;
    };
}>();
</script>

<template>
    <Head title="Reward Redemptions" />

    <AppLayout :breadcrumbs="[{ title: 'Cooperative', href: '/cooperative/members' }, { title: 'Redemptions', href: '/cooperative/redemptions' }]">
        <PageContainer>
            <div>
                <h1 class="text-2xl font-semibold">Reward Redemptions</h1>
                <p class="text-sm text-muted-foreground">Pantau penukaran reward yang diajukan anggota.</p>
            </div>

            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3">Member</th>
                            <th class="px-4 py-3">Reward</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Points</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Redeemed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="redemption in redemptions.data" :key="redemption.id" class="border-t">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ redemption.member.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ redemption.member.member_no }}</div>
                            </td>
                            <td class="px-4 py-3">{{ redemption.reward.name }}</td>
                            <td class="px-4 py-3">{{ redemption.quantity }}</td>
                            <td class="px-4 py-3">{{ redemption.points_used }}</td>
                            <td class="px-4 py-3">{{ redemption.status }}</td>
                            <td class="px-4 py-3">{{ redemption.redeemed_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </PageContainer>
    </AppLayout>
</template>
