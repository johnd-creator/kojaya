<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { ArrowLeft, FileText, Plus } from "lucide-vue-next";
import { ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency, formatDate } from "@/lib/formatters";

defineProps<{
    receipts: {
        data: Array<{
            id: number;
            receipt_no: string;
            received_at: string;
            total_amount: number | string;
            status: string;
            reference_no?: string | null;
            supplier?: { name: string } | null;
            location?: { name: string } | null;
            receiver?: { name: string } | null;
        }>;
    };
}>();
</script>

<template>
    <Head title="Penerimaan Stok POS" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '/cooperative' },
            { title: 'POS', href: '/cooperative/pos' },
            { title: 'Penerimaan Stok', href: '/cooperative/pos/inventory/receipts' },
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
                            <h1 class="text-2xl font-extrabold text-zinc-900 tracking-tight">Penerimaan Stok</h1>
                            <p class="text-sm text-zinc-500">Catat stok masuk dari supplier ke lokasi.</p>
                        </div>
                    </div>
                    <Link href="/cooperative/pos/inventory/receipts/create">
                        <Button class="rounded-xl">
                            <Plus class="mr-2 h-4 w-4" />
                            Catat Penerimaan
                        </Button>
                    </Link>
                </header>

                <div v-if="receipts.data.length === 0" class="rounded-3xl border border-dashed border-zinc-200 bg-white p-12 text-center">
                    <FileText class="mx-auto h-12 w-12 text-zinc-300" />
                    <p class="mt-4 text-lg font-bold text-zinc-800">Belum ada penerimaan</p>
                    <p class="mt-1 text-sm text-zinc-500">Penerimaan stok dari supplier akan muncul di sini.</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="receipt in receipts.data"
                        :key="receipt.id"
                        class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm"
                    >
                        <div class="flex items-center gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                                <FileText class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="font-bold text-zinc-800">{{ receipt.receipt_no }}</div>
                                <div class="text-xs text-zinc-500">
                                    {{ formatDate(receipt.received_at) }} ·
                                    {{ receipt.supplier?.name ?? 'Tanpa supplier' }} ·
                                    {{ receipt.location?.name ?? '-' }}
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-extrabold text-zinc-900">{{ formatCurrency(receipt.total_amount) }}</div>
                            <Badge variant="default" class="mt-1 text-[9px] font-bold uppercase tracking-wider">
                                {{ receipt.status }}
                            </Badge>
                        </div>
                    </div>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
