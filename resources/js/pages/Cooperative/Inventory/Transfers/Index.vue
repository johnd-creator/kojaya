<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, ArrowRightLeft } from "lucide-vue-next";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";

defineProps<{
    transfers: {
        data: Array<{
            id: number;
            transfer_no: string;
            transferred_at: string;
            status: string;
            fromLocation?: { name: string } | null;
            toLocation?: { name: string } | null;
            requester?: { name: string } | null;
        }>;
    };
}>();
</script>

<template>
    <Head title="Transfer Stok" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '/cooperative' },
            { title: 'POS', href: '/cooperative/pos' },
            { title: 'Transfer Stok', href: '/cooperative/pos/inventory/transfers' },
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
                            <h1 class="text-2xl font-extrabold text-zinc-900 tracking-tight">Transfer Stok</h1>
                            <p class="text-sm text-zinc-500">Pindahkan stok antar lokasi.</p>
                        </div>
                    </div>
                    <Link href="/cooperative/pos/inventory/transfers/create">
                        <Button class="rounded-xl">Transfer Baru</Button>
                    </Link>
                </header>

                <div v-if="transfers.data.length === 0" class="rounded-3xl border border-dashed border-zinc-200 bg-white p-12 text-center">
                    <ArrowRightLeft class="mx-auto h-12 w-12 text-zinc-300" />
                    <p class="mt-4 text-lg font-bold text-zinc-800">Belum ada transfer</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="t in transfers.data"
                        :key="t.id"
                        class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm"
                    >
                        <div>
                            <div class="font-bold text-zinc-800">{{ t.transfer_no }}</div>
                            <div class="text-xs text-zinc-500">
                                {{ t.fromLocation?.name }} → {{ t.toLocation?.name }} ·
                                {{ t.transferred_at }} ·
                                {{ t.requester?.name ?? '-' }}
                            </div>
                        </div>
                        <span class="rounded-md bg-emerald-50 px-2 py-1 text-[10px] font-extrabold uppercase text-emerald-700">
                            {{ t.status }}
                        </span>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
