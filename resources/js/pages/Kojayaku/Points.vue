<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
  Coins,
  Gift,
  History,
  Star,
  Award,
  ArrowDownLeft,
  ArrowUpRight,
} from 'lucide-vue-next';
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
            <div class="flex flex-col gap-6">
                <header class="flex items-center gap-5">
                    <div
                      class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-yellow-600 text-white shadow-lg shadow-amber-500/20"
                    >
                      <Star class="h-8 w-8" />
                    </div>
                    <div>
                      <h1 class="text-3xl font-extrabold text-zinc-900 tracking-tight">Poin & Loyalitas</h1>
                      <p class="mt-1 text-sm text-zinc-500">
                        Kumpulkan poin dari setiap transaksi belanja Anda dan tukarkan dengan hadiah menarik.
                      </p>
                    </div>
                </header>

                <div class="grid gap-6 md:grid-cols-4">
                    <!-- Saldo Poin -->
                    <div class="group rounded-3xl border border-amber-100 bg-gradient-to-br from-amber-500 to-yellow-600 p-6 text-white shadow-md shadow-amber-500/10 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-amber-100">Saldo Poin</span>
                            <Coins class="h-5 w-5 text-amber-200" />
                        </div>
                        <div class="mt-4 text-3xl font-extrabold tracking-tight">{{ summary.total_points }}</div>
                        <div class="mt-2 text-xs text-amber-100/90 leading-normal">Poin aktif saat ini</div>
                    </div>

                    <!-- Poin Masuk -->
                    <div class="group rounded-3xl border border-zinc-100 bg-white p-6 shadow-sm hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Poin Masuk</span>
                            <div class="p-1.5 bg-emerald-50 rounded-lg text-emerald-700">
                                <ArrowDownLeft class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="mt-4 text-3xl font-extrabold text-zinc-900 tracking-tight">{{ summary.points_earned }}</div>
                        <div class="mt-2 text-xs text-zinc-500 leading-normal">Total poin yang diperoleh</div>
                    </div>

                    <!-- Poin Ditukar -->
                    <div class="group rounded-3xl border border-zinc-100 bg-white p-6 shadow-sm hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Poin Ditukar</span>
                            <div class="p-1.5 bg-rose-50 rounded-lg text-rose-700">
                                <ArrowUpRight class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="mt-4 text-3xl font-extrabold text-zinc-900 tracking-tight">{{ summary.points_redeemed }}</div>
                        <div class="mt-2 text-xs text-zinc-500 leading-normal">Total poin yang dibelanjakan</div>
                    </div>

                    <!-- Tier Anggota -->
                    <div class="group rounded-3xl border border-zinc-100 bg-white p-6 shadow-sm hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Tier Keanggotaan</span>
                            <div class="p-1.5 bg-violet-50 rounded-lg text-violet-700">
                                <Award class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="mt-4 text-3xl font-extrabold text-zinc-900 tracking-tight">{{ summary.member_tier }}</div>
                        <div class="mt-2 text-xs text-zinc-500 leading-normal">Tingkat keanggotaan Anda</div>
                    </div>
                </div>

                <div v-if="summary.next_tier" class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50/60 to-yellow-50/30 p-5 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800 shadow-sm border border-amber-200/20">
                            <Coins class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-bold text-amber-950">Kemajuan Naik Level Tier berikutnya</p>
                            <p class="text-xs text-amber-800 mt-0.5">Kumpulkan <span class="font-bold font-mono text-amber-950">{{ summary.points_to_next_tier }}</span> poin lagi untuk naik ke tingkat <span class="font-bold uppercase text-amber-950">{{ summary.next_tier }}</span>.</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-amber-200/60 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-amber-950 border border-amber-300/30 text-center">On Track</span>
                </div>

                <div class="grid gap-6 xl:grid-cols-2">
                    <!-- Riwayat Poin -->
                    <div class="rounded-3xl border border-zinc-100 bg-white shadow-sm overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-4 border-b border-zinc-50 p-6">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 shadow-sm">
                                    <History class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 class="font-bold text-zinc-900 tracking-tight">Riwayat Transaksi Poin</h2>
                                    <p class="text-xs text-zinc-400 mt-0.5">Rincian poin masuk dan keluar akun Anda.</p>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-zinc-50/50 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                                        <tr>
                                            <th class="px-6 py-4">Tanggal</th>
                                            <th class="px-6 py-4">Keterangan</th>
                                            <th class="px-6 py-4">Tipe</th>
                                            <th class="px-6 py-4 text-right">Poin</th>
                                            <th class="px-6 py-4 text-right">Sisa Poin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in history.data" :key="item.id" class="border-t border-zinc-50 transition-colors hover:bg-zinc-50/50">
                                            <td class="px-6 py-4 text-zinc-500 font-medium text-xs">{{ formatDate(item.posted_at || null) }}</td>
                                            <td class="px-6 py-4 font-semibold text-zinc-800 text-xs">{{ item.description || '-' }}</td>
                                            <td class="px-6 py-4 text-xs font-medium text-zinc-500">{{ item.transaction_type }}</td>
                                            <td class="px-6 py-4 text-right text-xs font-extrabold" :class="item.points >= 0 ? 'text-emerald-700' : 'text-rose-700'">
                                                {{ item.points >= 0 ? '+' : '' }}{{ item.points }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-xs font-bold text-zinc-800">{{ item.balance_after }}</td>
                                        </tr>
                                        <tr v-if="history.data.length === 0">
                                            <td colspan="5" class="px-6 py-12 text-center text-zinc-400">Belum ada riwayat poin.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Penukaran Terbaru -->
                    <div class="rounded-3xl border border-zinc-100 bg-white shadow-sm overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-4 border-b border-zinc-50 p-6">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-700 shadow-sm">
                                    <Gift class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 class="font-bold text-zinc-900 tracking-tight">Penukaran Terbaru</h2>
                                    <p class="text-xs text-zinc-400 mt-0.5">Daftar penukaran reward yang sudah diajukan.</p>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-zinc-50/50 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                                        <tr>
                                            <th class="px-6 py-4">Reward</th>
                                            <th class="px-6 py-4">Tanggal</th>
                                            <th class="px-6 py-4">Status</th>
                                            <th class="px-6 py-4 text-right">Poin Ditukar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="redemption in redemptions" :key="redemption.id" class="border-t border-zinc-50 transition-colors hover:bg-zinc-50/50">
                                            <td class="px-6 py-4 font-bold text-zinc-800 text-xs">{{ redemption.reward?.name || '-' }}</td>
                                            <td class="px-6 py-4 text-zinc-500 font-medium text-xs">{{ formatDate(redemption.redeemed_at) }}</td>
                                            <td class="px-6 py-4">
                                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide bg-emerald-100 text-emerald-800">
                                                    {{ redemption.status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right font-extrabold text-zinc-800 text-xs">{{ redemption.points_used }}</td>
                                        </tr>
                                        <tr v-if="redemptions.length === 0">
                                            <td colspan="4" class="px-6 py-12 text-center text-zinc-400">Belum ada riwayat penukaran reward.</td>
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
