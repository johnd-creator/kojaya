<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { ArrowLeft, CheckCircle2, Send } from "lucide-vue-next";
import { computed } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDate } from "@/lib/formatters";

const props = defineProps<{
    count: {
        id: number;
        count_no: string;
        status: string;
        counted_at: string;
        location?: { name: string } | null;
        requester?: { name: string } | null;
        approver?: { name: string } | null;
        items: Array<{
            id: number;
            product?: { name: string } | null;
            system_qty: number;
            counted_qty: number;
            difference: number;
            notes?: string | null;
        }>;
    };
}>();

const totalDiff = computed(() => props.count.items.reduce((s, i) => s + i.difference, 0));

function submit(): void {
    router.post(`/cooperative/pos/inventory/counts/${props.count.id}/submit`);
}
function approve(): void {
    router.post(`/cooperative/pos/inventory/counts/${props.count.id}/approve`);
}
</script>

<template>
    <Head :title="`Opname ${count.count_no}`" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Stock Opname', href: '/cooperative/pos/inventory/counts' },
            { title: count.count_no, href: `/cooperative/pos/inventory/counts/${count.id}` },
        ]"
    >
        <PageContainer>
            <div class="flex flex-col gap-6">
                <header class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Link href="/cooperative/pos/inventory/counts">
                            <Button variant="ghost" size="icon" class="rounded-full">
                                <ArrowLeft class="h-5 w-5" />
                            </Button>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-extrabold text-zinc-900 tracking-tight">{{ count.count_no }}</h1>
                            <p class="text-sm text-zinc-500">
                                {{ count.location?.name }} · {{ formatDate(count.counted_at) }} · {{ count.requester?.name ?? '-' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge variant="default" class="text-[10px] font-extrabold uppercase">{{ count.status }}</Badge>
                        <Button v-if="count.status === 'DRAFT'" size="sm" variant="outline" class="rounded-xl" @click="submit">
                            <Send class="mr-2 h-4 w-4" />
                            Submit Review
                        </Button>
                        <Button v-if="count.status === 'REVIEW'" size="sm" class="rounded-xl" @click="approve">
                            <CheckCircle2 class="mr-2 h-4 w-4" />
                            Approve & Sesuaikan
                        </Button>
                    </div>
                </header>

                <div class="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-bold">Item</h2>
                        <div class="text-sm text-zinc-500">Total Selisih: <span :class="totalDiff < 0 ? 'text-red-600' : 'text-emerald-600'" class="font-extrabold">{{ totalDiff }}</span></div>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-100 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                            <tr>
                                <th class="py-2">Produk</th>
                                <th class="py-2 text-right">Sistem</th>
                                <th class="py-2 text-right">Hitung</th>
                                <th class="py-2 text-right">Selisih</th>
                                <th class="py-2">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in count.items" :key="item.id" class="border-b border-zinc-50">
                                <td class="py-2 font-medium">{{ item.product?.name ?? '-' }}</td>
                                <td class="py-2 text-right">{{ item.system_qty }}</td>
                                <td class="py-2 text-right">{{ item.counted_qty }}</td>
                                <td class="py-2 text-right font-extrabold" :class="item.difference < 0 ? 'text-red-600' : item.difference > 0 ? 'text-emerald-600' : ''">{{ item.difference }}</td>
                                <td class="py-2 text-zinc-500">{{ item.notes ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
