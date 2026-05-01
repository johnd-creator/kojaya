<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { LockKeyhole, RefreshCw } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { close, index } from '@/routes/cooperative/shu';

const props = defineProps<{ preview: any; closedPeriod: any; filters: any }>();

const filter = reactive({
    year: Number(props.filters?.year ?? new Date().getFullYear()),
    cooperative_pool: Number(props.filters?.cooperative_pool ?? 0),
    pos_profit_pool: props.filters?.pos_profit_pool ?? '',
});

const closeForm = useForm({
    year: filter.year,
    cooperative_pool: filter.cooperative_pool,
    pos_profit_pool: filter.pos_profit_pool,
});

const isClosed = computed(() => props.closedPeriod?.status === 'CLOSED');
const allocations = computed(() => (isClosed.value ? props.closedPeriod?.allocations : props.preview?.allocations) ?? []);
const totals = computed(() => isClosed.value ? props.closedPeriod : props.preview);
const formatCurrency = (amount: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(amount ?? 0));

const refreshPreview = () => {
    router.get(index().url, {
        year: filter.year,
        cooperative_pool: filter.cooperative_pool,
        pos_profit_pool: filter.pos_profit_pool || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const closePeriod = () => {
    closeForm.year = filter.year;
    closeForm.cooperative_pool = filter.cooperative_pool;
    closeForm.pos_profit_pool = filter.pos_profit_pool;
    closeForm.post(close().url, { preserveScroll: true });
};
</script>

<template>
    <Head title="SHU Koperasi Tahunan" />
    <AppLayout :breadcrumbs="[{ title: 'Iuran & Simpanan', href: '#' }, { title: 'SHU Koperasi', href: index().url }]">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">SHU Koperasi Tahunan</h1>
                    <p class="mt-1 text-sm text-zinc-500">Alokasi dari pool SHU koperasi berdasarkan bulan aktif dan kedisiplinan iuran wajib.</p>
                </div>
                <div class="grid gap-3 rounded-lg border bg-white p-4 dark:bg-zinc-900 sm:grid-cols-[120px_180px_180px_auto_auto]">
                    <Input v-model.number="filter.year" type="number" min="2020" />
                    <Input v-model.number="filter.cooperative_pool" type="number" min="0" placeholder="Pool SHU koperasi" />
                    <Input v-model="filter.pos_profit_pool" type="number" min="0" placeholder="Pool POS opsional" />
                    <Button type="button" variant="outline" @click="refreshPreview">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Preview
                    </Button>
                    <Button type="button" :disabled="isClosed || closeForm.processing" @click="closePeriod">
                        <LockKeyhole class="mr-2 h-4 w-4" />
                        Tutup Tahun
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Pool SHU Koperasi</div>
                    <div class="mt-2 text-2xl font-semibold">{{ formatCurrency(totals.cooperative_pool) }}</div>
                </div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Total Skor SHU</div>
                    <div class="mt-2 text-2xl font-semibold">{{ totals.total_shu_score }}</div>
                </div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Skor Keanggotaan</div>
                    <div class="mt-2 text-2xl font-semibold">{{ totals.total_membership_score }}</div>
                </div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Skor Iuran Wajib</div>
                    <div class="mt-2 text-2xl font-semibold">{{ totals.total_dues_score }}</div>
                </div>
                <div class="rounded-lg border bg-white p-5 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">Status Tahun</div>
                    <div class="mt-2 text-2xl font-semibold">{{ isClosed ? 'CLOSED' : 'PREVIEW' }}</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
                <table class="w-full text-sm">
                    <thead class="border-b bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-zinc-950">
                        <tr>
                            <th class="px-4 py-3">Anggota</th>
                            <th class="px-4 py-3 text-right">Bulan Aktif</th>
                            <th class="px-4 py-3 text-right">Iuran Lunas</th>
                            <th class="px-4 py-3 text-right">Skor</th>
                            <th class="px-4 py-3 text-right">Alokasi SHU</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="allocation in allocations" :key="allocation.member.id">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ allocation.member.member_no }}</div>
                                <div class="text-xs text-zinc-500">{{ allocation.member.name }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">{{ allocation.membership_score }}</td>
                            <td class="px-4 py-3 text-right">{{ allocation.dues_score }}</td>
                            <td class="px-4 py-3 text-right">{{ allocation.shu_score }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(allocation.cooperative_shu_amount) }}</td>
                        </tr>
                        <tr v-if="allocations.length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-zinc-500">Belum ada anggota aktif untuk periode ini.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
