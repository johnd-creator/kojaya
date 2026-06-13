<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, ClipboardList } from "lucide-vue-next";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatDate } from "@/lib/formatters";

defineProps<{
    counts: {
        data: Array<{
            id: number;
            count_no: string;
            counted_at: string;
            status: string;
            location?: { name: string } | null;
            requester?: { name: string } | null;
        }>;
    };
}>();
</script>

<template>
    <Head title="Stock Opname" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '/cooperative' },
            { title: 'POS', href: '/cooperative/pos' },
            { title: 'Stock Opname', href: '/cooperative/pos/inventory/counts' },
        ]"
    >
        <PageContainer>
            <div class="flex flex-col gap-6">
                <header class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Link href="/cooperative/pos">
                            <Button variant="ghost" size="icon" class="rounded-full">
                                <ArrowLeft class="h-5 w-5" />
                            </Button>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-extrabold text-zinc-900 tracking-tight">Stock Opname</h1>
                            <p class="text-sm text-zinc-500">Hitung stok fisik dan sesuaikan dengan sistem.</p>
                        </div>
                    </div>
                    <Link href="/cooperative/pos/inventory/counts/create">
                        <Button class="rounded-xl">Buat Opname</Button>
                    </Link>
                </header>

                <div v-if="counts.data.length === 0" class="rounded-3xl border border-dashed border-zinc-200 bg-white p-12 text-center">
                    <ClipboardList class="mx-auto h-12 w-12 text-zinc-300" />
                    <p class="mt-4 text-lg font-bold text-zinc-800">Belum ada stock opname</p>
                </div>

                <div v-else class="space-y-3">
                    <Link
                        v-for="c in counts.data"
                        :key="c.id"
                        :href="`/cooperative/pos/inventory/counts/${c.id}`"
                        class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm hover:border-zinc-200"
                    >
                        <div>
                            <div class="font-bold text-zinc-800">{{ c.count_no }}</div>
                            <div class="text-xs text-zinc-500">
                                {{ c.location?.name }} · {{ formatDate(c.counted_at) }} · {{ c.requester?.name ?? '-' }}
                            </div>
                        </div>
                        <Badge variant="default" class="text-[9px] font-extrabold uppercase">{{ c.status }}</Badge>
                    </Link>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
