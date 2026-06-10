<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Coins, Gift, History, Sparkles } from 'lucide-vue-next';
import StatusJourney from '@/components/Kojayaku/StatusJourney.vue';
import PageContainer from '@/components/PageContainer.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/lib/formatters';

const props = defineProps<{
    summary: { total_points: number };
    rewards: { data: Array<{ id: string; name: string; category: string; description?: string | null; points_required: number; stock?: number | null; valid_until?: string | null }> };
    redemptions: { data: Array<{ id: string; redeemed_at: string; status: string; quantity: number; points_used: number; reward?: { name: string } | null }> };
    journey: { title: string; current_status: string; reference?: string | null; amount?: number | string | null; steps: Array<{ label: string; completed: boolean; completed_at?: string | null }> };
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
            <StatusJourney :title="journey.title" :current-status="journey.current_status" :reference="journey.reference" :amount="journey.amount" :steps="journey.steps" />

            <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <!-- Katalog Reward Card -->
                <div class="rounded-3xl border border-zinc-100 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-zinc-50 px-6 py-5 flex items-center justify-between gap-4">
                        <div>
                            <h1 class="text-xl font-bold text-zinc-900 tracking-tight flex items-center gap-2">
                                <Sparkles class="h-5 w-5 text-amber-500" />
                                Katalog Reward
                            </h1>
                            <p class="text-xs text-zinc-400 mt-1">Daftar hadiah menarik yang siap Anda klaim.</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider block">Poin Tersedia</span>
                            <span class="text-lg font-extrabold text-amber-600 flex items-center gap-1 justify-end mt-0.5">
                                <Coins class="h-4.5 w-4.5" />
                                {{ summary.total_points }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Grid of Rewards Cards -->
                    <div class="p-6 grid gap-6 sm:grid-cols-2">
                        <div v-for="reward in rewards.data" :key="reward.id" class="group flex flex-col justify-between rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm hover:shadow-lg hover:border-emerald-300 transition-all duration-300 hover:-translate-y-1">
                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-emerald-800 border border-emerald-100/50">
                                        {{ reward.category }}
                                    </span>
                                    <div class="flex items-center gap-1 font-extrabold text-amber-600 text-sm bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-100/50">
                                        <Coins class="h-4 w-4" />
                                        {{ reward.points_required }} <span class="text-[10px] font-bold text-amber-500 uppercase tracking-wide">Poin</span>
                                    </div>
                                </div>
                                <h3 class="mt-4 font-bold text-zinc-950 text-base leading-tight group-hover:text-emerald-950 transition-colors">
                                    {{ reward.name }}
                                </h3>
                                <p class="mt-2 text-xs text-zinc-500 leading-relaxed">
                                    {{ reward.description || 'Tidak ada deskripsi tambahan.' }}
                                </p>
                            </div>

                            <div class="mt-5 pt-3 border-t border-zinc-50 flex items-center justify-between text-xs text-zinc-400 font-medium">
                                <span>Stok: <strong class="text-zinc-600 font-bold">{{ reward.stock ?? 'Unlimited' }}</strong></span>
                                <span>Valid: <strong class="text-zinc-650 font-bold">{{ formatDate(reward.valid_until || null) }}</strong></span>
                            </div>
                        </div>
                        <div v-if="rewards.data.length === 0" class="col-span-2 text-center py-12 text-zinc-400">
                            Belum ada reward terdaftar di katalog.
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Tukar Reward Form Card -->
                    <div class="rounded-3xl border border-zinc-100 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-zinc-900 tracking-tight">Klaim Penukaran</h2>
                        <p class="text-xs text-zinc-400 mt-1">
                            Pilih hadiah dari katalog dan masukkan jumlah klaim Anda.
                        </p>
                        <div class="mt-6 space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold uppercase tracking-wider text-zinc-450">Pilih Reward</label>
                                <select v-model="form.reward_id" class="h-10 w-full rounded-xl border border-zinc-200 bg-background px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all">
                                    <option v-for="reward in rewards.data" :key="reward.id" :value="reward.id">{{ reward.name }} · {{ reward.points_required }} poin</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold uppercase tracking-wider text-zinc-450">Jumlah</label>
                                <Input v-model.number="form.quantity" type="number" min="1" max="20" placeholder="Jumlah" class="rounded-xl" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold uppercase tracking-wider text-zinc-450">Alamat Pengiriman</label>
                                <textarea v-model="form.delivery_address" class="min-h-24 w-full rounded-xl border border-zinc-200 bg-background px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all" placeholder="Tuliskan alamat pengiriman lengkap Anda..."></textarea>
                            </div>
                            <Button class="w-full rounded-xl py-6 font-bold uppercase tracking-wider text-xs bg-emerald-800 hover:bg-emerald-900 shadow-md shadow-emerald-800/10 hover:scale-[1.01] transition-all" @click="submit">Tukar Sekarang</Button>
                        </div>
                    </div>

                    <!-- Riwayat Penukaran Card -->
                    <div class="rounded-3xl border border-zinc-100 bg-white shadow-sm overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-4 border-b border-zinc-50 p-6">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-700 shadow-sm">
                                    <History class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 class="font-bold text-zinc-900 tracking-tight">Riwayat Klaim</h2>
                                    <p class="text-xs text-zinc-400 mt-0.5">Status penukaran reward Anda sebelumnya.</p>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-zinc-50/50 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                                        <tr>
                                            <th class="px-6 py-4">Hadiah</th>
                                            <th class="px-6 py-4">Tanggal</th>
                                            <th class="px-6 py-4">Status</th>
                                            <th class="px-6 py-4 text-right">Poin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="redemption in redemptions.data" :key="redemption.id" class="border-t border-zinc-50 transition-colors hover:bg-zinc-50/50">
                                            <td class="px-6 py-4 font-bold text-zinc-800 text-xs">{{ redemption.reward?.name || '-' }}</td>
                                            <td class="px-6 py-4 text-zinc-500 font-medium text-xs">{{ formatDate(redemption.redeemed_at) }}</td>
                                            <td class="px-6 py-4">
                                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide bg-emerald-100 text-emerald-800">
                                                    {{ redemption.status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right font-extrabold text-zinc-800 text-xs">{{ redemption.points_used }}</td>
                                        </tr>
                                        <tr v-if="redemptions.data.length === 0">
                                            <td colspan="4" class="px-6 py-10 text-center text-zinc-400">Belum ada riwayat klaim reward.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
