<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ArrowLeft, CheckCircle2, Lock } from "lucide-vue-next";
import { ref } from "vue";
import PageContainer from "@/components/PageContainer.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";

const props = defineProps<{
    date: string;
    summary: {
        transaction_count: number;
        gross_sales: number;
        total_discount: number;
        total_void: number;
        total_return: number;
        net_sales: number;
    };
    payment_summary: Array<{ method: string; count: number; total: number }>;
    member_credit_outstanding: number;
    is_locked: boolean;
}>();

const localDate = ref(props.date);
const closeForm = useForm({ date: props.date });

function applyDate(): void {
    router.get("/cooperative/pos/closings", { date: localDate.value });
}

function closeDay(): void {
    closeForm.date = localDate.value;
    closeForm.post("/cooperative/pos/closings", { preserveScroll: true });
}
</script>

<template>
    <Head title="Closing Harian" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Koperasi', href: '/cooperative' },
            { title: 'POS', href: '/cooperative/pos' },
            { title: 'Closing Harian', href: '/cooperative/pos/closings' },
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
                            <h1 class="text-2xl font-extrabold text-zinc-900 tracking-tight">Closing Harian</h1>
                            <p class="text-sm text-zinc-500">Kunci transaksi harian dan posting ke jurnal.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Input v-model="localDate" type="date" class="rounded-xl" />
                        <Button class="rounded-xl" @click="applyDate">Terapkan</Button>
                    </div>
                </header>

                <div v-if="is_locked" class="flex items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                    <Lock class="h-5 w-5" />
                    Hari ini sudah ditutup dan terkunci.
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Transaksi</p>
                        <p class="mt-1 text-2xl font-extrabold text-zinc-900">{{ summary.transaction_count }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Penjualan Kotor</p>
                        <p class="mt-1 text-2xl font-extrabold text-zinc-900">{{ formatCurrency(summary.gross_sales) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Diskon</p>
                        <p class="mt-1 text-2xl font-extrabold text-amber-600">{{ formatCurrency(summary.total_discount) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Penjualan Bersih</p>
                        <p class="mt-1 text-2xl font-extrabold text-emerald-600">{{ formatCurrency(summary.net_sales) }}</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Void</p>
                        <p class="mt-1 text-xl font-extrabold text-red-600">{{ formatCurrency(summary.total_void) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Retur</p>
                        <p class="mt-1 text-xl font-extrabold text-orange-600">{{ formatCurrency(summary.total_return) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Outstanding Kredit</p>
                        <p class="mt-1 text-xl font-extrabold text-blue-600">{{ formatCurrency(member_credit_outstanding) }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-100 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-zinc-900">Ringkasan Pembayaran</h2>
                    <table class="mt-4 w-full text-left text-sm">
                        <thead class="border-b border-zinc-100 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                            <tr>
                                <th class="py-2">Metode</th>
                                <th class="py-2 text-right">Jumlah</th>
                                <th class="py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in payment_summary" :key="r.method" class="border-b border-zinc-50">
                                <td class="py-2 font-medium">{{ r.method }}</td>
                                <td class="py-2 text-right">{{ r.count }}</td>
                                <td class="py-2 text-right font-bold">{{ formatCurrency(r.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <Button class="rounded-xl" :disabled="is_locked || closeForm.processing" @click="closeDay">
                        <CheckCircle2 class="mr-2 h-4 w-4" />
                        {{ is_locked ? "Sudah Ditutup" : "Tutup Hari Ini" }}
                    </Button>
                </div>
            </div>
        </PageContainer>
    </AppLayout>
</template>
