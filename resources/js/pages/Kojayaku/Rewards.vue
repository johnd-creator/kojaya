<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';

const props = defineProps<{
    summary: { total_points: number };
    rewards: { data: Array<{ id: string; name: string; category: string; description?: string | null; points_required: number; stock?: number | null; valid_until?: string | null }> };
    redemptions: { data: Array<{ id: string; redeemed_at: string; status: string; quantity: number; points_used: number; reward?: { name: string } | null }> };
}>();

const form = useForm({ reward_id: props.rewards.data[0]?.id ?? '', quantity: 1, delivery_address: '' });
const submit = (): void => {
    if (!form.reward_id) {
        return;
    }

    router.post(`/member/rewards/${form.reward_id}/redeem`, { quantity: form.quantity, delivery_address: form.delivery_address });
};
</script>

<template>
    <Head title="Rewards" />
    <AppLayout :breadcrumbs="[{ title: 'Kojayaku', href: '/member' }, { title: 'Rewards', href: '/member/rewards' }]">
        <PageContainer>
            <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="rounded-lg border overflow-x-auto">
                    <div class="border-b px-4 py-3">
                        <h1 class="text-xl font-semibold">Katalog Reward</h1>
                        <p class="text-sm text-muted-foreground">Poin tersedia: {{ summary.total_points }}</p>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">Reward</th>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3">Poin</th>
                                <th class="px-4 py-3">Stok</th>
                                <th class="px-4 py-3">Valid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="reward in rewards.data" :key="reward.id" class="border-t">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ reward.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ reward.description || '-' }}</div>
                                </td>
                                <td class="px-4 py-3">{{ reward.category }}</td>
                                <td class="px-4 py-3">{{ reward.points_required }}</td>
                                <td class="px-4 py-3">{{ reward.stock ?? 'Unlimited' }}</td>
                                <td class="px-4 py-3">{{ formatDate(reward.valid_until || null) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-6">
                    <div class="rounded-lg border p-4">
                        <h2 class="text-lg font-semibold">Tukar Reward</h2>
                        <div class="mt-4 space-y-4">
                            <select v-model="form.reward_id" class="h-10 w-full rounded-md border bg-background px-3 text-sm">
                                <option v-for="reward in rewards.data" :key="reward.id" :value="reward.id">{{ reward.name }} · {{ reward.points_required }} poin</option>
                            </select>
                            <Input v-model.number="form.quantity" type="number" min="1" max="20" placeholder="Jumlah" />
                            <textarea v-model="form.delivery_address" class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Alamat pengiriman"></textarea>
                            <Button class="w-full" @click="submit">Tukar Sekarang</Button>
                        </div>
                    </div>

                    <div class="rounded-lg border overflow-x-auto">
                        <div class="border-b px-4 py-3 font-semibold">Riwayat Penukaran</div>
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
                                <tr v-for="redemption in redemptions.data" :key="redemption.id" class="border-t">
                                    <td class="px-4 py-3">{{ redemption.reward?.name || '-' }}</td>
                                    <td class="px-4 py-3">{{ formatDate(redemption.redeemed_at) }}</td>
                                    <td class="px-4 py-3">{{ redemption.status }}</td>
                                    <td class="px-4 py-3">{{ redemption.points_used }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
